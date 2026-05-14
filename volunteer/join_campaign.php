<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/auth_check.php';
require_once dirname(__DIR__) . '/includes/role_check.php';
require_once dirname(__DIR__) . '/includes/notification_helper.php';
require_once dirname(__DIR__) . '/includes/mail_helper.php';
require_role(['volunteer']);
$pdo=db();$campaignId=intval($_GET['campaign_id']??$_POST['campaign_id']??0);
$stmt=$pdo->prepare('SELECT id,skills,availability FROM volunteer_profiles WHERE user_id=:u LIMIT 1');$stmt->execute(['u'=>(int)$authUser['id']]);$volProfile=$stmt->fetch();$volunteerId=(int)($volProfile['id']??0);if(!$volunteerId) exit('Volunteer profile not found.');
$stmt=$pdo->prepare("SELECT c.id,c.title,c.status,c.ngo_id,c.image_url,np.ngo_name,u.id ngo_user_id,u.email ngo_email FROM campaigns c INNER JOIN ngo_profiles np ON np.id=c.ngo_id INNER JOIN users u ON u.id=np.user_id WHERE c.id=:id AND c.status IN ('approved','active') LIMIT 1");$stmt->execute(['id'=>$campaignId]);$campaign=$stmt->fetch();if(!$campaign) exit('Campaign not available.');
$stmt=$pdo->prepare("SELECT status FROM volunteer_campaigns WHERE volunteer_id=:v AND campaign_id=:c AND status IN ('pending','accepted') LIMIT 1");$stmt->execute(['v'=>$volunteerId,'c'=>$campaignId]);$existingVolunteerRow=$stmt->fetch();
$errors=[];$success='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!verify_csrf_token($_POST['csrf_token']??null)) exit('Invalid CSRF token');
 $message=trim((string)($_POST['message']??''));
 $stmt=$pdo->prepare("SELECT id FROM volunteer_campaigns WHERE volunteer_id=:v AND campaign_id=:c AND status IN ('pending','accepted') LIMIT 1");$stmt->execute(['v'=>$volunteerId,'c'=>$campaignId]); if($stmt->fetch()) $errors[]='You already have an active request for this campaign.';
 if(!$errors){$stmt=$pdo->prepare('INSERT INTO volunteer_campaigns (volunteer_id,campaign_id,ngo_id,message,status) VALUES (:v,:c,:n,:m,:s)');$stmt->execute(['v'=>$volunteerId,'c'=>$campaignId,'n'=>(int)$campaign['ngo_id'],'m'=>$message?:null,'s'=>'pending']);create_notification($pdo,(int)$campaign['ngo_user_id'],'New Volunteer Request','A volunteer requested to join campaign: '.$campaign['title'],'volunteer');send_volunteer_request_email(['id'=>(int)$campaign['ngo_user_id'],'email'=>$campaign['ngo_email']],['volunteer_name'=>$authUser['full_name'],'campaign_title'=>$campaign['title']]);log_activity($pdo,(int)$authUser['id'],'volunteer_request_created','volunteer_campaigns',(int)$pdo->lastInsertId(),'Volunteer requested campaign join');$success='Your volunteer request has been sent to the NGO.';}
}
$pageTitle='Join Campaign';require_once dirname(__DIR__) . '/includes/dashboard_layout_start.php';require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>
<h1 class="section-title">Join Campaign</h1>
<?php if ($success): ?>
  <div class="toast success"><?= sanitize($success) ?></div>
  <div class="glass-card" style="padding:1.25rem;max-width:640px;">
    <h2 style="margin-top:0;">Request sent</h2>
    <p style="color:var(--text-muted);">The NGO will review your message. You can track status under My Campaign Requests.</p>
    <p style="margin-top:1rem;"><a class="gradient-button" href="<?= APP_URL ?>/volunteer/my_campaigns.php">Open My Campaign Requests</a> <a class="outline-button" href="<?= APP_URL ?>/volunteer/browse_campaigns.php">Back to browse</a></p>
  </div>
<?php elseif ($existingVolunteerRow && !$errors): $st = (string) ($existingVolunteerRow['status'] ?? ''); ?>
  <div class="glass-card" style="padding:1.25rem;max-width:640px;">
    <h2 style="margin-top:0;"><?= $st === 'accepted' ? 'You are already on this campaign' : 'Request already sent' ?></h2>
    <p style="color:var(--text-muted);"><?= $st === 'accepted' ? 'You have been accepted as a volunteer. You can review details under My Campaign Requests.' : 'Your join request is pending NGO review. You will see updates under My Campaign Requests.' ?></p>
    <p style="margin-top:1rem;"><a class="gradient-button" href="<?= APP_URL ?>/volunteer/my_campaigns.php">Open My Campaign Requests</a> <a class="outline-button" href="<?= APP_URL ?>/volunteer/browse_campaigns.php">Back to browse</a></p>
  </div>
<?php else: ?>
  <?php if ($errors): ?><div class="toast error"><?= sanitize(implode(' | ', $errors)) ?></div><?php endif; ?>
  <div class="grid" style="grid-template-columns:1fr 1fr;"><div class="glass-card" style="padding:1rem;"><img src="<?= sanitize(image_or_placeholder((string)($campaign['image_url']??''),'campaign')) ?>" alt="Campaign"><h3><?= sanitize($campaign['title']) ?></h3><p>NGO: <?= sanitize($campaign['ngo_name']) ?></p><p>Status: <span class="status-badge"><?= sanitize($campaign['status']) ?></span></p></div><div class="form-card"><h3>Volunteer Profile Summary</h3><p>Skills: <?= sanitize((string)($volProfile['skills']??'')) ?></p><p>Availability: <?= sanitize((string)($volProfile['availability']??'')) ?></p><form method="post"><input type="hidden" name="csrf_token" value="<?= sanitize(csrf_token()) ?>"><input type="hidden" name="campaign_id" value="<?= $campaignId ?>"><div class="form-group"><label>Message to NGO</label><textarea name="message" rows="4" placeholder="Optional message"></textarea></div><button class="gradient-button" type="submit">Send Request</button></form></div></div>
<?php endif; ?>
<?php require_once dirname(__DIR__) . '/includes/dashboard_layout_end.php'; ?>
