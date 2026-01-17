<?php
/**
 * VS System ERP - Database Update Utility (Multi-Supplier)
 */
require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/lib/Database.php';

use Vsys\Lib\Database;

echo "<h1>VS System - Actualización de Base de Datos</h1>";

try {
    $db = Database::getInstance();

    // 1. Update entities type
    echo "<li>Actualizando tipos de entidades... ";
    $db->exec("ALTER TABLE entities MODIFY COLUMN type ENUM('client', 'provider', 'supplier') NOT NULL");
    echo "<span style='color:green'>OK</span></li>";

    // 2. Create supplier_prices table
    echo "<li>Creando tabla de precios por proveedor... ";
    $sql = "CREATE TABLE IF NOT EXISTS supplier_prices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        supplier_id INT NOT NULL,
        cost_usd DECIMAL(15, 2) NOT NULL,
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (supplier_id) REFERENCES entities(id) ON DELETE CASCADE,
        UNIQUE KEY (product_id, supplier_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $db->exec($sql);
    echo "<span style='color:green'>OK</span></li>";

    echo "<h3>✅ Base de Datos actualizada correctamente.</h3>";
} catch (Exception $e) {
    echo "<h3>❌ Error: " . $e->getMessage() . "</h3>";
}
?>