<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/auth_check.php';
$pdo=db();$stmt=$pdo->prepare('SELECT id,report_type,subject,status,admin_note,created_at FROM reports WHERE reporter_user_id=:u ORDER BY created_at DESC');$stmt->execute(['u'=>(int)$authUser['id']]);$reports=$stmt->fetchAll();
$summary=['total'=>0,'open'=>0,'under_review'=>0,'resolved'=>0,'rejected'=>0];foreach($reports as $r){$summary['total']++;$k=(string)$r['status'];if(isset($summary[$k]))$summary[$k]++;}
$pageTitle='My Reports';require_once dirname(__DIR__) . '/includes/dashboard_layout_start.php';require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>
<h1 class="section-title">My Reports</h1>
<div class="dashboard-widgets" style="margin-bottom:1rem;"><div class="stat-card dashboard-card"><small>Total Reports</small><h3><?= $summary['total'] ?></h3></div><div class="stat-card dashboard-card"><small>Open</small><h3><?= $summary['open'] ?></h3></div><div class="stat-card dashboard-card"><small>Under Review</small><h3><?= $summary['under_review'] ?></h3></div><div class="stat-card dashboard-card"><small>Resolved</small><h3><?= $summary['resolved'] ?></h3></div><div class="stat-card dashboard-card"><small>Rejected</small><h3><?= $summary['rejected'] ?></h3></div></div>
<p><a class="gradient-button" href="<?= APP_URL ?>/reports/create_report.php">Create Report</a></p>
<div class="table-wrapper"><table><thead><tr><th>Type</th><th>Subject</th><th>Status</th><th>Date</th><th>Admin Note</th></tr></thead><tbody><?php if(!$reports): ?><tr><td colspan="5">No reports submitted yet.</td></tr><?php endif; ?><?php foreach($reports as $r): ?><tr><td><?= sanitize($r['report_type']) ?></td><td><?= sanitize($r['subject']) ?></td><td><span class="status-badge"><?= sanitize($r['status']) ?></span></td><td><?= sanitize((string)$r['created_at']) ?></td><td><?= sanitize((string)$r['admin_note']) ?></td></tr><?php endforeach; ?></tbody></table></div>
<?php require_once dirname(__DIR__) . '/includes/dashboard_layout_end.php'; ?>
