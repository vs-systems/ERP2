<?php
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

$db = Vsys\Lib\Database::getInstance();
$stmt = $db->prepare('SELECT name, type FROM entities WHERE name LIKE ?');
$stmt->execute(['%EDGARDO%']);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($results);
