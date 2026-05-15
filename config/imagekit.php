<?php
declare(strict_types=1);

/**
 * ImageKit CDN uploads (campaign images, donation proof, profile photos).
 * Optional: falls back to placeholders in ui_helpers when not configured.
 */

require_once __DIR__ . '/app.php';

function imagekit_config(): array
{
    return [
        'public_key' => (string) env_value('IMAGEKIT_PUBLIC_KEY', ''),
        'private_key' => (string) env_value('IMAGEKIT_PRIVATE_KEY', ''),
        'url_endpoint' => rtrim((string) env_value('IMAGEKIT_URL_ENDPOINT', ''), '/'),
        'upload_endpoint' => 'https://upload.imagekit.io/api/v1/files/upload',
    ];
}
