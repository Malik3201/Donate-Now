<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/auth_check.php';
require_once dirname(__DIR__) . '/includes/role_check.php';
require_once dirname(__DIR__) . '/includes/upload_helper.php';
require_once dirname(__DIR__) . '/includes/notification_helper.php';
require_once dirname(__DIR__) . '/includes/mail_helper.php';
require_role(['ngo']);
$pdo = db();
$stmt=$pdo->prepare('SELECT id, ngo_name, verification_status FROM ngo_profiles WHERE user_id=:u LIMIT 1');$stmt->execute(['u'=>(int)$authUser['id']]);$ngoProfile=$stmt->fetch();
$errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!verify_csrf_token($_POST['csrf_token']??null)) exit('Invalid CSRF token.');
 $title=trim((string)($_POST['title']??''));$description=trim((string)($_POST['description']??''));$targetAmount=(float)($_POST['target_amount']??0);$startDate=trim((string)($_POST['start_date']??''));$endDate=trim((string)($_POST['end_date']??''));
 if($title===''||$description===''||$targetAmount<=0||$startDate===''||$endDate==='') $errors[]='All required fields must be filled correctly.'; if($endDate<$startDate) $errors[]='End date must not be before start date.';
 $imageUrl=null;$imageFileId=null;if(!empty($_FILES['campaign_image']['name'])){$upload=upload_to_imagekit($_FILES['campaign_image'],'campaigns');if(empty($upload['success'])){$errors[]=(string)($upload['message']??'Image upload failed.');}else{$imageUrl=$upload['url'];$imageFileId=$upload['fileId'];}}
 if(!$errors && $ngoProfile && $ngoProfile['verification_status']==='verified'){
  $stmt=$pdo->prepare('INSERT INTO campaigns (ngo_id, category_id, title, description, target_amount, image_url, imagekit_file_id, start_date, end_date, status) VALUES (:ngo_id,:category_id,:title,:description,:target_amount,:image_url,:imagekit_file_id,:start_date,:end_date,:status)');
  $stmt->execute(['ngo_id'=>(int)$ngoProfile['id'],'category_id'=>null,'title'=>$title,'description'=>$description,'target_amount'=>$targetAmount,'image_url'=>$imageUrl,'imagekit_file_id'=>$imageFileId,'start_date'=>$startDate,'end_date'=>$endDate,'status'=>'pending']);
  $campaignId=(int)$pdo->lastInsertId();$stmt=$pdo->prepare('UPDATE ngo_profiles SET total_campaigns=total_campaigns+1,updated_at=NOW() WHERE id=:n');$stmt->execute(['n'=>(int)$ngoProfile['id']]);
  $admins=$pdo->query("SELECT id,email FROM users WHERE role='admin' AND account_status='active'")->fetchAll(); foreach($admins as $admin){create_notification($pdo,(int)$admin['id'],'New Campaign Submitted','A new campaign is waiting for admin review.','campaign');send_new_campaign_submitted_email((string)$admin['email'],['ngo_name'=>(string)$ngoProfile['ngo_name'],'title'=>$title,'target_amount'=>(string)$targetAmount]);}
  log_activity($pdo,(int)$authUser['id'],'campaign_created','campaigns',$campaignId,'Campaign submitted for approval');redirect('ngo/my_campaigns.php');
 }
}
$pageTitle='Create Campaign';require_once dirname(__DIR__) . '/includes/dashboard_layout_start.php';require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>
<h1 class="section-title">Create Campaign</h1>
<?php if(!$ngoProfile || $ngoProfile['verification_status']!=='verified'): ?><div class="toast warning">Your NGO is pending verification. You can update profile but cannot create campaigns or payment methods until verified.</div><?php endif; ?>
<?php if($errors): ?><div class="toast error"><?= sanitize(implode(' | ',$errors)) ?></div><?php endif; ?>
<form method="post" enctype="multipart/form-data" class="form-card" data-loading-button style="opacity:<?= (!$ngoProfile || $ngoProfile['verification_status']!=='verified') ? '0.65' : '1' ?>;"><input type="hidden" name="csrf_token" value="<?= sanitize(csrf_token()) ?>"><div class="form-grid"><div class="form-group"><label>Title</label><input name="title" required <?= (!$ngoProfile || $ngoProfile['verification_status']!=='verified') ? 'disabled' : '' ?>></div></div><div class="form-group"><label>Description</label><textarea name="description" rows="5" required <?= (!$ngoProfile || $ngoProfile['verification_status']!=='verified') ? 'disabled' : '' ?>></textarea></div><div class="form-grid"><div class="form-group"><label>Target Amount</label><input name="target_amount" type="number" step="0.01" min="1" required <?= (!$ngoProfile || $ngoProfile['verification_status']!=='verified') ? 'disabled' : '' ?>></div><div class="form-group"><label>Campaign Image</label><input name="campaign_image" id="campaign_image" type="file" accept="image/*" <?= (!$ngoProfile || $ngoProfile['verification_status']!=='verified') ? 'disabled' : '' ?>><img id="campaignPreview" class="hidden" alt="Campaign preview" style="margin-top:.5rem;max-height:170px;"></div></div><div class="form-grid"><div class="form-group"><label>Start Date</label><input name="start_date" type="date" required <?= (!$ngoProfile || $ngoProfile['verification_status']!=='verified') ? 'disabled' : '' ?>></div><div class="form-group"><label>End Date</label><input name="end_date" type="date" required <?= (!$ngoProfile || $ngoProfile['verification_status']!=='verified') ? 'disabled' : '' ?>></div></div><button class="gradient-button" type="submit" <?= (!$ngoProfile || $ngoProfile['verification_status']!=='verified') ? 'disabled' : '' ?>>Submit Campaign</button></form>
<script>var ci=document.getElementById('campaign_image');var cp=document.getElementById('campaignPreview');if(ci&&cp){ci.addEventListener('change',function(){var f=ci.files&&ci.files[0];if(!f)return;cp.src=URL.createObjectURL(f);cp.classList.remove('hidden');});}</script>
<?php require_once dirname(__DIR__) . '/includes/dashboard_layout_end.php'; ?>
