<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

$nonce = base64_encode(random_bytes(18));
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self' 'nonce-{$nonce}'; font-src 'self'; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = '/' . trim($path, '/');

if ($path === '/sitemap.xml') {
    header('Content-Type: application/xml; charset=UTF-8');
    $pages = ['', 'about', 'services', 'contact'];
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">';
    foreach ($pages as $pageName) {
        foreach (['ar', 'en', 'ku'] as $siteLang) {
            $suffix = $pageName === '' ? '/' : '/' . $pageName;
            echo '<url><loc>' . e(app_url($siteLang . $suffix)) . '</loc>';
            foreach (['ar', 'en', 'ku'] as $alternate) {
                echo '<xhtml:link rel="alternate" hreflang="' . $alternate . '" href="' . e(app_url($alternate . $suffix)) . '"/>';
            }
            echo '</url>';
        }
    }
    echo '</urlset>';
    exit;
}

if (str_starts_with($path, '/admin')) {
    require ROOT_PATH . '/app/admin.php';
    exit;
}

if ($path === '/') {
    $preferred = $_COOKIE['site_lang'] ?? 'ar';
    $preferred = in_array($preferred, ['ar', 'en', 'ku'], true) ? $preferred : 'ar';
    redirect('/' . $preferred . '/');
}

$segments = array_values(array_filter(explode('/', trim($path, '/'))));
$lang = $segments[0] ?? 'ar';
$pageKey = $segments[1] ?? 'home';
$validPages = ['home', 'about', 'services', 'contact'];

if (!in_array($lang, ['ar', 'en', 'ku'], true) || !in_array($pageKey, $validPages, true) || count($segments) > 2) {
    http_response_code(404);
    $lang = in_array($lang, ['ar', 'en', 'ku'], true) ? $lang : 'ar';
    $pageKey = '404';
}

setcookie('site_lang', $lang, [
    'expires' => time() + 31536000,
    'path' => '/',
    'secure' => (bool) $config['session_secure'],
    'httponly' => false,
    'samesite' => 'Lax',
]);

try {
    $settings = ContentRepository::settings();
    $preview = is_admin() && ($_GET['preview'] ?? '') === '1';
    if ($pageKey === '404') {
        render('public/404', compact('lang', 'pageKey', 'settings', 'nonce'));
        exit;
    }
    $pageData = ContentRepository::page($pageKey, $lang, $preview);
    if (!$pageData) {
        http_response_code(404);
        $pageKey = '404';
        render('public/404', compact('lang', 'pageKey', 'settings', 'nonce'));
        exit;
    }
    $content = $pageData['content'];
    $services = ContentRepository::services($lang, $preview);

    if ($pageKey === 'contact' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf(t('form_error', $lang));
        handle_contact_submission($lang, $services, $config);
    }

    render('public/' . $pageKey, compact('lang', 'pageKey', 'pageData', 'content', 'services', 'settings', 'preview', 'nonce'));
} catch (PDOException $e) {
    http_response_code(503);
    if ($config['env'] === 'local') {
        echo '<pre>' . e($e->getMessage()) . '</pre>';
    } else {
        echo 'Service temporarily unavailable.';
    }
}

function handle_contact_submission(string $lang, array $services, array $config): never
{
    $name = trim((string) ($_POST['name'] ?? ''));
    $company = trim((string) ($_POST['company'] ?? ''));
    $contactMethod = trim((string) ($_POST['contact_method'] ?? ''));
    $serviceSlug = trim((string) ($_POST['service'] ?? ''));
    $details = trim((string) ($_POST['details'] ?? ''));
    $honeypot = trim((string) ($_POST['website'] ?? ''));
    $errors = [];

    if ($honeypot !== '') {
        redirect(page_url($lang, 'contact'));
    }
    if (!rate_limit('contact', 5, 3600)) {
        old_input($_POST);
        flash('error', t('rate_error', $lang));
        redirect(page_url($lang, 'contact'));
    }
    if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
        $errors[] = t('contact_name', $lang);
    }
    if (mb_strlen($contactMethod) < 5 || mb_strlen($contactMethod) > 190) {
        $errors[] = t('contact_method', $lang);
    }
    if (mb_strlen($company) > 160) {
        $errors[] = t('company_name', $lang);
    }
    if (mb_strlen($details) < 10 || mb_strlen($details) > 5000) {
        $errors[] = t('request_details', $lang);
    }

    $serviceId = null;
    $serviceName = '';
    foreach ($services as $service) {
        if ($serviceSlug !== '' && hash_equals((string) $service['slug'], $serviceSlug)) {
            $serviceId = (int) $service['id'];
            $serviceName = (string) $service['name'];
            break;
        }
    }

    if ($errors) {
        old_input($_POST);
        flash('error', t('form_error', $lang) . ' ' . implode('، ', $errors));
        redirect(page_url($lang, 'contact', $serviceSlug ? ['service' => $serviceSlug] : []));
    }

    try {
        $stmt = db()->prepare('INSERT INTO contact_requests (name, company, contact_method, service_id, details, lang, status, ip_hash) VALUES (?, ?, ?, ?, ?, ?, \'new\', ?)');
        $stmt->execute([$name, $company ?: null, $contactMethod, $serviceId, $details, $lang, client_ip_hash()]);
        $requestId = (int) db()->lastInsertId();
    } catch (Throwable $e) {
        error_log('Contact request save failed: ' . $e->getMessage());
        old_input($_POST);
        flash('error', t('form_error', $lang));
        redirect(page_url($lang, 'contact'));
    }

    $notificationSent = Mailer::sendContactNotification($config['smtp'], [
        'name' => $name,
        'company' => $company,
        'contact_method' => $contactMethod,
        'service_name' => $serviceName,
        'details' => $details,
    ]);
    if ($notificationSent) {
        db()->prepare('UPDATE contact_requests SET notification_sent=1 WHERE id=?')->execute([$requestId]);
    }
    clear_old();
    flash('success', t('form_success', $lang));
    redirect(page_url($lang, 'contact') . '#contact-form');
}
