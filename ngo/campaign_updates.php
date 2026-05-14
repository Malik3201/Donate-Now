<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/auth_check.php';
require_once dirname(__DIR__) . '/includes/role_check.php';
require_role(['ngo']);
$pdo = db();$campaignId=intval($_GET['campaign_id']??0);
$stmt=$pdo->prepare('SELECT np.id AS ngo_profile_id FROM ngo_profiles np WHERE np.user_id=:u LIMIT 1');$stmt->execute(['u'=>(int)$authUser['id']]);$ngo=$stmt->fetch();if(!$ngo) exit('NGO profile not found.');
$stmt=$pdo->prepare('SELECT id,title,status FROM campaigns WHERE id=:id AND ngo_id=:n LIMIT 1');$stmt->execute(['id'=>$campaignId,'n'=>(int)$ngo['ngo_profile_id']]);$campaign=$stmt->fetch();if(!$campaign) exit('Campaign not found or unauthorized.');if(!in_array($campaign['status'],['approved','active'],true)) exit('Only approved or active campaigns can receive updates.');
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!verify_csrf_token($_POST['csrf_token']??null)) exit('Invalid CSRF token.');
 $title=trim((string)($_POST['update_title']??''));$description=trim((string)($_POST['update_description']??''));
 if($title!==''&&$description!==''){$stmt=$pdo->prepare('INSERT INTO campaign_updates (campaign_id,update_title,update_description) VALUES (:c,:t,:d)');$stmt->execute(['c'=>$campaignId,'t'=>$title,'d'=>$description]);log_activity($pdo,(int)$authUser['id'],'campaign_update_added','campaign_updates',(int)$pdo->lastInsertId(),'Campaign update posted');redirect('ngo/campaign_updates.php?campaign_id='.$campaignId);} }
$stmt=$pdo->prepare('SELECT * FROM campaign_updates WHERE campaign_id=:c ORDER BY created_at DESC');$stmt->execute(['c'=>$campaignId]);$updates=$stmt->fetchAll();
$pageTitle='Campaign Updates';require_once dirname(__DIR__) . '/includes/dashboard_layout_start.php';require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>
<h1 class="section-title">Campaign Updates: <?= sanitize($campaign['title']) ?></h1>
<div class="form-card"><form method="post"><input type="hidden" name="csrf_token" value="<?= sanitize(csrf_token()) ?>"><div class="form-group"><label>Update Title</label><input name="update_title" required></div><div class="form-group"><label>Update Description</label><textarea name="update_description" rows="4" required></textarea></div><button class="gradient-button" type="submit">Add Update</button></form></div>
<div class="glass-card" style="padding:1rem;margin-top:1rem;"><h3>Update Timeline</h3><?php if(!$updates): ?><div class="empty-state">No updates yet.</div><?php endif; ?><?php foreach($updates as $u): ?><div style="padding:.8rem;border-left:3px solid var(--color-accent);margin-bottom:.75rem;background:rgba(255,255,255,.05);"><strong><?= sanitize($u['update_title']) ?></strong><small style="display:block;opacity:.8;"><?= sanitize((string)$u['created_at']) ?></small><p><?= nl2br(sanitize($u['update_description'])) ?></p></div><?php endforeach; ?></div>
<?php require_once dirname(__DIR__) . '/includes/dashboard_layout_end.php'; ?>
