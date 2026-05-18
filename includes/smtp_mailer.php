<?php
declare(strict_types=1);

/**
 * Minimal SMTP client (SSL on 465, STARTTLS on 587/2525) for Brevo / generic relays.
 *
 * @param array{host: string, port: int, user: string, pass: string, from_email: string, from_name: string} $cfg
 * @return array{ok: bool, error: ?string}
 */
function smtp_send_html_email(array $cfg, string $to, string $subject, string $htmlBody): array
{
    $host = trim((string) ($cfg['host'] ?? ''));
    $port = (int) ($cfg['port'] ?? 587);
    $user = trim((string) ($cfg['user'] ?? ''));
    $pass = (string) ($cfg['pass'] ?? '');
    $fromEmail = trim((string) ($cfg['from_email'] ?? ''));
    $fromName = trim((string) ($cfg['from_name'] ?? 'Donate Now'));

    if ($host === '' || $user === '' || $pass === '' || $fromEmail === '') {
        return ['ok' => false, 'error' => 'Incomplete SMTP configuration.'];
    }

    if (str_starts_with($pass, 'xkeysib-')) {
        return ['ok' => false, 'error' => 'BREVO_SMTP_PASS must be an SMTP key (xsmtpsib-…), not an API key (xkeysib-…).'];
    }

    $allowedPorts = [25, 465, 587, 2525];
    if (!in_array($port, $allowedPorts, true)) {
        $port = 587;
    }

    $useSsl = $port === 465;
    $target = ($useSsl ? 'ssl://' : 'tcp://') . $host . ':' . $port;

    $errno = 0;
    $errstr = '';
    $socket = @stream_socket_client(
        $target,
        $errno,
        $errstr,
        30,
        STREAM_CLIENT_CONNECT
    );

    if (!$socket) {
        return ['ok' => false, 'error' => smtp_format_connect_error($errstr, $errno)];
    }

    stream_set_timeout($socket, 30);

    try {
        smtp_expect($socket, [220]);
        smtp_cmd($socket, 'EHLO localhost', [250]);

        if (!$useSsl) {
            smtp_cmd($socket, 'STARTTLS', [220]);
            if (!extension_loaded('openssl')) {
                return ['ok' => false, 'error' => 'OpenSSL extension is required for SMTP STARTTLS.'];
            }
            $cryptoOk = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if (!$cryptoOk) {
                return ['ok' => false, 'error' => 'SMTP STARTTLS negotiation failed.'];
            }
            smtp_cmd($socket, 'EHLO localhost', [250]);
        }

        smtp_cmd($socket, 'AUTH LOGIN', [334]);
        smtp_cmd($socket, base64_encode($user), [334]);
        smtp_cmd($socket, base64_encode($pass), [235]);

        smtp_cmd($socket, 'MAIL FROM:<' . $fromEmail . '>', [250]);
        smtp_cmd($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
        smtp_cmd($socket, 'DATA', [354]);

        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $fromHeader = $fromName !== ''
            ? 'From: ' . smtp_encode_header_name($fromName) . ' <' . $fromEmail . '>'
            : 'From: ' . $fromEmail;

        $message = $fromHeader . "\r\n"
            . 'To: <' . $to . ">\r\n"
            . 'Subject: ' . $encodedSubject . "\r\n"
            . "MIME-Version: 1.0\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: base64\r\n"
            . "\r\n"
            . chunk_split(base64_encode($htmlBody), 76, "\r\n")
            . "\r\n.\r\n";

        fwrite($socket, $message);
        smtp_expect($socket, [250]);
        smtp_cmd($socket, 'QUIT', [221]);
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => smtp_sanitize_error_message($e->getMessage())];
    } finally {
        fclose($socket);
    }

    return ['ok' => true, 'error' => null];
}

function smtp_format_connect_error(string $errstr, int $errno): string
{
    $detail = $errstr !== '' ? $errstr : (string) $errno;
    $lower = strtolower($detail);

    if (
        str_contains($lower, 'refused')
        || str_contains($lower, 'timed out')
        || str_contains($lower, 'could not connect')
        || str_contains($lower, 'unable to connect')
    ) {
        return 'SMTP connection failed. The hosting provider may be blocking outbound SMTP ports. Configure BREVO_API_KEY to send through Brevo API over HTTPS.';
    }

    return 'SMTP connect failed: ' . $detail;
}

function smtp_sanitize_error_message(string $message): string
{
    $message = preg_replace('/AUTH[^\r\n]*/i', 'AUTH [redacted]', $message) ?? $message;
    $message = preg_replace('/\bxkeysib-[A-Za-z0-9_-]+/i', '[api-key-redacted]', $message) ?? $message;
    $message = preg_replace('/\bxsmtpsib-[A-Za-z0-9_-]+/i', '[smtp-key-redacted]', $message) ?? $message;

    $lower = strtolower($message);
    if (
        str_contains($lower, 'refused')
        || str_contains($lower, 'timed out')
        || str_contains($lower, 'could not connect')
    ) {
        return 'SMTP connection failed. The hosting provider may be blocking outbound SMTP ports. Configure BREVO_API_KEY to send through Brevo API over HTTPS.';
    }

    return $message;
}

function smtp_encode_header_name(string $name): string
{
    if (preg_match('/[^\x20-\x7E]/', $name)) {
        return '=?UTF-8?B?' . base64_encode($name) . '?=';
    }

    return $name;
}

/**
 * @param resource $socket
 * @param list<int> $codes
 */
function smtp_cmd($socket, string $command, array $codes): void
{
    fwrite($socket, $command . "\r\n");
    smtp_expect($socket, $codes);
}

/**
 * @param resource $socket
 * @param list<int> $codes
 */
function smtp_expect($socket, array $codes): void
{
    $response = smtp_read_response($socket);
    $code = (int) substr($response, 0, 3);
    if (!in_array($code, $codes, true)) {
        throw new RuntimeException('SMTP error: ' . trim($response));
    }
}

/**
 * @param resource $socket
 */
function smtp_read_response($socket): string
{
    $data = '';
    while (($line = fgets($socket, 515)) !== false) {
        $data .= $line;
        if (isset($line[3]) && $line[3] === ' ') {
            break;
        }
    }

    return $data;
}
