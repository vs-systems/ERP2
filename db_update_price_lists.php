<?php
// db_update_price_lists.php
require_once 'src/config/config.php';
require_once 'src/lib/Database.php';

use Vsys\Lib\Database;

try {
    $db = Database::getInstance();

    echo "Updating price_lists names and margins...\n";

    // Ensure Mostrador exists (replacing MercadoLibre or adding if missing)
    $db->query("UPDATE price_lists SET name = 'Mostrador' WHERE name = 'MercadoLibre'");

    $lists = [
        ['name' => 'Gremio', 'margin' => 25.00],
        ['name' => 'Web', 'margin' => 40.00],
        ['name' => 'Mostrador', 'margin' => 55.00]
    ];

    $stmt = $db->prepare("INSERT INTO price_lists (name, margin_percent) VALUES (:name, :margin) 
                          ON DUPLICATE KEY UPDATE margin_percent = VALUES(margin_percent)");

    foreach ($lists as $l) {
        $stmt->execute([':name' => $l['name'], ':margin' => $l['margin']]);
        echo "Set {$l['name']} to {$l['margin']}%\n";
    }

    echo "Price lists updated successfully.\n";

} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}
?>