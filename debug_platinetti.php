<?php
require_once 'src/lib/Database.php';
$db = Vsys\Lib\Database::getInstance();
$name = 'EDGARDO PLATINETTI';
$entity = $db->query("SELECT id, name FROM entities WHERE name LIKE '%$name%'")->fetch();
if ($entity) {
    echo "ID: " . $entity['id'] . " | Name: " . $entity['name'] . "\n";
    $movements = $db->query("SELECT * FROM client_movements WHERE client_id = " . $entity['id'])->fetchAll();
    echo "Movements:\n";
    print_r($movements);
} else {
    echo "Entity not found\n";
}
