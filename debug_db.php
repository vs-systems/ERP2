<?php
/**
 * VS System ERP - Database Debugger
 */

require_once __DIR__ . '/src/config/config.php';

echo "<h1>Depurador de Base de Datos</h1>";
echo "<ul>";
echo "<li><strong>Servidor:</strong> " . DB_HOST . "</li>";
echo "<li><strong>Base de Datos:</strong> " . DB_NAME . "</li>";
echo "<li><strong>Usuario configurado:</strong> " . DB_USER . "</li>";
echo "<li><strong>Password:</strong> " . (empty(DB_PASS) ? "<em>Vacio</em>" : "<em>Tiene valor</em>") . "</li>";
echo "</ul>";

try {
    $dsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    echo "<p style='color: green;'>✅ Conexión al servidor MySQL exitosa.</p>";

    // Check if DB exists
    $stmt = $pdo->query("SHOW DATABASES LIKE '" . DB_NAME . "'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ La base de datos <strong>" . DB_NAME . "</strong> existe.</p>";

        // Try to connect to specific DB
        $pdo->query("USE " . DB_NAME);
        echo "<p style='color: green;'>✅ Acceso a la base de datos concedido.</p>";

        $tables = $pdo->query("SHOW TABLES");
        echo "<p>Tablas encontradas: " . $tables->rowCount() . "</p>";
    } else {
        echo "<p style='color: red;'>❌ La base de datos <strong>" . DB_NAME . "</strong> NO existe. Por favor créala en PHPMyAdmin.</p>";
    }

} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error de Conexión: " . $e->getMessage() . "</p>";
    echo "<p><strong>Sugerencia:</strong> Verifica que el usuario tenga permisos 'Globales' o específicos sobre la base de datos en PHPMyAdmin (Sección 'Cuentas de Usuario' -> 'Editar Privilegios').</p>";
}
?>
