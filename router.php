<?php

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$file = __DIR__ . '/public' . $path;
if (str_starts_with((string) $path, '/uploads/') && preg_match('/\.(?:php|phtml|phar|cgi|pl|py|sh)$/i', (string) $path)) {
    http_response_code(403);
    exit('Forbidden');
}
if ($path !== '/' && is_file($file)) {
    // The PHP development server does not implement byte ranges for static MP4s.
    // Safari and mobile video players need them; Apache/Nginx serve these natively.
    if (preg_match('#^/assets/video/(?:bg|bg2)-(?:720|1080)\.mp4$#D', (string) $path)) {
        require __DIR__ . '/app/serve_video.php';
        serve_local_video($file);
    }
    return false;
}
require __DIR__ . '/public/index.php';
