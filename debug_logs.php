<?php
/**
 * Herramienta de Diagnóstico de Logs y Base de Datos - VS System
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Diagnóstico de Logs PHP</h1>";

$log_path = ini_get('error_log');

if (empty($log_path)) {
    echo "<p style='color: orange;'>⚠️ El archivo de error_log no está definido en el archivo php.ini.</p>";
    $common_logs = ['error_log', 'php_error.log', '../logs/php_error.log'];
    foreach ($common_logs as $log) {
        if (file_exists($log)) {
            $log_path = realpath($log);
            break;
        }
    }
}

if ($log_path && file_exists($log_path)) {
    echo "<p><strong>Ruta del log:</strong> $log_path</p>";
    echo "<h2>Últimas 100 líneas:</h2>";
    echo "<pre style='background: #f4f4f4; padding: 10px; border: 1px solid #ddd; overflow: auto; max-height: 500px;'>";

    $lines = file($log_path);
    $last_lines = array_slice($lines, -100);
    foreach ($last_lines as $line) {
        echo htmlspecialchars($line);
    }

    echo "</pre>";
} else {
    echo "<p style='color: red;'>❌ No se pudo encontrar el archivo de log en: " . ($log_path ?: 'Desconocido') . "</p>";
}

echo "<hr>";
echo "<h2>Diagnóstico de Base de Datos (Logística)</h2>";

require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

try {
    $db = Vsys\Lib\Database::getInstance();

    foreach (['logistics_process', 'quotations'] as $table) {
        echo "<h3>Esquema de tabla '$table':</h3>";
        $stmt = $db->query("DESCRIBE $table");
        echo "<pre>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "{$row['Field']} - {$row['Type']}\n";
        }
        echo "</pre>";
    }

    echo "<h3>Últimos 10 registros en 'logistics_process':</h3>";
    $stmt = $db->query("SELECT * FROM logistics_process ORDER BY updated_at DESC LIMIT 10");
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #eee;'><th>ID</th><th>Cotización</th><th>Fase</th><th>Actualizado</th><th>Company ID</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr><td>{$row['id']}</td><td>{$row['quote_number']}</td><td>{$row['current_phase']}</td><td>{$row['updated_at']}</td><td>" . ($row['company_id'] ?? 'NULL') . "</td></tr>";
    }
    echo "</table>";

} catch (Exception $e) {
    echo "<p style='color: red;'>Error de base de datos: " . $e->getMessage() . "</p>";
}

echo "<h3>HEX Check (Hidden Characters):</h3>";
try {
    $stmtHex = $db->query("SELECT quote_number, HEX(quote_number) as hex_val FROM logistics_process ORDER BY updated_at DESC LIMIT 5");
    echo "<ul>";
    while ($row = $stmtHex->fetch(PDO::FETCH_ASSOC)) {
        echo "<li>{$row['quote_number']}: <code>{$row['hex_val']}</code></li>";
    }
    echo "</ul>";
} catch (Exception $e) {
}

echo "<h3>Configuración Servidor/PHP:</h3>";
echo "<ul>";
echo "<li>display_errors: " . ini_get('display_errors') . "</li>";
echo "<li>html_errors: " . ini_get('html_errors') . "</li>";
echo "<li>error_log: " . ini_get('error_log') . "</li>";
echo "<li>Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "</li>";
echo "<li>PHP Version: " . phpversion() . "</li>";
echo "</ul>";

echo "<hr>";
echo "<p><a href='index.php'>Volver al Dashboard</a></p>";
