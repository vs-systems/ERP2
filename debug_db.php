<?php
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

if (session_status() === PHP_SESSION_NONE)
    session_start();

header('Content-Type: text/plain');

echo "SESSION INFO:\n";
print_r($_SESSION);

echo "\nDATABASE INFO:\n";
try {
    $db = Vsys\Lib\Database::getInstance();

    $comp_count = $db->query("SELECT company_id, COUNT(*) as count FROM entities GROUP BY company_id")->fetchAll(PDO::FETCH_ASSOC);
    echo "\nEntities per company:\n";
    print_r($comp_count);

    $entities = $db->query("SELECT id, name, tax_id FROM entities WHERE company_id = 1 LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    echo "\nSample Entities (Company 1):\n";
    print_r($entities);

    $prod_count = $db->query("SELECT company_id, COUNT(*) as count FROM products GROUP BY company_id")->fetchAll(PDO::FETCH_ASSOC);
    echo "\nProducts per company:\n";
    print_r($prod_count);

    $products = $db->query("SELECT id, sku, description FROM products WHERE company_id = 1 LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    echo "\nSample Products (Company 1):\n";
    print_r($products);

    $hik_check = $db->prepare("SELECT COUNT(*) FROM products WHERE description LIKE ? OR sku LIKE ?");
    $hik_check->execute(['%hik%', '%hik%']);
    echo "\nProducts matching 'hik': " . $hik_check->fetchColumn() . "\n";

    $gozzi_check = $db->prepare("SELECT COUNT(*) FROM entities WHERE name LIKE ?");
    $gozzi_check->execute(['%gozzi%']);
    echo "\nEntities matching 'gozzi': " . $gozzi_check->fetchColumn() . "\n";

    $users = $db->query("SELECT id, username, company_id FROM users")->fetchAll(PDO::FETCH_ASSOC);
    echo "\nUsers:\n";
    print_r($users);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
