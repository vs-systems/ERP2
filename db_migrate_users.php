<?php
/**
 * Migration: Create Users Table & Update Entities
 */

require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

use Vsys\Lib\Database;

try {
    $db = Database::getInstance();
    echo "<h1>Database Migration - Users & Entities</h1>";

    // 1. Create Users Table
    $sqlUsers = "CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `entity_id` INT NULL COMMENT 'Link to entities (for sellers or clients)',
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `password_hash` VARCHAR(255) NOT NULL,
        `role` ENUM('Admin', 'Vendedor', 'Contabilidad', 'Deposito', 'Compras', 'Marketing', 'Sistemas', 'Cliente', 'Invitado') NOT NULL,
        `status` ENUM('Active', 'Inactive', 'Pending') DEFAULT 'Active',
        `last_login` DATETIME NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $db->exec($sqlUsers);
    echo "Table 'users' created or already exists.<br>";

    // 2. Update Entities (Clients/Suppliers/Sellers)
    $entityCols = $db->query("DESCRIBE entities")->fetchAll(PDO::FETCH_COLUMN);

    // Seller assignment
    if (!in_array('seller_id', $entityCols)) {
        $db->exec("ALTER TABLE entities ADD COLUMN seller_id INT NULL AFTER is_enabled");
        echo "Column 'seller_id' added to 'entities'.<br>";
    }

    // Client profile
    if (!in_array('client_profile', $entityCols)) {
        $db->exec("ALTER TABLE entities ADD COLUMN client_profile ENUM('Gremio', 'Web', 'ML', 'Otro') DEFAULT 'Otro' AFTER seller_id");
        echo "Column 'client_profile' added to 'entities'.<br>";
    }

    // Verification status (for clients)
    if (!in_array('is_verified', $entityCols)) {
        $db->exec("ALTER TABLE entities ADD COLUMN is_verified BOOLEAN DEFAULT 0 AFTER client_profile");
        echo "Column 'is_verified' added to 'entities'.<br>";
    }

    // 3. Create initial Admin user if none exists
    $count = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($count == 0) {
        $adminPass = password_hash('Andrea1910@', PASSWORD_DEFAULT);
        $db->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)")
            ->execute(['admin', $adminPass, 'Admin']);
        echo "Default admin user created (admin / Andrea1910@).<br>";
    }

    echo "<h2>Migration completed successfully.</h2>";

} catch (Exception $e) {
    echo "<h2 style='color:red'>Migration Error: " . $e->getMessage() . "</h2>";
}
