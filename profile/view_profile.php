<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/auth_check.php';
$pdo = db();
$user = $authUser;
$profile = null;
if ($user['role'] === 'donor') {
    $stmt = $pdo->prepare('SELECT * FROM donor_profiles WHERE user_id = :id LIMIT 1');
    $stmt->execute(['id' => (int)$user['id']]);
    $profile = $stmt->fetch();
} elseif ($user['role'] === 'ngo') {
    $stmt = $pdo->prepare('SELECT * FROM ngo_profiles WHERE user_id = :id LIMIT 1');
    $stmt->execute(['id' => (int)$user['id']]);
    $profile = $stmt->fetch();
} elseif ($user['role'] === 'volunteer') {
    $stmt = $pdo->prepare('SELECT * FROM volunteer_profiles WHERE user_id = :id LIMIT 1');
    $stmt->execute(['id' => (int)$user['id']]);
    $profile = $stmt->fetch();
}
$pageTitle='My Profile';
require_once dirname(__DIR__) . '/includes/dashboard_layout_start.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
$photo = image_or_placeholder((string)($user['profile_photo_url'] ?? ''), 'profile');
?>
<h1 class="section-title">My Profile</h1>
<div class="glass-card" style="padding:1.2rem;margin-bottom:1rem;background:linear-gradient(120deg,rgba(91,124,250,.25),rgba(34,199,215,.2));">
  <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
    <img src="<?= sanitize($photo) ?>" alt="Profile photo" style="width:90px;height:90px;border-radius:50%;object-fit:cover;">
    <div>
      <h2><?= sanitize($user['full_name']) ?> <span class="badge"><?= strtoupper($user['role']) ?></span></h2>
      <p><?= sanitize($user['email']) ?> | <?= sanitize((string)($user['phone'] ?? '')) ?></p>
      <p>Account Status: <span class="status-badge"><?= sanitize((string)($user['account_status'] ?? 'active')) ?></span></p>
    </div>
    <div style="margin-left:auto;display:flex;gap:.5rem;">
      <a class="outline-button" href="<?= APP_URL ?>/profile/upload_profile_photo.php">Upload Photo</a>
      <a class="gradient-button" href="<?= APP_URL ?>/profile/update_profile.php">Edit Profile</a>
      <a class="outline-button" href="<?= APP_URL ?>/profile/change_password.php">Change Password</a>
    </div>
  </div>
</div>
<div class="table-wrapper"><h3>Overview</h3><table><tbody>
<tr><th style="width:240px;">Full Name</th><td><?= sanitize($user['full_name']) ?></td></tr>
<tr><th>Email</th><td><?= sanitize($user['email']) ?></td></tr>
<tr><th>Phone</th><td><?= sanitize((string)($user['phone'] ?? '')) ?></td></tr>
<?php if ($user['role']==='donor'): ?>
<tr><th>Donor Type</th><td><?= sanitize((string)($profile['donor_type'] ?? 'individual')) ?></td></tr>
<tr><th>Address</th><td><?= sanitize((string)($profile['address'] ?? '')) ?></td></tr>
<tr><th>Total Donated Amount</th><td>PKR <?= number_format((float)($profile['total_donated_amount'] ?? 0),2) ?></td></tr>
<tr><th>Donation Count</th><td><?= (int)($profile['total_donations_count'] ?? 0) ?></td></tr>
<?php elseif ($user['role']==='ngo'): ?>
<tr><th>NGO Name</th><td><?= sanitize((string)($profile['ngo_name'] ?? '')) ?></td></tr>
<tr><th>Verification Status</th><td><span class="status-badge"><?= sanitize((string)($profile['verification_status'] ?? 'pending')) ?></span></td></tr>
<tr><th>Registration Number</th><td><?= sanitize((string)($profile['registration_number'] ?? '')) ?></td></tr>
<tr><th>Description</th><td><?= sanitize((string)($profile['description'] ?? '')) ?></td></tr>
<tr><th>Address</th><td><?= sanitize((string)($profile['address'] ?? '')) ?></td></tr>
<?php elseif ($user['role']==='volunteer'): ?>
<tr><th>Skills</th><td><?= sanitize((string)($profile['skills'] ?? '')) ?></td></tr>
<tr><th>Availability</th><td><?= sanitize((string)($profile['availability'] ?? '')) ?></td></tr>
<tr><th>Joined Campaigns</th><td><?= (int)($profile['total_joined_campaigns'] ?? 0) ?></td></tr>
<?php endif; ?>
</tbody></table></div>
<?php require_once dirname(__DIR__) . '/includes/dashboard_layout_end.php'; ?>
