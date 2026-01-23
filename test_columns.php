<?php
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    $db = Vsys\Lib\Database::getInstance();
    $cols = ['tax_id', 'address', 'city', 'email', 'phone', 'state'];

    foreach ($cols as $col) {
        try {
            $db->query("SELECT $col FROM entities LIMIT 1");
            echo "Column $col: OK<br>";
        } catch (Exception $e) {
            echo "Column $col: FAIL (" . $e->getMessage() . ")<br>";
        }
    }
} catch (Exception $e) {
    echo "DB Connection FAIL: " . $e->getMessage();
}
?>