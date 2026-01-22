<?php
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

$db = Vsys\Lib\Database::getInstance();

echo "Iniciando migración de tabla quotations...\n";

try {
    // Add Tax Columns
    $db->query("ALTER TABLE quotations ADD COLUMN total_iva_105 DECIMAL(15,2) DEFAULT 0.00 AFTER subtotal_usd");
    echo "Columna total_iva_105 agregada.\n";
} catch (Exception $e) {
    echo "Nota: " . $e->getMessage() . "\n";
}

try {
    // Add Tax Columns
    $db->query("ALTER TABLE quotations ADD COLUMN total_iva_21 DECIMAL(15,2) DEFAULT 0.00 AFTER total_iva_105");
    echo "Columna total_iva_21 agregada.\n";
} catch (Exception $e) {
    echo "Nota: " . $e->getMessage() . "\n";
}

try {
    // Add Hash Column
    $db->query("ALTER TABLE quotations ADD COLUMN public_hash CHAR(32) DEFAULT NULL AFTER observations");
    $db->query("ALTER TABLE quotations ADD INDEX (public_hash)");
    echo "Columna public_hash agregada.\n";
} catch (Exception $e) {
    echo "Nota: " . $e->getMessage() . "\n";
}

echo "Migración completada con éxito.";
