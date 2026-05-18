<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

session_unset();
session_destroy();

redirect_to('auth/login.php');
