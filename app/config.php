<?php

declare(strict_types=1);

function env_value(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    return $value === false ? $default : $value;
}

return [
    'env' => env_value('APP_ENV', 'production'),
    'url' => rtrim((string) env_value('APP_URL', 'http://127.0.0.1:8080'), '/'),
    'timezone' => env_value('APP_TIMEZONE', 'Asia/Baghdad'),
    'session_secure' => filter_var(env_value('SESSION_COOKIE_SECURE', 'false'), FILTER_VALIDATE_BOOL),
    'db' => [
        'host' => env_value('DB_HOST', '127.0.0.1'),
        'port' => (int) env_value('DB_PORT', '3306'),
        'name' => env_value('DB_NAME', 'almawaheb'),
        'user' => env_value('DB_USER', 'root'),
        'pass' => env_value('DB_PASS', ''),
    ],
    'smtp' => [
        'host' => env_value('SMTP_HOST', ''),
        'port' => (int) env_value('SMTP_PORT', '587'),
        'encryption' => env_value('SMTP_ENCRYPTION', 'tls'),
        'user' => env_value('SMTP_USER', ''),
        'pass' => env_value('SMTP_PASS', ''),
        'from' => env_value('SMTP_FROM', 'info@almawaheb-co.com'),
        'from_name' => env_value('SMTP_FROM_NAME', 'شركة المواهب'),
        'to' => env_value('SMTP_TO', 'info@almawaheb-co.com'),
    ],
];

