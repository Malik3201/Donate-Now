<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/auth_check.php';

$currentRole = (string) ($_SESSION['role'] ?? '');
if (!in_array($currentRole, ['admin', 'ngo'], true)) {
    http_response_code(403);
    exit('Unauthorized access for this role.');
}

$pdo = db();
$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT c.*, np.ngo_name, u.email AS ngo_email FROM campaigns c INNER JOIN ngo_profiles np ON np.id = c.ngo_id INNER JOIN users u ON u.id = np.user_id WHERE c.id = :id LIMIT 1');
$stmt->execute(['id' => $id]);
$c = $stmt->fetch();
if (!$c) {
    exit('Campaign not found');
}

if ($currentRole === 'ngo') {
    $stmt = $pdo->prepare('SELECT id FROM ngo_profiles WHERE user_id = :uid LIMIT 1');
    $stmt->execute(['uid' => (int) $authUser['id']]);
    $ngoProfile = $stmt->fetch();
    if (!$ngoProfile) {
        exit('NGO profile not found');
    }
    if ((int) $c['ngo_id'] !== (int) $ngoProfile['id']) {
        http_response_code(403);
        exit('You do not have access to this campaign.');
    }
}
$stmt = $pdo->prepare('SELECT * FROM donations WHERE campaign_id = :id ORDER BY created_at DESC');
$stmt->execute(['id' => $id]);
$donations = $stmt->fetchAll();
$stmt = $pdo->prepare('SELECT vc.*, u.full_name FROM volunteer_campaigns vc INNER JOIN volunteer_profiles vp ON vp.id = vc.volunteer_id INNER JOIN users u ON u.id = vp.user_id WHERE vc.campaign_id = :id ORDER BY vc.created_at DESC');
$stmt->execute(['id' => $id]);
$vol = $stmt->fetchAll();
$stmt = $pdo->prepare('SELECT * FROM reports WHERE reported_campaign_id = :id ORDER BY created_at DESC');
$stmt->execute(['id' => $id]);
$reports = $stmt->fetchAll();
$stmt = $pdo->prepare('SELECT * FROM campaign_updates WHERE campaign_id = :id ORDER BY created_at DESC');
$stmt->execute(['id' => $id]);
$updates = $stmt->fetchAll();

$pageTitle = 'Campaign detail';
$pageDescription = 'Full campaign record: donations, volunteers, reports, and updates.';
require_once dirname(__DIR__) . '/includes/dashboard_layout_start.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>
<div class="dn-page-head">
  <h1 class="dn-page-title"><?= htmlspecialchars((string) $c['title'], ENT_QUOTES, 'UTF-8') ?></h1>
  <p class="dn-page-lead"><?= htmlspecialchars((string) $c['ngo_name'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars((string) $c['ngo_email'], ENT_QUOTES, 'UTF-8') ?></p>
</div>
<div class="glass-card" style="padding:1.15rem;margin-bottom:1rem;">
  <p><strong>Status:</strong> <?= dash_status_badge((string) $c['status']) ?></p>
  <p><strong>Target:</strong> PKR <?= number_format((float) $c['target_amount'], 2) ?> &nbsp;|&nbsp; <strong>Collected:</strong> PKR <?= number_format((float) $c['collected_amount'], 2) ?></p>
  <p><?= nl2br(htmlspecialchars((string) $c['description'], ENT_QUOTES, 'UTF-8')) ?></p>
</div>
<div class="data-panel table-wrapper">
  <h3>Donations</h3>
  <table>
    <thead><tr><th>Amount</th><th>Status</th><th>TID</th><th>Created</th></tr></thead>
    <tbody>
    <?php foreach ($donations as $d): ?>
      <tr>
        <td><?= number_format((float) $d['amount'], 2) ?></td>
        <td><?= dash_status_badge((string) $d['status']) ?></td>
        <td><?= htmlspecialchars((string) ($d['transaction_reference'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string) $d['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$donations): ?><tr><td colspan="4" class="empty-state">No donations.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<div class="data-panel table-wrapper">
  <h3>Volunteer requests</h3>
  <table>
    <thead><tr><th>Volunteer</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($vol as $v): ?>
      <tr>
        <td><?= htmlspecialchars((string) $v['full_name'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= dash_status_badge((string) $v['status']) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$vol): ?><tr><td colspan="2" class="empty-state">No volunteer rows.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<div class="data-panel table-wrapper">
  <h3>Reports</h3>
  <table>
    <thead><tr><th>Subject</th><th>Status</th><th>Type</th></tr></thead>
    <tbody>
    <?php foreach ($reports as $r): ?>
      <tr>
        <td><?= htmlspecialchars((string) $r['subject'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= dash_status_badge((string) $r['status']) ?></td>
        <td><?= htmlspecialchars((string) $r['report_type'], ENT_QUOTES, 'UTF-8') ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$reports): ?><tr><td colspan="3" class="empty-state">No reports.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<div class="data-panel">
  <h3>Campaign updates</h3>
  <?php foreach ($updates as $u): ?>
    <article style="padding:0.85rem 1rem;border-bottom:1px solid rgba(43,33,27,.08);">
      <strong><?= htmlspecialchars((string) $u['update_title'], ENT_QUOTES, 'UTF-8') ?></strong>
      <span style="color:var(--text-muted);font-size:0.85rem;"><?= htmlspecialchars((string) $u['created_at'], ENT_QUOTES, 'UTF-8') ?></span>
      <p style="margin:0.35rem 0 0;"><?= nl2br(htmlspecialchars((string) $u['update_description'], ENT_QUOTES, 'UTF-8')) ?></p>
    </article>
  <?php endforeach; ?>
  <?php if (!$updates): ?><p class="empty-state" style="margin:0;padding:1rem;">No updates.</p><?php endif; ?>
</div>
<?php if ($currentRole === 'admin'): ?>
<details class="glass-card" style="padding:0.75rem 1rem;margin-top:1rem;">
  <summary style="cursor:pointer;font-weight:700;">Raw JSON (debug)</summary>
  <pre class="dn-code-block" style="border-top:none;"><?= htmlspecialchars((string) json_encode(['campaign' => $c], JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8') ?></pre>
</details>
<?php endif; ?>
<?php require_once dirname(__DIR__) . '/includes/dashboard_layout_end.php'; ?>
