<?php
/**
 * Unified Financial Migration
 * Creates missing tables: client_movements, provider_movements, treasury_movements
 */
require_once __DIR__ . '/src/lib/Database.php';

try {
    $db = Vsys\Lib\Database::getInstance();
    echo "<pre>";

    // 1. Client Movements
    $sqlClient = "CREATE TABLE IF NOT EXISTS client_movements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT DEFAULT 1,
        client_id INT NOT NULL,
        type VARCHAR(50) NOT NULL,
        reference_id INT DEFAULT NULL,
        debit DECIMAL(15, 2) DEFAULT 0.00,
        credit DECIMAL(15, 2) DEFAULT 0.00,
        balance DECIMAL(15, 2) DEFAULT 0.00,
        notes TEXT,
        date DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (client_id),
        INDEX (date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $db->exec($sqlClient);
    echo "Check: Table 'client_movements' is ready.\n";

    // 2. Provider Movements
    $sqlProvider = "CREATE TABLE IF NOT EXISTS provider_movements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT DEFAULT 1,
        provider_id INT NOT NULL,
        type VARCHAR(50) NOT NULL,
        reference_id INT DEFAULT NULL,
        debit DECIMAL(15, 2) DEFAULT 0.00,
        credit DECIMAL(15, 2) DEFAULT 0.00,
        balance DECIMAL(15, 2) DEFAULT 0.00,
        notes TEXT,
        date DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (provider_id),
        INDEX (date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $db->exec($sqlProvider);
    echo "Check: Table 'provider_movements' is ready.\n";

    // 3. Treasury Movements
    $sqlTreasury = "CREATE TABLE IF NOT EXISTS treasury_movements (
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
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (date),
        INDEX (type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $db->exec($sqlTreasury);
    echo "Check: Table 'treasury_movements' is ready.\n";

    echo "\nSuccess: Database updated. You can now refresh the dashboard.";
    echo "</pre>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
