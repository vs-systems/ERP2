<?php
require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/lib/Database.php';

try {
    $db = Vsys\Lib\Database::getInstance();
    $tables = $db->query("SHOW TABLES LIKE 'crm_%'")->fetchAll(PDO::FETCH_COLUMN);
    echo "CRM Tables found: " . implode(', ', $tables) . "<br>";

    foreach ($tables as $table) {
        echo "<h4>Structure of $table:</h4>";
        $columns = $db->query("DESCRIBE $table")->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>" . print_r($columns, true) . "</pre>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
