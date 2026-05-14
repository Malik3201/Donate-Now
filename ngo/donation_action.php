<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_once dirname(__DIR__) . '/includes/role_check.php';
require_once dirname(__DIR__) . '/includes/notification_helper.php';
require_once dirname(__DIR__) . '/includes/mail_helper.php';

require_role(['ngo']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed.'); }
if (!verify_csrf_token($_POST['csrf_token'] ?? null)) exit('Invalid CSRF token.');

$pdo = db();
$donationId = intval($_POST['donation_id'] ?? 0);
$action = trim((string)($_POST['action'] ?? ''));
$note = trim((string)($_POST['note'] ?? ''));
if (!in_array($action, ['confirm', 'reject', 'flag'], true)) exit('Invalid action.');
if (in_array($action, ['reject', 'flag'], true) && $note === '') exit('Note is required for reject/flag.');

$stmt = $pdo->prepare('SELECT id FROM ngo_profiles WHERE user_id = :user_id LIMIT 1');
$stmt->execute(['user_id' => (int)$authUser['id']]);
$ngo = $stmt->fetch();
if (!$ngo) exit('NGO profile not found.');

$stmt = $pdo->prepare("SELECT d.*, c.title AS campaign_title, u.id AS donor_user_id, u.email AS donor_email, u.full_name AS donor_name
FROM donations d
INNER JOIN campaigns c ON c.id = d.campaign_id
INNER JOIN donor_profiles dp ON dp.id = d.donor_id
INNER JOIN users u ON u.id = dp.user_id
WHERE d.id = :id AND d.ngo_id = :ngo_id LIMIT 1");
$stmt->execute(['id' => $donationId, 'ngo_id' => (int)$ngo['id']]);
$donation = $stmt->fetch();
if (!$donation) exit('Donation not found or unauthorized.');

$oldStatus = $donation['status'];
if ($oldStatus === 'confirmed' && $action === 'confirm') exit('Donation is already confirmed.');

if ($action === 'confirm') {
    if ($donation['status'] !== 'pending') exit('Only pending donations can be confirmed.');

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("UPDATE donations SET status='confirmed', ngo_verification_note=:note, confirmed_at=NOW(), updated_at=NOW() WHERE id=:id AND status='pending'");
        $stmt->execute(['note' => $note ?: 'Confirmed by NGO.', 'id' => $donationId]);
        if ($stmt->rowCount() === 0) {
            throw new RuntimeException('Donation state changed. Try again.');
        }

        $stmt = $pdo->prepare('UPDATE campaigns SET collected_amount = collected_amount + :amount, updated_at=NOW() WHERE id = :campaign_id');
        $stmt->execute(['amount' => $donation['amount'], 'campaign_id' => $donation['campaign_id']]);

        $stmt = $pdo->prepare('UPDATE donor_profiles SET total_donations_count = total_donations_count + 1, total_donated_amount = total_donated_amount + :amount, updated_at=NOW() WHERE id = :donor_id');
        $stmt->execute(['amount' => $donation['amount'], 'donor_id' => $donation['donor_id']]);

        $stmt = $pdo->prepare('UPDATE ngo_profiles SET total_confirmed_donations_count = total_confirmed_donations_count + 1, total_received_amount = total_received_amount + :amount, updated_at=NOW() WHERE id = :ngo_id');
        $stmt->execute(['amount' => $donation['amount'], 'ngo_id' => $donation['ngo_id']]);

        $stmt = $pdo->prepare('INSERT INTO donation_status_history (donation_id, changed_by_user_id, old_status, new_status, note) VALUES (:donation_id, :changed_by_user_id, :old_status, :new_status, :note)');
        $stmt->execute([
            'donation_id' => $donationId,
            'changed_by_user_id' => (int)$authUser['id'],
            'old_status' => $oldStatus,
            'new_status' => 'confirmed',
            'note' => $note ?: 'Confirmed by NGO.',
        ]);

        $pdo->commit();
        create_notification($pdo, (int)$donation['donor_user_id'], 'Donation Confirmed', 'Your donation for ' . $donation['campaign_title'] . ' has been confirmed.', 'donation');
        send_donation_confirmed_email(['id' => (int)$donation['donor_user_id'], 'email' => $donation['donor_email']], ['campaign_title' => $donation['campaign_title'], 'amount' => (string)$donation['amount']]);
        log_activity($pdo, (int)$authUser['id'], 'donation_confirmed', 'donations', $donationId, 'Donation confirmed by NGO');
    } catch (Throwable $e) {
        $pdo->rollBack();
        exit('Failed to confirm donation.');
    }
} elseif ($action === 'reject') {
    $stmt = $pdo->prepare('UPDATE donations SET status=:status, ngo_verification_note=:note, rejected_at=NOW(), updated_at=NOW() WHERE id=:id');
    $stmt->execute(['status' => 'rejected', 'note' => $note, 'id' => $donationId]);

    $stmt = $pdo->prepare('INSERT INTO donation_status_history (donation_id, changed_by_user_id, old_status, new_status, note) VALUES (:donation_id, :changed_by_user_id, :old_status, :new_status, :note)');
    $stmt->execute([
        'donation_id' => $donationId,
        'changed_by_user_id' => (int)$authUser['id'],
        'old_status' => $oldStatus,
        'new_status' => 'rejected',
        'note' => $note,
    ]);

    create_notification($pdo, (int)$donation['donor_user_id'], 'Donation Rejected', 'Your donation proof was rejected. Reason: ' . $note, 'donation');
    send_donation_rejected_email(['id' => (int)$donation['donor_user_id'], 'email' => $donation['donor_email']], ['campaign_title' => $donation['campaign_title'], 'amount' => (string)$donation['amount']], $note);
    log_activity($pdo, (int)$authUser['id'], 'donation_rejected', 'donations', $donationId, 'Donation rejected by NGO');
} else {
    $stmt = $pdo->prepare('UPDATE donations SET status=:status, ngo_verification_note=:note, updated_at=NOW() WHERE id=:id');
    $stmt->execute(['status' => 'flagged', 'note' => $note, 'id' => $donationId]);

    $stmt = $pdo->prepare('INSERT INTO donation_status_history (donation_id, changed_by_user_id, old_status, new_status, note) VALUES (:donation_id, :changed_by_user_id, :old_status, :new_status, :note)');
    $stmt->execute([
        'donation_id' => $donationId,
        'changed_by_user_id' => (int)$authUser['id'],
        'old_status' => $oldStatus,
        'new_status' => 'flagged',
        'note' => $note,
    ]);

    $admins = $pdo->query("SELECT id, email FROM users WHERE role='admin' AND account_status='active'")->fetchAll();
    foreach ($admins as $admin) {
        create_notification($pdo, (int)$admin['id'], 'Donation Flagged', 'A donation has been flagged by NGO for admin review.', 'donation');
        send_flagged_donation_admin_email((string)$admin['email'], [
            'campaign_title' => $donation['campaign_title'],
            'donor_name' => $donation['donor_name'],
            'transaction_reference' => $donation['transaction_reference'],
        ]);
    }
    log_activity($pdo, (int)$authUser['id'], 'donation_flagged', 'donations', $donationId, 'Donation flagged by NGO');
}

redirect('ngo/donation_dashboard.php');
