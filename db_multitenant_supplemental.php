<?php
/**
 * VS System ERP - Supplemental Multitenancy Migration
 * Adds company_id to tables missed in the initial pass.
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

$db = Vsys\Lib\Database::getInstance();

try {
    // 1. Get Default Company ID
    $companyId = $db->query("SELECT id FROM companies LIMIT 1")->fetchColumn() ?: 1;

    $tables = [
        'crm_leads',
        'crm_interactions',
        'transports',
        'logistics_process',
        'logistics_freight_costs',
        'logistics_remitos',
        'purchases',
        'purchase_items',
        'operation_documents'
    ];

    echo "Starting supplemental migration...\n";

    foreach ($tables as $table) {
        // Check if table exists
        $check = $db->query("SHOW TABLES LIKE '$table'");
        if (!$check->fetch()) {
            echo "Skipping missing table: $table\n";
            continue;
        }

        // Check if company_id exists
        $columns = $db->query("SHOW COLUMNS FROM $table LIKE 'company_id'")->fetch();
        if (!$columns) {
            echo "Adding company_id to $table...\n";
            $db->exec("ALTER TABLE $table ADD COLUMN company_id INT NOT NULL DEFAULT $companyId");
            $db->exec("ALTER TABLE $table ADD INDEX idx_company_id_$table (company_id)");
        } else {
            echo "Table $table already has company_id.\n";
        }
    }

    echo "Supplemental migration completed successfully!\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
