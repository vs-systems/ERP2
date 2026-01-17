<?php
require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/lib/Database.php';

$db = Vsys\Lib\Database::getInstance();

echo "<h1>Esquema de Base de Datos</h1>";

$tables = ['entities', 'products', 'quotations', 'quotation_items'];

foreach ($tables as $table) {
    echo "<h2>Tabla: $table</h2>";
    $stmt = $db->query("DESCRIBE $table");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1'><tr><th>Columna</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td><td>{$col['Default']}</td></tr>";
    }
    echo "</table>";
}
?>