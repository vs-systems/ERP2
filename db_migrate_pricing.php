<?php
/**
 * VS System ERP - Pricing Migration
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

use Vsys\Lib\Database;

$db = Database::getInstance();

try {
    echo "Agregando columnas de precios a la tabla products...<br>";
    $db->exec("ALTER TABLE products ADD COLUMN price_gremio DECIMAL(15,2) DEFAULT NULL AFTER unit_price_usd");
    $db->exec("ALTER TABLE products ADD COLUMN price_web DECIMAL(15,2) DEFAULT NULL AFTER price_gremio");

    echo "Columnas agregadas con éxito.<br>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>Probablemente las columnas ya existen.";
}
?>
<a href="index.php">Volver al ERP</a>