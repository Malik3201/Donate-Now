<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/auth_check.php';
require_once dirname(__DIR__) . '/includes/notification_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed'); }
if (!verify_csrf_token($_POST['csrf_token'] ?? null)) { exit('Invalid CSRF token'); }

$pdo = db();
$notificationId = intval($_POST['notification_id'] ?? 0);
mark_notification_read($pdo, $notificationId, (int)$authUser['id']);
redirect('notifications/index.php');
