<?php
declare(strict_types=1);

require_once __DIR__ . '/notification_helper.php';

function get_admin_dashboard_stats(PDO $pdo): array
{
    return [
        'total_users' => (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
        'active_users' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE account_status='active'")->fetchColumn(),
        'blocked_suspended_hold_users' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE account_status IN ('blocked','suspended','temporary_hold')")->fetchColumn(),
        'total_donors' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='donor'")->fetchColumn(),
        'total_ngos' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='ngo'")->fetchColumn(),
        'verified_ngos' => (int)$pdo->query("SELECT COUNT(*) FROM ngo_profiles WHERE verification_status='verified'")->fetchColumn(),
        'pending_ngos' => (int)$pdo->query("SELECT COUNT(*) FROM ngo_profiles WHERE verification_status='pending'")->fetchColumn(),
        'total_volunteers' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='volunteer'")->fetchColumn(),
        'total_campaigns' => (int)$pdo->query("SELECT COUNT(*) FROM campaigns")->fetchColumn(),
        'pending_campaigns' => (int)$pdo->query("SELECT COUNT(*) FROM campaigns WHERE status='pending'")->fetchColumn(),
        'active_campaigns' => (int)$pdo->query("SELECT COUNT(*) FROM campaigns WHERE status='active'")->fetchColumn(),
        'completed_campaigns' => (int)$pdo->query("SELECT COUNT(*) FROM campaigns WHERE status='completed'")->fetchColumn(),
        'total_donations' => (int)$pdo->query("SELECT COUNT(*) FROM donations")->fetchColumn(),
        'pending_donations' => (int)$pdo->query("SELECT COUNT(*) FROM donations WHERE status='pending'")->fetchColumn(),
        'confirmed_donations' => (int)$pdo->query("SELECT COUNT(*) FROM donations WHERE status='confirmed'")->fetchColumn(),
        'rejected_donations' => (int)$pdo->query("SELECT COUNT(*) FROM donations WHERE status='rejected'")->fetchColumn(),
        'total_confirmed_donation_amount' => (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM donations WHERE status='confirmed'")->fetchColumn(),
        'total_reports' => (int)$pdo->query("SELECT COUNT(*) FROM reports")->fetchColumn(),
        'open_reports' => (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE status='open'")->fetchColumn(),
        'reports_under_review' => (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE status='under_review'")->fetchColumn(),
    ];
}

function get_ngo_dashboard_stats(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare('SELECT id, verification_status FROM ngo_profiles WHERE user_id = :user_id LIMIT 1');
    $stmt->execute(['user_id' => $userId]);
    $ngo = $stmt->fetch();
    $ngoId = (int)($ngo['id'] ?? 0);

    return [
        'verification_status' => $ngo['verification_status'] ?? 'pending',
        'total_payment_methods' => $ngoId ? (int)$pdo->query("SELECT COUNT(*) FROM ngo_payment_methods WHERE ngo_id={$ngoId}")->fetchColumn() : 0,
        'total_campaigns' => $ngoId ? (int)$pdo->query("SELECT COUNT(*) FROM campaigns WHERE ngo_id={$ngoId}")->fetchColumn() : 0,
        'active_campaigns' => $ngoId ? (int)$pdo->query("SELECT COUNT(*) FROM campaigns WHERE ngo_id={$ngoId} AND status='active'")->fetchColumn() : 0,
        'completed_campaigns' => $ngoId ? (int)$pdo->query("SELECT COUNT(*) FROM campaigns WHERE ngo_id={$ngoId} AND status='completed'")->fetchColumn() : 0,
        'pending_donation_proofs' => $ngoId ? (int)$pdo->query("SELECT COUNT(*) FROM donations WHERE ngo_id={$ngoId} AND status='pending'")->fetchColumn() : 0,
        'confirmed_donations' => $ngoId ? (int)$pdo->query("SELECT COUNT(*) FROM donations WHERE ngo_id={$ngoId} AND status='confirmed'")->fetchColumn() : 0,
        'confirmed_received_amount' => $ngoId ? (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM donations WHERE ngo_id={$ngoId} AND status='confirmed'")->fetchColumn() : 0.0,
        'volunteer_pending_requests' => $ngoId ? (int)$pdo->query("SELECT COUNT(*) FROM volunteer_campaigns WHERE ngo_id={$ngoId} AND status='pending'")->fetchColumn() : 0,
        'reports_related' => $ngoId ? (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE reported_user_id={$userId} OR reported_campaign_id IN (SELECT id FROM campaigns WHERE ngo_id={$ngoId})")->fetchColumn() : 0,
        'unread_notifications' => get_unread_notification_count($pdo, $userId),
    ];
}

function get_donor_dashboard_stats(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare('SELECT id FROM donor_profiles WHERE user_id = :user_id LIMIT 1');
    $stmt->execute(['user_id' => $userId]);
    $donorId = (int)($stmt->fetchColumn() ?: 0);

    $recent = [];
    if ($donorId) {
        $stmt = $pdo->prepare("SELECT d.amount, d.status, d.created_at, c.title AS campaign_title FROM donations d INNER JOIN campaigns c ON c.id = d.campaign_id WHERE d.donor_id = :donor_id ORDER BY d.created_at DESC LIMIT 5");
        $stmt->execute(['donor_id' => $donorId]);
        $recent = $stmt->fetchAll();
    }

    return [
        'total_donations_submitted' => $donorId ? (int)$pdo->query("SELECT COUNT(*) FROM donations WHERE donor_id={$donorId}")->fetchColumn() : 0,
        'confirmed_donation_count' => $donorId ? (int)$pdo->query("SELECT COUNT(*) FROM donations WHERE donor_id={$donorId} AND status='confirmed'")->fetchColumn() : 0,
        'confirmed_donation_amount' => $donorId ? (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM donations WHERE donor_id={$donorId} AND status='confirmed'")->fetchColumn() : 0.0,
        'pending_donations' => $donorId ? (int)$pdo->query("SELECT COUNT(*) FROM donations WHERE donor_id={$donorId} AND status='pending'")->fetchColumn() : 0,
        'rejected_donations' => $donorId ? (int)$pdo->query("SELECT COUNT(*) FROM donations WHERE donor_id={$donorId} AND status='rejected'")->fetchColumn() : 0,
        'recent_donations' => $recent,
        'unread_notifications' => get_unread_notification_count($pdo, $userId),
    ];
}

function get_volunteer_dashboard_stats(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare('SELECT id FROM volunteer_profiles WHERE user_id = :user_id LIMIT 1');
    $stmt->execute(['user_id' => $userId]);
    $volunteerId = (int)($stmt->fetchColumn() ?: 0);

    return [
        'total_campaign_requests' => $volunteerId ? (int)$pdo->query("SELECT COUNT(*) FROM volunteer_campaigns WHERE volunteer_id={$volunteerId}")->fetchColumn() : 0,
        'pending' => $volunteerId ? (int)$pdo->query("SELECT COUNT(*) FROM volunteer_campaigns WHERE volunteer_id={$volunteerId} AND status='pending'")->fetchColumn() : 0,
        'accepted' => $volunteerId ? (int)$pdo->query("SELECT COUNT(*) FROM volunteer_campaigns WHERE volunteer_id={$volunteerId} AND status='accepted'")->fetchColumn() : 0,
        'rejected' => $volunteerId ? (int)$pdo->query("SELECT COUNT(*) FROM volunteer_campaigns WHERE volunteer_id={$volunteerId} AND status='rejected'")->fetchColumn() : 0,
        'unread_notifications' => get_unread_notification_count($pdo, $userId),
    ];
}
