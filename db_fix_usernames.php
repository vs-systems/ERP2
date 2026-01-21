<?php
require_once 'src/config/config.php';
require_once 'src/lib/Database.php';

try {
    $db = Vsys\Lib\Database::getInstance();

    // Add full_name column if doesn't exist
    $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS full_name VARCHAR(100) AFTER username");

    // Update some default names for common usernames if they exist
    $db->prepare("UPDATE users SET full_name = 'Javier Gozzi' WHERE username = 'admin' AND (full_name IS NULL OR full_name = '')")->execute();
    $db->prepare("UPDATE users SET full_name = 'Javier' WHERE username = 'javier' AND (full_name IS NULL OR full_name = '')")->execute();
    $db->prepare("UPDATE users SET full_name = 'Andrea' WHERE username = 'andrea' AND (full_name IS NULL OR full_name = '')")->execute();

    echo "Migration successful: full_name column added/verified.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

unlink(__FILE__);
?>