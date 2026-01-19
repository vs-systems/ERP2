<?php
/**
 * Migration Script: Assign Existing Products to BIG DIPPER S.R.L.
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

use Vsys\Lib\Database;

echo "<h1>Asignació³n Masiva: BIG DIPPER S.R.L.</h1>";

try {
    $db = Database::getInstance();

    // 1. Find BIG DIPPER ID
    $supplier = $db->query("SELECT id, name FROM entities WHERE name LIKE '%BIG DIPPER%'")->fetch();

    if (!$supplier) {
        // If not found, create it as a fallback
        echo "<li>Proveedor 'BIG DIPPER S.R.L.' no encontrado. Creó¡ndolo... ";
        $db->exec("INSERT INTO entities (type, name, is_enabled) VALUES ('supplier', 'BIG DIPPER S.R.L.', 1)");
        $supplierId = $db->lastInsertId();
        echo "<span style='color:green'>ID: $supplierId</span></li>";
    } else {
        $supplierId = $supplier['id'];
        echo "<li>Proveedor encontrado: <strong>" . $supplier['name'] . "</strong> (ID: $supplierId)</li>";
    }

    // 2. Initialize supplier_prices with current product costs
    echo "<li>Poblando tabla de precios comparativos (supplier_prices)... ";
    $sql = "INSERT INTO supplier_prices (product_id, supplier_id, cost_usd)
            SELECT id, :sid, unit_cost_usd FROM products
            ON DUPLICATE KEY UPDATE cost_usd = VALUES(cost_usd)";

    $stmt = $db->prepare($sql);
    $stmt->execute(['sid' => $supplierId]);
    $count = $stmt->rowCount();
    echo "<span style='color:green'>$count productos vinculados.</span></li>";

    echo "<h3>âœ… Proceso completado exitosamente.</h3>";
} catch (Exception $e) {
    echo "<h3>âŒ Error: " . $e->getMessage() . "</h3>";
}
?>





