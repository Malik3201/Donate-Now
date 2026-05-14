<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/auth_check.php';
require_once dirname(__DIR__) . '/includes/mail_helper.php';
$pdo = db();
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) exit('Invalid CSRF token');
    $current = (string)($_POST['current_password'] ?? '');
    $new = (string)($_POST['new_password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');
    if ($new !== $confirm) {
        $msg = 'New passwords do not match.';
    } else {
        $stmt = $pdo->prepare('SELECT password, email, full_name FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => (int)$authUser['id']]);
        $row = $stmt->fetch();
        if ($row && password_verify($current, (string)$row['password'])) {
            $stmt = $pdo->prepare('UPDATE users SET password = :password, updated_at = NOW() WHERE id = :id');
            $stmt->execute(['password' => password_hash($new, PASSWORD_DEFAULT), 'id' => (int)$authUser['id']]);
            send_email((string)$row['email'], 'Password Changed', render_email_template('Password Changed', '<p>Your password was changed successfully.</p>'), (int)$authUser['id'], 'password_changed');
            $msg = 'Password changed successfully.';
        } else {
            $msg = 'Current password is incorrect.';
        }
    }
}
$pageTitle='Change Password';
require_once dirname(__DIR__) . '/includes/dashboard_layout_start.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>
<h1 class="section-title">Change Password</h1>
<?php require dirname(__DIR__) . '/includes/flash_messages.php'; ?>
<div class="form-card" style="max-width:620px;"><form method="post"><input type="hidden" name="csrf_token" value="<?= sanitize(csrf_token()) ?>"><div class="form-group password-wrap"><label>Current Password</label><input id="current_password" name="current_password" type="password" required><button class="password-toggle" type="button" data-password-toggle="current_password">Show</button></div><div class="form-group password-wrap"><label>New Password</label><input id="new_password" name="new_password" type="password" required><button class="password-toggle" type="button" data-password-toggle="new_password">Show</button></div><div class="form-group password-wrap"><label>Confirm Password</label><input id="confirm_password" name="confirm_password" type="password" required><button class="password-toggle" type="button" data-password-toggle="confirm_password">Show</button></div><button class="gradient-button" type="submit">Update Password</button></form></div>
<?php require_once dirname(__DIR__) . '/includes/dashboard_layout_end.php'; ?>
