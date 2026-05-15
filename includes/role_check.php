<?php
declare(strict_types=1);

/**
 * Role guard for dashboard pages. Call after auth_check.php.
 * Example: require_role(['ngo']); on ngo/create_campaign.php
 */

require_once __DIR__ . '/functions.php';

/** Stop with 403 unless $_SESSION['role'] is in $allowed_roles */
function require_role(array $allowed_roles): void
{
    $currentRole = $_SESSION['role'] ?? null;
    if (!$currentRole || !in_array($currentRole, $allowed_roles, true)) {
        http_response_code(403);
        exit('Unauthorized access for this role.');
    }
}
