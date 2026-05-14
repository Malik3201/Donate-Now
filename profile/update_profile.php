<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/auth_check.php';
require_once dirname(__DIR__) . '/includes/mail_helper.php';
$pdo = db();
$msg = '';
$currentProfile = [];
if ($authUser['role'] === 'donor') {
  $stmt = $pdo->prepare('SELECT * FROM donor_profiles WHERE user_id=:id LIMIT 1');$stmt->execute(['id'=>(int)$authUser['id']]);$currentProfile = $stmt->fetch() ?: [];
} elseif ($authUser['role'] === 'ngo') {
  $stmt = $pdo->prepare('SELECT * FROM ngo_profiles WHERE user_id=:id LIMIT 1');$stmt->execute(['id'=>(int)$authUser['id']]);$currentProfile = $stmt->fetch() ?: [];
} elseif ($authUser['role'] === 'volunteer') {
  $stmt = $pdo->prepare('SELECT * FROM volunteer_profiles WHERE user_id=:id LIMIT 1');$stmt->execute(['id'=>(int)$authUser['id']]);$currentProfile = $stmt->fetch() ?: [];
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) exit('Invalid CSRF token');
    $full_name = trim((string)($_POST['full_name'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $stmt = $pdo->prepare('UPDATE users SET full_name = :full_name, phone = :phone, updated_at = NOW() WHERE id = :id');
    $stmt->execute(['full_name' => $full_name, 'phone' => $phone ?: null, 'id' => (int)$authUser['id']]);
    if ($authUser['role'] === 'donor') {
        $stmt = $pdo->prepare('UPDATE donor_profiles SET address = :address, updated_at = NOW() WHERE user_id = :id');
        $stmt->execute(['address' => trim((string)($_POST['address'] ?? '')) ?: null, 'id' => (int)$authUser['id']]);
    } elseif ($authUser['role'] === 'ngo') {
        $stmt = $pdo->prepare('UPDATE ngo_profiles SET ngo_name = :ngo_name, description = :description, address = :address, updated_at = NOW() WHERE user_id = :id');
        $stmt->execute(['ngo_name' => trim((string)($_POST['ngo_name'] ?? '')), 'description' => trim((string)($_POST['description'] ?? '')) ?: null, 'address' => trim((string)($_POST['address'] ?? '')) ?: null, 'id' => (int)$authUser['id']]);
    } elseif ($authUser['role'] === 'volunteer') {
        $stmt = $pdo->prepare('UPDATE volunteer_profiles SET skills = :skills, availability = :availability, address = :address, updated_at = NOW() WHERE user_id = :id');
        $stmt->execute(['skills' => trim((string)($_POST['skills'] ?? '')) ?: null, 'availability' => trim((string)($_POST['availability'] ?? '')) ?: null, 'address' => trim((string)($_POST['address'] ?? '')) ?: null, 'id' => (int)$authUser['id']]);
    }
    log_activity($pdo, (int)$authUser['id'], 'profile_update', 'users', (int)$authUser['id'], 'Profile updated');
    $msg = 'Profile updated successfully.';
}
$pageTitle='Update Profile';
require_once dirname(__DIR__) . '/includes/dashboard_layout_start.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>
<h1 class="section-title">Edit Profile</h1>
<?php require dirname(__DIR__) . '/includes/flash_messages.php'; ?>
<div class="form-card"><form method="post"><input type="hidden" name="csrf_token" value="<?= sanitize(csrf_token()) ?>">
<div class="form-grid"><div class="form-group"><label>Full Name</label><input name="full_name" value="<?= sanitize((string)($authUser['full_name'] ?? '')) ?>" required></div><div class="form-group"><label>Phone</label><input name="phone" value="<?= sanitize((string)($authUser['phone'] ?? '')) ?>"></div></div>
<?php if($authUser['role']==='donor'): ?>
<div class="form-group"><label>Address</label><input name="address" value="<?= sanitize((string)($currentProfile['address'] ?? '')) ?>"></div>
<?php elseif($authUser['role']==='ngo'): ?>
<div class="form-grid"><div class="form-group"><label>NGO Name</label><input name="ngo_name" value="<?= sanitize((string)($currentProfile['ngo_name'] ?? '')) ?>"></div><div class="form-group"><label>Address</label><input name="address" value="<?= sanitize((string)($currentProfile['address'] ?? '')) ?>"></div></div><div class="form-group"><label>Description</label><textarea name="description" rows="4"><?= sanitize((string)($currentProfile['description'] ?? '')) ?></textarea></div>
<?php elseif($authUser['role']==='volunteer'): ?>
<div class="form-grid"><div class="form-group"><label>Skills</label><input name="skills" value="<?= sanitize((string)($currentProfile['skills'] ?? '')) ?>"></div><div class="form-group"><label>Availability</label><input name="availability" value="<?= sanitize((string)($currentProfile['availability'] ?? '')) ?>"></div></div><div class="form-group"><label>Address</label><input name="address" value="<?= sanitize((string)($currentProfile['address'] ?? '')) ?>"></div>
<?php endif; ?>
<button class="gradient-button" type="submit">Save Changes</button>
</form></div>
<?php require_once dirname(__DIR__) . '/includes/dashboard_layout_end.php'; ?>
