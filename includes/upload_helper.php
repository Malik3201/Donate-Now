<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/imagekit.php';

function upload_to_imagekit(array $file, string $folder): array
{
    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'message' => 'Invalid upload request.'];
    }

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    $maxSize = str_contains(strtolower($folder), 'proof') ? 5 * 1024 * 1024 : 2 * 1024 * 1024;

    $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExtensions, true)) {
        return ['success' => false, 'message' => 'Only jpg, jpeg, png, and webp are allowed.'];
    }

    if ((int) $file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File exceeds allowed size limit.'];
    }

    $cfg = imagekit_config();
    if (empty($cfg['private_key'])) {
        return ['success' => false, 'message' => 'ImageKit is not configured.'];
    }

    $postFields = [
        'file' => base64_encode((string) file_get_contents($file['tmp_name'])),
        'fileName' => uniqid('img_', true) . '.' . $ext,
        'folder' => '/' . trim($folder, '/'),
    ];

    $ch = curl_init($cfg['upload_endpoint']);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postFields,
        CURLOPT_USERPWD => $cfg['private_key'] . ':',
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_TIMEOUT => 30,
    ]);
    $raw = curl_exec($ch);
    $error = curl_error($ch);
    $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false || $error) {
        return ['success' => false, 'message' => 'Upload failed: ' . $error];
    }

    $response = json_decode($raw, true);
    if ($statusCode >= 300 || empty($response['url'])) {
        return ['success' => false, 'message' => 'ImageKit rejected upload.', 'response' => $response];
    }

    return [
        'success' => true,
        'url' => $response['url'],
        'fileId' => $response['fileId'] ?? null,
    ];
}

function upload_report_attachment_to_imagekit(array $file, string $folder = 'reports'): array
{
    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'message' => 'Invalid upload request.'];
    }

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
    $maxSize = 5 * 1024 * 1024;
    $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExtensions, true)) {
        return ['success' => false, 'message' => 'Only jpg, jpeg, png, webp, and pdf are allowed.'];
    }
    if ((int) $file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'Attachment exceeds 5MB limit.'];
    }

    $cfg = imagekit_config();
    if (empty($cfg['private_key'])) {
        return ['success' => false, 'message' => 'ImageKit is not configured.'];
    }

    $postFields = [
        'file' => base64_encode((string) file_get_contents($file['tmp_name'])),
        'fileName' => uniqid('report_', true) . '.' . $ext,
        'folder' => '/' . trim($folder, '/'),
    ];

    $ch = curl_init($cfg['upload_endpoint']);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postFields,
        CURLOPT_USERPWD => $cfg['private_key'] . ':',
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_TIMEOUT => 30,
    ]);
    $raw = curl_exec($ch);
    $error = curl_error($ch);
    $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false || $error) {
        return ['success' => false, 'message' => 'Upload failed: ' . $error];
    }

    $response = json_decode($raw, true);
    if ($statusCode >= 300 || empty($response['url'])) {
        return ['success' => false, 'message' => 'ImageKit rejected upload.', 'response' => $response];
    }

    return [
        'success' => true,
        'url' => $response['url'],
        'fileId' => $response['fileId'] ?? null,
    ];
}
