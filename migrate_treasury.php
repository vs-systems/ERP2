<?php
/**
 * Migration: Create Treasury Table
 */
require_once __DIR__ . '/src/lib/Database.php';

try {
    $db = Vsys\Lib\Database::getInstance();

    $sql = "CREATE TABLE IF NOT EXISTS treasury_movements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        date DATETIME DEFAULT CURRENT_TIMESTAMP,
        type ENUM('Ingreso', 'Egreso') NOT NULL,
        category VARCHAR(50) DEFAULT 'Varios',
        amount DECIMAL(15, 2) NOT NULL,
        currency VARCHAR(10) DEFAULT 'ARS',
        payment_method VARCHAR(50) DEFAULT 'Efectivo',
        reference_id INT DEFAULT NULL,
        reference_type VARCHAR(50) DEFAULT NULL,
        notes TEXT,
        created_by INT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $db->exec($sql);
    echo "Treasury table created successfully.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
