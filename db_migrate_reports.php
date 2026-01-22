<?php
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

try {
    $db = \Vsys\Lib\Database::getInstance();
    
    // Add lat and lng to entities table
    $sql = "ALTER TABLE entities 
            ADD COLUMN lat DECIMAL(10, 8) DEFAULT NULL AFTER address,
            ADD COLUMN lng DECIMAL(11, 8) DEFAULT NULL AFTER lat";
            
    $db->exec($sql);
    
    // Also add city/locality if not present (to help with charts)
    $cols = $db->query("DESCRIBE entities")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('city', $cols)) {
        $db->exec("ALTER TABLE entities ADD COLUMN city VARCHAR(100) DEFAULT NULL AFTER address");
    }

    echo "Migration successful: columns lat, lng (and city if missing) added to entities table.";
} catch (Exception $e) {
    echo "Migration error or already applied: " . $e->getMessage();
}
