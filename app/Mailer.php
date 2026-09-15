<?php

declare(strict_types=1);

final class Mailer
{
    public static function configured(array $smtp): bool
    {
        return trim((string) $smtp['host']) !== '' && trim((string) $smtp['to']) !== '';
    }

    public static function sendContactNotification(array $smtp, array $request): bool
    {
        if (!self::configured($smtp)) {
            return false;
        }
        $host = (string) $smtp['host'];
        $port = (int) $smtp['port'];
        $transport = $smtp['encryption'] === 'ssl' ? 'ssl://' : '';
        $socket = @stream_socket_client($transport . $host . ':' . $port, $errno, $error, 12, STREAM_CLIENT_CONNECT);
        if (!$socket) {
            error_log("SMTP connection failed: {$errno} {$error}");
            return false;
        }
        stream_set_timeout($socket, 12);

        try {
            self::expect($socket, [220]);
            self::command($socket, 'EHLO almawaheb.local', [250]);
            if ($smtp['encryption'] === 'tls') {
                self::command($socket, 'STARTTLS', [220]);
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('Unable to enable TLS');
                }
                self::command($socket, 'EHLO almawaheb.local', [250]);
            }
            if ((string) $smtp['user'] !== '') {
                self::command($socket, 'AUTH LOGIN', [334]);
                self::command($socket, base64_encode((string) $smtp['user']), [334]);
                self::command($socket, base64_encode((string) $smtp['pass']), [235]);
            }
            self::command($socket, 'MAIL FROM:<' . $smtp['from'] . '>', [250]);
            self::command($socket, 'RCPT TO:<' . $smtp['to'] . '>', [250, 251]);
            self::command($socket, 'DATA', [354]);

            $subject = 'طلب تواصل جديد - شركة المواهب';
            $body = "Name: {$request['name']}\r\nCompany: {$request['company']}\r\nContact: {$request['contact_method']}\r\nService: {$request['service_name']}\r\nDetails:\r\n{$request['details']}";
            $headers = [
                'From: ' . self::encodeHeader((string) $smtp['from_name']) . ' <' . $smtp['from'] . '>',
                'To: <' . $smtp['to'] . '>',
                'Subject: ' . self::encodeHeader($subject),
                'MIME-Version: 1.0',
                'Content-Type: text/plain; charset=UTF-8',
                'Content-Transfer-Encoding: 8bit',
            ];
            fwrite($socket, implode("\r\n", $headers) . "\r\n\r\n" . str_replace("\n.", "\n..", $body) . "\r\n.\r\n");
            self::expect($socket, [250]);
            self::command($socket, 'QUIT', [221]);
            fclose($socket);
            return true;
        } catch (Throwable $e) {
            error_log('SMTP send failed: ' . $e->getMessage());
            fclose($socket);
            return false;
        }
    }

    private static function command($socket, string $command, array $codes): void
    {
        fwrite($socket, $command . "\r\n");
        self::expect($socket, $codes);
    }

    private static function expect($socket, array $codes): void
    {
        $response = '';
        while (($line = fgets($socket, 1024)) !== false) {
            $response .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }
        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $codes, true)) {
            throw new RuntimeException('Unexpected SMTP response: ' . trim($response));
        }
    }

    private static function encodeHeader(string $value): string
    {
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }
}

