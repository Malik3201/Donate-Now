<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/auth_check.php';
require_once dirname(__DIR__) . '/includes/role_check.php';
require_once dirname(__DIR__) . '/includes/dashboard_functions.php';
require_role(['ngo']);
$pdo = db();
$stats = get_ngo_dashboard_stats($pdo, (int)$authUser['id']);
$stmt = $pdo->prepare('SELECT id FROM ngo_profiles WHERE user_id=:u LIMIT 1');$stmt->execute(['u'=>(int)$authUser['id']]);$ngoId=(int)($stmt->fetchColumn()?:0);
$recentProofs=[];$campaignStats=[];
if($ngoId){
  $stmt=$pdo->prepare("SELECT d.id,d.amount,d.transaction_reference,d.proof_image_url,d.status,d.created_at,u.full_name donor_name,c.title campaign_title FROM donations d INNER JOIN donor_profiles dp ON dp.id=d.donor_id INNER JOIN users u ON u.id=dp.user_id INNER JOIN campaigns c ON c.id=d.campaign_id WHERE d.ngo_id=:n ORDER BY d.created_at DESC LIMIT 8");$stmt->execute(['n'=>$ngoId]);$recentProofs=$stmt->fetchAll();
  $stmt=$pdo->prepare("SELECT title,collected_amount,target_amount,status FROM campaigns WHERE ngo_id=:n ORDER BY created_at DESC LIMIT 6");$stmt->execute(['n'=>$ngoId]);$campaignStats=$stmt->fetchAll();
}

$pageTitle='NGO Dashboard';
require_once dirname(__DIR__) . '/includes/dashboard_layout_start.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>
<div class="dn-welcome-panel">
  <h2>NGO workspace</h2>
  <p>Keep donors informed, reconcile proofs quickly, and coordinate volunteers from one calm surface.</p>
</div>
<?php if (($stats['verification_status'] ?? 'pending') !== 'verified'): ?>
<div class="dn-alert-verification">
  <strong>Verification pending</strong>
  <p>Your NGO is pending verification. You can update your profile, but you cannot create campaigns or payment methods until an administrator verifies your organization.</p>
  <a class="gradient-button" href="<?= APP_URL ?>/profile/view_profile.php">Go to profile</a>
</div>
<?php endif; ?>
<div class="dashboard-widgets" style="margin-bottom:1rem;">
  <div class="stat-card dashboard-card"><small>Verification Status</small><h3><?= htmlspecialchars((string)$stats['verification_status'], ENT_QUOTES, 'UTF-8') ?></h3></div>
  <div class="stat-card dashboard-card"><small>Total payment methods</small><h3><?= (int)$stats['total_payment_methods'] ?></h3></div>
  <div class="stat-card dashboard-card"><small>Total campaigns</small><h3><?= (int)$stats['total_campaigns'] ?></h3></div>
  <div class="stat-card dashboard-card"><small>Active campaigns</small><h3><?= (int)$stats['active_campaigns'] ?></h3></div>
  <div class="stat-card dashboard-card"><small>Pending donation proofs</small><h3><?= (int)$stats['pending_donation_proofs'] ?></h3></div>
  <div class="stat-card dashboard-card"><small>Confirmed donations</small><h3><?= (int)$stats['confirmed_donations'] ?></h3></div>
  <div class="stat-card dashboard-card"><small>Confirmed received amount</small><h3>PKR <?= number_format((float)$stats['confirmed_received_amount'],2) ?></h3></div>
  <div class="stat-card dashboard-card"><small>Volunteer pending requests</small><h3><?= (int)$stats['volunteer_pending_requests'] ?></h3></div>
</div>
<div class="grid" style="grid-template-columns:1.3fr .7fr;">
  <div class="data-panel table-wrapper"><h3>Recent donation proofs</h3><table><thead><tr><th>Donor</th><th>Campaign</th><th>Amount</th><th>TID</th><th>Status</th></tr></thead><tbody><?php if(!$recentProofs): ?><tr><td colspan="5" class="empty-state">No donation proofs yet.</td></tr><?php endif; ?><?php foreach($recentProofs as $d): ?><tr><td><?= htmlspecialchars((string)$d['donor_name'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars((string)$d['campaign_title'], ENT_QUOTES, 'UTF-8') ?></td><td><?= number_format((float)$d['amount'],2) ?></td><td><?= htmlspecialchars((string)$d['transaction_reference'], ENT_QUOTES, 'UTF-8') ?></td><td><?= dash_status_badge((string)$d['status']) ?></td></tr><?php endforeach; ?></tbody></table></div>
  <div class="glass-card" style="padding:1rem;"><h3>Recent campaign stats</h3><?php if(!$campaignStats): ?><p>No campaigns found.</p><?php endif; ?><?php foreach($campaignStats as $c): $p=((float)$c['target_amount']>0)?min(100,((float)$c['collected_amount']/(float)$c['target_amount']*100)):0; ?><div style="margin-bottom:.75rem;"><strong><?= htmlspecialchars((string)$c['title'], ENT_QUOTES, 'UTF-8') ?></strong><div class="progress-wrap"><div class="progress-bar" style="width:<?= number_format($p,2) ?>%"></div></div><small><?= number_format($p,2) ?>% | <?= htmlspecialchars((string)$c['status'], ENT_QUOTES, 'UTF-8') ?></small></div><?php endforeach; ?></div>
</div>
<?php require_once dirname(__DIR__) . '/includes/dashboard_layout_end.php'; ?>
