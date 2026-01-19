<?php
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
use Vsys\Lib\Database;

$db = Database::getInstance();
$output = "Users Table Schema:\n";
try {
    $stmt = $db->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        $output .= "Field: " . $col['Field'] . " | Type: " . $col['Type'] . "\n";
    }
} catch (Exception $e) {
    $output .= "Error: " . $e->getMessage();
}
file_put_contents(__DIR__ . '/schema_output.txt', $output);
echo "Schema saved to schema_output.txt";
