<?php

declare(strict_types=1);

function db(): PDO
{
    global $config;
    return Database::connection($config['db']);
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function app_url(string $path = ''): string
{
    global $config;
    return $config['url'] . '/' . ltrim($path, '/');
}

function page_url(string $lang, string $page = 'home', array $query = []): string
{
    $path = '/' . $lang . ($page === 'home' ? '/' : '/' . $page);
    return $path . ($query ? '?' . http_build_query($query) : '');
}

function redirect(string $path): never
{
    header('Location: ' . $path, true, 303);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(?string $message = null): void
{
    $given = $_POST['_token'] ?? '';
    if (!is_string($given) || !hash_equals(csrf_token(), $given)) {
        http_response_code(419);
        exit(e($message ?? 'طلب غير صالح أو انتهت صلاحيته.'));
    }
}

function flash(string $key, ?string $value = null): ?string
{
    if ($value !== null) {
        $_SESSION['_flash'][$key] = $value;
        return null;
    }
    $message = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $message;
}

function old_input(array $data): void
{
    $_SESSION['_old'] = $data;
}

function old(string $key, string $default = ''): string
{
    return (string) ($_SESSION['_old'][$key] ?? $default);
}

function clear_old(): void
{
    unset($_SESSION['_old']);
}

function client_ip_hash(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    return hash('sha256', $ip . '|' . (string) env_value('APP_KEY', 'mawaheb-rate-limit'));
}

function rate_limit(string $action, int $maxAttempts, int $windowSeconds): bool
{
    $key = client_ip_hash();
    $pdo = db();
    $pdo->prepare('DELETE FROM rate_limits WHERE expires_at < NOW()')->execute();
    $stmt = $pdo->prepare('SELECT id, attempts FROM rate_limits WHERE action_name = ? AND identity_hash = ? AND expires_at >= NOW() LIMIT 1');
    $stmt->execute([$action, $key]);
    $row = $stmt->fetch();
    if (!$row) {
        $stmt = $pdo->prepare('INSERT INTO rate_limits (action_name, identity_hash, attempts, expires_at) VALUES (?, ?, 1, DATE_ADD(NOW(), INTERVAL ? SECOND))');
        $stmt->execute([$action, $key, $windowSeconds]);
        return true;
    }
    if ((int) $row['attempts'] >= $maxAttempts) {
        return false;
    }
    $pdo->prepare('UPDATE rate_limits SET attempts = attempts + 1 WHERE id = ?')->execute([$row['id']]);
    return true;
}

function is_admin(): bool
{
    return !empty($_SESSION['admin_id']);
}

function require_admin(): void
{
    if (!is_admin()) {
        flash('error', 'يرجى تسجيل الدخول أولاً.');
        redirect('/admin/login');
    }
}

function t(string $key, string $lang, array $replace = []): string
{
    static $catalogues = [];
    if (!isset($catalogues[$lang])) {
        $file = ROOT_PATH . '/app/lang/' . $lang . '.php';
        $catalogues[$lang] = is_file($file) ? require $file : [];
    }
    $text = $catalogues[$lang][$key] ?? $key;
    foreach ($replace as $name => $value) {
        $text = str_replace(':' . $name, (string) $value, $text);
    }
    return $text;
}

function lines(string $text): array
{
    return array_values(array_filter(array_map('trim', preg_split('/\R/u', $text) ?: [])));
}

function render(string $view, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require ROOT_PATH . '/app/views/' . $view . '.php';
}

function content_field(array $content, string $key, string $default = ''): string
{
    return isset($content[$key]) && is_scalar($content[$key]) ? (string) $content[$key] : $default;
}

function service_icon(string $name): string
{
    $paths = [
        'land' => '<path d="M4 20V8l8-4 8 4v12M1 20h22M8 20V10h8v10M12 10v10M5 4l7-3 7 3"/>',
        'sea' => '<path d="M3 13l9-3 9 3-3 6H6zM7 11V5h10v6M10 5V2h4v3M2 22q2-3 5 0 2-3 5 0 2-3 5 0 2-3 5 0"/>',
        'air' => '<path d="M21 3L3 10l7 3 3 8 8-18zM10 13L21 3"/>',
        'clearance' => '<path d="M4 19h16M6 19V9l6-5 6 5v10M9 19v-5h6v5M8 10h.01M16 10h.01"/>',
        'document' => '<path d="M7 3h7l4 4v14H7zM14 3v5h5M10 13h5M10 17h5"/>',
        'consultancy' => '<path d="M4 5h16v12H8l-4 4V5zM8 9h8M8 13h5"/>',
        'tracking' => '<circle cx="12" cy="12" r="8"/><path d="M12 8v5l3 2M3 12H1M23 12h-2"/>',
        'resolution' => '<path d="M12 3l8 4v5c0 5-3.5 8-8 9-4.5-1-8-4-8-9V7zM8.5 12l2.3 2.3 4.7-5"/>',
    ];
    $path = $paths[$name] ?? $paths['document'];
    return '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';
}
