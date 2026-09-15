<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This installer must be run from the command line.\n");
    exit(1);
}

require dirname(__DIR__) . '/app/bootstrap.php';

$options = getopt('', ['admin-email:', 'admin-password:', 'admin-name::', 'skip-seed', 'without-admin']);
$withoutAdmin = isset($options['without-admin']);
$email = trim((string) ($options['admin-email'] ?? ''));
$password = (string) ($options['admin-password'] ?? '');
$name = trim((string) ($options['admin-name'] ?? 'مدير الموقع'));

if (!$withoutAdmin) {
    if ($email === '') {
        $email = trim((string) readline('Admin email: '));
    }
    if ($password === '') {
        if (function_exists('shell_exec')) {
            fwrite(STDOUT, 'Admin password (12+ characters): ');
            shell_exec('stty -echo');
            $password = trim((string) fgets(STDIN));
            shell_exec('stty echo');
            fwrite(STDOUT, "\n");
        } else {
            $password = trim((string) readline('Admin password (12+ characters): '));
        }
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12) {
        fwrite(STDERR, "Use a valid email and a password of at least 12 characters.\n");
        exit(1);
    }
}

$pdo = db();
$schema = file_get_contents(ROOT_PATH . '/database/schema.sql');
foreach (preg_split('/;\s*(?:\r?\n|$)/', (string) $schema) ?: [] as $statement) {
    $statement = trim($statement);
    if ($statement !== '') {
        $pdo->exec($statement);
    }
}

if (!isset($options['skip-seed'])) {
    $seed = require ROOT_PATH . '/database/seed.php';
    $pdo->beginTransaction();
    try {
        foreach ($seed['pages'] as $pageKey => $translations) {
            $pdo->prepare('INSERT INTO pages (page_key) VALUES (?) ON DUPLICATE KEY UPDATE page_key = VALUES(page_key)')->execute([$pageKey]);
            $stmt = $pdo->prepare('SELECT id FROM pages WHERE page_key = ?');
            $stmt->execute([$pageKey]);
            $pageId = (int) $stmt->fetchColumn();
            foreach ($translations as $lang => $translation) {
                $json = json_encode($translation['content'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
                $sql = 'INSERT INTO page_translations (page_id, lang, draft_json, published_json, draft_seo_title, draft_seo_description, published_seo_title, published_seo_description, translation_status, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE draft_json=VALUES(draft_json), published_json=VALUES(published_json), draft_seo_title=VALUES(draft_seo_title), draft_seo_description=VALUES(draft_seo_description), published_seo_title=VALUES(published_seo_title), published_seo_description=VALUES(published_seo_description), translation_status=VALUES(translation_status), published_at=COALESCE(published_at, NOW())';
                $pdo->prepare($sql)->execute([$pageId, $lang, $json, $json, $translation['seo_title'], $translation['seo_description'], $translation['seo_title'], $translation['seo_description'], $translation['status']]);
            }
        }

        foreach ($seed['services'] as $service) {
            $pdo->prepare('INSERT INTO services (slug, icon, sort_order, is_visible) VALUES (?, ?, ?, 1) ON DUPLICATE KEY UPDATE icon=VALUES(icon), sort_order=VALUES(sort_order)')->execute([$service['slug'], $service['icon'], $service['sort']]);
            $stmt = $pdo->prepare('SELECT id FROM services WHERE slug = ?');
            $stmt->execute([$service['slug']]);
            $serviceId = (int) $stmt->fetchColumn();
            foreach ($service['translations'] as $lang => [$serviceName, $description, $status]) {
                $sql = 'INSERT INTO service_translations (service_id, lang, draft_name, draft_description, published_name, published_description, translation_status, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE draft_name=VALUES(draft_name), draft_description=VALUES(draft_description), published_name=VALUES(published_name), published_description=VALUES(published_description), translation_status=VALUES(translation_status), published_at=COALESCE(published_at, NOW())';
                $pdo->prepare($sql)->execute([$serviceId, $lang, $serviceName, $description, $serviceName, $description, $status]);
            }
        }

        foreach ($seed['settings'] as $key => $value) {
            $pdo->prepare('INSERT INTO settings (setting_key, setting_value, is_public) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)')->execute([$key, $value]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

if (!$withoutAdmin) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $pdo->prepare('INSERT INTO admins (email, password_hash, display_name) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash), display_name=VALUES(display_name)')->execute([$email, $hash, $name]);
}

fwrite(STDOUT, $withoutAdmin
    ? "Database installed. Create the admin account separately when needed.\n"
    : "Database installed and admin account created.\n");
