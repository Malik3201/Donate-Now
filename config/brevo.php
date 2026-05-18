<?php
declare(strict_types=1);

/**
 * Brevo email: API key (xkeysib-…) preferred; SMTP (xsmtpsib-…) as fallback.
 */

require_once __DIR__ . '/app.php';

function brevo_config(): array
{
    $apiKey = trim((string) env_value('BREVO_API_KEY', ''));
    if ($apiKey === '') {
        // Back-compat: transactional API key was sometimes stored in BREVO_SMTP_PASS by mistake
        $pass = trim((string) env_value('BREVO_SMTP_PASS', ''));
        if ($pass !== '' && str_starts_with($pass, 'xkeysib-')) {
            $apiKey = $pass;
        }
    }

    $smtpUser = trim((string) env_value('BREVO_SMTP_USER', ''));
    $smtpPass = trim((string) env_value('BREVO_SMTP_PASS', ''));
    // Never use API keys as SMTP password
    if ($smtpPass !== '' && str_starts_with($smtpPass, 'xkeysib-')) {
        $smtpPass = '';
    }

    $fromEmail = trim((string) env_value('BREVO_FROM_EMAIL', ''));
    $smtpPort = (int) env_value('BREVO_SMTP_PORT', '587');
    if ($smtpPort <= 0) {
        $smtpPort = 587;
    }

    return [
        'api_key' => $apiKey,
        'smtp_host' => trim((string) env_value('BREVO_SMTP_HOST', 'smtp-relay.brevo.com')),
        'smtp_port' => $smtpPort,
        'smtp_user' => $smtpUser,
        'smtp_pass' => $smtpPass,
        'from_email' => $fromEmail,
        'from_name' => trim((string) env_value('BREVO_FROM_NAME', 'Donate Now')) ?: 'Donate Now',
        'admin_email' => brevo_admin_email_from_env(),
        'api_url' => 'https://api.brevo.com/v3/smtp/email',
    ];
}

/** First valid ADMIN_EMAIL from .env (optional). */
function brevo_admin_email_from_env(): string
{
    $env = trim((string) env_value('ADMIN_EMAIL', ''));
    if ($env === '') {
        return '';
    }
    foreach (preg_split('/\s*,\s*/', $env) as $part) {
        $part = trim($part);
        if ($part !== '' && filter_var($part, FILTER_VALIDATE_EMAIL)) {
            return $part;
        }
    }

    return '';
}

function brevo_api_is_configured(): bool
{
    $cfg = brevo_config();

    return $cfg['from_email'] !== '' && $cfg['api_key'] !== '';
}

function brevo_smtp_is_configured(): bool
{
    $cfg = brevo_config();

    return $cfg['from_email'] !== '' && $cfg['smtp_user'] !== '' && $cfg['smtp_pass'] !== '';
}

/** Verified sender plus at least one transport (API and/or SMTP). */
function brevo_mail_is_configured(): bool
{
    return brevo_api_is_configured() || brevo_smtp_is_configured();
}

/** Preferred transport label for admin UI (API tried first when configured). */
function brevo_mail_transport(): string
{
    if (brevo_api_is_configured()) {
        return 'api';
    }
    if (brevo_smtp_is_configured()) {
        return 'smtp';
    }

    return '';
}

/**
 * Safe environment snapshot for admin diagnostics (no secrets).
 *
 * @return array<string, mixed>
 */
function brevo_mail_diagnostics(): array
{
    $cfg = brevo_config();

    return [
        'api_configured' => brevo_api_is_configured(),
        'smtp_configured' => brevo_smtp_is_configured(),
        'mail_configured' => brevo_mail_is_configured(),
        'curl_available' => function_exists('curl_init'),
        'openssl_available' => extension_loaded('openssl'),
        'allow_url_fopen' => filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOL),
        'from_email' => $cfg['from_email'],
        'from_name' => $cfg['from_name'],
        'admin_email' => $cfg['admin_email'],
        'smtp_host' => $cfg['smtp_host'],
        'smtp_port' => $cfg['smtp_port'],
        'smtp_user_configured' => $cfg['smtp_user'] !== '',
        'smtp_pass_configured' => $cfg['smtp_pass'] !== '',
        'preferred_method' => brevo_mail_transport(),
    ];
}
