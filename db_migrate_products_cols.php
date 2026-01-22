<?php
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

try {
    $db = Vsys\Lib\Database::getInstance();

    echo "<h1>Iniciando Migración de Productos (Nuevas Columnas)</h1>";

    // Add has_serial_number column
    try {
        $db->exec("ALTER TABLE products ADD COLUMN has_serial_number TINYINT(1) DEFAULT 0 AFTER brand");
        echo "✅ Columna 'has_serial_number' agregada correctamente.<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "Duplicate column name") !== false) {
            echo "ℹ️ La columna 'has_serial_number' ya existe.<br>";
        } else {
            echo "❌ Error agregando 'has_serial_number': " . $e->getMessage() . "<br>";
        }
    }

    // Add stock_current column
    try {
        $db->exec("ALTER TABLE products ADD COLUMN stock_current INT DEFAULT 0 AFTER has_serial_number");
        echo "✅ Columna 'stock_current' agregada correctamente.<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "Duplicate column name") !== false) {
            echo "ℹ️ La columna 'stock_current' ya existe.<br>";
        } else {
            echo "❌ Error agregando 'stock_current': " . $e->getMessage() . "<br>";
        }
    }

    // Add image_url column
    try {
        $db->exec("ALTER TABLE products ADD COLUMN image_url TEXT NULL AFTER barcode");
        echo "✅ Columna 'image_url' agregada correctamente.<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "Duplicate column name") !== false) {
            echo "ℹ️ La columna 'image_url' ya existe.<br>";
        } else {
            echo "❌ Error agregando 'image_url': " . $e->getMessage() . "<br>";
        }
    }

    echo "<h2>Migración de Productos Completada.</h2>";

} catch (Exception $e) {
    echo "<h1>Error Fatal: " . $e->getMessage() . "</h1>";
}
