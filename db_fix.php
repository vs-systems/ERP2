<?php
/**
 * Database Migration Trigger - Logistics
 * Visiting this script will apply the missing database tables.
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

use Vsys\Lib\Database;

header('Content-Type: text/plain');

try {
    $db = Database::getInstance();
    echo "Starting migration...\n";

    // 1. Update transports table
    $db->exec("ALTER TABLE transports ADD COLUMN IF NOT EXISTS address TEXT AFTER email");
    $db->exec("ALTER TABLE transports ADD COLUMN IF NOT EXISTS cuit VARCHAR(20) AFTER address");
    $db->exec("ALTER TABLE transports ADD COLUMN IF NOT EXISTS can_pickup BOOLEAN DEFAULT 0 AFTER cuit");
    echo "Table 'transports' updated.\n";

    // 2. Create logistics_process table
    $sqlProcess = "CREATE TABLE IF NOT EXISTS logistics_process (
        id INT AUTO_INCREMENT PRIMARY KEY,
        quote_number VARCHAR(50) NOT NULL UNIQUE,
        current_phase ENUM('En reserva', 'En preparación', 'Disponible', 'En su transporte', 'Entregado') DEFAULT 'En reserva',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (quote_number)
    )";
    $db->exec($sqlProcess);
    echo "Table 'logistics_process' created/verified.\n";

    // 3. Create logistics_freight_costs table
    $sqlFreight = "CREATE TABLE IF NOT EXISTS logistics_freight_costs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        quote_number VARCHAR(50) NOT NULL,
        dispatch_date DATE,
        client_id INT,
        packages_qty INT DEFAULT 0,
        freight_cost DECIMAL(15,2) DEFAULT 0.00,
        transport_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (quote_number),
        INDEX (transport_id)
    )";
    $db->exec($sqlFreight);
    echo "Table 'logistics_freight_costs' created/verified.\n";

    // 4. Operation Documents Table (for guide photos)
    $sqlDocs = "CREATE TABLE IF NOT EXISTS operation_documents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        entity_id VARCHAR(50) NOT NULL,
        entity_type VARCHAR(20) NOT NULL,
        doc_type VARCHAR(50),
        file_path VARCHAR(255) NOT NULL,
        notes TEXT,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $db->exec($sqlDocs);
    echo "Table 'operation_documents' created/verified.\n";

    // Initialize existing active quotes into logistics_process
    $db->exec("INSERT IGNORE INTO logistics_process (quote_number, current_phase) 
               SELECT quote_number, 'En reserva' 
               FROM quotations 
               WHERE quote_number NOT IN (SELECT quote_number FROM logistics_process)");
    echo "Logistics phases initialized for existing quotes.\n";

    echo "\nMigration complete! You can now return to the logistics module.";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage();
}
