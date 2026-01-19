<?php
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

use Vsys\Lib\Database;

$db = Database::getInstance();

echo "--- TABLE Structure: users ---\n";
try {
    $q = $db->query("DESCRIBE users");
    while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n--- CHECK IF VIEW ---\n";
try {
    $q = $db->query("SHOW FULL TABLES WHERE Tables_in_gozziar_vs_system_erp = 'users'");
    print_r($q->fetch(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>