<?php
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

$db = Vsys\Lib\Database::getInstance();

echo "<h1>Debug Price Lists</h1>";

// 1. Show current state
echo "<h2>Current State</h2>";
$stmt = $db->query("SELECT * FROM price_lists");
$lists = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>" . print_r($lists, true) . "</pre>";

// 2. Try to update 'Mostrador' to 50
echo "<h2>Attempting Update 'Mostrador' to 50.00</h2>";
$name = 'Mostrador';
$target = 50.00;

// Find ID for Mostrador
$id = null;
foreach ($lists as $l) {
    if ($l['name'] === $name)
        $id = $l['id'];
}

if ($id) {
    $sql = "UPDATE price_lists SET margin_percent = ? WHERE id = ?";
    $res = $db->prepare($sql)->execute([$target, $id]);
    if ($res) {
        echo "Update Query Executed. Result: Success<br>";
    } else {
        echo "Update Query Failed.<br>";
    }

    // 3. Show new state
    echo "<h2>New State</h2>";
    $stmt = $db->query("SELECT * FROM price_lists WHERE id = $id");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<pre>" . print_r($row, true) . "</pre>";

    if (floatval($row['margin_percent']) == 50.0) {
        echo "<h3 style='color:green'>TEST PASSED: Value is 50</h3>";
    } else {
        echo "<h3 style='color:red'>TEST FAILED: Value is " . $row['margin_percent'] . "</h3>";
    }

} else {
    echo "ERROR: 'Mostrador' list not found.";
}
