<?php
declare(strict_types=1);

require_once __DIR__ . '/app.php';

function brevo_config(): array
{
    $apiKey = trim((string) env_value('BREVO_API_KEY', ''));
    if ($apiKey === '') {
        // Back-compat: transactional API key (xkeysib-…) was sometimes stored in BREVO_SMTP_PASS by mistake
        $pass = trim((string) env_value('BREVO_SMTP_PASS', ''));
        if ($pass !== '' && str_starts_with($pass, 'xkeysib-')) {
            $apiKey = $pass;
        }
    }

    return [
        'api_key' => $apiKey,
        'smtp_host' => (string) env_value('BREVO_SMTP_HOST', 'smtp-relay.brevo.com'),
        'smtp_port' => (int) env_value('BREVO_SMTP_PORT', '587'),
        'smtp_user' => (string) env_value('BREVO_SMTP_USER', ''),
        'smtp_pass' => (string) env_value('BREVO_SMTP_PASS', ''),
        'from_email' => (string) env_value('BREVO_FROM_EMAIL', ''),
        'from_name' => (string) env_value('BREVO_FROM_NAME', 'Donate Now'),
        'api_url' => 'https://api.brevo.com/v3/smtp/email',
    ];
}
