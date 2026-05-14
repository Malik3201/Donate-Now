<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_once dirname(__DIR__) . '/includes/role_check.php';
require_once dirname(__DIR__) . '/includes/mail_helper.php';
require_once dirname(__DIR__) . '/includes/notification_helper.php';
require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}
if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    exit('Invalid CSRF token');
}

$pdo = db();
$userId = intval($_POST['user_id'] ?? 0);
$action = trim((string)($_POST['action'] ?? ''));
$reason = trim((string)($_POST['reason'] ?? ''));
$allowed = ['activate', 'block', 'suspend', 'temporary_hold'];
if (!in_array($action, $allowed, true)) exit('Invalid action');
if (in_array($action, ['block', 'suspend', 'temporary_hold'], true) && $reason === '') exit('Reason required');

$map = ['activate' => 'active', 'block' => 'blocked', 'suspend' => 'suspended', 'temporary_hold' => 'temporary_hold'];
$status = $map[$action];

$stmt = $pdo->prepare('SELECT account_status FROM users WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $userId]);
$oldStatus = (string)($stmt->fetchColumn() ?: '');

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare('UPDATE users SET account_status = :status, status_reason = :reason, updated_at = NOW() WHERE id = :id');
    $stmt->execute(['status' => $status, 'reason' => $reason ?: null, 'id' => $userId]);

    $stmt = $pdo->prepare('INSERT INTO account_status_history (user_id, changed_by_admin_id, old_status, new_status, reason) VALUES (:user_id, :changed_by_admin_id, :old_status, :new_status, :reason)');
    $stmt->execute([
        'user_id' => $userId,
        'changed_by_admin_id' => (int)$authUser['id'],
        'old_status' => $oldStatus ?: null,
        'new_status' => $status,
        'reason' => $reason ?: null,
    ]);
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    exit('Failed to update user status.');
}

$stmt = $pdo->prepare('SELECT id, email, full_name FROM users WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch();
if ($user) {
    send_account_status_email($user, $status, $reason ?: 'Status updated by admin.');
    create_notification($pdo, (int)$user['id'], 'Account Status Updated', 'Your account status changed to ' . $status, 'account');
}
log_activity($pdo, (int)$authUser['id'], 'admin_user_status_change', 'users', $userId, 'Changed status to ' . $status);
redirect('admin/manage_users.php');
