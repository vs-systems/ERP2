<?php
require_once 'src/lib/Database.php';
$db = Vsys\Lib\Database::getInstance();

try {
    $db->exec("ALTER TABLE products 
               ADD COLUMN stock_min INT DEFAULT 0, 
               ADD COLUMN stock_transit INT DEFAULT 0, 
               ADD COLUMN stock_incoming INT DEFAULT 0, 
               ADD COLUMN incoming_date DATE DEFAULT NULL");
    echo "Columnas de stock avanzado agregadas con éxito.";
} catch (Exception $e) {
    echo "Error o las columnas ya existen: " . $e->getMessage();
}
