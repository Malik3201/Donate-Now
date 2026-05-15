<?php
declare(strict_types=1);

/**
 * Brevo email: API key (xkeysib-…) and/or SMTP (xsmtpsib-…). See .env and mail_helper.php.
 */

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

    $smtpUser = trim((string) env_value('BREVO_SMTP_USER', ''));
    $smtpPass = trim((string) env_value('BREVO_SMTP_PASS', ''));
    $fromEmail = trim((string) env_value('BREVO_FROM_EMAIL', ''));

    return [
        'api_key' => $apiKey,
        'smtp_host' => (string) env_value('BREVO_SMTP_HOST', 'smtp-relay.brevo.com'),
        'smtp_port' => (int) env_value('BREVO_SMTP_PORT', '587'),
        'smtp_user' => $smtpUser,
        'smtp_pass' => $smtpPass,
        'from_email' => $fromEmail,
        'from_name' => (string) env_value('BREVO_FROM_NAME', 'Donate Now'),
        'api_url' => 'https://api.brevo.com/v3/smtp/email',
    ];
}

/** API (xkeysib-…) or SMTP (xsmtpsib-… + BREVO_SMTP_USER) + verified sender. */
function brevo_mail_is_configured(): bool
{
    $cfg = brevo_config();
    if ($cfg['from_email'] === '') {
        return false;
    }
    if ($cfg['api_key'] !== '') {
        return true;
    }

    return $cfg['smtp_user'] !== '' && $cfg['smtp_pass'] !== '';
}

function brevo_mail_transport(): string
{
    $cfg = brevo_config();
    if ($cfg['api_key'] !== '') {
        return 'api';
    }
    if ($cfg['smtp_user'] !== '' && $cfg['smtp_pass'] !== '') {
        return 'smtp';
    }

    return '';
}
