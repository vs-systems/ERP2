<?php
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

$db = Vsys\Lib\Database::getInstance();
$tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

echo "<h1>Listado de Tablas</h1><ul>";
foreach ($tables as $table) {
    echo "<li>$table</li>";
}
echo "</ul>";

foreach ($tables as $table) {
    echo "<h2>Tabla: $table</h2><pre>";
    $stmt = $db->query("DESCRIBE $table");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['Field']} - {$row['Type']}\n";
    }
    echo "</pre>";
}
