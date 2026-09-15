<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

if (($config['env'] ?? 'production') !== 'local') {
    fwrite(STDERR, "Smoke tests are restricted to APP_ENV=local.\n");
    exit(2);
}
db()->exec('DELETE FROM rate_limits');

$base = rtrim((string) ($argv[1] ?? 'http://127.0.0.1:8080'), '/');
$adminEmail = (string) ($argv[2] ?? 'admin@almawaheb.local');
$adminPassword = (string) ($argv[3] ?? '');
if ($adminPassword === '') {
    fwrite(STDERR, "Usage: php tests/smoke.php URL ADMIN_EMAIL ADMIN_PASSWORD\n");
    exit(2);
}

$cookieFile = tempnam(sys_get_temp_dir(), 'mawaheb-test-cookie-');
$checks = [];

function request(string $url, string $cookieFile, ?array $post = null, array $files = []): array
{
    $curl = curl_init($url);
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_TIMEOUT => 20,
    ];
    if ($post !== null) {
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = $files ? array_merge($post, $files) : http_build_query($post);
    }
    curl_setopt_array($curl, $options);
    $raw = curl_exec($curl);
    if ($raw === false) {
        throw new RuntimeException(curl_error($curl));
    }
    $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
    return ['status' => $status, 'headers' => substr($raw, 0, $headerSize), 'body' => substr($raw, $headerSize)];
}

function tokenFrom(string $html): string
{
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML($html);
    $xpath = new DOMXPath($dom);
    $nodes = $xpath->query('//input[@name="_token"]/@value');
    return $nodes && $nodes->length ? (string) $nodes->item(0)->nodeValue : '';
}

function check(bool $condition, string $label): void
{
    global $checks;
    $checks[] = [$condition, $label];
    fwrite(STDOUT, ($condition ? '[PASS] ' : '[FAIL] ') . $label . "\n");
}

try {
    foreach (['ar' => 'rtl', 'en' => 'ltr', 'ku' => 'rtl'] as $lang => $dir) {
        foreach (['', 'about', 'services', 'contact'] as $page) {
            $response = request($base . '/' . $lang . '/' . $page, $cookieFile);
            check($response['status'] === 200, strtoupper($lang) . ' /' . ($page ?: 'home') . ' responds 200');
            check(str_contains($response['body'], 'lang="' . $lang . '" dir="' . $dir . '"'), strtoupper($lang) . ' direction and lang metadata');
        }
    }
    $notFound = request($base . '/ar/not-a-page', $cookieFile);
    check($notFound['status'] === 404, 'Localized 404 responds 404');
    $sitemap = request($base . '/sitemap.xml', $cookieFile);
    check($sitemap['status'] === 200 && substr_count($sitemap['body'], '<url>') === 12, 'Sitemap exposes 12 localized URLs');
    $securityPage = request($base . '/ar/', $cookieFile);
    check(str_contains($securityPage['headers'], 'Content-Security-Policy:'), 'Security headers include Content Security Policy');
    check(substr_count($securityPage['body'], 'rel="alternate" hreflang=') === 4 && str_contains($securityPage['body'], 'rel="canonical"'), 'Canonical and hreflang metadata are present');
    check(substr_count($securityPage['body'], 'class="hero-slide ') === 3, 'Homepage slider exposes three editorial slides');
    check(str_contains($securityPage['body'], 'data-slider-previous') && str_contains($securityPage['body'], 'data-slider-next') && str_contains($securityPage['body'], 'data-slider-pause'), 'Slider controls include previous, next, and pause actions');
    check(str_contains($securityPage['body'], '/assets/css/identity.css?v=') && str_contains($securityPage['body'], '/assets/js/app.js?v='), 'Homepage loads the identity stylesheet and cache-versioned script');
    foreach (['slider-seaport.webp', 'slider-air-cargo.webp'] as $sliderAsset) {
        $assetResponse = request($base . '/assets/img/' . $sliderAsset, $cookieFile);
        check($assetResponse['status'] === 200 && str_contains(strtolower($assetResponse['headers']), 'content-type: image/webp'), $sliderAsset . ' is served as WebP');
    }
    check(str_contains(request($base . '/ar/about', $cookieFile)['body'], 'inner-hero-media-company'), 'About page includes a company hero photograph');
    check(str_contains(request($base . '/ar/services', $cookieFile)['body'], 'inner-hero-media-services'), 'Services page includes a contextual hero photograph');
    check(str_contains(request($base . '/ar/contact', $cookieFile)['body'], 'inner-hero-media-contact'), 'Contact page includes a contextual hero photograph');
    $languageContext = request($base . '/ar/contact?service=customs-consultancy', $cookieFile);
    check(str_contains($languageContext['body'], '/en/contact?service=customs-consultancy'), 'Language switch retains selected service context');
    check(request($base . '/uploads/not-allowed.php', $cookieFile)['status'] === 403, 'Executable upload paths are blocked by the local router');

    $beforeRequests = (int) db()->query('SELECT COUNT(*) FROM contact_requests')->fetchColumn();
    $contact = request($base . '/ar/contact', $cookieFile);
    $token = tokenFrom($contact['body']);
    $invalid = request($base . '/ar/contact', $cookieFile, ['_token' => $token, 'name' => 'أ', 'contact_method' => '1', 'details' => 'قصير']);
    check($invalid['status'] === 303, 'Invalid contact request is redirected');
    check((int) db()->query('SELECT COUNT(*) FROM contact_requests')->fetchColumn() === $beforeRequests, 'Invalid contact request is not saved');
    $invalidPage = request($base . '/ar/contact', $cookieFile);
    check(str_contains($invalidPage['body'], 'تعذر حفظ الطلب'), 'Invalid contact request shows localized error');

    $contact = request($base . '/ar/contact?service=customs-consultancy', $cookieFile);
    $token = tokenFrom($contact['body']);
    $valid = request($base . '/ar/contact', $cookieFile, [
        '_token' => $token,
        'name' => 'اختبار الجودة',
        'company' => 'شركة اختبار',
        'contact_method' => 'qa@example.com',
        'service' => 'customs-consultancy',
        'details' => 'هذا طلب اختبار وظيفي سيتم إغلاقه من لوحة الإدارة.',
    ]);
    check($valid['status'] === 303, 'Valid contact request is redirected after save');
    $afterRequests = (int) db()->query('SELECT COUNT(*) FROM contact_requests')->fetchColumn();
    check($afterRequests === $beforeRequests + 1, 'Valid contact request is saved in MySQL');
    $saved = db()->query('SELECT * FROM contact_requests ORDER BY id DESC LIMIT 1')->fetch();
    check($saved && $saved['status'] === 'new' && $saved['notification_sent'] == 0, 'Saved request has new status and does not claim email notification');
    $successPage = request($base . '/ar/contact', $cookieFile);
    check(str_contains($successPage['body'], 'تم استلام طلبك وحفظه بنجاح'), 'Success feedback appears only after database save');

    $loginPage = request($base . '/admin/login', $cookieFile);
    $adminToken = tokenFrom($loginPage['body']);
    $badLogin = request($base . '/admin/login', $cookieFile, ['_token' => $adminToken, 'email' => $adminEmail, 'password' => 'incorrect-password']);
    check($badLogin['status'] === 200 && str_contains($badLogin['body'], 'بيانات الدخول غير صحيحة'), 'Invalid admin login is rejected');
    $adminToken = tokenFrom($badLogin['body']);
    $goodLogin = request($base . '/admin/login', $cookieFile, ['_token' => $adminToken, 'email' => $adminEmail, 'password' => $adminPassword]);
    check($goodLogin['status'] === 303 && str_contains($goodLogin['headers'], 'Location: /admin'), 'Valid admin login creates an authenticated session');
    $dashboard = request($base . '/admin', $cookieFile);
    check($dashboard['status'] === 200 && str_contains($dashboard['body'], 'نظرة عامة'), 'Authenticated dashboard loads');
    $adminToken = tokenFrom($dashboard['body']);

    $stmt = db()->query("SELECT pt.*, p.page_key FROM page_translations pt JOIN pages p ON p.id=pt.page_id WHERE p.page_key='home' AND pt.lang='en' LIMIT 1");
    $page = $stmt->fetch();
    $originalContent = json_decode($page['draft_json'], true, 512, JSON_THROW_ON_ERROR);
    $draftContent = $originalContent;
    $marker = ' [QA-DRAFT]';
    $draftContent['eyebrow'] .= $marker;
    $pagePost = [
        '_token' => $adminToken,
        'id' => $page['id'],
        'action' => 'save_page',
        'content' => $draftContent,
        'seo_title' => $page['draft_seo_title'],
        'seo_description' => $page['draft_seo_description'],
        'translation_status' => $page['translation_status'],
    ];
    $draftSave = request($base . '/admin/pages?id=' . $page['id'], $cookieFile, $pagePost);
    check($draftSave['status'] === 303, 'Page draft save action succeeds');
    $publicBeforePublish = request($base . '/en/', $cookieFile);
    check(!str_contains($publicBeforePublish['body'], $marker), 'Draft remains hidden from public page');
    $previewPage = request($base . '/en/?preview=1', $cookieFile);
    check(str_contains($previewPage['body'], $marker), 'Authenticated preview shows the draft');

    $pagePost['action'] = 'publish_page';
    $publish = request($base . '/admin/pages?id=' . $page['id'], $cookieFile, $pagePost);
    check($publish['status'] === 303, 'Page publish action succeeds');
    $publicAfterPublish = request($base . '/en/', $cookieFile);
    check(str_contains($publicAfterPublish['body'], $marker), 'Published content appears on the public site');

    $pagePost['content'] = $originalContent;
    $restore = request($base . '/admin/pages?id=' . $page['id'], $cookieFile, $pagePost);
    check($restore['status'] === 303, 'Page content is restored after the publishing test');

    $service = db()->query('SELECT * FROM services ORDER BY id LIMIT 1')->fetch();
    $stmt = db()->prepare('SELECT * FROM service_translations WHERE service_id=?');
    $stmt->execute([$service['id']]);
    $serviceTranslations = [];
    foreach ($stmt->fetchAll() as $translation) {
        $serviceTranslations[$translation['lang']] = $translation;
    }
    $serviceMarker = ' [QA-SERVICE]';
    $servicePost = [
        '_token' => $adminToken,
        'id' => $service['id'],
        'action' => 'save_service',
        'slug' => $service['slug'],
        'icon' => $service['icon'],
        'sort_order' => $service['sort_order'],
        'is_visible' => '1',
        'translations' => [],
    ];
    foreach (['ar', 'en', 'ku'] as $serviceLang) {
        $servicePost['translations'][$serviceLang] = [
            'name' => $serviceTranslations[$serviceLang]['draft_name'] . ($serviceLang === 'en' ? $serviceMarker : ''),
            'description' => $serviceTranslations[$serviceLang]['draft_description'],
            'status' => $serviceTranslations[$serviceLang]['translation_status'],
        ];
    }
    $serviceDraft = request($base . '/admin/services?id=' . $service['id'], $cookieFile, $servicePost);
    check($serviceDraft['status'] === 303, 'Service draft save action succeeds');
    check(!str_contains(request($base . '/en/services', $cookieFile)['body'], $serviceMarker), 'Service draft remains hidden from the public page');
    check(str_contains(request($base . '/en/services?preview=1', $cookieFile)['body'], $serviceMarker), 'Service draft appears in authenticated preview');
    $servicePost['action'] = 'publish_service';
    $servicePublish = request($base . '/admin/services?id=' . $service['id'], $cookieFile, $servicePost);
    check($servicePublish['status'] === 303 && str_contains(request($base . '/en/services', $cookieFile)['body'], $serviceMarker), 'Published service appears on the public page');
    foreach (['ar', 'en', 'ku'] as $serviceLang) {
        $servicePost['translations'][$serviceLang] = [
            'name' => $serviceTranslations[$serviceLang]['draft_name'],
            'description' => $serviceTranslations[$serviceLang]['draft_description'],
            'status' => $serviceTranslations[$serviceLang]['translation_status'],
        ];
    }
    $serviceRestore = request($base . '/admin/services?id=' . $service['id'], $cookieFile, $servicePost);
    check($serviceRestore['status'] === 303, 'Service content is restored after the publishing test');

    $requestUpdate = request($base . '/admin/requests', $cookieFile, [
        '_token' => $adminToken,
        'action' => 'request_status',
        'id' => $saved['id'],
        'status' => 'closed',
    ]);
    $stmt = db()->prepare('SELECT status FROM contact_requests WHERE id = ?');
    $stmt->execute([$saved['id']]);
    check($requestUpdate['status'] === 303 && $stmt->fetchColumn() === 'closed', 'Admin can update a contact request status');

    $settingsPage = request($base . '/admin/settings', $cookieFile);
    $adminToken = tokenFrom($settingsPage['body']);
    $settingsRows = db()->query('SELECT setting_key, setting_value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
    $settingsKeys = ['company_legal_ar','company_name_en','phone','whatsapp','email','address_ar','address_en','address_ku','working_hours_ar','working_hours_en','working_hours_ku','facebook','instagram','linkedin','map_url'];
    $settingsPost = ['_token' => $adminToken, 'action' => 'save_settings'];
    foreach ($settingsKeys as $settingsKey) {
        $settingsPost[$settingsKey] = $settingsRows[$settingsKey] ?? '';
    }
    $settingsPost['working_hours_ar'] = 'اختبار ساعات العمل';
    $settingsSave = request($base . '/admin/settings', $cookieFile, $settingsPost);
    check($settingsSave['status'] === 303 && str_contains(request($base . '/ar/contact', $cookieFile)['body'], 'اختبار ساعات العمل'), 'Contact settings update appears on the public page');
    $settingsPost['working_hours_ar'] = $settingsRows['working_hours_ar'] ?? '';
    check(request($base . '/admin/settings', $cookieFile, $settingsPost)['status'] === 303, 'Contact settings are restored after the test');

    $mediaBefore = (int) db()->query('SELECT COUNT(*) FROM media')->fetchColumn();
    $badUploadPath = tempnam(sys_get_temp_dir(), 'mawaheb-invalid-');
    file_put_contents($badUploadPath, 'not an image');
    $badUpload = request($base . '/admin/settings', $cookieFile, [
        '_token' => $adminToken,
        'action' => 'upload_media',
        'slot' => 'hero',
    ], ['media_file' => new CURLFile($badUploadPath, 'text/plain', 'bad.txt')]);
    check($badUpload['status'] === 303 && (int) db()->query('SELECT COUNT(*) FROM media')->fetchColumn() === $mediaBefore, 'Invalid media type is rejected');
    @unlink($badUploadPath);

    $originalLogoSetting = (string) db()->query("SELECT setting_value FROM settings WHERE setting_key='media_logo'")->fetchColumn();
    $validUpload = request($base . '/admin/settings', $cookieFile, [
        '_token' => $adminToken,
        'action' => 'upload_media',
        'slot' => 'logo',
        'alt_ar' => 'شعار شركة المواهب',
        'alt_en' => 'Al-Mawaheb logo',
        'alt_ku' => 'لۆگۆی کۆمپانیای المواهب',
    ], ['media_file' => new CURLFile(ROOT_PATH . '/public/assets/img/logo.png', 'image/png', 'logo.png')]);
    $uploadedLogo = (string) db()->query("SELECT setting_value FROM settings WHERE setting_key='media_logo'")->fetchColumn();
    check($validUpload['status'] === 303 && (int) db()->query('SELECT COUNT(*) FROM media')->fetchColumn() === $mediaBefore + 1, 'Valid logo upload is stored and recorded');
    check(str_starts_with($uploadedLogo, '/uploads/') && is_file(PUBLIC_PATH . $uploadedLogo), 'Uploaded media is linked to the public setting');
    db()->prepare("UPDATE settings SET setting_value=? WHERE setting_key='media_logo'")->execute([$originalLogoSetting]);
    $mediaId = (int) db()->query('SELECT MAX(id) FROM media')->fetchColumn();
    db()->prepare('DELETE FROM media WHERE id=?')->execute([$mediaId]);
    if (str_starts_with($uploadedLogo, '/uploads/') && is_file(PUBLIC_PATH . $uploadedLogo)) {
        unlink(PUBLIC_PATH . $uploadedLogo);
    }

    $serviceAdmin = request($base . '/admin/services', $cookieFile);
    check($serviceAdmin['status'] === 200 && substr_count($serviceAdmin['body'], 'content-row') >= 5, 'Service administration lists database-backed services');
    $accountAdmin = request($base . '/admin/account', $cookieFile);
    check($accountAdmin['status'] === 200 && str_contains($accountAdmin['body'], $adminEmail), 'Account administration loads current account data');
} catch (Throwable $e) {
    fwrite(STDERR, "[ERROR] {$e->getMessage()}\n");
    @unlink($cookieFile);
    exit(1);
}

@unlink($cookieFile);
$failed = array_filter($checks, fn(array $item): bool => !$item[0]);
fwrite(STDOUT, "\n" . count($checks) . ' checks, ' . count($failed) . " failed.\n");
exit($failed ? 1 : 0);
