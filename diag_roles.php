<?php
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

try {
    $db = Vsys\Lib\Database::getInstance();
    $stmt = $db->query("SELECT username, role, status FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "USER LIST:\n";
    foreach ($users as $u) {
        echo "- Username: {$u['username']}, Role: {$u['role']}, Status: {$u['status']}\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
