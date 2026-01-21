<?php
require_once 'src/config/config.php';
require_once 'src/lib/Database.php';
$db = Vsys\Lib\Database::getInstance();
$stmt = $db->query("DESCRIBE users");
echo "<pre>";
print_r($stmt->fetchAll());
echo "</pre>";
unlink(__FILE__);
?>