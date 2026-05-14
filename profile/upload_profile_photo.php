<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/auth_check.php';
require_once dirname(__DIR__) . '/includes/upload_helper.php';
$pdo = db();
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) exit('Invalid CSRF token');
    $result = upload_to_imagekit($_FILES['photo'], 'profiles');
    if (!empty($result['success'])) {
        $stmt = $pdo->prepare('UPDATE users SET profile_photo_url = :url, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['url' => $result['url'], 'id' => (int)$authUser['id']]);
        $msg = 'Profile photo updated.';
    } else {
        $msg = (string)($result['message'] ?? 'Upload failed.');
    }
}
$pageTitle='Upload Profile Photo';
require_once dirname(__DIR__) . '/includes/dashboard_layout_start.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
$photo = image_or_placeholder((string)($authUser['profile_photo_url'] ?? ''),'profile');
?>
<h1 class="section-title">Upload Profile Photo</h1>
<?php require dirname(__DIR__) . '/includes/flash_messages.php'; ?>
<div class="form-card" style="max-width:700px;"><div style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap;"><img src="<?= sanitize($photo) ?>" alt="Current profile" style="width:90px;height:90px;border-radius:50%;object-fit:cover;"><form method="post" enctype="multipart/form-data" style="flex:1;"><input type="hidden" name="csrf_token" value="<?= sanitize(csrf_token()) ?>"><label class="help-text">Choose JPG/JPEG/PNG/WEBP. Max 2MB.</label><input type="file" name="photo" required><button class="gradient-button" type="submit" style="margin-top:.75rem;">Upload</button></form></div></div>
<?php require_once dirname(__DIR__) . '/includes/dashboard_layout_end.php'; ?>
