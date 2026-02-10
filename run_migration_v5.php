<?php
/**
 * Migration: Unification of Transports and Status Update
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

try {
    $db = Vsys\Lib\Database::getInstance();

    echo "<h1>Iniciando Migración...</h1>";

    // 1. Add is_transport to entities
    echo "<p>1. Agregando campo 'is_transport' a la tabla 'entities'...</p>";
    $db->exec("ALTER TABLE entities ADD COLUMN IF NOT EXISTS is_transport TINYINT(1) DEFAULT 0 AFTER is_retention_agent");

    // 2. Update status enum in quotations
    echo "<p>2. Actualizando estados de presupuestos...</p>";
    // We try to change the type to include 'Perdido' and 'En espera'
    // Note: status might already exist from a previous partial migration or be different.
    $db->exec("ALTER TABLE quotations MODIFY COLUMN status ENUM('Pendiente', 'Aceptado', 'Perdido', 'En espera', 'rejected', 'ordered', 'draft', 'sent', 'accepted', 'rejected', 'expired') DEFAULT 'Pendiente'");

    // 3. Fix potential mapping issues (Legacy statuses to New statuses)
    $db->exec("UPDATE quotations SET status = 'Perdido' WHERE status = 'rejected'");
    $db->exec("UPDATE quotations SET status = 'Aceptado' WHERE status = 'accepted' OR status = 'ordered'");
    $db->exec("UPDATE quotations SET status = 'Pendiente' WHERE status = 'draft' OR status = 'sent' OR status IS NULL");

    // 4. Update exchange_rates schema check (ensure consistency)
    // Actually, sync script expects fetched_at or created_at? 
    // Schema has fetched_at. Let's ensure it.
    echo "<p>3. Verificando tabla exchange_rates...</p>";
    $db->exec("ALTER TABLE exchange_rates MODIFY COLUMN fetched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");

    echo "<h2 style='color:green;'>¡Migración Exitosa!</h2>";
    echo "<a href='dashboard.php'>Volver al Dashboard</a>";

} catch (Exception $e) {
    echo "<h2 style='color:red;'>ERROR: " . $e->getMessage() . "</h2>";
}
