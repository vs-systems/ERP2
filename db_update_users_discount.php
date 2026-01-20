<?php
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

$db = Vsys\Lib\Database::getInstance();
try {
    $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS discount_cap DECIMAL(5,2) DEFAULT 0.00 AFTER permissions");
    echo "Columna discount_cap agregada correctamente.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
