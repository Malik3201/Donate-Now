<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/auth_check.php';
require_once dirname(__DIR__) . '/includes/role_check.php';
require_role(['donor']);
$pdo = db();
$stmt = $pdo->prepare('SELECT dp.id, dp.total_donations_count, dp.total_donated_amount, u.full_name, u.email, u.phone FROM donor_profiles dp INNER JOIN users u ON u.id = dp.user_id WHERE dp.user_id = :u LIMIT 1');
$stmt->execute(['u' => (int) $authUser['id']]);
$donor = $stmt->fetch();
if (!$donor) {
    exit('Donor profile not found');
}
$stmt = $pdo->prepare("SELECT SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) pending_count, SUM(CASE WHEN status='rejected' THEN 1 ELSE 0 END) rejected_count, SUM(CASE WHEN status='confirmed' THEN 1 ELSE 0 END) confirmed_count, SUM(CASE WHEN status='confirmed' THEN amount ELSE 0 END) confirmed_amount FROM donations WHERE donor_id = :d");
$stmt->execute(['d' => (int) $donor['id']]);
$sum = $stmt->fetch();
$stmt = $pdo->prepare('SELECT d.*, c.title AS campaign_title, n.ngo_name FROM donations d INNER JOIN campaigns c ON c.id = d.campaign_id INNER JOIN ngo_profiles n ON n.id = d.ngo_id WHERE d.donor_id = :d ORDER BY d.created_at DESC');
$stmt->execute(['d' => (int) $donor['id']]);
$history = $stmt->fetchAll();

$pendingCount = (int) ($sum['pending_count'] ?? 0);
$rejectedCount = (int) ($sum['rejected_count'] ?? 0);
$confirmedCount = (int) ($sum['confirmed_count'] ?? 0);
$confirmedAmount = (float) ($sum['confirmed_amount'] ?? 0);
$profileDonations = (int) ($donor['total_donations_count'] ?? 0);
$profileLifetime = (float) ($donor['total_donated_amount'] ?? 0);

$pageTitle = 'Donation reports';
$pageDescription = 'Summary of your giving activity and line-by-line donation history.';
require_once dirname(__DIR__) . '/includes/dashboard_layout_start.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>
<div class="dn-page-head">
  <h1 class="dn-page-title">Donation reports</h1>
  <p class="dn-page-lead">Snapshot of your donor profile and proof-based giving history.</p>
</div>

<div class="dashboard-widgets dn-dr-snapshot-widgets" style="margin-bottom:1.15rem;">
  <div class="stat-card dashboard-card"><small>Profile — donations recorded</small><h3><?= $profileDonations ?></h3></div>
  <div class="stat-card dashboard-card"><small>Profile — lifetime total</small><h3>PKR <?= number_format($profileLifetime, 2) ?></h3></div>
  <div class="stat-card dashboard-card"><small>Confirmed (verified)</small><h3><?= $confirmedCount ?></h3></div>
  <div class="stat-card dashboard-card"><small>Confirmed amount</small><h3>PKR <?= number_format($confirmedAmount, 2) ?></h3></div>
  <div class="stat-card dashboard-card"><small>Pending review</small><h3><?= $pendingCount ?></h3></div>
  <div class="stat-card dashboard-card"><small>Rejected</small><h3><?= $rejectedCount ?></h3></div>
</div>

<div class="glass-card dn-dr-profile-card" style="margin-bottom:1.25rem;">
  <h2 class="dn-dr-profile-card__title">Profile snapshot</h2>
  <div class="dn-dr-profile-grid">
    <div class="dn-dr-profile-item">
      <span class="dn-dr-profile-item__k">Name</span>
      <span class="dn-dr-profile-item__v"><?= sanitize((string) ($donor['full_name'] ?? '')) ?></span>
    </div>
    <div class="dn-dr-profile-item">
      <span class="dn-dr-profile-item__k">Email</span>
      <span class="dn-dr-profile-item__v"><a href="mailto:<?= sanitize((string) ($donor['email'] ?? '')) ?>"><?= sanitize((string) ($donor['email'] ?? '')) ?></a></span>
    </div>
    <div class="dn-dr-profile-item">
      <span class="dn-dr-profile-item__k">Phone</span>
      <span class="dn-dr-profile-item__v"><?= sanitize((string) ($donor['phone'] ?? '—')) ?></span>
    </div>
    <div class="dn-dr-profile-item">
      <span class="dn-dr-profile-item__k">Donor record</span>
      <span class="dn-dr-profile-item__v">ID #<?= (int) ($donor['id'] ?? 0) ?></span>
    </div>
  </div>
  <p class="help-text" style="margin:1rem 0 0;">Totals in the tiles above combine your donation rows. <strong>Confirmed</strong> amounts are NGO-verified; <strong>pending</strong> proofs are still under review.</p>
</div>

<div class="data-panel table-wrapper">
  <h3>Donation history</h3>
  <table>
    <thead>
      <tr>
        <th>Campaign</th>
        <th>NGO</th>
        <th>Amount</th>
        <th>TID</th>
        <th>Proof</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($history as $h): ?>
      <tr>
        <td><?= htmlspecialchars((string) $h['campaign_title'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string) $h['ngo_name'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= number_format((float) $h['amount'], 2) ?></td>
        <td><?= htmlspecialchars((string) $h['transaction_reference'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?php if (!empty($h['proof_image_url'])): ?><a class="outline-button" href="<?= htmlspecialchars((string) $h['proof_image_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">View</a><?php else: ?>—<?php endif; ?></td>
        <td><?= dash_status_badge((string) $h['status']) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$history): ?>
      <tr><td colspan="6" class="empty-state">No donations yet.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?php require_once dirname(__DIR__) . '/includes/dashboard_layout_end.php'; ?>
