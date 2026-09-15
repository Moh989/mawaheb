<?php

declare(strict_types=1);

$adminRoute = substr($path, strlen('/admin')) ?: '/';

if ($adminRoute === '/login') {
    if (is_admin()) {
        redirect('/admin');
    }
    $loginError = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
        $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        if (!rate_limit('admin_login', 5, 900)) {
            $loginError = 'محاولات كثيرة. انتظر 15 دقيقة ثم حاول مجدداً.';
        } else {
            $stmt = db()->prepare('SELECT * FROM admins WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $admin = $stmt->fetch();
            if ($admin && password_verify($password, $admin['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['admin_id'] = (int) $admin['id'];
                $_SESSION['admin_name'] = $admin['display_name'];
                db()->prepare('UPDATE admins SET last_login_at = NOW() WHERE id = ?')->execute([$admin['id']]);
                redirect('/admin');
            }
            usleep(350000);
            $loginError = 'بيانات الدخول غير صحيحة.';
        }
    }
    render('admin/login', compact('loginError', 'nonce'));
    return;
}

if ($adminRoute === '/logout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'], $params['httponly']);
    }
    session_destroy();
    redirect('/admin/login');
}

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    match ($action) {
        'save_page' => admin_save_page(false),
        'publish_page' => admin_save_page(true),
        'save_service' => admin_save_service(false),
        'publish_service' => admin_save_service(true),
        'delete_service' => admin_delete_service(),
        'request_status' => admin_update_request(),
        'save_settings' => admin_save_settings(),
        'upload_media' => admin_upload_media(),
        'update_account' => admin_update_account(),
        default => flash('error', 'الإجراء المطلوب غير معروف.'),
    };
}

$adminSection = trim($adminRoute, '/');
if ($adminSection === '') {
    $counts = [
        'new_requests' => (int) db()->query("SELECT COUNT(*) FROM contact_requests WHERE status = 'new'")->fetchColumn(),
        'all_requests' => (int) db()->query('SELECT COUNT(*) FROM contact_requests')->fetchColumn(),
        'published_pages' => (int) db()->query('SELECT COUNT(*) FROM page_translations WHERE published_at IS NOT NULL')->fetchColumn(),
        'visible_services' => (int) db()->query('SELECT COUNT(*) FROM services WHERE is_visible = 1')->fetchColumn(),
    ];
    $recent = db()->query('SELECT cr.*, COALESCE(st.published_name, st.draft_name) AS service_name FROM contact_requests cr LEFT JOIN service_translations st ON st.service_id = cr.service_id AND st.lang = \'ar\' ORDER BY cr.created_at DESC LIMIT 6')->fetchAll();
    render('admin/dashboard', compact('counts', 'recent', 'adminSection', 'nonce'));
    return;
}

if ($adminSection === 'pages') {
    if (isset($_GET['id'])) {
        $stmt = db()->prepare('SELECT pt.*, p.page_key FROM page_translations pt JOIN pages p ON p.id = pt.page_id WHERE pt.id = ? LIMIT 1');
        $stmt->execute([(int) $_GET['id']]);
        $translation = $stmt->fetch();
        if (!$translation) {
            flash('error', 'المحتوى المطلوب غير موجود.');
            redirect('/admin/pages');
        }
        $fields = json_decode((string) $translation['draft_json'], true) ?: [];
        render('admin/page_edit', compact('translation', 'fields', 'adminSection', 'nonce'));
        return;
    }
    $pages = db()->query('SELECT pt.*, p.page_key FROM page_translations pt JOIN pages p ON p.id = pt.page_id ORDER BY FIELD(p.page_key, \'home\',\'about\',\'services\',\'contact\'), FIELD(pt.lang, \'ar\',\'en\',\'ku\')')->fetchAll();
    render('admin/pages', compact('pages', 'adminSection', 'nonce'));
    return;
}

if ($adminSection === 'services') {
    if (isset($_GET['id']) || isset($_GET['new'])) {
        $service = null;
        $translations = [];
        if (isset($_GET['id'])) {
            $stmt = db()->prepare('SELECT * FROM services WHERE id = ? LIMIT 1');
            $stmt->execute([(int) $_GET['id']]);
            $service = $stmt->fetch();
            if (!$service) {
                flash('error', 'الخدمة غير موجودة.');
                redirect('/admin/services');
            }
            $stmt = db()->prepare('SELECT * FROM service_translations WHERE service_id = ?');
            $stmt->execute([$service['id']]);
            foreach ($stmt->fetchAll() as $row) {
                $translations[$row['lang']] = $row;
            }
        }
        render('admin/service_edit', compact('service', 'translations', 'adminSection', 'nonce'));
        return;
    }
    $servicesAdmin = db()->query("SELECT s.*, st.published_name, st.draft_name FROM services s LEFT JOIN service_translations st ON st.service_id = s.id AND st.lang = 'ar' ORDER BY s.sort_order, s.id")->fetchAll();
    render('admin/services', compact('servicesAdmin', 'adminSection', 'nonce'));
    return;
}

if ($adminSection === 'requests') {
    $statusFilter = in_array($_GET['status'] ?? '', ['new', 'in_progress', 'closed'], true) ? $_GET['status'] : '';
    $sql = "SELECT cr.*, COALESCE(st.published_name, st.draft_name) AS service_name FROM contact_requests cr LEFT JOIN service_translations st ON st.service_id = cr.service_id AND st.lang = 'ar'";
    $params = [];
    if ($statusFilter !== '') {
        $sql .= ' WHERE cr.status = ?';
        $params[] = $statusFilter;
    }
    $sql .= ' ORDER BY cr.created_at DESC LIMIT 200';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $requests = $stmt->fetchAll();
    render('admin/requests', compact('requests', 'statusFilter', 'adminSection', 'nonce'));
    return;
}

if ($adminSection === 'settings') {
    $settingsAdmin = ContentRepository::settings();
    $media = db()->query('SELECT * FROM media ORDER BY created_at DESC LIMIT 20')->fetchAll();
    render('admin/settings', compact('settingsAdmin', 'media', 'adminSection', 'nonce'));
    return;
}

if ($adminSection === 'account') {
    $stmt = db()->prepare('SELECT id, email, display_name, last_login_at FROM admins WHERE id = ?');
    $stmt->execute([$_SESSION['admin_id']]);
    $account = $stmt->fetch();
    render('admin/account', compact('account', 'adminSection', 'nonce'));
    return;
}

http_response_code(404);
flash('error', 'الصفحة غير موجودة.');
redirect('/admin');

function admin_save_page(bool $publish): void
{
    $id = (int) ($_POST['id'] ?? 0);
    $stmt = db()->prepare('SELECT id FROM page_translations WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetchColumn()) {
        flash('error', 'المحتوى غير موجود.');
        redirect('/admin/pages');
    }
    $fields = [];
    foreach ((array) ($_POST['content'] ?? []) as $key => $value) {
        if (preg_match('/^[a-z0-9_]+$/', (string) $key) && is_string($value)) {
            $fields[$key] = trim($value);
        }
    }
    $title = trim((string) ($_POST['seo_title'] ?? ''));
    $description = trim((string) ($_POST['seo_description'] ?? ''));
    $status = ($_POST['translation_status'] ?? '') === 'approved' ? 'approved' : 'needs_review';
    if ($title === '' || mb_strlen($title) > 180 || $description === '' || mb_strlen($description) > 320 || !$fields) {
        flash('error', 'راجع العنوان والوصف والحقول المطلوبة.');
        redirect('/admin/pages?id=' . $id);
    }
    $json = json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    if ($publish) {
        $sql = 'UPDATE page_translations SET draft_json=?, published_json=?, draft_seo_title=?, published_seo_title=?, draft_seo_description=?, published_seo_description=?, translation_status=?, published_at=NOW() WHERE id=?';
        db()->prepare($sql)->execute([$json, $json, $title, $title, $description, $description, $status, $id]);
        flash('success', 'تم حفظ المحتوى ونشره بنجاح.');
    } else {
        $sql = 'UPDATE page_translations SET draft_json=?, draft_seo_title=?, draft_seo_description=?, translation_status=? WHERE id=?';
        db()->prepare($sql)->execute([$json, $title, $description, $status, $id]);
        flash('success', 'تم حفظ المسودة بنجاح.');
    }
    redirect('/admin/pages?id=' . $id);
}

function admin_save_service(bool $publish): void
{
    $id = (int) ($_POST['id'] ?? 0);
    $slug = strtolower(trim((string) ($_POST['slug'] ?? '')));
    $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug) ?: '';
    $slug = trim($slug, '-');
    $icon = in_array($_POST['icon'] ?? '', ['clearance', 'document', 'consultancy', 'tracking', 'resolution'], true) ? $_POST['icon'] : 'document';
    $sortOrder = max(0, min(999, (int) ($_POST['sort_order'] ?? 0)));
    $visible = isset($_POST['is_visible']) ? 1 : 0;
    if ($slug === '') {
        flash('error', 'المعرّف اللاتيني مطلوب.');
        redirect($id ? '/admin/services?id=' . $id : '/admin/services?new=1');
    }
    $pdo = db();
    try {
        $pdo->beginTransaction();
        if ($id > 0) {
            $pdo->prepare('UPDATE services SET slug=?, icon=?, sort_order=?, is_visible=? WHERE id=?')->execute([$slug, $icon, $sortOrder, $visible, $id]);
        } else {
            $pdo->prepare('INSERT INTO services (slug, icon, sort_order, is_visible) VALUES (?, ?, ?, ?)')->execute([$slug, $icon, $sortOrder, $visible]);
            $id = (int) $pdo->lastInsertId();
        }
        foreach (['ar', 'en', 'ku'] as $lang) {
            $name = trim((string) ($_POST['translations'][$lang]['name'] ?? ''));
            $description = trim((string) ($_POST['translations'][$lang]['description'] ?? ''));
            $status = ($_POST['translations'][$lang]['status'] ?? '') === 'approved' ? 'approved' : 'needs_review';
            if ($name === '' || $description === '') {
                throw new InvalidArgumentException('يجب إكمال اسم ووصف الخدمة باللغات الثلاث دون خلط.');
            }
            if ($publish) {
                $sql = 'INSERT INTO service_translations (service_id,lang,draft_name,draft_description,published_name,published_description,translation_status,published_at) VALUES (?,?,?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE draft_name=VALUES(draft_name), draft_description=VALUES(draft_description), published_name=VALUES(published_name), published_description=VALUES(published_description), translation_status=VALUES(translation_status), published_at=NOW()';
                $pdo->prepare($sql)->execute([$id, $lang, $name, $description, $name, $description, $status]);
            } else {
                $sql = 'INSERT INTO service_translations (service_id,lang,draft_name,draft_description,translation_status) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE draft_name=VALUES(draft_name), draft_description=VALUES(draft_description), translation_status=VALUES(translation_status)';
                $pdo->prepare($sql)->execute([$id, $lang, $name, $description, $status]);
            }
        }
        $pdo->commit();
        flash('success', $publish ? 'تم حفظ الخدمة ونشرها.' : 'تم حفظ مسودة الخدمة.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash('error', $e instanceof InvalidArgumentException ? $e->getMessage() : 'تعذر حفظ الخدمة. تحقق من عدم تكرار المعرّف.');
    }
    redirect('/admin/services?id=' . $id);
}

function admin_delete_service(): void
{
    $id = (int) ($_POST['id'] ?? 0);
    db()->prepare('DELETE FROM services WHERE id = ?')->execute([$id]);
    flash('success', 'تم حذف الخدمة. بقيت الطلبات السابقة محفوظة دون ربط بالخدمة.');
    redirect('/admin/services');
}

function admin_update_request(): void
{
    $id = (int) ($_POST['id'] ?? 0);
    $status = in_array($_POST['status'] ?? '', ['new', 'in_progress', 'closed'], true) ? $_POST['status'] : 'new';
    db()->prepare('UPDATE contact_requests SET status = ? WHERE id = ?')->execute([$status, $id]);
    flash('success', 'تم تحديث حالة الطلب.');
    redirect('/admin/requests');
}

function admin_save_settings(): void
{
    $keys = ['company_legal_ar','company_name_en','phone','whatsapp','email','address_ar','address_en','address_ku','working_hours_ar','working_hours_en','working_hours_ku','facebook','instagram','linkedin','map_url'];
    $pdo = db();
    foreach ($keys as $key) {
        $value = trim((string) ($_POST[$key] ?? ''));
        if ($key === 'email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'صيغة البريد الإلكتروني غير صحيحة.');
            redirect('/admin/settings');
        }
        if (in_array($key, ['facebook', 'instagram', 'linkedin', 'map_url'], true) && $value !== '') {
            $scheme = parse_url($value, PHP_URL_SCHEME);
            if (!filter_var($value, FILTER_VALIDATE_URL) || !in_array($scheme, ['http', 'https'], true)) {
                flash('error', 'روابط الشبكات الاجتماعية والخريطة يجب أن تبدأ بـ http أو https.');
                redirect('/admin/settings');
            }
        }
        $pdo->prepare('INSERT INTO settings (setting_key, setting_value, is_public) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)')->execute([$key, $value]);
    }
    flash('success', 'تم حفظ بيانات الشركة والاتصال.');
    redirect('/admin/settings');
}

function admin_upload_media(): void
{
    $slot = in_array($_POST['slot'] ?? '', ['logo', 'hero', 'profile'], true) ? $_POST['slot'] : '';
    $file = $_FILES['media_file'] ?? null;
    if ($slot === '' || !$file || $file['error'] !== UPLOAD_ERR_OK) {
        flash('error', 'اختر نوع الملف وملفاً صالحاً للرفع.');
        redirect('/admin/settings');
    }
    $allowed = $slot === 'profile'
        ? ['application/pdf' => 'pdf']
        : ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/avif' => 'avif'];
    $maxSize = $slot === 'profile' ? 100 * 1024 * 1024 : 8 * 1024 * 1024;
    if ((int) $file['size'] < 1 || (int) $file['size'] > $maxSize) {
        flash('error', 'حجم الملف يتجاوز الحد المسموح.');
        redirect('/admin/settings');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($allowed[$mime])) {
        flash('error', 'نوع الملف غير مسموح.');
        redirect('/admin/settings');
    }
    if ($slot !== 'profile' && @getimagesize($file['tmp_name']) === false) {
        flash('error', 'تعذر التحقق من سلامة ملف الصورة.');
        redirect('/admin/settings');
    }
    $filename = $slot . '-' . bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
    $target = PUBLIC_PATH . '/uploads/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        flash('error', 'تعذر نقل الملف المرفوع.');
        redirect('/admin/settings');
    }
    chmod($target, 0644);
    $path = '/uploads/' . $filename;
    $stmt = db()->prepare('INSERT INTO media (media_type,file_path,original_name,mime_type,file_size,alt_ar,alt_en,alt_ku) VALUES (?,?,?,?,?,?,?,?)');
    $stmt->execute([$slot, $path, basename((string) $file['name']), $mime, (int) $file['size'], trim((string) ($_POST['alt_ar'] ?? '')), trim((string) ($_POST['alt_en'] ?? '')), trim((string) ($_POST['alt_ku'] ?? ''))]);
    db()->prepare('INSERT INTO settings (setting_key,setting_value,is_public) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)')->execute(['media_' . $slot, $path]);
    foreach (['ar', 'en', 'ku'] as $lang) {
        $alt = trim((string) ($_POST['alt_' . $lang] ?? ''));
        db()->prepare('INSERT INTO settings (setting_key,setting_value,is_public) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)')->execute(['media_' . $slot . '_alt_' . $lang, $alt]);
    }
    flash('success', 'تم رفع الملف وربطه بالموقع.');
    redirect('/admin/settings');
}

function admin_update_account(): void
{
    $id = (int) $_SESSION['admin_id'];
    $stmt = db()->prepare('SELECT * FROM admins WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $admin = $stmt->fetch();
    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
    $displayName = trim((string) ($_POST['display_name'] ?? ''));
    $newPassword = (string) ($_POST['new_password'] ?? '');
    if (!$admin || !password_verify($currentPassword, $admin['password_hash'])) {
        flash('error', 'كلمة المرور الحالية غير صحيحة.');
        redirect('/admin/account');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($displayName) < 2 || ($newPassword !== '' && strlen($newPassword) < 12)) {
        flash('error', 'راجع الاسم والبريد، ويجب ألا تقل كلمة المرور الجديدة عن 12 حرفاً.');
        redirect('/admin/account');
    }
    try {
        if ($newPassword !== '') {
            db()->prepare('UPDATE admins SET email=?, display_name=?, password_hash=? WHERE id=?')->execute([$email, $displayName, password_hash($newPassword, PASSWORD_DEFAULT), $id]);
        } else {
            db()->prepare('UPDATE admins SET email=?, display_name=? WHERE id=?')->execute([$email, $displayName, $id]);
        }
        $_SESSION['admin_name'] = $displayName;
        session_regenerate_id(true);
        flash('success', 'تم تحديث بيانات الحساب.');
    } catch (PDOException $e) {
        flash('error', 'تعذر الحفظ. قد يكون البريد مستخدماً لحساب آخر.');
    }
    redirect('/admin/account');
}
