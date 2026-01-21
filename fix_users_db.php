<?php
/**
 * VS System ERP - Database Schema Fix for Users
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

use Vsys\Lib\Database;

try {
    $db = Database::getInstance();
    echo "Checking 'users' table...<br>";

    // Check if column exists
    $cols = $db->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('entity_id', $cols)) {
        echo "Adding 'entity_id' to 'users'...<br>";
        $db->exec("ALTER TABLE users ADD COLUMN entity_id INT NULL AFTER id");
    }

    if (!in_array('status', $cols)) {
        echo "Adding 'status' to 'users'...<br>";
        $db->exec("ALTER TABLE users ADD COLUMN status ENUM('Active', 'Inactive', 'Pending') DEFAULT 'Active' AFTER role");
    }

    // Ensure 'active' column is handled if present (legacy)
    if (in_array('active', $cols)) {
        echo "Migrating legacy 'active' status...<br>";
        $db->exec("UPDATE users SET status = 'Active' WHERE active = 1 AND status = 'Active'");
        $db->exec("UPDATE users SET status = 'Inactive' WHERE active = 0 AND status = 'Active'");
    }

    echo "Table 'users' is now correctly structured.<br>";
    echo "<a href='usuarios.php'>Go back to Users Panel</a>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>