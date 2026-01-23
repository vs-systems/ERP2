<?php
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

$db = Vsys\Lib\Database::getInstance();

echo "Migrating Brands Table...<br>";

// Create brands table
$sql = "CREATE TABLE IF NOT EXISTS brands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

try {
    $db->query($sql);
    echo "Table 'brands' created or already exists.<br>";

    // Seed from existing products
    $seedSql = "INSERT IGNORE INTO brands (name) SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand != ''";
    $db->query($seedSql);
    echo "Seeded brands from products table.<br>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>