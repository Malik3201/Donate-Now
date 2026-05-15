<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/mail_helper.php';
require_once dirname(__DIR__) . '/includes/notification_helper.php';

$pdo = db();
$errors = [];

$defaultRole = (string)($_GET['role'] ?? 'donor');
if (!in_array($defaultRole, ['donor', 'ngo', 'volunteer'], true)) {
    $defaultRole = 'donor';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim((string)($_POST['full_name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirm_password = (string)($_POST['confirm_password'] ?? '');
    $role = (string)($_POST['role'] ?? '');

    if (!in_array($role, ['donor', 'ngo', 'volunteer'], true)) {
        $errors[] = 'Invalid role selected.';
    }
    if ($full_name === '' || $email === '' || $password === '' || $confirm_password === '') {
        $errors[] = 'Required fields missing.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
    }
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }
    if (auth_password_strength_tier($password) === 'weak') {
        $errors[] = 'Password is too weak. Use at least 8 characters and mix uppercase, lowercase, and numbers or symbols (three types for a strong password).';
    }

    if ($role === 'ngo' && trim((string)($_POST['ngo_name'] ?? '')) === '') {
        $errors[] = 'NGO name is required when registering as an NGO.';
    }

    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) {
        $errors[] = 'Email already registered.';
    }

    if (!$errors) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO users (full_name, email, phone, password, role) VALUES (:full_name, :email, :phone, :password, :role)');
            $stmt->execute([
                'full_name' => $full_name,
                'email' => $email,
                'phone' => $phone ?: null,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'role' => $role,
            ]);
            $userId = (int)$pdo->lastInsertId();

            if ($role === 'donor') {
                $stmt = $pdo->prepare('INSERT INTO donor_profiles (user_id, address, donor_type) VALUES (:user_id, :address, :donor_type)');
                $stmt->execute([
                    'user_id' => $userId,
                    'address' => trim((string)($_POST['donor_address'] ?? '')) ?: null,
                    'donor_type' => (string)($_POST['donor_type'] ?? 'individual'),
                ]);
            }

            if ($role === 'ngo') {
                $stmt = $pdo->prepare('INSERT INTO ngo_profiles (user_id, ngo_name, registration_number, description, address, verification_status) VALUES (:user_id, :ngo_name, :registration_number, :description, :address, :verification_status)');
                $stmt->execute([
                    'user_id' => $userId,
                    'ngo_name' => trim((string)($_POST['ngo_name'] ?? '')),
                    'registration_number' => trim((string)($_POST['registration_number'] ?? '')) ?: null,
                    'description' => trim((string)($_POST['description'] ?? '')) ?: null,
                    'address' => trim((string)($_POST['ngo_address'] ?? '')) ?: null,
                    'verification_status' => 'pending',
                ]);

                foreach (admin_users_for_notifications($pdo) as $admin) {
                    create_notification($pdo, (int) $admin['id'], 'New NGO Registration', 'A new NGO has registered and is awaiting verification.', 'ngo');
                }
                $ngoAdminPayload = ['ngo_name' => (string) ($_POST['ngo_name'] ?? ''), 'email' => $email];
                foreach (admin_mail_recipients($pdo) as $adminEmail) {
                    send_new_ngo_registration_admin_email($adminEmail, $ngoAdminPayload);
                }
            }

            if ($role === 'volunteer') {
                $stmt = $pdo->prepare('INSERT INTO volunteer_profiles (user_id, skills, availability, address) VALUES (:user_id, :skills, :availability, :address)');
                $stmt->execute([
                    'user_id' => $userId,
                    'skills' => trim((string)($_POST['skills'] ?? '')) ?: null,
                    'availability' => trim((string)($_POST['availability'] ?? '')) ?: null,
                    'address' => trim((string)($_POST['volunteer_address'] ?? '')) ?: null,
                ]);
            }

            log_activity($pdo, $userId, 'register', 'users', $userId, 'User registered as ' . $role);
            send_registration_email(['id' => $userId, 'full_name' => $full_name, 'email' => $email]);
            $pdo->commit();
            redirect('auth/login.php');
        } catch (Throwable $e) {
            $pdo->rollBack();
            $errors[] = 'Registration failed. Please try again.';
        }
    }
}

$pageTitle = 'Register';
require_once dirname(__DIR__) . '/includes/auth_visual_images.php';
$authVisualImage = $authImages['register'];
require_once dirname(__DIR__) . '/includes/auth_header.php';
?>
<main class="auth-layout">
  <section class="auth-visual auth-image-panel auth-hero auth-side" style="--auth-visual-image: url('<?= sanitize($authVisualImage) ?>');">
    <div class="auth-visual-bg" aria-hidden="true"></div>
    <div class="auth-visual-overlay" aria-hidden="true"></div>
    <div class="auth-visual-inner auth-welcome">
      <p class="auth-visual-kicker">Join the community</p>
      <h1>Create your account in minutes.</h1>
      <p>Choose how you want to participate—donor, NGO, or volunteer—and follow a transparent, proof-based path built for local trust.</p>
      <div class="auth-visual-badges auth-feature-pills">
        <span class="auth-badge">Verified NGOs</span>
        <span class="auth-badge">Donation proof trail</span>
        <span class="auth-badge">Volunteer matching</span>
      </div>
    </div>
  </section>
  <div class="auth-form-wrap auth-form-panel">
    <div class="auth-form-card auth-card">
      <h2>Create account</h2>
      <p class="auth-form-lead">Tell us who you are, set a strong password, and add role-specific details when asked.</p>
      <?php require dirname(__DIR__) . '/includes/flash_messages.php'; ?>
      <form method="post" action="" data-loading-button data-auth-register novalidate>
        <fieldset class="auth-role-fieldset">
          <legend class="auth-role-legend">I am joining as</legend>
          <div class="auth-role-grid">
            <label class="auth-role-tile">
              <input type="radio" name="role" value="donor" <?= $defaultRole === 'donor' ? 'checked' : '' ?> required>
              <span>Donor</span>
            </label>
            <label class="auth-role-tile">
              <input type="radio" name="role" value="ngo" <?= $defaultRole === 'ngo' ? 'checked' : '' ?>>
              <span>NGO</span>
            </label>
            <label class="auth-role-tile">
              <input type="radio" name="role" value="volunteer" <?= $defaultRole === 'volunteer' ? 'checked' : '' ?>>
              <span>Volunteer</span>
            </label>
          </div>
        </fieldset>

        <div class="auth-form-grid">
          <div class="auth-field">
            <label for="full_name">Full name</label>
            <input id="full_name" name="full_name" type="text" autocomplete="name" required placeholder="Your full name">
          </div>
          <div class="auth-field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" autocomplete="email" required placeholder="you@example.com">
          </div>
          <div class="auth-field">
            <label for="phone">Phone <span style="font-weight:400;color:var(--color-muted);">(optional)</span></label>
            <input id="phone" name="phone" type="tel" autocomplete="tel" placeholder="+92 …">
          </div>
        </div>

        <div class="auth-form-grid">
          <div class="auth-field">
            <label for="registerPassword">Password</label>
            <div class="auth-input-row">
              <input class="auth-password-input" id="registerPassword" name="password" type="password" autocomplete="new-password" required placeholder="Create a password" data-auth-strength-field>
              <button type="button" class="auth-pw-toggle" data-auth-pw-toggle="registerPassword" aria-label="Show password" aria-pressed="false">
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
              <p class="password-meter-hint">Use 8+ characters with uppercase, lowercase, and numbers or symbols. Three types = strong.</p>
            </div>
          </div>
          <div class="auth-field">
            <label for="confirm_password">Confirm password</label>
            <div class="auth-input-row">
              <input class="auth-password-input" id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" required placeholder="Repeat password">
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
        </div>

        <div class="auth-role-panel-wrap<?= $defaultRole === 'donor' ? ' is-open' : '' ?>" data-role-panel="donor">
          <div class="auth-role-panel-inner">
            <div class="auth-role-panel">
              <h3>Donor profile</h3>
              <div class="auth-form-grid">
                <div class="auth-field" style="grid-column: 1 / -1;">
                  <label for="donor_type">Donor type</label>
                  <select id="donor_type" name="donor_type">
                    <option value="individual">Individual</option>
                    <option value="organization">Organization</option>
                  </select>
                </div>
                <div class="auth-field" style="grid-column: 1 / -1;">
                  <label for="donor_address">Address <span style="font-weight:400;color:var(--color-muted);">(optional)</span></label>
                  <input id="donor_address" name="donor_address" type="text" autocomplete="street-address" placeholder="City or area">
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="auth-role-panel-wrap<?= $defaultRole === 'ngo' ? ' is-open' : '' ?>" data-role-panel="ngo">
          <div class="auth-role-panel-inner">
            <div class="auth-role-panel">
              <h3>NGO details</h3>
              <div class="auth-form-grid">
                <div class="auth-field">
                  <label for="ngo_name">NGO name</label>
                  <input id="ngo_name" name="ngo_name" type="text" placeholder="Registered organization name">
                </div>
                <div class="auth-field">
                  <label for="registration_number">Registration # <span style="font-weight:400;color:var(--color-muted);">(optional)</span></label>
                  <input id="registration_number" name="registration_number" type="text" placeholder="If applicable">
                </div>
              </div>
              <div class="auth-field">
                <label for="description">Description <span style="font-weight:400;color:var(--color-muted);">(optional)</span></label>
                <textarea id="description" name="description" rows="3" placeholder="What does your NGO do?"></textarea>
              </div>
              <div class="auth-field">
                <label for="ngo_address">Address</label>
                <input id="ngo_address" name="ngo_address" type="text" autocomplete="street-address" placeholder="Office or contact address">
              </div>
            </div>
          </div>
        </div>

        <div class="auth-role-panel-wrap<?= $defaultRole === 'volunteer' ? ' is-open' : '' ?>" data-role-panel="volunteer">
          <div class="auth-role-panel-inner">
            <div class="auth-role-panel">
              <h3>Volunteer details</h3>
              <div class="auth-form-grid">
                <div class="auth-field">
                  <label for="skills">Skills <span style="font-weight:400;color:var(--color-muted);">(optional)</span></label>
                  <input id="skills" name="skills" type="text" placeholder="e.g. teaching, logistics">
                </div>
                <div class="auth-field">
                  <label for="availability">Availability <span style="font-weight:400;color:var(--color-muted);">(optional)</span></label>
                  <input id="availability" name="availability" type="text" placeholder="Weekends, evenings…">
                </div>
              </div>
              <div class="auth-field">
                <label for="volunteer_address">Address <span style="font-weight:400;color:var(--color-muted);">(optional)</span></label>
                <input id="volunteer_address" name="volunteer_address" type="text" autocomplete="street-address" placeholder="City or area">
              </div>
            </div>
          </div>
        </div>

        <div class="auth-form-actions">
          <button type="submit" class="btn btn-primary auth-submit">Create account</button>
          <p class="auth-secondary-link">Already registered? <a href="<?= APP_URL ?>/auth/login.php">Log in instead</a></p>
        </div>
      </form>
    </div>
  </div>
</main>
<?php require_once dirname(__DIR__) . '/includes/auth_footer.php'; ?>
