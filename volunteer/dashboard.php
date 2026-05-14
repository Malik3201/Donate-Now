<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/auth_check.php';
require_once dirname(__DIR__) . '/includes/role_check.php';
require_once dirname(__DIR__) . '/includes/dashboard_functions.php';
require_role(['volunteer']);
$pdo = db();
$stats = get_volunteer_dashboard_stats($pdo, (int)$authUser['id']);
$suggested = $pdo->query("SELECT id,title,image_url,target_amount,collected_amount FROM campaigns WHERE status IN ('approved','active') ORDER BY created_at DESC LIMIT 4")->fetchAll();
$stmt=$pdo->prepare('SELECT vp.id FROM volunteer_profiles vp WHERE vp.user_id=:u LIMIT 1');$stmt->execute(['u'=>(int)$authUser['id']]);$volunteerId=(int)($stmt->fetchColumn()?:0);
$history=[]; if($volunteerId){$stmt=$pdo->prepare("SELECT vc.status,vc.created_at,c.title,np.ngo_name FROM volunteer_campaigns vc INNER JOIN campaigns c ON c.id=vc.campaign_id INNER JOIN ngo_profiles np ON np.id=vc.ngo_id WHERE vc.volunteer_id=:v ORDER BY vc.created_at DESC LIMIT 8");$stmt->execute(['v'=>$volunteerId]);$history=$stmt->fetchAll();}
$pageTitle='Volunteer Dashboard';
require_once dirname(__DIR__) . '/includes/dashboard_layout_start.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>
<div class="dn-welcome-panel">
  <h2>Volunteer home</h2>
  <p>Explore trusted campaigns, send structured requests, and keep your commitments organized.</p>
</div>
<div class="dashboard-widgets" style="margin-bottom:1rem;">
  <div class="stat-card dashboard-card"><small>Total campaign requests</small><h3><?= (int)$stats['total_campaign_requests'] ?></h3></div>
  <div class="stat-card dashboard-card"><small>Pending</small><h3><?= (int)$stats['pending'] ?></h3></div>
  <div class="stat-card dashboard-card"><small>Accepted</small><h3><?= (int)$stats['accepted'] ?></h3></div>
  <div class="stat-card dashboard-card"><small>Rejected</small><h3><?= (int)$stats['rejected'] ?></h3></div>
  <div class="stat-card dashboard-card"><small>Unread notifications</small><h3><?= (int)$stats['unread_notifications'] ?></h3></div>
</div>
<div class="grid" style="grid-template-columns:1.2fr .8fr;">
  <div class="data-panel table-wrapper"><h3>Recent campaign requests</h3><table><thead><tr><th>Campaign</th><th>NGO</th><th>Status</th><th>Date</th></tr></thead><tbody><?php if(!$history): ?><tr><td colspan="4" class="empty-state">No campaign requests yet.</td></tr><?php endif; ?><?php foreach($history as $h): ?><tr><td><?= htmlspecialchars((string)$h['title'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars((string)$h['ngo_name'], ENT_QUOTES, 'UTF-8') ?></td><td><?= dash_status_badge((string)$h['status']) ?></td><td><?= htmlspecialchars((string)$h['created_at'], ENT_QUOTES, 'UTF-8') ?></td></tr><?php endforeach; ?></tbody></table></div>
  <div class="glass-card" style="padding:1rem;"><h3>Suggested campaigns</h3><?php if(!$suggested): ?><p>No campaigns available.</p><?php endif; ?><?php foreach($suggested as $c): ?><div style="margin-bottom:.75rem;"><a href="<?= APP_URL ?>/volunteer/join_campaign.php?campaign_id=<?= (int)$c['id'] ?>"><strong><?= htmlspecialchars((string)$c['title'], ENT_QUOTES, 'UTF-8') ?></strong></a></div><?php endforeach; ?>
  <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border-light);">
    <h4 style="margin:0 0 0.35rem;">Volunteer tips</h4>
    <p style="margin:0;font-size:0.85rem;color:var(--text-muted);line-height:1.5;">Keep your availability up to date in your profile, and respond quickly when NGOs approve your help.</p>
  </div></div>
</div>
<?php require_once dirname(__DIR__) . '/includes/dashboard_layout_end.php'; ?>
