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

    $prod_count = $db->query("SELECT company_id, COUNT(*) as count FROM products GROUP BY company_id")->fetchAll(PDO::FETCH_ASSOC);
    echo "\nProducts per company:\n";
    print_r($prod_count);

    $users = $db->query("SELECT id, username, company_id FROM users")->fetchAll(PDO::FETCH_ASSOC);
    echo "\nUsers:\n";
    print_r($users);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
