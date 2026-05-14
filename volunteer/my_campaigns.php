<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/auth_check.php';
require_once dirname(__DIR__) . '/includes/role_check.php';
require_role(['volunteer']);
$pdo=db();$stmt=$pdo->prepare('SELECT id FROM volunteer_profiles WHERE user_id=:u LIMIT 1');$stmt->execute(['u'=>(int)$authUser['id']]);$volunteerId=(int)($stmt->fetchColumn()?:0);if(!$volunteerId) exit('Volunteer profile not found.');
$stmt=$pdo->prepare("SELECT vc.*, c.title campaign_title, np.ngo_name FROM volunteer_campaigns vc INNER JOIN campaigns c ON c.id=vc.campaign_id INNER JOIN ngo_profiles np ON np.id=vc.ngo_id WHERE vc.volunteer_id=:v ORDER BY vc.created_at DESC");$stmt->execute(['v'=>$volunteerId]);$rows=$stmt->fetchAll();
$sum=['total'=>0,'pending'=>0,'accepted'=>0,'rejected'=>0,'cancelled'=>0];foreach($rows as $r){$sum['total']++;$k=(string)$r['status'];if(isset($sum[$k]))$sum[$k]++;}
$pageTitle='My Campaign Requests';require_once dirname(__DIR__) . '/includes/dashboard_layout_start.php';require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>
<h1 class="section-title">My Campaign Requests</h1>
<div class="dashboard-widgets" style="margin-bottom:1rem;"><div class="stat-card dashboard-card"><small>Total</small><h3><?= $sum['total'] ?></h3></div><div class="stat-card dashboard-card"><small>Pending</small><h3><?= $sum['pending'] ?></h3></div><div class="stat-card dashboard-card"><small>Accepted</small><h3><?= $sum['accepted'] ?></h3></div><div class="stat-card dashboard-card"><small>Rejected</small><h3><?= $sum['rejected'] ?></h3></div><div class="stat-card dashboard-card"><small>Cancelled</small><h3><?= $sum['cancelled'] ?></h3></div></div>
<div class="table-wrapper"><table><thead><tr><th>Campaign</th><th>NGO</th><th>Message</th><th>Status</th><th>NGO Note</th><th>Date</th><th>Action</th></tr></thead><tbody><?php if(!$rows): ?><tr><td colspan="7">No requests found.</td></tr><?php endif; ?><?php foreach($rows as $r): ?><tr><td><?= sanitize($r['campaign_title']) ?></td><td><?= sanitize($r['ngo_name']) ?></td><td><?= sanitize((string)$r['message']) ?></td><td><span class="status-badge"><?= sanitize($r['status']) ?></span></td><td><?= sanitize((string)$r['ngo_note']) ?></td><td><?= sanitize((string)$r['created_at']) ?></td><td><?php if($r['status']==='pending'): ?><form method="post" action="<?= APP_URL ?>/volunteer/cancel_request.php"><input type="hidden" name="csrf_token" value="<?= sanitize(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="outline-button" onclick="return confirm('Cancel this request?')" type="submit">Cancel</button></form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div>
<?php require_once dirname(__DIR__) . '/includes/dashboard_layout_end.php'; ?>
