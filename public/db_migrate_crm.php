<?php
require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/lib/Database.php';

try {
    $db = Vsys\Lib\Database::getInstance();

    // 1. CRM Leads Table
    $db->exec("CREATE TABLE IF NOT EXISTS crm_leads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        contact_person VARCHAR(255),
        email VARCHAR(255),
        phone VARCHAR(100),
        status ENUM('Nuevo', 'Contactado', 'Presupuestado', 'Ganado', 'Perdido') DEFAULT 'Nuevo',
        notes TEXT,
        entity_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (status),
        INDEX (entity_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 2. CRM Interactions Table (if not exists)
    $db->exec("CREATE TABLE IF NOT EXISTS crm_interactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        entity_id INT,
        user_id INT,
        type VARCHAR(50),
        description TEXT,
        interaction_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (entity_id),
        INDEX (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    echo "✅ Tablas de CRM verificadas/creadas.<br>";
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage();
}
