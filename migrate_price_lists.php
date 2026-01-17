<?php
// migrate_price_lists.php
require_once 'src/config/config.php';
require_once 'src/lib/Database.php';

use Vsys\Lib\Database;

try {
    $db = Database::getInstance();

    echo "Creating price_lists table...\n";
    $sql = "CREATE TABLE IF NOT EXISTS price_lists (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE,
        margin_percent DECIMAL(5,2) DEFAULT 0.00,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $db->query($sql);
    echo "Table created.\n";

    // Insert Defaults
    $defaults = [
        ['name' => 'Gremio', 'margin' => 30.00],
        ['name' => 'Web', 'margin' => 40.00],
        ['name' => 'MercadoLibre', 'margin' => 50.00]
    ];

    echo "Inserting default lists...\n";
    $stmt = $db->prepare("INSERT IGNORE INTO price_lists (name, margin_percent) VALUES (:name, :margin)");
    foreach ($defaults as $def) {
        $stmt->execute([':name' => $def['name'], ':margin' => $def['margin']]);
    }
    echo "Defaults inserted.\n";

} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}
?>