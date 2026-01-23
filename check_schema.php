<?php
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
$db = Vsys\Lib\Database::getInstance();
$tables = ['quotations', 'purchases'];
foreach ($tables as $t) {
    echo "--- Schema for $t ---\n";
    $cols = $db->query("DESCRIBE $t")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        echo "{$c['Field']} ({$c['Type']})\n";
    }
}
?>