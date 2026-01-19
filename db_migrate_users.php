<?php
/**
 * VS System ERP - Multitenancy Migration Script (REUSED FILENAME)
 * This script prepares the DB for Multiple Companies.
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

$db = Vsys\Lib\Database::getInstance();

try {
    $db->beginTransaction();

    echo "<h1>Database Migration - Multitenancy</h1>";

    echo "<p>Creating 'companies' table...</p>";
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
        echo "<p>Inserting default company...</p>";
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
        'transport_companies',
        'permissions'
    ];

    foreach ($tablesToMigrate as $table) {
        $check = $db->query("SHOW TABLES LIKE '$table'");
        if (!$check->fetch()) {
            echo "<p>Skipping missing table: $table</p>";
            continue;
        }

        echo "<p>Migrating table: $table...</p>";

        // Check if column exists
        $columns = $db->query("SHOW COLUMNS FROM $table LIKE 'company_id'")->fetch();
        if (!$columns) {
            $db->exec("ALTER TABLE $table ADD COLUMN company_id INT NOT NULL DEFAULT $companyId AFTER id");
            $db->exec("ALTER TABLE $table ADD INDEX idx_company_id (company_id)");
        }
    }

    // Special case: Update unique index for products (sku per company)
    echo "<p>Updating products SKU unique index...</p>";
    try {
        $db->exec("ALTER TABLE products DROP INDEX sku");
    } catch (Exception $e) { /* ignore if not exists */
    }
    $db->exec("ALTER TABLE products ADD UNIQUE KEY uk_sku_company (sku, company_id)");

    // Special case: users (username per company)
    echo "<p>Updating users username unique index...</p>";
    try {
        $db->exec("ALTER TABLE users DROP INDEX username");
    } catch (Exception $e) { /* ignore if not exists */
    }
    $db->exec("ALTER TABLE users ADD UNIQUE KEY uk_username_company (username, company_id)");

    $db->commit();
    echo "<h2>Migration completed successfully!</h2>";

} catch (Exception $e) {
    if ($db->inTransaction())
        $db->rollBack();
    echo "<h2 style='color:red;'>ERROR: " . $e->getMessage() . "</h2>";
}
