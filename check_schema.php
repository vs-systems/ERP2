<?php
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

use Vsys\Lib\Database;

try {
    $db = Database::getInstance();
    $tables = ['entities', 'logistics_process', 'quotations', 'users'];
    foreach ($tables as $table) {
        echo "---\nTable: $table\n";
        $result = $db->query("DESC $table");
        $cols = $result->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $c)
            echo "  {$c['Field']} | {$c['Type']} | {$c['Null']} | {$c['Key']} | {$c['Default']} | {$c['Extra']}\n";

        echo "Status:\n";
        $result = $db->query("SHOW TABLE STATUS LIKE '$table'");
        $status = $result->fetch(PDO::FETCH_ASSOC);
        echo "  Auto_increment: " . ($status['Auto_increment'] ?? 'N/A') . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>