<?php
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

try {
    $db = Vsys\Lib\Database::getInstance();
    $stm = $db->query("DESCRIBE entities");
    $columns = $stm->fetchAll(PDO::FETCH_ASSOC);
    echo "<h1>Entities Table Schema</h1>";
    echo "<pre>";
    print_r($columns);
    echo "</pre>";

    $stm2 = $db->query("DESCRIBE purchases");
    $cols2 = $stm2->fetchAll(PDO::FETCH_ASSOC);
    echo "<h1>Purchases Table Schema</h1>";
    echo "<pre>";
    print_r($cols2);
    echo "</pre>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
