<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/auth_check.php';
require_once dirname(__DIR__) . '/includes/role_check.php';
require_role(['donor']);
$pdo = db();
$status = trim((string)($_GET['status'] ?? ''));
$search = trim((string)($_GET['search'] ?? ''));
$from = trim((string)($_GET['from'] ?? ''));
$to = trim((string)($_GET['to'] ?? ''));
$stmt=$pdo->prepare('SELECT id FROM donor_profiles WHERE user_id=:u LIMIT 1');$stmt->execute(['u'=>(int)$authUser['id']]);$donor=$stmt->fetch();if(!$donor) exit('Donor profile not found.');
$sql="SELECT d.id,d.amount,d.transaction_reference,d.status,d.created_at,c.title campaign_title,n.ngo_name FROM donations d INNER JOIN campaigns c ON c.id=d.campaign_id INNER JOIN ngo_profiles n ON n.id=d.ngo_id WHERE d.donor_id=:d";$p=['d'=>(int)$donor['id']];
if($status!==''&&in_array($status,['pending','confirmed','rejected','flagged'],true)){$sql.=' AND d.status=:s';$p['s']=$status;} if($search!==''){$sql.=' AND c.title LIKE :q';$p['q']='%'.$search.'%';} if($from!==''){$sql.=' AND DATE(d.created_at)>=:f';$p['f']=$from;} if($to!==''){$sql.=' AND DATE(d.created_at)<=:t';$p['t']=$to;}
$sql.=' ORDER BY d.created_at DESC';$stmt=$pdo->prepare($sql);$stmt->execute($p);$donations=$stmt->fetchAll();
$totals=$pdo->prepare("SELECT SUM(CASE WHEN status='confirmed' THEN 1 ELSE 0 END) confirmed_count,SUM(CASE WHEN status='confirmed' THEN amount ELSE 0 END) confirmed_amount,SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) pending_count,SUM(CASE WHEN status='rejected' THEN 1 ELSE 0 END) rejected_count FROM donations WHERE donor_id=:d");$totals->execute(['d'=>(int)$donor['id']]);$summary=$totals->fetch();
$pageTitle='My Donations';
require_once dirname(__DIR__) . '/includes/dashboard_layout_start.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>
<h1 class="section-title">My Donations</h1>
<div class="dashboard-widgets" style="margin-bottom:1rem;"><div class="stat-card dashboard-card"><small>Total confirmed count</small><h3><?= (int)($summary['confirmed_count'] ?? 0) ?></h3></div><div class="stat-card dashboard-card"><small>Total confirmed amount</small><h3>PKR <?= number_format((float)($summary['confirmed_amount'] ?? 0),2) ?></h3></div><div class="stat-card dashboard-card"><small>Pending</small><h3><?= (int)($summary['pending_count'] ?? 0) ?></h3></div><div class="stat-card dashboard-card"><small>Rejected</small><h3><?= (int)($summary['rejected_count'] ?? 0) ?></h3></div></div>
<div class="glass-card" style="padding:1rem;margin-bottom:1rem;"><form class="form-grid"><select name="status"><option value="">All Status</option><option value="pending">pending</option><option value="confirmed">confirmed</option><option value="rejected">rejected</option><option value="flagged">flagged</option></select><input name="search" value="<?= sanitize($search) ?>" placeholder="Campaign search"><input type="date" name="from" value="<?= sanitize($from) ?>"><input type="date" name="to" value="<?= sanitize($to) ?>"><button class="gradient-button" type="submit">Filter</button></form></div>
<div class="table-wrapper"><table><thead><tr><th>Campaign</th><th>NGO</th><th>Amount</th><th>TID</th><th>Status</th><th>Date</th><th>Action</th></tr></thead><tbody><?php if(!$donations): ?><tr><td colspan="7">No donations found.</td></tr><?php endif; ?><?php foreach($donations as $d): ?><tr><td><?= sanitize($d['campaign_title']) ?></td><td><?= sanitize($d['ngo_name']) ?></td><td><?= number_format((float)$d['amount'],2) ?></td><td><?= sanitize((string)$d['transaction_reference']) ?></td><td><span class="status-badge"><?= sanitize($d['status']) ?></span></td><td><?= sanitize((string)$d['created_at']) ?></td><td><a class="outline-button" href="<?= APP_URL ?>/donor/donation_detail.php?id=<?= (int)$d['id'] ?>">View</a></td></tr><?php endforeach; ?></tbody></table></div>
<?php require_once dirname(__DIR__) . '/includes/dashboard_layout_end.php'; ?>
