<?php
declare(strict_types=1);

/**
 * Login form. On success sets $_SESSION user id/role and redirects by role dashboard.
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$pdo = db();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, (string)$user['password'])) {
        if (($user['account_status'] ?? 'inactive') !== 'active') {
            $error = 'Your account is not active.';
        } else {
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['full_name'] = (string)$user['full_name'];
            $_SESSION['email'] = (string)$user['email'];
            $_SESSION['role'] = (string)$user['role'];
            log_activity($pdo, (int)$user['id'], 'login', 'users', (int)$user['id'], 'User logged in successfully.');

            $map = [
                'admin' => 'admin/dashboard.php',
                'donor' => 'donor/dashboard.php',
                'ngo' => 'ngo/dashboard.php',
                'volunteer' => 'volunteer/dashboard.php',
            ];
            redirect($map[$user['role']] ?? 'index.php');
        }
    } else {
        $error = 'Invalid email or password.';
    }
}

$pageTitle = 'Login';
require_once dirname(__DIR__) . '/includes/auth_visual_images.php';
$authVisualImage = $authImages['login'];
require_once dirname(__DIR__) . '/includes/auth_header.php';
?>
<main class="auth-layout">
  <section class="auth-visual auth-image-panel auth-hero auth-side" style="--auth-visual-image: url('<?= sanitize($authVisualImage) ?>');">
    <div class="auth-visual-bg" aria-hidden="true"></div>
    <div class="auth-visual-overlay" aria-hidden="true"></div>
    <div class="auth-visual-inner auth-welcome">
      <p class="auth-visual-kicker">Welcome back</p>
      <h1>Sign in to continue giving with clarity.</h1>
      <p>Access your dashboard, donation history, and verified campaigns in one transparent workspace.</p>
      <div class="auth-visual-badges auth-feature-pills">
        <span class="auth-badge">Proof-based flow</span>
        <span class="auth-badge">Verified NGOs</span>
        <span class="auth-badge">Local impact</span>
      </div>
    </div>
  </section>
  <div class="auth-form-wrap auth-form-panel">
    <div class="auth-form-card auth-card">
      <h2>Log in</h2>
      <p class="auth-form-lead">Use the email and password you registered with.</p>
      <?php require dirname(__DIR__) . '/includes/flash_messages.php'; ?>
      <form method="post" action="" data-loading-button novalidate>
        <div class="auth-field">
          <label for="email">Email address</label>
          <input id="email" name="email" type="email" autocomplete="email" required placeholder="you@example.com">
        </div>
        <div class="auth-field">
          <label for="password">Password</label>
          <div class="auth-input-row">
            <input class="auth-password-input" id="password" name="password" type="password" autocomplete="current-password" required placeholder="Enter your password">
            <button type="button" class="auth-pw-toggle" id="toggle-login-password" data-auth-pw-toggle="password" aria-label="Show password" aria-pressed="false">
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
        <div class="auth-form-actions-row">
          <a href="<?= APP_URL ?>/auth/forgot_password.php">Forgot password?</a>
          <a href="<?= APP_URL ?>/auth/register.php">Create an account</a>
        </div>
        <div class="auth-form-actions">
          <button type="submit" class="btn btn-primary auth-submit">Log in</button>
        </div>
      </form>
      <p class="auth-secondary-link" style="margin-top: 1.25rem;">New here? <a href="<?= APP_URL ?>/auth/register.php">Join Donate Now</a></p>
    </div>
  </div>
</main>
<?php require_once dirname(__DIR__) . '/includes/auth_footer.php'; ?>
