<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/functions.php';

$pdo = db();

if (!is_logged_in()) {
    redirect('auth/login.php');
}

$authUser = current_user($pdo);
if (!$authUser) {
    session_destroy();
    redirect('auth/login.php');
}

if (!check_account_active($authUser)) {
    $status = $authUser['account_status'] ?? 'inactive';
    session_destroy();
    exit('Access restricted. Account status: ' . sanitize((string) $status));
}
