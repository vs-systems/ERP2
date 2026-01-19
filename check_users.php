<?php
/**
 * VS System ERP - Temporary User Check Tool
 * DELETE THIS FILE AFTER USE!
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

use Vsys\Lib\Database;

try {
    $db = Database::getInstance();
    $stmt = $db->query("SELECT username, role, status FROM users");
    $users = $stmt->fetchAll();

    echo "<h3>Lista de Usuarios en la Base de Datos:</h3>";
    echo "<ul>";
    foreach ($users as $user) {
        echo "<li><strong>Usuario:</strong> {$user['username']} | <strong>Rol:</strong> {$user['role']} | <strong>Estado:</strong> {$user['status']}</li>";
    }
    echo "</ul>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
