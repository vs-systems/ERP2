<?php
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

try {
    $db = Vsys\Lib\Database::getInstance();

    echo "<h2>Users Table</h2>";
    try {
        $stm = $db->query("DESCRIBE users");
        echo "<pre>" . print_r($stm->fetchAll(PDO::FETCH_ASSOC), true) . "</pre>";
    } catch (Exception $e) {
        echo "Users table not found or error: " . $e->getMessage() . "<br>";
    }

    echo "<h2>Brands Table</h2>";
    try {
        $stm = $db->query("DESCRIBE brands");
        echo "<pre>" . print_r($stm->fetchAll(PDO::FETCH_ASSOC), true) . "</pre>";
    } catch (Exception $e) {
        echo "Brands table not found or error: " . $e->getMessage() . "<br>";
    }

    echo "<h2>CRM Tables (Pipelines/Sources)</h2>";
    try {
        $stm = $db->query("SHOW TABLES LIKE 'crm_%'");
        echo "<pre>" . print_r($stm->fetchAll(PDO::FETCH_COLUMN), true) . "</pre>";
    } catch (Exception $e) {
        echo "CRM tables check failed: " . $e->getMessage() . "<br>";
    }

} catch (Exception $e) {
    echo "DB Error: " . $e->getMessage();
}
