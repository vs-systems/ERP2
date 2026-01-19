<?php
/**
 * VS System ERP - Multitenancy Migration Script
 * This script prepares the DB for Multiple Companies.
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

$db = Vsys\Lib\Database::getInstance();

try {
    $db->beginTransaction();

    echo "Creating 'companies' table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS companies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(200) NOT NULL,
        tax_id VARCHAR(20) UNIQUE,
        email VARCHAR(100),
        status ENUM('active', 'inactive') DEFAULT 'active',
        settings JSON DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Create Default Company (Original Owner)
    $stmt = $db->query("SELECT id FROM companies LIMIT 1");
    if (!$stmt->fetch()) {
        echo "Inserting default company...\n";
        $db->exec("INSERT INTO companies (name, tax_id) VALUES ('Vecino Seguro ERP', '30-11111111-9')");
        $companyId = $db->lastInsertId();
    } else {
        $companyId = 1;
    }

    $tablesToMigrate = [
        'users',
        'exchange_rates',
        'categories',
        'products',
        'product_serials',
        'entities',
        'supplier_prices',
        'quotations',
        'quotation_items',
        'price_analysis',
        'price_analysis_items',
        'purchase_orders',
        'price_lists',
        'transport_companies'
    ];

    foreach ($tablesToMigrate as $table) {
        $check = $db->query("SHOW TABLES LIKE '$table'");
        if (!$check->fetch()) {
            echo "Skipping missing table: $table\n";
            continue;
        }

        echo "Migrating table: $table...\n";

        // Check if column exists
        $columns = $db->query("SHOW COLUMNS FROM $table LIKE 'company_id'")->fetch();
        if (!$columns) {
            $db->exec("ALTER TABLE $table ADD COLUMN company_id INT NOT NULL DEFAULT $companyId AFTER id");
            $db->exec("ALTER TABLE $table ADD INDEX idx_company_id (company_id)");
        }
    }

    // Special case: Update unique index for products (sku per company)
    echo "Updating products SKU unique index...\n";
    try {
        $db->exec("ALTER TABLE products DROP INDEX sku");
    } catch (Exception $e) { /* ignore if not exists */
    }
    $db->exec("ALTER TABLE products ADD UNIQUE KEY uk_sku_company (sku, company_id)");

    // Special case: users (username per company)
    echo "Updating users username unique index...\n";
    try {
        $db->exec("ALTER TABLE users DROP INDEX username");
    } catch (Exception $e) { /* ignore if not exists */
    }
    $db->exec("ALTER TABLE users ADD UNIQUE KEY uk_username_company (username, company_id)");

    $db->commit();
    echo "Migration completed successfully!\n";

} catch (Exception $e) {
    $db->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
