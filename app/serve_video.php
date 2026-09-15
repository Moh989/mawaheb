<?php

declare(strict_types=1);

/** Byte-range delivery for the four local MP4 assets in the development router. */
function serve_local_video(string $file): never
{
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!in_array($method, ['GET', 'HEAD'], true)) {
        header('Allow: GET, HEAD');
        http_response_code(405);
        exit;
    }
    $stream = fopen($file, 'rb');
    if ($stream === false) {
        http_response_code(404);
        exit;
    }
    $size = (int) fstat($stream)['size'];
    $start = 0;
    $end = $size - 1;
    header('Content-Type: video/mp4');
    header('X-Content-Type-Options: nosniff');
    header('Accept-Ranges: bytes');
    header('Cache-Control: no-cache');

    // Range only applies to GET. Without validators, If-Range falls back to full delivery.
    $range = $method === 'GET' && empty($_SERVER['HTTP_IF_RANGE']) ? ($_SERVER['HTTP_RANGE'] ?? '') : '';
    if ($range !== '') {
        $valid = preg_match('/\Abytes=(\d*)-(\d*)\z/', $range, $parts) === 1 && ($parts[1] !== '' || $parts[2] !== '');
        if ($valid) {
            if ($parts[1] === '') {
                $suffix = (int) $parts[2];
                $valid = $suffix > 0;
                $start = max(0, $size - $suffix);
            } else {
                $start = (int) $parts[1];
                $end = $parts[2] === '' ? $end : min($end, (int) $parts[2]);
            }
            $valid = $valid && $start <= $end && $start < $size;
        }
        if (!$valid) {
            fclose($stream);
            http_response_code(416);
            header('Content-Range: bytes */' . $size);
            header('Content-Length: 0');
            exit;
        }
        http_response_code(206);
        header("Content-Range: bytes {$start}-{$end}/{$size}");
    }
    $remaining = max(0, $end - $start + 1);
    header('Content-Length: ' . $remaining);
    if ($method !== 'HEAD') {
        fseek($stream, $start);
        while ($remaining > 0 && !feof($stream) && !connection_aborted()) {
            $chunk = fread($stream, min(65536, $remaining));
            if ($chunk === false || $chunk === '') break;
            echo $chunk;
            $remaining -= strlen($chunk);
        }
    }
    fclose($stream);
    exit;
}
