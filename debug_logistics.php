<?php
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

$db = Vsys\Lib\Database::getInstance();

echo "--- TABLE SCHEMA: logistics_process ---\n";
$stmt = $db->query("DESCRIBE logistics_process");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}

echo "\n--- TABLE DATA (Sample) ---\n";
$stmt = $db->query("SELECT * FROM logistics_process ORDER BY updated_at DESC LIMIT 10");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}

echo "\n--- CHECKING SPECIFIC QUOTES ---\n";
$quotes = ['VS-2026-01-0006_01', 'VS-2026-01-0005_01'];
foreach ($quotes as $q) {
    echo "Checking $q:\n";
    $stmt = $db->prepare("SELECT * FROM quotations WHERE quote_number = ?");
    $stmt->execute([$q]);
    print_r($stmt->fetch(PDO::FETCH_ASSOC));

    $stmt = $db->prepare("SELECT * FROM logistics_process WHERE quote_number = ?");
    $stmt->execute([$q]);
    print_r($stmt->fetch(PDO::FETCH_ASSOC));
}
