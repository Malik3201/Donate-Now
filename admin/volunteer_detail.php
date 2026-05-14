<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/auth_check.php';
require_once dirname(__DIR__) . '/includes/role_check.php';
require_role(['admin']);
$pdo=db();$id=intval($_GET['id']??0);
$stmt=$pdo->prepare('SELECT vp.*,u.full_name,u.email,u.phone,u.profile_photo_url FROM volunteer_profiles vp INNER JOIN users u ON u.id=vp.user_id WHERE vp.id=:id LIMIT 1');$stmt->execute(['id'=>$id]);$v=$stmt->fetch();if(!$v) exit('Volunteer not found');
$stmt=$pdo->prepare('SELECT vc.*,c.title,np.ngo_name FROM volunteer_campaigns vc INNER JOIN campaigns c ON c.id=vc.campaign_id INNER JOIN ngo_profiles np ON np.id=vc.ngo_id WHERE vc.volunteer_id=:v ORDER BY vc.created_at DESC');$stmt->execute(['v'=>$id]);$hist=$stmt->fetchAll();
$sum=['accepted'=>0,'rejected'=>0,'cancelled'=>0,'pending'=>0];foreach($hist as $h){$k=(string)$h['status'];if(isset($sum[$k]))$sum[$k]++;}
$stmt=$pdo->prepare('SELECT id,subject,status FROM reports WHERE reported_user_id=:u OR reporter_user_id=:u ORDER BY created_at DESC');$stmt->execute(['u'=>(int)$v['user_id']]);$reports=$stmt->fetchAll();
$stmt=$pdo->prepare('SELECT * FROM admin_notes WHERE target_user_id=:u ORDER BY created_at DESC LIMIT 20');$stmt->execute(['u'=>(int)$v['user_id']]);$notes=$stmt->fetchAll();
$pageTitle='Volunteer Detail';require_once dirname(__DIR__) . '/includes/dashboard_layout_start.php';require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>
<h1 class="section-title">Volunteer Detail</h1>
<div class="glass-card" style="padding:1rem;margin-bottom:1rem;"><img src="<?= sanitize(image_or_placeholder((string)($v['profile_photo_url']??''),'profile')) ?>" alt="Volunteer photo" style="width:62px;height:62px;border-radius:50%;object-fit:cover;"> <strong><?= sanitize($v['full_name']) ?></strong><p><?= sanitize($v['email']) ?> | <?= sanitize((string)$v['phone']) ?></p><p>Skills: <?= sanitize((string)$v['skills']) ?> | Availability: <?= sanitize((string)$v['availability']) ?></p></div>
<div class="dashboard-widgets" style="margin-bottom:1rem;"><div class="stat-card dashboard-card"><small>Accepted</small><h3><?= $sum['accepted'] ?></h3></div><div class="stat-card dashboard-card"><small>Rejected</small><h3><?= $sum['rejected'] ?></h3></div><div class="stat-card dashboard-card"><small>Cancelled</small><h3><?= $sum['cancelled'] ?></h3></div><div class="stat-card dashboard-card"><small>Pending</small><h3><?= $sum['pending'] ?></h3></div></div>
<div class="table-wrapper"><h3>Joined Campaign History</h3><table><thead><tr><th>Campaign</th><th>NGO</th><th>Status</th><th>Date</th></tr></thead><tbody><?php foreach($hist as $h): ?><tr><td><?= sanitize($h['title']) ?></td><td><?= sanitize($h['ngo_name']) ?></td><td><span class="status-badge"><?= sanitize($h['status']) ?></span></td><td><?= sanitize((string)$h['created_at']) ?></td></tr><?php endforeach; ?></tbody></table></div>
<div class="grid" style="grid-template-columns:1fr 1fr;margin-top:1rem;"><div class="glass-card" style="padding:1rem;"><h3>Related Reports</h3><?php foreach($reports as $r): ?><p>#<?= (int)$r['id'] ?> <?= sanitize($r['subject']) ?></p><?php endforeach; ?></div><div class="glass-card" style="padding:1rem;"><h3>Admin Notes</h3><?php foreach($notes as $n): ?><p><?= sanitize((string)$n['created_at']) ?> - <?= sanitize($n['note']) ?></p><?php endforeach; ?></div></div>
<?php require_once dirname(__DIR__) . '/includes/dashboard_layout_end.php'; ?>
