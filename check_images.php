<?php
require_once 'src/config/config.php';
require_once 'src/lib/Database.php';
$db = Vsys\Lib\Database::getInstance();
$stmt = $db->query("SELECT id, image_url FROM products WHERE image_url IS NOT NULL AND image_url != '' LIMIT 20");
echo "<table>";
foreach ($stmt->fetchAll() as $row) {
    echo "<tr><td>{$row['id']}</td><td>" . htmlspecialchars($row['image_url']) . "</td></tr>";
}
echo "</table>";
unlink(__FILE__);
?>