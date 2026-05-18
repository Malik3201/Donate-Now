<?php
declare(strict_types=1);

/**
 * Core helpers used on almost every page.
 * Full project map: see includes/CODE_GUIDE.php
 *
 * Typical public page:  require config/database.php + this file
 * Typical dashboard:    auth_check.php (loads database + this file)
 */

require_once dirname(__DIR__) . '/config/app.php';

// --- Output & navigation ---

/** Escape HTML for safe echo in templates */
function sanitize(?string $data): string
{
    return htmlspecialchars(trim((string) $data), ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    redirect_to($path);
}

/** HTTP redirect to an app route or absolute URL. */
function redirect_to(string $path): void
{
    if (preg_match('#^https?://#i', $path)) {
        header('Location: ' . $path);
        exit;
    }

    header('Location: ' . app_url($path));
    exit;
}

// --- Session / user ---

function is_logged_in(): bool
{
    return isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] > 0;
}

/** Load the logged-in row from `users` (null if guest) */
function current_user(PDO $pdo): ?array
{
    if (!is_logged_in()) {
        return null;
    }
    return get_user_by_id($pdo, (int) $_SESSION['user_id']);
}

function get_user_by_id(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT id, full_name, email, phone, role, profile_photo_url, account_status, status_reason, email_verified, created_at FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function check_account_active(array $user): bool
{
    return isset($user['account_status']) && $user['account_status'] === 'active';
}

function json_response(bool $status, string $message, array $data = []): void
{
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data,
    ]);
    exit;
}

function log_activity(PDO $pdo, ?int $actor_user_id, string $action, ?string $entity_type = null, ?int $entity_id = null, ?string $description = null): void
{
    $stmt = $pdo->prepare('INSERT INTO activity_logs (actor_user_id, action, entity_type, entity_id, description, ip_address) VALUES (:actor_user_id, :action, :entity_type, :entity_id, :description, :ip_address)');
    $stmt->execute([
        'actor_user_id' => $actor_user_id,
        'action' => $action,
        'entity_type' => $entity_type,
        'entity_id' => $entity_id,
        'description' => $description,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}

// --- Security (forms) ---

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool
{
    return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string) $token);
}

/** @return 'weak'|'fair'|'strong' */
function auth_password_strength_tier(string $password): string
{
    $len = strlen($password);
    $classes = 0;
    if (preg_match('/[a-z]/', $password)) {
        $classes++;
    }
    if (preg_match('/[A-Z]/', $password)) {
        $classes++;
    }
    if (preg_match('/[0-9]/', $password)) {
        $classes++;
    }
    if (preg_match('/[^A-Za-z0-9]/', $password)) {
        $classes++;
    }
    if ($len < 8) {
        return 'weak';
    }
    if ($classes >= 3) {
        return 'strong';
    }
    if ($classes >= 2) {
        return 'fair';
    }

    return 'weak';
}
