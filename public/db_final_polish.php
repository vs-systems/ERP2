<?php
/**
 * VS System ERP - FINAL POLISH Migration
 * Adds Categories and Subcategories to products.
 * Consolidates last schema fixes.
 */
require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/lib/Database.php';

try {
    $db = Vsys\Lib\Database::getInstance();

    echo "<h2>VS System ERP - Final Polish</h2>";

    // 1. Update products table
    echo "Updating 'products' table...<br>";
    $db->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS category VARCHAR(100) AFTER description;");
    $db->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS subcategory VARCHAR(100) AFTER category;");
    echo "✅ 'products' updated with categories.<br>";

    // 2. Ensure subtotal_usd and subtotal_ars in quotations (Double check)
    echo "Verifying 'quotations' schema...<br>";
    $db->exec("ALTER TABLE quotations ADD COLUMN IF NOT EXISTS subtotal_usd DECIMAL(15,2) AFTER exchange_rate_usd;");
    $db->exec("ALTER TABLE quotations ADD COLUMN IF NOT EXISTS subtotal_ars DECIMAL(15,2) AFTER subtotal_usd;");
    echo "✅ 'quotations' schema verified.<br>";

    // 3. Ensure subtotal_usd and subtotal_ars in purchases (Double check)
    echo "Verifying 'purchases' schema...<br>";
    $db->exec("ALTER TABLE purchases ADD COLUMN IF NOT EXISTS subtotal_usd DECIMAL(15,2) AFTER exchange_rate_usd;");
    $db->exec("ALTER TABLE purchases ADD COLUMN IF NOT EXISTS subtotal_ars DECIMAL(15,2) AFTER subtotal_usd;");
    echo "✅ 'purchases' schema verified.<br>";

    echo "<br>🚀 **SISTEMA ACTUALIZADO AL 100% PARA SALIR A PRODUCCIÓN**";

} catch (Exception $e) {
    echo "<br>❌ ERROR: " . $e->getMessage();
}
