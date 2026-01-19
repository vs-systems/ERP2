<?php
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
use Vsys\Lib\Database;

$db = Database::getInstance();
echo "<h3>Users Table Schema</h3><pre>";
try {
    $stmt = $db->query("DESCRIBE users");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

    echo "\n<h3>First User Data (Sensitive data hidden)</h3>";
    $stmt = $db->query("SELECT * FROM users LIMIT 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        unset($user['password']);
        unset($user['password_hash']);
        print_r($user);
    } else {
        echo "No users found.";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
echo "</pre>";
