<?php

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
define('PUBLIC_PATH', ROOT_PATH . '/public');

$envFile = ROOT_PATH . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, "\"'");
        if (getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
}

$config = require ROOT_PATH . '/app/config.php';
date_default_timezone_set((string) $config['timezone']);

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_name('mawaheb_session');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => (bool) $config['session_secure'],
    'httponly' => true,
    'samesite' => 'Lax',
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once ROOT_PATH . '/app/Database.php';
require_once ROOT_PATH . '/app/helpers.php';
require_once ROOT_PATH . '/app/ContentRepository.php';
require_once ROOT_PATH . '/app/Mailer.php';

