<?php
require_once __DIR__ . '/src/lib/Database.php';
$db = Vsys\Lib\Database::getInstance();
$res = $db->query("DESCRIBE quotations")->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($res, JSON_PRETTY_PRINT);
