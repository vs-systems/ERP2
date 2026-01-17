<?php
/**
 * VS System ERP - MEGA FIX Database Migration
 * Consolidates all missing schema changes and data migrations.
 */
require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/lib/Database.php';

try {
    $db = Vsys\Lib\Database::getInstance();

    echo "<h2>VS System ERP - Mega Fix</h2>";

    // 1. Update quotations table
    echo "Updating 'quotations' table...<br>";
    $db->exec("ALTER TABLE quotations ADD COLUMN IF NOT EXISTS subtotal_usd DECIMAL(15,2) AFTER exchange_rate_usd;");
    $db->exec("ALTER TABLE quotations ADD COLUMN IF NOT EXISTS subtotal_ars DECIMAL(15,2) AFTER subtotal_usd;");
    // Ensure subtotal is populated for old records
    $db->exec("UPDATE quotations SET subtotal_usd = total_usd WHERE subtotal_usd IS NULL OR subtotal_usd = 0;");
    $db->exec("UPDATE quotations SET subtotal_ars = total_ars WHERE subtotal_ars IS NULL OR subtotal_ars = 0;");
    echo "✅ 'quotations' updated.<br>";

    // 2. Update purchases table
    echo "Updating 'purchases' table...<br>";
    $db->exec("ALTER TABLE purchases ADD COLUMN IF NOT EXISTS subtotal_usd DECIMAL(15,2) AFTER exchange_rate_usd;");
    $db->exec("ALTER TABLE purchases ADD COLUMN IF NOT EXISTS subtotal_ars DECIMAL(15,2) AFTER subtotal_usd;");
    $db->exec("UPDATE purchases SET subtotal_usd = total_usd WHERE subtotal_usd IS NULL OR subtotal_usd = 0;");
    $db->exec("UPDATE purchases SET subtotal_ars = total_ars WHERE subtotal_ars IS NULL OR subtotal_ars = 0;");
    echo "✅ 'purchases' updated.<br>";

    // 3. Ensure users table has full_name for CRM
    echo "Checking 'users' table...<br>";
    try {
        $db->query("SELECT full_name FROM users LIMIT 1");
    } catch (Exception $e) {
        $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS full_name VARCHAR(255) AFTER username;");
        $db->exec("UPDATE users SET full_name = username WHERE full_name IS NULL OR full_name = '';");
    }
    echo "✅ 'users' checked.<br>";

    echo "<br>🚀 **SISTEMA ACTUALIZADO CORRECTAMENTE**";

} catch (Exception $e) {
    echo "<br>❌ ERROR: " . $e->getMessage();
}
