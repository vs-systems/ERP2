<?php
require_once 'src/config/config.php';
require_once 'src/lib/Database.php';

$db = Vsys\Lib\Database::getInstance();
$tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

echo "--- DB SCHEMA ANALYSIS ---\n";
foreach ($tables as $table) {
    echo "TABLE: $table\n";
    $columns = $db->query("DESCRIBE $table")->fetchAll();
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
    echo "\n";
}
