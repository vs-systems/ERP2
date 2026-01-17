<?php
/**
 * VS System ERP - FINAL REPAIR & ENHANCEMENT
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

// Temporarily disable errors to ensure clean output if needed
error_reporting(0);
ini_set('display_errors', 0);

try {
    $db = Vsys\Lib\Database::getInstance();

    echo "<h2>VS System ERP - Final Repair System</h2>";

    // 1. General Schema Updates
    echo "Updating general tables...<br>";
    $db->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS category VARCHAR(100) AFTER description;");
    $db->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS subcategory VARCHAR(100) AFTER category;");
    $db->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS supplier_id INT AFTER subcategory;");

    $db->exec("ALTER TABLE purchases ADD COLUMN IF NOT EXISTS is_confirmed TINYINT(1) DEFAULT 0 AFTER status;");
    $db->exec("ALTER TABLE purchases ADD COLUMN IF NOT EXISTS payment_status ENUM('Pendiente', 'Pagado') DEFAULT 'Pendiente' AFTER is_confirmed;");
    $db->exec("ALTER TABLE purchases ADD COLUMN IF NOT EXISTS subtotal_usd DECIMAL(15,2) AFTER exchange_rate_usd;");
    $db->exec("ALTER TABLE purchases ADD COLUMN IF NOT EXISTS subtotal_ars DECIMAL(15,2) AFTER subtotal_usd;");

    $db->exec("ALTER TABLE quotations ADD COLUMN IF NOT EXISTS is_confirmed TINYINT(1) DEFAULT 0 AFTER status;");
    $db->exec("ALTER TABLE quotations ADD COLUMN IF NOT EXISTS payment_status ENUM('Pendiente', 'Pagado') DEFAULT 'Pendiente' AFTER is_confirmed;");
    $db->exec("ALTER TABLE quotations ADD COLUMN IF NOT EXISTS subtotal_usd DECIMAL(15,2) AFTER exchange_rate_usd;");
    $db->exec("ALTER TABLE quotations ADD COLUMN IF NOT EXISTS subtotal_ars DECIMAL(15,2) AFTER subtotal_usd;");

    $db->exec("ALTER TABLE entities ADD COLUMN IF NOT EXISTS payment_condition VARCHAR(100) AFTER delivery_address;");
    $db->exec("ALTER TABLE entities ADD COLUMN IF NOT EXISTS preferred_payment_method VARCHAR(100) AFTER payment_condition;");
    $db->exec("ALTER TABLE entities ADD COLUMN IF NOT EXISTS tax_id VARCHAR(50) AFTER fantasy_name;");
    $db->exec("ALTER TABLE entities ADD COLUMN IF NOT EXISTS address VARCHAR(255) AFTER tax_id;");
    echo "✅ General schema verified.<br>";

    // 2. CRM REBUILD (Fresh start as requested, fixing all legacy issues)
    echo "Rebuilding CRM tables (Lead & Interactions)...<br>";
    $db->exec("DROP TABLE IF EXISTS crm_interactions;");
    $db->exec("DROP TABLE IF EXISTS crm_leads;");

    $db->exec("CREATE TABLE crm_leads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        contact_person VARCHAR(255),
        email VARCHAR(255),
        tax_id VARCHAR(50),
        address VARCHAR(255),
        phone VARCHAR(50),
        status ENUM('Nuevo', 'Contactado', 'Presupuestado', 'Ganado', 'Perdido') DEFAULT 'Nuevo',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $db->exec("CREATE TABLE crm_interactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        entity_id INT NOT NULL,
        entity_type ENUM('entity', 'lead') DEFAULT 'entity',
        user_id INT NOT NULL,
        type VARCHAR(50),
        description TEXT,
        interaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "✅ CRM tables rebuilt successfully.<br>";

    // 3. Traceability fields
    echo "Adding Traceability & Observations...<br>";
    $db->exec("ALTER TABLE quotations ADD COLUMN IF NOT EXISTS observations TEXT AFTER valid_until;");
    $db->exec("ALTER TABLE purchases ADD COLUMN IF NOT EXISTS observations TEXT AFTER notes;");
    $db->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS barcode VARCHAR(100) AFTER description;");
    $db->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS image_url VARCHAR(500) AFTER barcode;");
    $db->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS has_serial_number TINYINT(1) DEFAULT 0 AFTER image_url;");
    $db->exec("ALTER TABLE quotation_items ADD COLUMN IF NOT EXISTS serial_numbers TEXT;");
    $db->exec("ALTER TABLE purchase_items ADD COLUMN IF NOT EXISTS serial_numbers TEXT;");
    echo "✅ Traceability fields added.<br>";

    echo "<br>🚀 🚀 **ESQUEMA REPARADO Y ACTUALIZADO** 🚀 🚀";
    echo "<br><p>Por favor, vuelve al sistema e intenta grabar una cotización.</p>";

} catch (Exception $e) {
    echo "<br>❌ ERROR CRÍTICO: " . $e->getMessage();
}
