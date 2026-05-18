<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/mail_helper.php';

$pdo = db();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));
    $stmt = $pdo->prepare('SELECT id, full_name, email FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expiresAt = (new DateTime('+30 minutes'))->format('Y-m-d H:i:s');
        $stmt = $pdo->prepare('INSERT INTO password_resets (user_id, token, expires_at, used) VALUES (:user_id, :token, :expires_at, 0)');
        $stmt->execute(['user_id' => (int)$user['id'], 'token' => $token, 'expires_at' => $expiresAt]);
        $link = app_url('auth/reset_password.php?token=' . urlencode($token));
        send_password_reset_email($user, $link);
    }

    $msg = 'If this email exists, a reset link has been sent.';
}

$pageTitle = 'Forgot Password';
require_once dirname(__DIR__) . '/includes/auth_header.php';
?>
<div class="auth-shell">
  <section class="auth-panel">
    <div class="auth-card glass-card">
      <h2>Forgot password</h2>
      <p class="auth-form-lead" style="margin-top:0.35rem;">Enter your account email and we will send a reset link if it matches our records.</p>
      <?php require dirname(__DIR__) . '/includes/flash_messages.php'; ?>
      <?php if ($msg): ?>
        <div class="toast success" style="margin-bottom:1rem;"><?= sanitize($msg) ?></div>
      <?php endif; ?>
      <form method="post" data-loading-button novalidate>
        <div class="auth-field">
          <label for="email">Email address</label>
          <input id="email" name="email" type="email" required placeholder="you@example.com" autocomplete="email">
        </div>
        <button type="submit" class="btn btn-primary auth-submit" style="margin-top:0.5rem;">Send reset link</button>
      </form>
      <p class="auth-secondary-link" style="margin-top:1.15rem;"><a href="<?= APP_URL ?>/auth/login.php">Back to login</a></p>
    </div>
  </section>
</div>
<?php require_once dirname(__DIR__) . '/includes/auth_footer.php'; ?>
