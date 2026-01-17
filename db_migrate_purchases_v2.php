<?php
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

try {
    $db = Vsys\Lib\Database::getInstance();

    // Add IVA and Unit Price ARS to purchase_items
    $db->exec("ALTER TABLE purchase_items ADD COLUMN IF NOT EXISTS iva_rate DECIMAL(5,2) DEFAULT 21.00 AFTER qty;");
    $db->exec("ALTER TABLE purchase_items ADD COLUMN IF NOT EXISTS unit_price_ars DECIMAL(15,2) DEFAULT 0.00 AFTER unit_price_usd;");

    echo "✅ Columnas 'iva_rate' y 'unit_price_ars' agregadas a 'purchase_items'.<br>";
    echo "<h3>Migración de mejora completada.</h3>";
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage();
}
