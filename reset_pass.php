<?php
/**
 * VS System ERP - Password Reset Tool
 * DELETE THIS FILE AFTER USE!
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

use Vsys\Lib\Database;

$new_password = 'vsys2026';
$hash = password_hash($new_password, PASSWORD_DEFAULT);

try {
    $db = Database::getInstance();
    $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE username = 'admin'");
    $stmt->execute([$hash]);

    if ($stmt->rowCount() > 0) {
        echo "<h3>¡Éxito!</h3>";
        echo "<p>La contraseña del usuario <strong>admin</strong> ha sido reseteada a: <strong>$new_password</strong></p>";
    } else {
        echo "<h3>Aviso</h3>";
        echo "<p>No se realizaron cambios. El usuario 'admin' ya podría tener esa contraseña o no se encontró.</p>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
