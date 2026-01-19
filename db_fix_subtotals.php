<?php
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

try {
    $db = Vsys\Lib\Database::getInstance();

    // Fix quotations
    $db->exec("UPDATE quotations SET subtotal_usd = total_usd WHERE subtotal_usd IS NULL OR subtotal_usd = 0;");

    // Fix purchases
    $db->exec("UPDATE purchases SET subtotal_usd = total_usd, subtotal_ars = total_ars WHERE subtotal_usd IS NULL OR subtotal_usd = 0;");

    echo "âœ… Datos heredados actualizados (subtotales sincronizados con totales).<br>";
} catch (Exception $e) {
    echo "âŒ ERROR: " . $e->getMessage();
}





