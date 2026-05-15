<?php
declare(strict_types=1);

/**
 * DEV ONLY: creates default admin (admin@localdonation.com / admin123) if missing.
 * Run once locally: php database/seed.php — do not use on production.
 */

require_once dirname(__DIR__) . '/config/database.php';

$pdo = db();

$email = 'admin@localdonation.com';
$password = 'admin123';

$stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
$stmt->execute(['email' => $email]);
if (!$stmt->fetch()) {
    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password, role, account_status, email_verified) VALUES (:full_name, :email, :phone, :password, 'admin', 'active', 1)");
    $stmt->execute([
        'full_name' => 'System Admin',
        'email' => $email,
        'phone' => null,
        'password' => password_hash($password, PASSWORD_DEFAULT),
    ]);
    echo "Default admin created.\n";
} else {
    echo "Default admin already exists.\n";
}

$categories = ['Education', 'Health', 'Food', 'Emergency', 'Orphan Support', 'Community Welfare'];
$stmt = $pdo->prepare('INSERT IGNORE INTO campaign_categories (name, status) VALUES (:name, :status)');
foreach ($categories as $category) {
    $stmt->execute([
        'name' => $category,
        'status' => 'active',
    ]);
}
echo "Default campaign categories seeded.\n";
