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

$pageTitle = 'My Profile';
$breadcrumbCurrent = 'My Profile';
require_once dirname(__DIR__) . '/includes/dashboard_layout_start.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';

$photo = image_or_placeholder((string)($user['profile_photo_url'] ?? ''), 'profile');
$phone = trim((string)($user['phone'] ?? ''));
$accountStatus = strtolower((string)($user['account_status'] ?? 'active'));
$roleLabel = strtoupper((string)($user['role'] ?? 'user'));
?>
<div class="dn-page-head">
  <h1 class="dn-page-title">My Profile</h1>
  <p class="dn-page-lead">Manage your account details, photo, and security settings.</p>
</div>

<section class="dn-profile-hero glass-card" aria-label="Profile summary">
  <div class="dn-profile-hero__main">
    <img
      class="dn-profile-hero__avatar"
      src="<?= sanitize($photo) ?>"
      alt="<?= sanitize((string)($user['full_name'] ?? 'User')) ?> profile photo"
      width="96"
      height="96"
    >
    <div class="dn-profile-hero__info">
      <div class="dn-profile-hero__name-row">
        <h2 class="dn-profile-hero__name"><?= sanitize((string)($user['full_name'] ?? '')) ?></h2>
        <span class="dn-badge dn-profile-role-badge"><?= sanitize($roleLabel) ?></span>
      </div>
      <ul class="dn-profile-hero__meta">
        <li>
          <span class="dn-profile-hero__meta-label">Email</span>
          <a href="mailto:<?= sanitize((string)($user['email'] ?? '')) ?>"><?= sanitize((string)($user['email'] ?? '')) ?></a>
        </li>
        <?php if ($phone !== ''): ?>
        <li>
          <span class="dn-profile-hero__meta-label">Phone</span>
          <a href="tel:<?= sanitize(preg_replace('/\s+/', '', $phone)) ?>"><?= sanitize($phone) ?></a>
        </li>
        <?php endif; ?>
      </ul>
      <p class="dn-profile-hero__status">
        <span class="dn-profile-hero__meta-label">Account status</span>
        <span class="dn-badge" data-status="<?= sanitize($accountStatus) ?>"><?= sanitize($accountStatus) ?></span>
      </p>
    </div>
  </div>
  <div class="dn-profile-hero__actions">
    <a class="outline-button" href="<?= APP_URL ?>/profile/upload_profile_photo.php">Upload Photo</a>
    <a class="gradient-button" href="<?= APP_URL ?>/profile/update_profile.php">Edit Profile</a>
    <a class="outline-button" href="<?= APP_URL ?>/profile/change_password.php">Change Password</a>
  </div>
</section>

<div class="table-wrapper dn-profile-overview">
  <h3 class="dn-profile-overview__title">Overview</h3>
  <table>
    <tbody>
      <tr><th>Full Name</th><td><?= sanitize((string)($user['full_name'] ?? '')) ?></td></tr>
      <tr><th>Email</th><td><?= sanitize((string)($user['email'] ?? '')) ?></td></tr>
      <tr><th>Phone</th><td><?= $phone !== '' ? sanitize($phone) : '—' ?></td></tr>
      <?php if ($user['role'] === 'donor'): ?>
      <tr><th>Donor Type</th><td><?= sanitize((string)($profile['donor_type'] ?? 'individual')) ?></td></tr>
      <tr><th>Address</th><td><?= sanitize((string)($profile['address'] ?? '')) ?></td></tr>
      <tr><th>Total Donated Amount</th><td>PKR <?= number_format((float)($profile['total_donated_amount'] ?? 0), 2) ?></td></tr>
      <tr><th>Donation Count</th><td><?= (int)($profile['total_donations_count'] ?? 0) ?></td></tr>
      <?php elseif ($user['role'] === 'ngo'): ?>
      <tr><th>NGO Name</th><td><?= sanitize((string)($profile['ngo_name'] ?? '')) ?></td></tr>
      <tr><th>Verification Status</th><td><span class="dn-badge" data-status="<?= sanitize(strtolower((string)($profile['verification_status'] ?? 'pending'))) ?>"><?= sanitize((string)($profile['verification_status'] ?? 'pending')) ?></span></td></tr>
      <tr><th>Registration Number</th><td><?= sanitize((string)($profile['registration_number'] ?? '')) ?></td></tr>
      <tr><th>Description</th><td><?= sanitize((string)($profile['description'] ?? '')) ?></td></tr>
      <tr><th>Address</th><td><?= sanitize((string)($profile['address'] ?? '')) ?></td></tr>
      <?php elseif ($user['role'] === 'volunteer'): ?>
      <tr><th>Skills</th><td><?= sanitize((string)($profile['skills'] ?? '')) ?></td></tr>
      <tr><th>Availability</th><td><?= sanitize((string)($profile['availability'] ?? '')) ?></td></tr>
      <tr><th>Joined Campaigns</th><td><?= (int)($profile['total_joined_campaigns'] ?? 0) ?></td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php require_once dirname(__DIR__) . '/includes/dashboard_layout_end.php'; ?>
