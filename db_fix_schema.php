<?php
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

try {
    $db = Vsys\Lib\Database::getInstance();

    echo "<h1>Reparación de Base de Datos</h1>";

    // 1. Fix supplier_prices table
    echo "<h2>1. Verificando Tabla 'supplier_prices'</h2>";

    // Check if table exists
    $tableExists = $db->query("SHOW TABLES LIKE 'supplier_prices'")->rowCount() > 0;

    if (!$tableExists) {
        $sql = "CREATE TABLE supplier_prices (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            supplier_id INT NOT NULL,
            cost_usd DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY product_supplier (product_id, supplier_id),
            KEY fk_product (product_id),
            KEY fk_supplier (supplier_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $db->exec($sql);
        echo "✅ Tabla 'supplier_prices' CREADA correctamente.<br>";
    } else {
        echo "ℹ️ La tabla 'supplier_prices' ya existe. Verificando columnas...<br>";

        // Check columns
        $columns = $db->query("SHOW COLUMNS FROM supplier_prices")->fetchAll(PDO::FETCH_COLUMN);

        if (!in_array('supplier_id', $columns)) {
            if (in_array('entity_id', $columns)) {
                $db->exec("ALTER TABLE supplier_prices CHANGE entity_id supplier_id INT NOT NULL");
                echo "✅ Columna 'entity_id' renombrada a 'supplier_id'.<br>";
            } else {
                $db->exec("ALTER TABLE supplier_prices ADD COLUMN supplier_id INT NOT NULL AFTER product_id");
                echo "✅ Columna 'supplier_id' AGREGADA.<br>";
            }
        } else {
            echo "✅ Columna 'supplier_id' ya existe.<br>";
        }
    }

    // 2. Fix Price Lists (Mostrador issues)
    echo "<h2>2. Verificando Listas de Precios</h2>";
    $lists = $db->query("SELECT * FROM price_lists")->fetchAll();
    if (count($lists) == 0) {
        $db->exec("INSERT INTO price_lists (name, margin_percent) VALUES ('Gremio', 25.0), ('Web', 40.0), ('Mostrador', 55.0)");
        echo "✅ Listas de precios por defecto creadas.<br>";
    } else {
        echo "ℹ️ Listas de precios encontradas (" . count($lists) . ").<br>";
    }


    echo "<h3>Proceso Finalizado. Por favor intenta guardar el producto nuevamente.</h3>";
    echo "<a href='config_productos_add.php'>Volver a Cargar Producto</a>";

} catch (Exception $e) {
    echo "<h1 style='color:red'>Error Fatal: " . $e->getMessage() . "</h1>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
