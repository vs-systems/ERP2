<?php
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

try {
    $db = Vsys\Lib\Database::getInstance();

    echo "<h1>Iniciando Migración de Entidades (Geo)</h1>";

    // Add city column
    try {
        $db->exec("ALTER TABLE entities ADD COLUMN city VARCHAR(100) NULL AFTER is_verified");
        echo "✅ Columna 'city' agregada correctamente.<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "Duplicate column name") !== false) {
            echo "ℹ️ La columna 'city' ya existe.<br>";
        } else {
            echo "❌ Error agregando 'city': " . $e->getMessage() . "<br>";
        }
    }

    // Add lat column
    try {
        $db->exec("ALTER TABLE entities ADD COLUMN lat VARCHAR(20) NULL AFTER city");
        echo "✅ Columna 'lat' agregada correctamente.<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "Duplicate column name") !== false) {
            echo "ℹ️ La columna 'lat' ya existe.<br>";
        } else {
            echo "❌ Error agregando 'lat': " . $e->getMessage() . "<br>";
        }
    }

    // Add lng column
    try {
        $db->exec("ALTER TABLE entities ADD COLUMN lng VARCHAR(20) NULL AFTER lat");
        echo "✅ Columna 'lng' agregada correctamente.<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "Duplicate column name") !== false) {
            echo "ℹ️ La columna 'lng' ya existe.<br>";
        } else {
            echo "❌ Error agregando 'lng': " . $e->getMessage() . "<br>";
        }
    }

    echo "<h2>Migración Completada.</h2>";
    echo "<p><a href='config_entities.php?type=client'>Volver a Cargar Cliente</a></p>";

} catch (Exception $e) {
    echo "<h1>Error Fatal: " . $e->getMessage() . "</h1>";
}
