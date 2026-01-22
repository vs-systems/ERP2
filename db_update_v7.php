<?php
require_once 'src/config/config.php';
require_once 'src/lib/Database.php';

$db = Vsys\Lib\Database::getInstance();

$updates = [
    "ALTER TABLE quotations ADD COLUMN total_iva_105 DECIMAL(10,2) DEFAULT 0.00 AFTER subtotal_usd",
    "ALTER TABLE quotations ADD COLUMN total_iva_21 DECIMAL(10,2) DEFAULT 0.00 AFTER total_iva_105",
    "ALTER TABLE quotations ADD COLUMN public_hash VARCHAR(32) DEFAULT NULL AFTER observations",
    "ALTER TABLE quotations ADD INDEX idx_public_hash (public_hash)"
];

foreach ($updates as $sql) {
    try {
        $db->query($sql);
        echo "Executed: $sql\n";
    } catch (PDOException $e) {
        // Ignore if exists (duplicate column)
        echo "Skipped (or error): " . $e->getMessage() . "\n";
    }
}

echo "Database schema updated.\n";
