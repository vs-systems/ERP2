<?php
/**
 * VS System ERP - Database Update Utility (Enhanced Entities & CRM)
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

use Vsys\Lib\Database;

echo "<h1>VS System - Actualizació³n de Entidades y CRM</h1>";

try {
    $db = Database::getInstance();

    // 1. Add new columns to entities
    $columns = [
        "document_number VARCHAR(20) AFTER tax_id",
        "fantasy_name VARCHAR(200) AFTER name",
        "mobile VARCHAR(50) AFTER phone",
        "delivery_address TEXT AFTER address",
        "default_voucher_type ENUM('Factura', 'Remito', 'Ninguno') DEFAULT 'Factura' AFTER delivery_address",
        "is_enabled TINYINT(1) DEFAULT 1 AFTER default_voucher_type"
    ];

    foreach ($columns as $col) {
        $parts = explode(" ", $col);
        $colName = $parts[0];

        $check = $db->query("SHOW COLUMNS FROM entities LIKE '$colName'")->fetch();
        if (!$check) {
            echo "<li>Agregando columna $colName... ";
            $db->exec("ALTER TABLE entities ADD COLUMN $col");
            echo "<span style='color:green'>OK</span></li>";
        } else {
            echo "<li>La columna $colName ya existe. <span style='color:orange'>Omitiendo</span></li>";
        }
    }

    // 2. Create CRM tables
    echo "<li>Creando tablas de CRM (Leads e Interacciones)... ";
    $db->exec("CREATE TABLE IF NOT EXISTS crm_leads (
        id INT AUTO_INCREMENT PRIMARY KEY, 
        entity_id INT NOT NULL, 
        status ENUM('Nuevo', 'Contactado', 'Presupuestado', 'Ganado', 'Perdido') DEFAULT 'Nuevo', 
        source VARCHAR(100), 
        notes TEXT, 
        last_contact TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
        FOREIGN KEY (entity_id) REFERENCES entities(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS crm_interactions (
        id INT AUTO_INCREMENT PRIMARY KEY, 
        entity_id INT NOT NULL, 
        user_id INT NOT NULL, 
        type ENUM('Llamada', 'Email', 'Visita', 'WhatsApp', 'Otro') DEFAULT 'WhatsApp', 
        description TEXT, 
        interaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
        FOREIGN KEY (entity_id) REFERENCES entities(id) ON DELETE CASCADE, 
        FOREIGN KEY (user_id) REFERENCES users(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<span style='color:green'>OK</span></li>";

    echo "<h3>âœ… Base de Datos actualizada correctamente.</h3>";
} catch (Exception $e) {
    echo "<h3>âŒ Error: " . $e->getMessage() . "</h3>";
}
?>





