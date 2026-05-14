<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function require_role(array $allowed_roles): void
{
    $currentRole = $_SESSION['role'] ?? null;
    if (!$currentRole || !in_array($currentRole, $allowed_roles, true)) {
        http_response_code(403);
        exit('Unauthorized access for this role.');
    }
}
