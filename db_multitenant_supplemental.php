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
        'operation_documents',
        'entities',
        'products'
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

        // Specific change for 'entities' table: add 'preferred_transport'
        if ($table === 'entities') {
            $preferredTransportColumn = $db->query("SHOW COLUMNS FROM $table LIKE 'preferred_transport'")->fetch();
            if (!$preferredTransportColumn) {
                echo "Adding preferred_transport to $table...\n";
                $db->exec("ALTER TABLE $table ADD COLUMN preferred_transport VARCHAR(100) AFTER preferred_payment_method");
            } else {
                echo "Table $table already has preferred_transport.\n";
            }
        }

        // Specific change for 'products' table: add stock management columns
        if ($table === 'products') {
            $stockCols = [
                "stock_min INT DEFAULT 0 AFTER stock_current",
                "stock_transit INT DEFAULT 0 AFTER stock_min",
                "stock_incoming INT DEFAULT 0 AFTER stock_transit",
                "incoming_date DATE NULL AFTER stock_incoming"
            ];
            foreach ($stockCols as $sCol) {
                $sColName = explode(" ", $sCol)[0];
                $checkS = $db->query("SHOW COLUMNS FROM $table LIKE '$sColName'")->fetch();
                if (!$checkS) {
                    echo "Adding $sColName to $table...\n";
                    $db->exec("ALTER TABLE $table ADD COLUMN $sCol");
                } else {
                    echo "Column $sColName already exists in $table.\n";
                }
            }
        }
    }

    // 2. Create System Logs Table
    echo "<li>Creando tabla de Logs de Sistema... ";
    $db->exec("CREATE TABLE IF NOT EXISTS system_logs (
        id INT AUTO_INCREMENT PRIMARY KEY, 
        user_id INT NOT NULL, 
        company_id INT NOT NULL, 
        action VARCHAR(255) NOT NULL, 
        entity_type VARCHAR(100), 
        entity_id INT, 
        details TEXT, 
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
        FOREIGN KEY (user_id) REFERENCES users(id),
        INDEX idx_logs_company (company_id),
        INDEX idx_logs_user (user_id),
        INDEX idx_logs_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<span style='color:green'>OK</span></li>";

    echo "Supplemental migration completed successfully!\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
