<?php
declare(strict_types=1);

/**
 * Outbound email: Brevo HTTP API or SMTP (see config/brevo.php + .env).
 * HTML layout in email_template.php. Admin alerts use admin_mail_recipients().
 */

require_once dirname(__DIR__) . '/config/brevo.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/smtp_mailer.php';
require_once __DIR__ . '/email_template.php';

/**
 * Active admin accounts (for in-app notifications).
 *
 * @return list<array{id: int, email: string}>
 */
function admin_users_for_notifications(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT id, email FROM users WHERE role = 'admin' AND account_status = 'active'");

    return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

/**
 * Email addresses that receive admin alerts (ADMIN_EMAIL in .env plus active admin users).
 *
 * @return list<string>
 */
function admin_mail_recipients(PDO $pdo): array
{
    $unique = [];

    $env = trim((string) env_value('ADMIN_EMAIL', ''));
    if ($env !== '') {
        foreach (preg_split('/\s*,\s*/', $env) as $part) {
            $part = trim($part);
            if ($part !== '' && filter_var($part, FILTER_VALIDATE_EMAIL)) {
                $unique[strtolower($part)] = $part;
            }
        }
    }

    foreach (admin_users_for_notifications($pdo) as $row) {
        $email = trim((string) ($row['email'] ?? ''));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $unique[strtolower($email)] = $email;
        }
    }

    return array_values($unique);
}

/** Primary admin inbox from .env (first ADMIN_EMAIL), if set. */
function admin_primary_email(): ?string
{
    $env = trim((string) env_value('ADMIN_EMAIL', ''));
    if ($env === '') {
        return null;
    }
    foreach (preg_split('/\s*,\s*/', $env) as $part) {
        $part = trim($part);
        if ($part !== '' && filter_var($part, FILTER_VALIDATE_EMAIL)) {
            return $part;
        }
    }

    return null;
}

function send_email(string $to, string $subject, string $htmlBody, ?int $user_id = null, ?string $template_name = null): bool
{
    return send_email_result($to, $subject, $htmlBody, $user_id, $template_name)['ok'];
}

/**
 * Send one transactional email via Brevo HTTP API; logs to email_logs.
 *
 * @return array{ok: bool, error: ?string}
 */
function send_email_result(string $to, string $subject, string $htmlBody, ?int $user_id = null, ?string $template_name = null): array
{
    $cfg = brevo_config();
    $transport = brevo_mail_transport();

    if ($transport === '') {
        $detail = 'Email not configured: set BREVO_FROM_EMAIL plus either BREVO_API_KEY (xkeysib-…) or BREVO_SMTP_USER + BREVO_SMTP_PASS (xsmtpsib-…).';
        try {
            $pdo = db();
            $stmt = $pdo->prepare('INSERT INTO email_logs (user_id, recipient_email, subject, template_name, status, error_message) VALUES (:user_id, :recipient_email, :subject, :template_name, :status, :error_message)');
            $stmt->execute([
                'user_id' => $user_id,
                'recipient_email' => $to,
                'subject' => $subject,
                'template_name' => $template_name,
                'status' => 'failed',
                'error_message' => $detail,
            ]);
        } catch (Throwable $e) {
        }

        return ['ok' => false, 'error' => $detail];
    }

    if ($transport === 'smtp') {
        $result = smtp_send_html_email([
            'host' => $cfg['smtp_host'],
            'port' => $cfg['smtp_port'],
            'user' => $cfg['smtp_user'],
            'pass' => $cfg['smtp_pass'],
            'from_email' => $cfg['from_email'],
            'from_name' => $cfg['from_name'],
        ], $to, $subject, $htmlBody);

        $ok = $result['ok'];
        $failureDetail = $result['error'] ?? 'SMTP send failed.';

        if (!$ok) {
            error_log('Brevo SMTP send_email failed: ' . $failureDetail);
        }

        try {
            $pdo = db();
            $stmt = $pdo->prepare('INSERT INTO email_logs (user_id, recipient_email, subject, template_name, status, error_message) VALUES (:user_id, :recipient_email, :subject, :template_name, :status, :error_message)');
            $stmt->execute([
                'user_id' => $user_id,
                'recipient_email' => $to,
                'subject' => $subject,
                'template_name' => $template_name,
                'status' => $ok ? 'sent' : 'failed',
                'error_message' => $ok ? null : $failureDetail,
            ]);
        } catch (Throwable $e) {
        }

        return ['ok' => $ok, 'error' => $ok ? null : $failureDetail];
    }

    $payload = [
        'sender' => ['name' => $cfg['from_name'], 'email' => $cfg['from_email']],
        'to' => [['email' => $to]],
        'subject' => $subject,
        'htmlContent' => $htmlBody,
    ];

    $json = json_encode($payload);
    if ($json === false) {
        $detail = 'Could not encode email payload as JSON.';
        try {
            $pdo = db();
            $stmt = $pdo->prepare('INSERT INTO email_logs (user_id, recipient_email, subject, template_name, status, error_message) VALUES (:user_id, :recipient_email, :subject, :template_name, :status, :error_message)');
            $stmt->execute([
                'user_id' => $user_id,
                'recipient_email' => $to,
                'subject' => $subject,
                'template_name' => $template_name,
                'status' => 'failed',
                'error_message' => $detail,
            ]);
        } catch (Throwable $e) {
        }

        return ['ok' => false, 'error' => $detail];
    }

    $ch = curl_init($cfg['api_url']);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $json,
        CURLOPT_HTTPHEADER => [
            'accept: application/json',
            'content-type: application/json',
            'api-key: ' . $cfg['api_key'],
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    ]);
    $raw = curl_exec($ch);
    $error = curl_error($ch);
    $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $failureDetail = $error;
    if ($failureDetail === '' && $raw !== false && $raw !== '') {
        $decoded = json_decode((string) $raw, true);
        if (is_array($decoded)) {
            if (isset($decoded['message'])) {
                $failureDetail = (string) $decoded['message'];
            } elseif (isset($decoded['code'])) {
                $failureDetail = 'HTTP ' . $statusCode . ' code ' . (string) $decoded['code'];
            }
        }
        if ($failureDetail === '') {
            $failureDetail = 'HTTP ' . $statusCode . ' ' . substr((string) $raw, 0, 400);
        }
    } elseif ($failureDetail === '') {
        $failureDetail = 'HTTP ' . $statusCode;
    }

    $ok = $error === '' && $statusCode >= 200 && $statusCode < 300;

    if (!$ok) {
        error_log('Brevo send_email failed: ' . $failureDetail);
    }

    try {
        $pdo = db();
        $stmt = $pdo->prepare('INSERT INTO email_logs (user_id, recipient_email, subject, template_name, status, error_message) VALUES (:user_id, :recipient_email, :subject, :template_name, :status, :error_message)');
        $stmt->execute([
            'user_id' => $user_id,
            'recipient_email' => $to,
            'subject' => $subject,
            'template_name' => $template_name,
            'status' => $ok ? 'sent' : 'failed',
            'error_message' => $ok ? null : $failureDetail,
        ]);
    } catch (Throwable $e) {
    }

    return ['ok' => $ok, 'error' => $ok ? null : $failureDetail];
}

function send_registration_email(array $user): bool
{
    $body = render_email_template('Welcome to Donate Now', '<p>Hello ' . htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8') . ', your account has been created successfully.</p>');
    return send_email($user['email'], 'Registration Successful', $body, (int) $user['id'], 'registration');
}

function send_new_ngo_registration_admin_email(string $adminEmail, array $ngoData): bool
{
    $msg = '<p>A new NGO has registered and is waiting verification.</p><p><strong>NGO:</strong> ' . htmlspecialchars($ngoData['ngo_name'], ENT_QUOTES, 'UTF-8') . '<br><strong>Email:</strong> ' . htmlspecialchars($ngoData['email'], ENT_QUOTES, 'UTF-8') . '</p>';
    $body = render_email_template('New NGO Registration Pending', $msg);
    return send_email($adminEmail, 'New NGO Registration', $body, null, 'new_ngo_admin_alert');
}

function send_ngo_verified_email(array $ngoUser): bool
{
    $body = render_email_template('NGO Verification Approved', '<p>Your NGO account has been verified.</p>', 'Login Now', APP_URL . '/auth/login.php');
    return send_email($ngoUser['email'], 'NGO Verified', $body, (int) $ngoUser['id'], 'ngo_verified');
}

function send_ngo_rejected_email(array $ngoUser, string $reason): bool
{
    $body = render_email_template('NGO Verification Update', '<p>Your NGO verification was not approved.</p><p><strong>Reason:</strong> ' . htmlspecialchars($reason, ENT_QUOTES, 'UTF-8') . '</p>');
    return send_email($ngoUser['email'], 'NGO Verification Rejected', $body, (int) $ngoUser['id'], 'ngo_rejected');
}

function send_account_status_email(array $user, string $status, string $reason): bool
{
    $body = render_email_template('Account Status Updated', '<p>Your account status is now: <strong>' . htmlspecialchars($status, ENT_QUOTES, 'UTF-8') . '</strong>.</p><p>' . htmlspecialchars($reason, ENT_QUOTES, 'UTF-8') . '</p>');
    return send_email($user['email'], 'Account Status Update', $body, (int) $user['id'], 'account_status_change');
}

function send_password_reset_email(array $user, string $resetLink): bool
{
    $body = render_email_template('Reset Your Password', '<p>Use the button below to reset your password. This link expires in 30 minutes.</p>', 'Reset Password', $resetLink);
    return send_email($user['email'], 'Password Reset', $body, (int) $user['id'], 'password_reset');
}

function send_new_campaign_submitted_email(string $adminEmail, array $campaignData): bool
{
    $msg = '<p>A new campaign has been submitted and is awaiting review.</p>'
        . '<p><strong>NGO:</strong> ' . htmlspecialchars($campaignData['ngo_name'] ?? '', ENT_QUOTES, 'UTF-8') . '<br>'
        . '<strong>Campaign:</strong> ' . htmlspecialchars($campaignData['title'] ?? '', ENT_QUOTES, 'UTF-8') . '<br>'
        . '<strong>Target:</strong> ' . htmlspecialchars((string) ($campaignData['target_amount'] ?? ''), ENT_QUOTES, 'UTF-8') . '</p>';
    $body = render_email_template('New Campaign Submitted', $msg, 'Review Campaigns', APP_URL . '/admin/manage_campaigns.php');
    return send_email($adminEmail, 'New Campaign Submitted', $body, null, 'campaign_submitted');
}

function send_campaign_status_email(array $ngoUser, string $campaignTitle, string $status, ?string $reason = null): bool
{
    $message = '<p>Your campaign status has been updated.</p>'
        . '<p><strong>Campaign:</strong> ' . htmlspecialchars($campaignTitle, ENT_QUOTES, 'UTF-8') . '<br>'
        . '<strong>Status:</strong> ' . htmlspecialchars($status, ENT_QUOTES, 'UTF-8') . '</p>';
    if ($reason) {
        $message .= '<p><strong>Reason:</strong> ' . htmlspecialchars($reason, ENT_QUOTES, 'UTF-8') . '</p>';
    }
    $body = render_email_template('Campaign Status Updated', $message, 'View Campaigns', APP_URL . '/ngo/my_campaigns.php');
    return send_email($ngoUser['email'], 'Campaign Status Update', $body, (int) $ngoUser['id'], 'campaign_status');
}

function send_new_donation_proof_email(array $ngoUser, array $donationData): bool
{
    $msg = '<p>A donor submitted a donation proof for verification.</p>'
        . '<p><strong>Donor:</strong> ' . htmlspecialchars($donationData['donor_name'] ?? '', ENT_QUOTES, 'UTF-8') . '<br>'
        . '<strong>Campaign:</strong> ' . htmlspecialchars($donationData['campaign_title'] ?? '', ENT_QUOTES, 'UTF-8') . '<br>'
        . '<strong>Amount:</strong> ' . htmlspecialchars((string) ($donationData['amount'] ?? ''), ENT_QUOTES, 'UTF-8') . '<br>'
        . '<strong>TID:</strong> ' . htmlspecialchars($donationData['transaction_reference'] ?? '', ENT_QUOTES, 'UTF-8') . '</p>';
    $body = render_email_template('New Donation Proof Received', $msg, 'Review Donations', APP_URL . '/ngo/donation_dashboard.php');
    return send_email($ngoUser['email'], 'New Donation Proof Received', $body, (int) $ngoUser['id'], 'donation_proof_received');
}

function send_donation_pending_email(array $donorUser, array $donationData): bool
{
    $msg = '<p>Your donation proof has been submitted and is pending NGO verification.</p>'
        . '<p><strong>Campaign:</strong> ' . htmlspecialchars($donationData['campaign_title'] ?? '', ENT_QUOTES, 'UTF-8') . '<br>'
        . '<strong>Amount:</strong> ' . htmlspecialchars((string) ($donationData['amount'] ?? ''), ENT_QUOTES, 'UTF-8') . '<br>'
        . '<strong>TID:</strong> ' . htmlspecialchars($donationData['transaction_reference'] ?? '', ENT_QUOTES, 'UTF-8') . '</p>';
    $body = render_email_template('Donation Submitted', $msg, 'My Donations', APP_URL . '/donor/my_donations.php');
    return send_email($donorUser['email'], 'Donation Pending Verification', $body, (int) $donorUser['id'], 'donation_pending');
}

function send_donation_confirmed_email(array $donorUser, array $donationData): bool
{
    $msg = '<p>Your donation has been verified and confirmed by the NGO.</p>'
        . '<p><strong>Campaign:</strong> ' . htmlspecialchars($donationData['campaign_title'] ?? '', ENT_QUOTES, 'UTF-8') . '<br>'
        . '<strong>Amount:</strong> ' . htmlspecialchars((string) ($donationData['amount'] ?? ''), ENT_QUOTES, 'UTF-8') . '</p>';
    $body = render_email_template('Donation Confirmed', $msg, 'View Donation', APP_URL . '/donor/my_donations.php');
    return send_email($donorUser['email'], 'Donation Confirmed', $body, (int) $donorUser['id'], 'donation_confirmed');
}

function send_donation_rejected_email(array $donorUser, array $donationData, string $reason): bool
{
    $msg = '<p>Your donation proof was rejected by the NGO.</p>'
        . '<p><strong>Campaign:</strong> ' . htmlspecialchars($donationData['campaign_title'] ?? '', ENT_QUOTES, 'UTF-8') . '<br>'
        . '<strong>Amount:</strong> ' . htmlspecialchars((string) ($donationData['amount'] ?? ''), ENT_QUOTES, 'UTF-8') . '<br>'
        . '<strong>Reason:</strong> ' . htmlspecialchars($reason, ENT_QUOTES, 'UTF-8') . '</p>';
    $body = render_email_template('Donation Rejected', $msg, 'View Donation', APP_URL . '/donor/my_donations.php');
    return send_email($donorUser['email'], 'Donation Rejected', $body, (int) $donorUser['id'], 'donation_rejected');
}

function send_flagged_donation_admin_email(string $adminEmail, array $donationData): bool
{
    $msg = '<p>A donation has been flagged by NGO and requires admin review.</p>'
        . '<p><strong>Campaign:</strong> ' . htmlspecialchars($donationData['campaign_title'] ?? '', ENT_QUOTES, 'UTF-8') . '<br>'
        . '<strong>Donor:</strong> ' . htmlspecialchars($donationData['donor_name'] ?? '', ENT_QUOTES, 'UTF-8') . '<br>'
        . '<strong>TID:</strong> ' . htmlspecialchars($donationData['transaction_reference'] ?? '', ENT_QUOTES, 'UTF-8') . '</p>';
    $body = render_email_template('Flagged Donation Alert', $msg, 'Open Donations', APP_URL . '/admin/all_donations.php');
    return send_email($adminEmail, 'Flagged Donation Alert', $body, null, 'donation_flagged');
}

function send_new_report_submitted_email(string $adminEmail, array $reportData): bool
{
    $msg = '<p>A new report has been submitted.</p>'
        . '<p><strong>Report ID:</strong> ' . htmlspecialchars((string)($reportData['report_id'] ?? ''), ENT_QUOTES, 'UTF-8') . '<br>'
        . '<strong>Subject:</strong> ' . htmlspecialchars($reportData['subject'] ?? '', ENT_QUOTES, 'UTF-8') . '<br>'
        . '<strong>Type:</strong> ' . htmlspecialchars($reportData['report_type'] ?? '', ENT_QUOTES, 'UTF-8') . '<br>'
        . '<strong>Reporter:</strong> ' . htmlspecialchars($reportData['reporter_name'] ?? '', ENT_QUOTES, 'UTF-8') . '</p>';
    $body = render_email_template('New Report Submitted', $msg, 'Open Reports', APP_URL . '/admin/reports.php');
    return send_email($adminEmail, 'New Report Submitted', $body, null, 'report_submitted_admin');
}

function send_report_received_email(array $reporterUser, string $subject): bool
{
    $msg = '<p>Your report has been received successfully and is currently open for review.</p>'
        . '<p><strong>Subject:</strong> ' . htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') . '</p>';
    $body = render_email_template('Report Received', $msg, 'View My Reports', APP_URL . '/reports/my_reports.php');
    return send_email($reporterUser['email'], 'Report Received', $body, (int)$reporterUser['id'], 'report_received_user');
}

function send_report_status_email(array $reporterUser, array $reportData): bool
{
    $msg = '<p>Your report status has been updated.</p>'
        . '<p><strong>Subject:</strong> ' . htmlspecialchars($reportData['subject'] ?? '', ENT_QUOTES, 'UTF-8') . '<br>'
        . '<strong>Status:</strong> ' . htmlspecialchars($reportData['status'] ?? '', ENT_QUOTES, 'UTF-8') . '<br>'
        . '<strong>Admin Note:</strong> ' . htmlspecialchars($reportData['admin_note'] ?? '', ENT_QUOTES, 'UTF-8') . '</p>';
    $body = render_email_template('Report Status Updated', $msg, 'View Report', APP_URL . '/reports/my_reports.php');
    return send_email($reporterUser['email'], 'Report Status Update', $body, (int)$reporterUser['id'], 'report_status_changed');
}

function send_volunteer_request_email(array $ngoUser, array $volunteerData): bool
{
    $msg = '<p>A volunteer requested to join your campaign.</p>'
        . '<p><strong>Volunteer:</strong> ' . htmlspecialchars($volunteerData['volunteer_name'] ?? '', ENT_QUOTES, 'UTF-8') . '<br>'
        . '<strong>Campaign:</strong> ' . htmlspecialchars($volunteerData['campaign_title'] ?? '', ENT_QUOTES, 'UTF-8') . '</p>';
    $body = render_email_template('New Volunteer Request', $msg, 'Review Requests', APP_URL . '/ngo/volunteer_requests.php');
    return send_email($ngoUser['email'], 'New Volunteer Request', $body, (int)$ngoUser['id'], 'volunteer_request_received');
}

function send_volunteer_request_status_email(array $volunteerUser, array $data): bool
{
    $msg = '<p>Your volunteer request has been updated by NGO.</p>'
        . '<p><strong>Campaign:</strong> ' . htmlspecialchars($data['campaign_title'] ?? '', ENT_QUOTES, 'UTF-8') . '<br>'
        . '<strong>Status:</strong> ' . htmlspecialchars($data['status'] ?? '', ENT_QUOTES, 'UTF-8') . '<br>'
        . '<strong>Note:</strong> ' . htmlspecialchars($data['ngo_note'] ?? '', ENT_QUOTES, 'UTF-8') . '</p>';
    $body = render_email_template('Volunteer Request Update', $msg, 'My Campaign Requests', APP_URL . '/volunteer/my_campaigns.php');
    return send_email($volunteerUser['email'], 'Volunteer Request Update', $body, (int)$volunteerUser['id'], 'volunteer_request_status');
}

/**
 * Read the latest email_logs row for an inbox (optionally scoped to a template name).
 */
function admin_last_email_error_for_recipient(string $recipientEmail, ?string $templateName = null): ?string
{
    try {
        $pdo = db();
        if ($templateName !== null && $templateName !== '') {
            $stmt = $pdo->prepare('SELECT error_message, status FROM email_logs WHERE recipient_email = :e AND template_name = :t ORDER BY id DESC LIMIT 1');
            $stmt->execute(['e' => $recipientEmail, 't' => $templateName]);
        } else {
            $stmt = $pdo->prepare('SELECT error_message, status FROM email_logs WHERE recipient_email = :e ORDER BY id DESC LIMIT 1');
            $stmt->execute(['e' => $recipientEmail]);
        }
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return 'Send failed (no log row).';
        }
        if (($row['status'] ?? '') === 'sent') {
            return null;
        }

        return (string) ($row['error_message'] ?? 'Send failed.');
    } catch (Throwable $e) {
        return 'Could not read email_logs.';
    }
}

/**
 * Admin-only: send a sample of a production template to any inbox for QA.
 *
 * @return array{ok: bool, error: ?string, template: string}
 */
function admin_send_test_email(string $recipientEmail, string $templateKey, int $adminUserId): array
{
    $recipientEmail = trim($recipientEmail);
    if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Invalid email address.', 'template' => $templateKey];
    }

    $mockUser = [
        'id' => $adminUserId,
        'full_name' => 'Test recipient',
        'email' => $recipientEmail,
    ];

    $logSuffix = '_admin_test';

    $fromResult = static function (array $result, string $template): array {
        return [
            'ok' => $result['ok'],
            'error' => $result['error'],
            'template' => $template,
        ];
    };

    $fromBool = static function (bool $ok, string $template, string $recipient): array {
        return [
            'ok' => $ok,
            'error' => $ok ? null : admin_last_email_error_for_recipient($recipient, $template),
            'template' => $template,
        ];
    };

    switch ($templateKey) {
        case 'plain':
            $body = render_email_template(
                '[TEST] Donate Now — connectivity',
                '<p>This is a <strong>test message</strong> from the admin email tester. If you see this, outbound mail is working.</p>',
                'Open site',
                APP_URL . '/index.php'
            );

            return $fromResult(send_email_result($recipientEmail, '[TEST] Donate Now — plain', $body, $adminUserId, 'plain' . $logSuffix), 'plain' . $logSuffix);

        case 'registration':
            $body = render_email_template(
                '[TEST] Welcome to Donate Now',
                '<p>Hello ' . htmlspecialchars($mockUser['full_name'], ENT_QUOTES, 'UTF-8') . ', your account has been created successfully. <em>(Admin test — not a new account.)</em></p>'
            );

            return $fromResult(send_email_result($recipientEmail, '[TEST] Registration Successful', $body, $adminUserId, 'registration' . $logSuffix), 'registration' . $logSuffix);

        case 'password_reset':
            $link = APP_URL . '/auth/reset_password.php?token=demo-test-token-invalid';

            return $fromResult(send_email_result(
                $recipientEmail,
                '[TEST] Password Reset',
                render_email_template('[TEST] Reset Your Password', '<p>Use the button below to reset your password. <em>This is a demo link and will not work.</em></p>', 'Reset Password (demo)', $link),
                $adminUserId,
                'password_reset' . $logSuffix
            ), 'password_reset' . $logSuffix);

        case 'ngo_verified':
            return $fromResult(send_email_result(
                $recipientEmail,
                '[TEST] NGO Verified',
                render_email_template('[TEST] NGO Verification Approved', '<p>Your NGO account has been verified. <em>(Admin test.)</em></p>', 'Login Now', APP_URL . '/auth/login.php'),
                $adminUserId,
                'ngo_verified' . $logSuffix
            ), 'ngo_verified' . $logSuffix);

        case 'ngo_rejected':
            return $fromResult(send_email_result(
                $recipientEmail,
                '[TEST] NGO Verification Rejected',
                render_email_template('[TEST] NGO Verification Update', '<p>Your NGO verification was not approved.</p><p><strong>Reason:</strong> Demo rejection text from admin email tester.</p>'),
                $adminUserId,
                'ngo_rejected' . $logSuffix
            ), 'ngo_rejected' . $logSuffix);

        case 'account_status':
            return $fromResult(send_email_result(
                $recipientEmail,
                '[TEST] Account Status Update',
                render_email_template('[TEST] Account Status Updated', '<p>Your account status is now: <strong>temporary_hold</strong>.</p><p>Demo notice from admin email tester.</p>'),
                $adminUserId,
                'account_status_change' . $logSuffix
            ), 'account_status_change' . $logSuffix);

        case 'new_ngo_admin':
            $ngoData = ['ngo_name' => 'Demo NGO (test)', 'email' => 'demo-ngo@example.com'];

            return $fromResult(send_email_result(
                $recipientEmail,
                '[TEST] New NGO Registration',
                render_email_template('[TEST] New NGO Registration Pending', '<p>A new NGO has registered and is waiting verification.</p><p><strong>NGO:</strong> ' . htmlspecialchars($ngoData['ngo_name'], ENT_QUOTES, 'UTF-8') . '<br><strong>Email:</strong> ' . htmlspecialchars($ngoData['email'], ENT_QUOTES, 'UTF-8') . '</p>'),
                $adminUserId,
                'new_ngo_admin_alert' . $logSuffix
            ), 'new_ngo_admin_alert' . $logSuffix);

        case 'campaign_submitted':
            $campaignData = ['ngo_name' => 'Demo NGO', 'title' => 'Winter relief fund', 'target_amount' => '500000'];
            $msg = '<p>A new campaign has been submitted and is awaiting review.</p>'
                . '<p><strong>NGO:</strong> ' . htmlspecialchars($campaignData['ngo_name'], ENT_QUOTES, 'UTF-8') . '<br>'
                . '<strong>Campaign:</strong> ' . htmlspecialchars($campaignData['title'], ENT_QUOTES, 'UTF-8') . '<br>'
                . '<strong>Target:</strong> ' . htmlspecialchars((string) $campaignData['target_amount'], ENT_QUOTES, 'UTF-8') . '</p>';

            return $fromResult(send_email_result(
                $recipientEmail,
                '[TEST] New Campaign Submitted',
                render_email_template('[TEST] New Campaign Submitted', $msg, 'Review Campaigns', APP_URL . '/admin/manage_campaigns.php'),
                $adminUserId,
                'campaign_submitted' . $logSuffix
            ), 'campaign_submitted' . $logSuffix);

        case 'campaign_status':
            return $fromBool(
                send_campaign_status_email($mockUser, 'Demo campaign title', 'approved', 'Demo approval note from tester.'),
                'campaign_status',
                $recipientEmail
            );

        case 'donation_pending':
            $donationData = ['campaign_title' => 'Demo campaign', 'amount' => '25000', 'transaction_reference' => 'TEST-TID-001'];

            return $fromBool(send_donation_pending_email($mockUser, $donationData), 'donation_pending', $recipientEmail);

        case 'donation_confirmed':
            $donationData = ['campaign_title' => 'Demo campaign', 'amount' => '25000'];

            return $fromBool(send_donation_confirmed_email($mockUser, $donationData), 'donation_confirmed', $recipientEmail);

        case 'donation_rejected':
            $donationData = ['campaign_title' => 'Demo campaign', 'amount' => '25000'];

            return $fromBool(send_donation_rejected_email($mockUser, $donationData, 'Demo rejection from admin tester.'), 'donation_rejected', $recipientEmail);

        case 'donation_proof_ngo':
            $donationData = ['donor_name' => 'Demo donor', 'campaign_title' => 'Demo campaign', 'amount' => '25000', 'transaction_reference' => 'TEST-TID-002'];

            return $fromBool(send_new_donation_proof_email($mockUser, $donationData), 'donation_proof_received', $recipientEmail);

        case 'donation_flagged_admin':
            $donationData = ['campaign_title' => 'Demo campaign', 'donor_name' => 'Demo donor', 'transaction_reference' => 'TEST-TID-003'];

            return $fromBool(send_flagged_donation_admin_email($recipientEmail, $donationData), 'donation_flagged', $recipientEmail);

        case 'report_submitted_admin':
            $reportData = ['report_id' => '0', 'subject' => 'Demo report subject', 'report_type' => 'spam', 'reporter_name' => 'Demo reporter'];

            return $fromBool(send_new_report_submitted_email($recipientEmail, $reportData), 'report_submitted_admin', $recipientEmail);

        case 'report_received':
            return $fromBool(send_report_received_email($mockUser, 'Demo report subject'), 'report_received_user', $recipientEmail);

        case 'report_status':
            $reportData = ['subject' => 'Demo report', 'status' => 'under_review', 'admin_note' => 'Demo admin note from tester.'];

            return $fromBool(send_report_status_email($mockUser, $reportData), 'report_status_changed', $recipientEmail);

        case 'volunteer_request':
            $volunteerData = ['volunteer_name' => 'Demo volunteer', 'campaign_title' => 'Demo campaign'];

            return $fromBool(send_volunteer_request_email($mockUser, $volunteerData), 'volunteer_request_received', $recipientEmail);

        case 'volunteer_request_status':
            $data = ['campaign_title' => 'Demo campaign', 'status' => 'accepted', 'ngo_note' => 'Welcome aboard (demo).'];

            return $fromBool(send_volunteer_request_status_email($mockUser, $data), 'volunteer_request_status', $recipientEmail);

        default:
            return ['ok' => false, 'error' => 'Unknown template.', 'template' => $templateKey];
    }
}
