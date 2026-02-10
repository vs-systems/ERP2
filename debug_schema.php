<?php
require_once __DIR__ . '/src/lib/Database.php';
$db = \Vsys\Lib\Database::getInstance();
$stmt = $db->query("DESCRIBE exchange_rates");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
