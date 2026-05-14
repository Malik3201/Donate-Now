<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_once dirname(__DIR__) . '/includes/role_check.php';
require_once dirname(__DIR__) . '/includes/notification_helper.php';
require_once dirname(__DIR__) . '/includes/mail_helper.php';

require_role(['admin']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed.'); }
if (!verify_csrf_token($_POST['csrf_token'] ?? null)) exit('Invalid CSRF token.');

$pdo = db();
$campaignId = intval($_POST['campaign_id'] ?? 0);
$action = trim((string)($_POST['action'] ?? ''));
$reason = trim((string)($_POST['reason'] ?? ''));
if (!in_array($action, ['approve', 'reject', 'activate', 'complete', 'temporary_hold'], true)) exit('Invalid action.');
if (in_array($action, ['reject', 'temporary_hold'], true) && $reason === '') exit('Reason required.');

$stmt = $pdo->prepare('SELECT c.id, c.title, c.status, c.ngo_id, np.user_id AS ngo_user_id, u.email AS ngo_email FROM campaigns c INNER JOIN ngo_profiles np ON np.id = c.ngo_id INNER JOIN users u ON u.id = np.user_id WHERE c.id = :id LIMIT 1');
$stmt->execute(['id' => $campaignId]);
$campaign = $stmt->fetch();
if (!$campaign) exit('Campaign not found.');

$statusMap = ['approve' => 'approved', 'reject' => 'rejected', 'activate' => 'active', 'complete' => 'completed', 'temporary_hold' => 'temporary_hold'];
$newStatus = $statusMap[$action];
$rejectionReason = in_array($newStatus, ['rejected', 'temporary_hold'], true) ? $reason : null;

$stmt = $pdo->prepare('UPDATE campaigns SET status = :status, rejection_reason = :rejection_reason, updated_at = NOW() WHERE id = :id');
$stmt->execute([
    'status' => $newStatus,
    'rejection_reason' => $rejectionReason,
    'id' => $campaignId,
]);

create_notification($pdo, (int)$campaign['ngo_user_id'], 'Campaign Status Updated', 'Campaign "' . $campaign['title'] . '" status changed to ' . $newStatus, 'campaign');
send_campaign_status_email(['id' => (int)$campaign['ngo_user_id'], 'email' => $campaign['ngo_email']], (string)$campaign['title'], $newStatus, $reason ?: null);
log_activity($pdo, (int)$authUser['id'], 'campaign_status_updated', 'campaigns', $campaignId, 'Admin changed campaign status to ' . $newStatus);

redirect('admin/manage_campaigns.php');
