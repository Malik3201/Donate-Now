<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/auth_check.php';
require_once dirname(__DIR__) . '/includes/role_check.php';
require_once dirname(__DIR__) . '/includes/notification_helper.php';
require_once dirname(__DIR__) . '/includes/mail_helper.php';
require_role(['admin']);
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit('Method not allowed');}
if(!verify_csrf_token($_POST['csrf_token']??null)) exit('Invalid CSRF token');
$pdo=db();$id=intval($_POST['report_id']??0);$action=trim((string)($_POST['action']??''));$adminNote=trim((string)($_POST['admin_note']??''));
if(!in_array($action,['mark_under_review','resolve','reject'],true)) exit('Invalid action');
if(in_array($action,['resolve','reject'],true)&&$adminNote==='') exit('Admin note required for resolve/reject');
$stmt=$pdo->prepare('SELECT r.*, u.email AS reporter_email FROM reports r INNER JOIN users u ON u.id=r.reporter_user_id WHERE r.id=:id LIMIT 1');$stmt->execute(['id'=>$id]);$report=$stmt->fetch();if(!$report) exit('Report not found');
$statusMap=['mark_under_review'=>'under_review','resolve'=>'resolved','reject'=>'rejected'];$newStatus=$statusMap[$action];
$stmt=$pdo->prepare('UPDATE reports SET status=:s, admin_note=:n, resolved_by_admin_id=:a, resolved_at=CASE WHEN :is_final=1 THEN NOW() ELSE resolved_at END, updated_at=NOW() WHERE id=:id');
$stmt->execute(['s'=>$newStatus,'n'=>$adminNote?:null,'a'=>(int)$authUser['id'],'is_final'=>in_array($newStatus,['resolved','rejected'],true)?1:0,'id'=>$id]);
if($adminNote!==''){ $stmt=$pdo->prepare('INSERT INTO admin_notes (admin_id, report_id, note) VALUES (:a,:r,:n)'); $stmt->execute(['a'=>(int)$authUser['id'],'r'=>$id,'n'=>$adminNote]); }
create_notification($pdo,(int)$report['reporter_user_id'],'Report Status Updated','Your report "'.$report['subject'].'" is now '.$newStatus,'report');
send_report_status_email(['id'=>(int)$report['reporter_user_id'],'email'=>$report['reporter_email']],['subject'=>$report['subject'],'status'=>$newStatus,'admin_note'=>$adminNote]);
log_activity($pdo,(int)$authUser['id'],'report_status_changed','reports',$id,'Admin changed report status to '.$newStatus);
redirect('admin/report_detail.php?id='.$id);
