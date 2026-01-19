<?php
/**
 * Database Migration - Logistics V2
 * Adds support for order phases, freight costs, and transport directory fields.
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

use Vsys\Lib\Database;

try {
    $db = Database::getInstance();

    // 1. Update transports table
    $db->exec("ALTER TABLE transports ADD COLUMN IF NOT EXISTS address TEXT AFTER email");
    $db->exec("ALTER TABLE transports ADD COLUMN IF NOT EXISTS cuit VARCHAR(20) AFTER address");
    $db->exec("ALTER TABLE transports ADD COLUMN IF NOT EXISTS can_pickup BOOLEAN DEFAULT 0 AFTER cuit");
    echo "Table 'transports' updated.\n";

    // 2. Create logistics_process table
    $sqlProcess = "CREATE TABLE IF NOT EXISTS logistics_process (
        id INT AUTO_INCREMENT PRIMARY KEY,
        quote_number VARCHAR(50) NOT NULL,
        current_phase ENUM('En reserva', 'En preparació³n', 'Disponible', 'En su transporte', 'Entregado') DEFAULT 'En reserva',
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

    // Initialize existing active quotes into logistics_process if needed
    $db->exec("INSERT INTO logistics_process (quote_number, current_phase) 
               SELECT quote_number, 'En reserva' 
               FROM quotations 
               WHERE quote_number NOT IN (SELECT quote_number FROM logistics_process)");
    echo "Logistics phases initialized for existing quotes.\n";

    echo "Migration complete!";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage();
}





