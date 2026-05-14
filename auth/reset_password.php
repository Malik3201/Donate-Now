<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$pdo = db();
$token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
$resetError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    if ($password !== $confirm) {
        $resetError = 'Passwords do not match.';
    } elseif (auth_password_strength_tier($password) === 'weak') {
        $resetError = 'Password is too weak. Use at least 8 characters and mix uppercase, lowercase, and numbers or symbols.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM password_resets WHERE token = :token AND used = 0 AND expires_at > NOW() ORDER BY id DESC LIMIT 1');
        $stmt->execute(['token' => $token]);
        $reset = $stmt->fetch();

        if ($reset) {
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare('UPDATE users SET password = :password, updated_at = NOW() WHERE id = :id');
                $stmt->execute(['password' => password_hash($password, PASSWORD_DEFAULT), 'id' => (int)$reset['user_id']]);
                $stmt = $pdo->prepare('UPDATE password_resets SET used = 1 WHERE id = :id');
                $stmt->execute(['id' => (int)$reset['id']]);
                $pdo->commit();
                redirect('auth/login.php');
            } catch (Throwable $e) {
                $pdo->rollBack();
                $resetError = 'Unable to reset password right now.';
            }
        } else {
            $resetError = 'Invalid or expired reset token.';
        }
    }
}

$pageTitle = 'Reset Password';
require_once dirname(__DIR__) . '/includes/auth_header.php';
?>
<div class="auth-shell">
  <section class="auth-panel">
    <div class="auth-card glass-card">
      <h2>Set a new password</h2>
      <p class="auth-form-lead" style="margin-top:0.35rem;">Choose a strong password you have not used here before.</p>
      <?php require dirname(__DIR__) . '/includes/flash_messages.php'; ?>
      <?php if ($resetError): ?>
        <div class="toast error" style="margin-bottom:1rem;"><?= sanitize($resetError) ?></div>
      <?php endif; ?>
      <form method="post" data-loading-button data-auth-register novalidate>
        <input type="hidden" name="token" value="<?= sanitize($token) ?>">
        <div class="auth-field">
          <label for="password">New password</label>
          <div class="auth-input-row">
            <input class="auth-password-input" id="password" name="password" type="password" required autocomplete="new-password" placeholder="New password" data-auth-strength-field>
            <button type="button" class="auth-pw-toggle" data-auth-pw-toggle="password" aria-label="Show password" aria-pressed="false">
              <span class="sr-only">Show password</span>
              <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z" />
                <circle cx="12" cy="12" r="3" />
              </svg>
              <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M3 3l18 18M10.6 10.6a3 3 0 004.8 4.8M9.9 5.1A10.4 10.4 0 0112 5c7 0 11 7 11 7a18.2 18.2 0 01-3.2 4.7M6.3 6.3C3.9 8 2 10.6 2 12s4 7 11 7a10 10 0 005-1.3" />
              </svg>
            </button>
          </div>
          <div class="password-meter" data-password-meter hidden>
            <div class="password-meter-bars" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
            <div class="password-meter-label">Password strength</div>
            <p class="password-meter-hint">Use 8+ characters with uppercase, lowercase, and numbers or symbols.</p>
          </div>
        </div>
        <div class="auth-field">
          <label for="confirm_password">Confirm password</label>
          <div class="auth-input-row">
            <input class="auth-password-input" id="confirm_password" name="confirm_password" type="password" required autocomplete="new-password" placeholder="Repeat password">
            <button type="button" class="auth-pw-toggle" data-auth-pw-toggle="confirm_password" aria-label="Show password" aria-pressed="false">
              <span class="sr-only">Show password</span>
              <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z" />
                <circle cx="12" cy="12" r="3" />
              </svg>
              <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M3 3l18 18M10.6 10.6a3 3 0 004.8 4.8M9.9 5.1A10.4 10.4 0 0112 5c7 0 11 7 11 7a18.2 18.2 0 01-3.2 4.7M6.3 6.3C3.9 8 2 10.6 2 12s4 7 11 7a10 10 0 005-1.3" />
              </svg>
            </button>
          </div>
        </div>
        <button type="submit" class="btn btn-primary auth-submit" style="margin-top:0.5rem;">Update password</button>
      </form>
      <p class="auth-secondary-link" style="margin-top:1.15rem;"><a href="<?= APP_URL ?>/auth/login.php">Back to login</a></p>
    </div>
  </section>
</div>
<?php require_once dirname(__DIR__) . '/includes/auth_footer.php'; ?>
