<?php
/**
 * VS System ERP - Manual Exchange Rate Fix (BNA)
 * Ejecutar para actualizar la cotización a $1455.00
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

use Vsys\Lib\Database;

try {
    $db = Database::getInstance();
    $rate = 1455.00;
    $stmt = $db->prepare("INSERT INTO exchange_rates (rate, source) VALUES (?, 'BNA')");
    $stmt->execute([$rate]);
    echo "<h1>Cotización Actualizada</h1>";
    echo "<p>Se ha establecido el dólar en <strong>ARS $rate</strong> (Fuente: BNA).</p>";
    echo "<a href='dashboard.php'>Volver al Dashboard</a>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>