<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/auth_check.php';
require_once dirname(__DIR__) . '/includes/role_check.php';
require_once dirname(__DIR__) . '/includes/payment_method_helpers.php';
require_role(['ngo']);
$pdo = db();
$stmt = $pdo->prepare('SELECT id, ngo_name, verification_status, total_confirmed_donations_count, total_received_amount FROM ngo_profiles WHERE user_id = :u LIMIT 1');
$stmt->execute(['u' => (int) $authUser['id']]);
$ngo = $stmt->fetch();
if (!$ngo) {
    exit('NGO not found');
}
$ngoId = (int) $ngo['id'];

$stmt = $pdo->prepare('SELECT * FROM ngo_payment_methods WHERE ngo_id = :n ORDER BY created_at DESC');
$stmt->execute(['n' => $ngoId]);
$methods = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT id, title, status, collected_amount, target_amount FROM campaigns WHERE ngo_id = :n ORDER BY created_at DESC');
$stmt->execute(['n' => $ngoId]);
$campaigns = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) pending_count, SUM(CASE WHEN status='rejected' THEN 1 ELSE 0 END) rejected_count FROM donations WHERE ngo_id = :n");
$stmt->execute(['n' => $ngoId]);
$sum = $stmt->fetch();

$stmt = $pdo->prepare("SELECT c.id, c.title, SUM(CASE WHEN d.status='confirmed' THEN d.amount ELSE 0 END) AS confirmed_amount, COUNT(d.id) AS total_donations FROM campaigns c LEFT JOIN donations d ON d.campaign_id = c.id WHERE c.ngo_id = :n GROUP BY c.id, c.title ORDER BY c.id DESC");
$stmt->execute(['n' => $ngoId]);
$campSum = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT vc.status, c.title, u.full_name FROM volunteer_campaigns vc INNER JOIN campaigns c ON c.id = vc.campaign_id INNER JOIN volunteer_profiles vp ON vp.id = vc.volunteer_id INNER JOIN users u ON u.id = vp.user_id WHERE vc.ngo_id = :n ORDER BY vc.created_at DESC');
$stmt->execute(['n' => $ngoId]);
$volReq = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT id, report_type, subject, status, created_at FROM reports WHERE reported_user_id = :uid OR reported_campaign_id IN (SELECT id FROM campaigns WHERE ngo_id = :n) ORDER BY created_at DESC LIMIT 50');
$stmt->execute(['uid' => (int) $authUser['id'], 'n' => $ngoId]);
$reports = $stmt->fetchAll();

$pageTitle = 'Donation reports';
$pageDescription = 'Operational snapshot: payouts, campaigns, volunteers, and compliance signals.';
require_once dirname(__DIR__) . '/includes/dashboard_layout_start.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';

$pendingDon = (int) ($sum['pending_count'] ?? 0);
$rejectedDon = (int) ($sum['rejected_count'] ?? 0);
$confirmedCount = (int) ($ngo['total_confirmed_donations_count'] ?? 0);
$received = (float) ($ngo['total_received_amount'] ?? 0);
?>
<div class="dn-page-head">
  <h1 class="dn-page-title">Donation reports</h1>
  <p class="dn-page-lead"><?= sanitize((string) $ngo['ngo_name']) ?> · Verification: <?= dash_status_badge((string) ($ngo['verification_status'] ?? '')) ?></p>
</div>

<div class="dashboard-widgets" style="margin-bottom:1.25rem;">
  <div class="stat-card dashboard-card"><small>Total received (confirmed)</small><h3>PKR <?= number_format($received, 2) ?></h3></div>
  <div class="stat-card dashboard-card"><small>Confirmed donations (profile)</small><h3><?= $confirmedCount ?></h3></div>
  <div class="stat-card dashboard-card"><small>Pending proofs</small><h3><?= $pendingDon ?></h3></div>
  <div class="stat-card dashboard-card"><small>Rejected proofs</small><h3><?= $rejectedDon ?></h3></div>
</div>

<div class="data-panel" style="margin-bottom:1.25rem;">
  <h3>Payment methods</h3>
  <?php if (!$methods): ?>
    <p class="empty-state" style="padding:1rem 0;">No payment methods on file.</p>
  <?php else: ?>
    <div class="dn-dr-card-grid">
      <?php foreach ($methods as $m):
          $tk = payment_method_type_key((string) ($m['method_type'] ?? ''));
          $theme = 'dn-pm-theme--' . $tk;
          ?>
        <article class="glass-card dn-dr-pm-card <?= sanitize($theme) ?>">
          <div class="dn-dr-pm-card__accent" aria-hidden="true"></div>
          <div class="dn-dr-pm-card__row">
            <span class="dn-pm-mono" aria-hidden="true"><?= sanitize(payment_method_type_short((string) ($m['method_type'] ?? ''))) ?></span>
            <div>
              <strong class="dn-dr-pm-card__title"><?= sanitize((string) ($m['method_title'] ?? '')) ?></strong>
              <div><span class="dn-pm-card__type-pill" style="font-size:0.68rem;"><?= sanitize(payment_method_type_label((string) ($m['method_type'] ?? ''))) ?></span></div>
            </div>
          </div>
          <dl class="dn-dr-dl">
            <div><dt>Account</dt><dd><?= sanitize((string) ($m['account_name'] ?? '')) ?></dd></div>
            <div><dt>Number</dt><dd class="dn-pm-kv__v--mono"><?= sanitize((string) ($m['account_number'] ?? '')) ?></dd></div>
            <?php if (trim((string) ($m['bank_name'] ?? '')) !== ''): ?>
              <div><dt>Bank</dt><dd><?= sanitize((string) $m['bank_name']) ?></dd></div>
            <?php endif; ?>
            <div><dt>Status</dt><dd><?= dash_status_badge((string) ($m['status'] ?? '')) ?></dd></div>
          </dl>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<div class="data-panel table-wrapper" style="margin-bottom:1.25rem;">
  <h3>Campaigns</h3>
  <table>
    <thead>
      <tr><th>Campaign</th><th>Status</th><th>Progress</th><th>Collected</th><th>Target</th></tr>
    </thead>
    <tbody>
    <?php foreach ($campaigns as $c):
        $target = (float) ($c['target_amount'] ?? 0);
        $collected = (float) ($c['collected_amount'] ?? 0);
        $pct = $target > 0 ? min(100, ($collected / $target) * 100) : 0;
        ?>
      <tr>
        <td><?= sanitize((string) ($c['title'] ?? '')) ?></td>
        <td><?= dash_status_badge((string) ($c['status'] ?? '')) ?></td>
        <td style="min-width:120px;">
          <div class="progress-wrap"><div class="progress-bar" style="width:<?= number_format($pct, 1) ?>%;"></div></div>
          <small><?= number_format($pct, 1) ?>%</small>
        </td>
        <td>PKR <?= number_format($collected, 2) ?></td>
        <td>PKR <?= number_format($target, 2) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$campaigns): ?>
      <tr><td colspan="5" class="empty-state">No campaigns yet.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<div class="data-panel table-wrapper" style="margin-bottom:1.25rem;">
  <h3>Campaign-wise donations</h3>
  <table>
    <thead>
      <tr><th>Campaign</th><th>Confirmed amount</th><th>Donation rows</th></tr>
    </thead>
    <tbody>
    <?php foreach ($campSum as $row): ?>
      <tr>
        <td><?= sanitize((string) ($row['title'] ?? '')) ?></td>
        <td>PKR <?= number_format((float) ($row['confirmed_amount'] ?? 0), 2) ?></td>
        <td><?= (int) ($row['total_donations'] ?? 0) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$campSum): ?>
      <tr><td colspan="3" class="empty-state">No campaign data.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<div class="data-panel table-wrapper" style="margin-bottom:1.25rem;">
  <h3>Volunteer requests</h3>
  <table>
    <thead>
      <tr><th>Volunteer</th><th>Campaign</th><th>Status</th></tr>
    </thead>
    <tbody>
    <?php foreach ($volReq as $v): ?>
      <tr>
        <td><?= sanitize((string) ($v['full_name'] ?? '')) ?></td>
        <td><?= sanitize((string) ($v['title'] ?? '')) ?></td>
        <td><?= dash_status_badge((string) ($v['status'] ?? '')) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$volReq): ?>
      <tr><td colspan="3" class="empty-state">No volunteer requests.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<div class="data-panel table-wrapper">
  <h3>Related reports</h3>
  <table>
    <thead>
      <tr><th>Type</th><th>Subject</th><th>Status</th><th>Date</th></tr>
    </thead>
    <tbody>
    <?php foreach ($reports as $rep): ?>
      <tr>
        <td><?= sanitize((string) ($rep['report_type'] ?? '')) ?></td>
        <td><?= sanitize((string) ($rep['subject'] ?? '')) ?></td>
        <td><?= dash_status_badge((string) ($rep['status'] ?? '')) ?></td>
        <td><?= sanitize((string) ($rep['created_at'] ?? '')) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$reports): ?>
      <tr><td colspan="4" class="empty-state">No related reports.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?php require_once dirname(__DIR__) . '/includes/dashboard_layout_end.php'; ?>
