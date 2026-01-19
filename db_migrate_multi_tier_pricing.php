<?php
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
use Vsys\Lib\Database;

try {
    $db = Database::getInstance();
    $db->exec("ALTER TABLE products ADD COLUMN price_gremio DECIMAL(10,2) NULL AFTER unit_price_usd");
    $db->exec("ALTER TABLE products ADD COLUMN price_web DECIMAL(10,2) NULL AFTER price_gremio");
    echo "Multi-tier pricing columns added successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}