<?php
require_once __DIR__ . '/src/lib/Database.php';
$db = Vsys\Lib\Database::getInstance();
$res = $db->query("SELECT type, COUNT(*) as count FROM entities GROUP BY type")->fetchAll();
echo "Tipos de entidades:\n";
print_r($res);

$providers = $db->query("SELECT id, name FROM entities WHERE type = 'provider' LIMIT 5")->fetchAll();
echo "\nEjemplos de proveedores:\n";
print_r($providers);

$movements = $db->query("SELECT COUNT(*) FROM provider_movements")->fetchColumn();
echo "\nTotal movimientos proveedores: $movements\n";
?>