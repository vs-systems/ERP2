<?php
require_once 'src/config/config.php';
require_once 'src/lib/Database.php';

$db = Vsys\Lib\Database::getInstance();
try {
    $db->exec("ALTER TABLE entities ADD COLUMN transport VARCHAR(255) DEFAULT NULL;");
    echo "SUCCESS: Column 'transport' added to 'entities'.\n";
} catch (Exception $e) {
    echo "ERROR/NOTICE: " . $e->getMessage() . "\n";
}

try {
    // Also add a table for catalog configuration if not exists
    $db->exec("CREATE TABLE IF NOT EXISTS system_config (
        config_key VARCHAR(50) PRIMARY KEY,
        config_value TEXT
    );");
    echo "SUCCESS: 'system_config' table verified.\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
unlink(__FILE__);
