<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/auth_check.php';
require_once dirname(__DIR__) . '/includes/role_check.php';
require_role(['admin']);
$pdo=db();if($_SERVER['REQUEST_METHOD']==='POST'){if(!verify_csrf_token($_POST['csrf_token']??null)) exit('Invalid CSRF token');$targetUserId=intval($_POST['target_user_id']??0);$campaignId=intval($_POST['campaign_id']??0);$donationId=intval($_POST['donation_id']??0);$reportId=intval($_POST['report_id']??0);$note=trim((string)($_POST['note']??''));if($note!==''){$stmt=$pdo->prepare('INSERT INTO admin_notes (admin_id,target_user_id,campaign_id,donation_id,report_id,note) VALUES (:a,:u,:c,:d,:r,:n)');$stmt->execute(['a'=>(int)$authUser['id'],'u'=>$targetUserId?:null,'c'=>$campaignId?:null,'d'=>$donationId?:null,'r'=>$reportId?:null,'n'=>$note]);}}
$stmt=$pdo->query('SELECT an.*,u.full_name admin_name FROM admin_notes an INNER JOIN users u ON u.id=an.admin_id ORDER BY an.created_at DESC LIMIT 200');$notes=$stmt->fetchAll();
$pageTitle='Admin Notes';require_once dirname(__DIR__) . '/includes/dashboard_layout_start.php';require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>
<h1 class="section-title">Admin Notes</h1>
<form method="post" class="form-card" style="margin-bottom:1rem;"><input type="hidden" name="csrf_token" value="<?= sanitize(csrf_token()) ?>"><div class="form-grid"><input name="target_user_id" type="number" placeholder="Target User ID"><input name="campaign_id" type="number" placeholder="Campaign ID"><input name="donation_id" type="number" placeholder="Donation ID"><input name="report_id" type="number" placeholder="Report ID"></div><div class="form-group"><label>Note</label><textarea name="note" rows="3" required></textarea></div><button class="gradient-button" type="submit">Add Note</button></form>
<div class="glass-card" style="padding:1rem;"><?php if(!$notes): ?><p>No notes added yet.</p><?php endif; ?><?php foreach($notes as $n): ?><div style="border-left:3px solid var(--color-primary);padding:.6rem;margin-bottom:.6rem;background:rgba(255,255,255,.05);"><strong><?= sanitize($n['admin_name']) ?></strong> <small><?= sanitize((string)$n['created_at']) ?></small><p><?= nl2br(sanitize($n['note'])) ?></p></div><?php endforeach; ?></div>
<?php require_once dirname(__DIR__) . '/includes/dashboard_layout_end.php'; ?>
