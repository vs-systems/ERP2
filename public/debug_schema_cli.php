<?php
require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/lib/Database.php';

$db = Vsys\Lib\Database::getInstance();

$tables = ['entities', 'products', 'quotations', 'quotation_items'];

foreach ($tables as $table) {
    echo "\n--- Tabla: $table ---\n";
    $stmt = $db->query("DESCRIBE $table");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    printf("%-20s %-20s %-5s %-5s\n", "Field", "Type", "Null", "Key");
    foreach ($columns as $col) {
        printf("%-20s %-20s %-5s %-5s\n", $col['Field'], $col['Type'], $col['Null'], $col['Key']);
    }
}
?>