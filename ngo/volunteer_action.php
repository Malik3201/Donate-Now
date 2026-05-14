<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/auth_check.php';
require_once dirname(__DIR__) . '/includes/role_check.php';
require_once dirname(__DIR__) . '/includes/notification_helper.php';
require_once dirname(__DIR__) . '/includes/mail_helper.php';
require_role(['ngo']);
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit('Method not allowed');}
if(!verify_csrf_token($_POST['csrf_token']??null)) exit('Invalid CSRF token');
$pdo=db();$id=intval($_POST['id']??0);$action=trim((string)($_POST['action']??''));$note=trim((string)($_POST['note']??'')); if(!in_array($action,['accept','reject'],true)) exit('Invalid action');
$stmt=$pdo->prepare('SELECT id FROM ngo_profiles WHERE user_id=:u LIMIT 1');$stmt->execute(['u'=>(int)$authUser['id']]);$ngoId=(int)($stmt->fetchColumn()?:0);
$stmt=$pdo->prepare("SELECT vc.*, c.title AS campaign_title, vp.user_id AS volunteer_user_id, u.email AS volunteer_email FROM volunteer_campaigns vc INNER JOIN campaigns c ON c.id=vc.campaign_id INNER JOIN volunteer_profiles vp ON vp.id=vc.volunteer_id INNER JOIN users u ON u.id=vp.user_id WHERE vc.id=:id AND vc.ngo_id=:n LIMIT 1");
$stmt->execute(['id'=>$id,'n'=>$ngoId]);$req=$stmt->fetch(); if(!$req) exit('Request not found.'); if($req['status']!=='pending') exit('Only pending requests can be processed.');
$newStatus=$action==='accept'?'accepted':'rejected';
$pdo->beginTransaction();
try{
 $stmt=$pdo->prepare('UPDATE volunteer_campaigns SET status=:s, ngo_note=:note, updated_at=NOW() WHERE id=:id');$stmt->execute(['s'=>$newStatus,'note'=>$note?:null,'id'=>$id]);
 if($newStatus==='accepted'){
  $stmt=$pdo->prepare('UPDATE volunteer_profiles SET total_accepted_campaigns=total_accepted_campaigns+1, total_joined_campaigns=total_joined_campaigns+1, updated_at=NOW() WHERE id=:id');$stmt->execute(['id'=>$req['volunteer_id']]);
 }
 $pdo->commit();
}catch(Throwable $e){$pdo->rollBack();exit('Failed to process request.');}
create_notification($pdo,(int)$req['volunteer_user_id'],'Volunteer Request '.$newStatus,'Your request for campaign '.$req['campaign_title'].' is '.$newStatus.'.','volunteer');
send_volunteer_request_status_email(['id'=>(int)$req['volunteer_user_id'],'email'=>$req['volunteer_email']],['campaign_title'=>$req['campaign_title'],'status'=>$newStatus,'ngo_note'=>$note]);
log_activity($pdo,(int)$authUser['id'],'volunteer_request_'.$newStatus,'volunteer_campaigns',$id,'NGO processed volunteer request');
redirect('ngo/volunteer_requests.php');
