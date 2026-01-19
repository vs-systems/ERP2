<?php
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

try {
    $db = Vsys\Lib\Database::getInstance();

    // Add subtotal columns to purchases header
    $db->exec("ALTER TABLE purchases ADD COLUMN IF NOT EXISTS subtotal_usd DECIMAL(15,2) AFTER exchange_rate_usd;");
    $db->exec("ALTER TABLE purchases ADD COLUMN IF NOT EXISTS subtotal_ars DECIMAL(15,2) AFTER subtotal_usd;");

    echo "âœ… Columnas de subtotal agregadas a la tabla 'purchases'.<br>";
} catch (Exception $e) {
    echo "âŒ ERROR: " . $e->getMessage();
}





