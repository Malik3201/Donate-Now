<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/auth_check.php';
require_once dirname(__DIR__) . '/includes/role_check.php';
require_role(['volunteer']);
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit('Method not allowed');}
if(!verify_csrf_token($_POST['csrf_token']??null)) exit('Invalid CSRF token');
$pdo=db();$id=intval($_POST['id']??0);
$stmt=$pdo->prepare('SELECT id FROM volunteer_profiles WHERE user_id=:u LIMIT 1');$stmt->execute(['u'=>(int)$authUser['id']]);$volunteerId=(int)($stmt->fetchColumn()?:0);
$stmt=$pdo->prepare("UPDATE volunteer_campaigns SET status='cancelled', updated_at=NOW() WHERE id=:id AND volunteer_id=:v AND status='pending'");$stmt->execute(['id'=>$id,'v'=>$volunteerId]);
log_activity($pdo,(int)$authUser['id'],'volunteer_request_cancelled','volunteer_campaigns',$id,'Volunteer cancelled pending request');
redirect('volunteer/my_campaigns.php');
