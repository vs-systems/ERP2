<?php
// check_schema.php
require_once 'src/config/config.php';
require_once 'src/lib/Database.php';

use Vsys\Lib\Database;

$db = Database::getInstance();
$tables = ['products', 'price_lists'];

echo "<h1>Database Schema Check</h1>";

foreach ($tables as $table) {
    try {
        echo "<h2>Table: $table</h2>";
        $stmt = $db->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($columns);
        echo "</pre>";
    } catch (Exception $e) {
        echo "<p style='color:red'>Table $table not found: " . $e->getMessage() . "</p>";
    }
}
?>




