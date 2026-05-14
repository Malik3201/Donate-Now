<?php
declare(strict_types=1);

require_once __DIR__ . '/notification_helper.php';
require_once __DIR__ . '/mail_helper.php';
require_once __DIR__ . '/functions.php';

function create_report_record(PDO $pdo, array $payload): int
{
    $reportedUserId = (int) ($payload['reported_user_id'] ?? 0);
    $reportedCampaignId = (int) ($payload['reported_campaign_id'] ?? 0);
    $reportedDonationId = (int) ($payload['reported_donation_id'] ?? 0);

    $stmt = $pdo->prepare('INSERT INTO reports (reporter_user_id, reported_user_id, reported_campaign_id, reported_donation_id, report_type, subject, description, attachment_url, attachment_imagekit_file_id, status) VALUES (:reporter_user_id, :reported_user_id, :reported_campaign_id, :reported_donation_id, :report_type, :subject, :description, :attachment_url, :attachment_imagekit_file_id, :status)');
    $stmt->execute([
        'reporter_user_id' => $payload['reporter_user_id'],
        'reported_user_id' => $reportedUserId > 0 ? $reportedUserId : null,
        'reported_campaign_id' => $reportedCampaignId > 0 ? $reportedCampaignId : null,
        'reported_donation_id' => $reportedDonationId > 0 ? $reportedDonationId : null,
        'report_type' => $payload['report_type'],
        'subject' => $payload['subject'],
        'description' => $payload['description'],
        'attachment_url' => $payload['attachment_url'] ?: null,
        'attachment_imagekit_file_id' => $payload['attachment_imagekit_file_id'] ?: null,
        'status' => 'open',
    ]);
    return (int)$pdo->lastInsertId();
}

function notify_admins_for_report(PDO $pdo, int $reportId, string $subject, string $reportType, string $reporterName): void
{
    $admins = $pdo->query("SELECT id, email FROM users WHERE role='admin' AND account_status='active'")->fetchAll();
    foreach ($admins as $admin) {
        create_notification($pdo, (int)$admin['id'], 'New Report Submitted', 'New report received: ' . $subject, 'report');
        send_new_report_submitted_email((string)$admin['email'], [
            'report_id' => $reportId,
            'subject' => $subject,
            'report_type' => $reportType,
            'reporter_name' => $reporterName,
        ]);
    }
}
