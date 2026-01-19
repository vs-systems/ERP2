<?php
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

try {
    $db = Vsys\Lib\Database::getInstance();

    // Check if permissions column exists
    $columns = $db->query("SHOW COLUMNS FROM users LIKE 'permissions'")->fetchAll();

    if (empty($columns)) {
        $db->exec("ALTER TABLE users ADD COLUMN permissions TEXT NULL");
        echo "Column 'permissions' added successfully.";
    } else {
        echo "Column 'permissions' already exists.";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
