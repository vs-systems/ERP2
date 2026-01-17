<?php
/**
 * VS System ERP - Database Repair Script
 */

require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/lib/Database.php';

use Vsys\Lib\Database;

echo "<h1>Reparador de Base de Datos</h1>";

try {
    $db = Database::getInstance();

    $fixes = [
        "ALTER TABLE products ADD COLUMN brand VARCHAR(100) AFTER iva_rate",
        "ALTER TABLE entities ADD COLUMN is_retention_agent TINYINT(1) DEFAULT 0",
        "ALTER TABLE quotations ADD COLUMN payment_method ENUM('cash', 'bank') DEFAULT 'cash'",
        "ALTER TABLE quotations ADD COLUMN with_iva TINYINT(1) DEFAULT 1"
    ];

    echo "<ul>";
    foreach ($fixes as $sql) {
        try {
            $db->exec($sql);
            echo "<li style='color: green;'>✅ Ejecutado: $sql</li>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "<li style='color: blue;'>ℹ️ Ya existe (ignorado): " . substr($sql, 0, 30) . "...</li>";
            } else {
                echo "<li style='color: red;'>❌ Error: " . $e->getMessage() . "</li>";
            }
        }
    }
    echo "</ul>";

    echo "<p style='font-weight: bold;'>¡Proceso terminado! Ahora intenta importar los productos de nuevo.</p>";
    echo "<a href='importar.php' style='padding: 10px 20px; background: #27ae60; color: white; text-decoration: none; border-radius: 5px;'>Ir al Importador</a>";

} catch (Exception $e) {
    echo "<p style='color: red;'>Error crítico: " . $e->getMessage() . "</p>";
}
?>