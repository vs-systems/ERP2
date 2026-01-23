<?php
/**
 * Herramienta de Diagnóstico de Logs - VS System
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Diagnóstico de Logs PHP</h1>";

$log_path = ini_get('error_log');

if (empty($log_path)) {
    echo "<p style='color: orange;'>⚠️ El archivo de error_log no está definido en el archivo php.ini.</p>";
    // Intentar buscar archivos comunes
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
    echo "<h2>Últimas 50 líneas:</h2>";
    echo "<pre style='background: #f4f4f4; padding: 10px; border: 1px solid #ddd; overflow: auto; max-height: 500px;'>";

    $lines = file($log_path);
    $last_lines = array_slice($lines, -50);
    foreach ($last_lines as $line) {
        echo htmlspecialchars($line);
    }

    echo "</pre>";
} else {
    echo "<p style='color: red;'>❌ No se pudo encontrar el archivo de log en: " . ($log_path ?: 'Desconocido') . "</p>";
    echo "<p>Sugerencia: Revisa el panel de control de tu hosting (cPanel, Hestia, etc.) o busca un archivo llamado <code>error_log</code> en la carpeta raíz del ERP.</p>";
}

echo "<hr>";
echo "<h2>Diagnóstico de Base de Datos (Logística)</h2>";

require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

try {
    $db = Vsys\Lib\Database::getInstance();

    echo "<h3>Esquema de tabla 'logistics_process':</h3>";
    $stmt = $db->query("DESCRIBE logistics_process");
    echo "<pre>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['Field']} - {$row['Type']}\n";
    }
    echo "</pre>";

    echo "<h3>Últimos 5 registros en 'logistics_process':</h3>";
    $stmt = $db->query("SELECT * FROM logistics_process ORDER BY updated_at DESC LIMIT 5");
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #eee;'><th>ID</th><th>Cotización</th><th>Fase</th><th>Actualizado</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr><td>{$row['id']}</td><td>{$row['quote_number']}</td><td>{$row['current_phase']}</td><td>{$row['updated_at']}</td></tr>";
    }
    echo "</table>";

} catch (Exception $e) {
    echo "<p style='color: red;'>Error de base de datos: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='index.php'>Volver al Dashboard</a></p>";
