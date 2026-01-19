<?php
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
$db = Vsys\Lib\Database::getInstance();
foreach (['products', 'crm_leads', 'categories'] as $table) {
    echo "--- $table ---\n";
    $stmt = $db->query("DESCRIBE $table");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['Field']} - {$row['Type']}\n";
    }
}
?>




