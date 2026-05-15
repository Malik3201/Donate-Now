<?php
declare(strict_types=1);

/**
 * One-off migration runner (CLI or browser as admin).
 * Usage: php database/run_migration.php
 */

require_once dirname(__DIR__) . '/config/database.php';

$pdo = db();

$statements = [
    "ALTER TABLE ngo_profiles ADD COLUMN latitude DECIMAL(10, 8) NULL AFTER address",
    "ALTER TABLE ngo_profiles ADD COLUMN longitude DECIMAL(11, 8) NULL AFTER latitude",
    "ALTER TABLE campaigns ADD COLUMN location_label VARCHAR(255) NULL AFTER description",
    "ALTER TABLE campaigns ADD COLUMN latitude DECIMAL(10, 8) NULL AFTER location_label",
    "ALTER TABLE campaigns ADD COLUMN longitude DECIMAL(11, 8) NULL AFTER latitude",
];

foreach ($statements as $sql) {
    try {
        $pdo->exec($sql);
        echo "OK: {$sql}\n";
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'Duplicate column')) {
            echo "SKIP (exists): {$sql}\n";
        } else {
            echo "FAIL: {$sql}\n  " . $e->getMessage() . "\n";
            exit(1);
        }
    }
}

echo "Migration complete.\n";
