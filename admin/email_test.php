<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_once dirname(__DIR__) . '/includes/role_check.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/mail_helper.php';
require_role(['admin']);

$pdo = db();

$templateOptions = [
    'plain' => 'Plain test (connectivity)',
    'registration' => 'Welcome / signup (registration)',
    'password_reset' => 'Password reset (demo link)',
    'ngo_verified' => 'NGO verified',
    'ngo_rejected' => 'NGO rejected',
    'account_status' => 'Account status change',
    'new_ngo_admin' => 'Admin alert: new NGO registration',
    'campaign_submitted' => 'Admin alert: new campaign submitted',
    'campaign_status' => 'NGO: campaign status update',
    'donation_pending' => 'Donor: donation pending verification',
    'donation_confirmed' => 'Donor: donation confirmed',
    'donation_rejected' => 'Donor: donation rejected',
    'donation_proof_ngo' => 'NGO: new donation proof received',
    'donation_flagged_admin' => 'Admin: flagged donation',
    'report_submitted_admin' => 'Admin: new report submitted',
    'report_received' => 'Reporter: report received',
    'report_status' => 'Reporter: report status update',
    'volunteer_request' => 'NGO: volunteer join request',
    'volunteer_request_status' => 'Volunteer: request status update',
];

$msg = '';
$error = '';
$lastMethod = null;
$brevo = brevo_config();
$diag = brevo_mail_diagnostics();
$mailConfigured = (bool) $diag['mail_configured'];
$defaultAdminEmail = admin_primary_email() ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid CSRF token. Refresh the page and try again.';
    } else {
        $email = trim((string) ($_POST['recipient_email'] ?? ''));
        $templateKey = trim((string) ($_POST['template'] ?? ''));

        if (!isset($templateOptions[$templateKey])) {
            $error = 'Please choose a valid template.';
        } else {
            $result = admin_send_test_email($email, $templateKey, (int) $authUser['id']);
            $lastMethod = $result['method'] ?? null;
            if ($result['ok']) {
                $methodLabel = mail_method_label($lastMethod);
                $msg = 'Email sent successfully via ' . $methodLabel . '. Template log key: ' . sanitize($result['template']) . '. Check the inbox (and spam) for ' . sanitize($email) . '.';
            } else {
                $error = 'Send failed. ' . ($result['error'] ?? 'Unknown error.');
                if ($lastMethod !== null) {
                    $error .= ' Last attempt: ' . mail_method_label($lastMethod) . '.';
                }
            }
        }
    }
}

$stmt = $pdo->query('SELECT id, recipient_email, subject, template_name, status, error_message, created_at FROM email_logs ORDER BY id DESC LIMIT 40');
$logs = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

$pageTitle = 'Email test';
$pageDescription = 'Send sample transactional emails and inspect delivery results in email_logs.';
require_once dirname(__DIR__) . '/includes/dashboard_layout_start.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>
<div class="dn-page-head">
  <h1 class="dn-page-title">Email test</h1>
  <p class="dn-page-lead">Send a templated test message to any address. On live hosting, <strong>Brevo API (HTTPS)</strong> is tried first when <code>BREVO_API_KEY</code> is set; SMTP is used only as fallback.</p>
</div>

<?php if (!$mailConfigured): ?>
  <div class="toast error" style="margin-bottom:1rem;">
    Email is not configured: set <strong>BREVO_FROM_EMAIL</strong> (verified sender in Brevo) and either
    <strong>BREVO_API_KEY</strong> (<code>xkeysib-…</code>, recommended for live hosting) or SMTP
    <strong>BREVO_SMTP_USER</strong> + <strong>BREVO_SMTP_PASS</strong> (<code>xsmtpsib-…</code>) in <code>.env</code>.
  </div>
<?php elseif ($diag['api_configured']): ?>
  <div class="toast" style="margin-bottom:1rem;background:rgba(34,197,94,0.12);border-color:rgba(34,197,94,0.35);">
    <strong>Brevo API</strong> is configured (preferred). SMTP fallback <?= $diag['smtp_configured'] ? 'is also available' : 'is not configured' ?>.
  </div>
<?php elseif ($diag['smtp_configured']): ?>
  <div class="toast" style="margin-bottom:1rem;background:rgba(244,183,64,0.15);border-color:rgba(244,183,64,0.4);">
    Only <strong>SMTP</strong> is configured (<?= htmlspecialchars((string) $diag['smtp_host'], ENT_QUOTES, 'UTF-8') ?>:<?= (int) $diag['smtp_port'] ?>).
    Many hosts block outbound SMTP — add <strong>BREVO_API_KEY</strong> for reliable delivery over HTTPS.
  </div>
<?php endif; ?>

<?php require dirname(__DIR__) . '/includes/flash_messages.php'; ?>

<div class="dn-admin-email-test">
  <section class="glass-card dn-admin-email-test__form-card" style="padding:1.2rem 1.25rem;margin-bottom:1.25rem;">
    <h2 class="dn-admin-email-test__h2">Mail configuration</h2>
    <dl class="dn-admin-email-test__diag" style="display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.2fr);gap:0.45rem 1rem;margin:0;font-size:0.9rem;">
      <dt>Brevo API configured</dt><dd><?= $diag['api_configured'] ? 'Yes' : 'No' ?></dd>
      <dt>cURL available</dt><dd><?= $diag['curl_available'] ? 'Yes' : 'No — required for Brevo API' ?></dd>
      <dt>OpenSSL available</dt><dd><?= $diag['openssl_available'] ? 'Yes' : 'No — required for HTTPS/TLS' ?></dd>
      <dt>SMTP configured</dt><dd><?= $diag['smtp_configured'] ? 'Yes' : 'No' ?></dd>
      <dt>SMTP host</dt><dd><code><?= htmlspecialchars((string) $diag['smtp_host'], ENT_QUOTES, 'UTF-8') ?></code></dd>
      <dt>SMTP port</dt><dd><?= (int) $diag['smtp_port'] ?> (587 STARTTLS, 465 SSL, 2525 STARTTLS)</dd>
      <dt>SMTP username configured</dt><dd><?= $diag['smtp_user_configured'] ? 'Yes' : 'No' ?></dd>
      <dt>SMTP password configured</dt><dd><?= $diag['smtp_pass_configured'] ? 'Yes' : 'No' ?></dd>
      <dt>From email</dt><dd><?= $diag['from_email'] !== '' ? htmlspecialchars((string) $diag['from_email'], ENT_QUOTES, 'UTF-8') : '<em>not set</em>' ?></dd>
      <dt>From name</dt><dd><?= htmlspecialchars((string) $diag['from_name'], ENT_QUOTES, 'UTF-8') ?></dd>
      <dt>ADMIN_EMAIL</dt><dd><?= $diag['admin_email'] !== '' ? htmlspecialchars((string) $diag['admin_email'], ENT_QUOTES, 'UTF-8') : '<em>not set</em>' ?></dd>
      <dt>Preferred method</dt><dd><?= $diag['api_configured'] ? 'Brevo API, then SMTP fallback' : ($diag['smtp_configured'] ? 'SMTP only' : '—') ?></dd>
    </dl>
    <?php if ($diag['api_configured'] && !$diag['curl_available']): ?>
      <p class="help-text" style="margin:0.75rem 0 0;color:#8f4329;">cURL is required for Brevo API sending. Enable the PHP cURL extension on this server.</p>
    <?php endif; ?>
  </section>

  <section class="glass-card dn-admin-email-test__form-card" style="padding:1.2rem 1.25rem;">
    <h2 class="dn-admin-email-test__h2">Send test email</h2>
    <form method="post" class="form-grid" style="gap:1rem;">
      <input type="hidden" name="csrf_token" value="<?= sanitize(csrf_token()) ?>">
      <div class="form-group" style="grid-column: 1 / -1;">
        <label for="recipient_email">Recipient email</label>
        <input id="recipient_email" name="recipient_email" type="email" required placeholder="you@example.com" value="<?= sanitize((string) ($_POST['recipient_email'] ?? $defaultAdminEmail)) ?>">
        <?php if ($defaultAdminEmail !== ''): ?>
          <p class="help-text" style="margin:0.35rem 0 0;">Admin alerts use <strong><?= sanitize($defaultAdminEmail) ?></strong> from <code>ADMIN_EMAIL</code> in <code>.env</code>.</p>
        <?php endif; ?>
      </div>
      <div class="form-group" style="grid-column: 1 / -1;">
        <label for="template">Template</label>
        <select id="template" name="template" required>
          <option value="">— Select template —</option>
          <?php foreach ($templateOptions as $value => $label): ?>
            <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= (string) ($_POST['template'] ?? '') === $value ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="grid-column: 1 / -1;">
        <button class="gradient-button" type="submit" <?= $mailConfigured ? '' : 'disabled' ?>>Send test email</button>
      </div>
    </form>
  </section>

  <section class="table-wrapper" style="margin-top:1.25rem;">
    <h3>Recent email logs (last 40)</h3>
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>When</th>
          <th>To</th>
          <th>Subject</th>
          <th>Template</th>
          <th>Status</th>
          <th>Error / note</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$logs): ?>
        <tr><td colspan="7" class="empty-state">No rows in email_logs yet.</td></tr>
      <?php endif; ?>
      <?php foreach ($logs as $row): ?>
        <tr>
          <td><?= (int) ($row['id'] ?? 0) ?></td>
          <td><?= sanitize((string) ($row['created_at'] ?? '')) ?></td>
          <td><?= sanitize((string) ($row['recipient_email'] ?? '')) ?></td>
          <td><?= sanitize((string) ($row['subject'] ?? '')) ?></td>
          <td><code style="font-size:0.78rem;"><?= sanitize((string) ($row['template_name'] ?? '—')) ?></code></td>
          <td><span class="status-badge"><?= sanitize((string) ($row['status'] ?? '')) ?></span></td>
          <td style="max-width:320px;font-size:0.85rem;word-break:break-word;"><?= sanitize((string) ($row['error_message'] ?? '—')) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </section>
</div>

<?php require_once dirname(__DIR__) . '/includes/dashboard_layout_end.php'; ?>
