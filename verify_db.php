<?php
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

$db = Vsys\Lib\Database::getInstance();

$tables = ['companies', 'users', 'products', 'entities', 'quotations'];
echo "<h1>Migration Verification</h1>";

foreach ($tables as $table) {
    try {
        $columns = $db->query("SHOW COLUMNS FROM $table")->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>Table: <b>$table</b> -> Columns: " . implode(', ', $columns) . "</p>";

        $indexes = $db->query("SHOW INDEX FROM $table")->fetchAll();
        echo "<ul>";
        foreach ($indexes as $idx) {
            echo "<li>Index: {$idx['Key_name']} (Column: {$idx['Column_name']})</li>";
        }
        echo "</ul>";
    } catch (Exception $e) {
        echo "<p style='color:red;'>Table: <b>$table</b> NOT FOUND or error: " . $e->getMessage() . "</p>";
    }
}
