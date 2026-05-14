<?php
declare(strict_types=1);

function create_notification(PDO $pdo, int $user_id, string $title, string $message, string $type = 'general'): bool
{
    $stmt = $pdo->prepare('INSERT INTO notifications (user_id, title, message, type) VALUES (:user_id, :title, :message, :type)');
    return $stmt->execute([
        'user_id' => $user_id,
        'title' => $title,
        'message' => $message,
        'type' => $type,
    ]);
}

function get_unread_notification_count(PDO $pdo, int $user_id): int
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = 0');
    $stmt->execute(['user_id' => $user_id]);
    return (int) $stmt->fetchColumn();
}

function mark_notification_read(PDO $pdo, int $notification_id, int $user_id): bool
{
    $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :user_id');
    return $stmt->execute([
        'id' => $notification_id,
        'user_id' => $user_id,
    ]);
}

function mark_all_notifications_read(PDO $pdo, int $user_id): bool
{
    $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = :user_id');
    return $stmt->execute([
        'user_id' => $user_id,
    ]);
}
