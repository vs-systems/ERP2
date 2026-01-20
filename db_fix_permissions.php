<?php
require_once 'src/lib/Database.php';
$db = Vsys\Lib\Database::getInstance();

try {
    $db->exec("ALTER TABLE users ADD COLUMN permissions JSON DEFAULT NULL");
    echo "Columna 'permissions' agregada con éxito.";
} catch (Exception $e) {
    echo "Error o la columna ya existe: " . $e->getMessage();
}
