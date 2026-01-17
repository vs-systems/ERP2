<?php
// restore_files.php - Restauración de Archivos Críticos v17 (Logística Fix Table Name)
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

echo "<h1>Restaurador de Archivos Críticos v17 (Logística Fix)</h1>";

function writeFile($path, $content)
{
    echo "<p>Escribiendo: $path ... ";
    $dir = dirname($path);
    if (!is_dir($dir))
        mkdir($dir, 0755, true);
    if (file_exists($path))
        unlink($path);
    if (file_put_contents($path, $content) !== false) {
        echo "<span style='color:green'> [OK] </span></p>";
        if (function_exists('opcache_invalidate'))
            opcache_invalidate($path, true);
        return true;
    } else {
        echo "<span style='color:red'> [ERROR] </span></p>";
        return false;
    }
}

// 1. MySQL Database Migrations (Fix: Rename quotes to quotations)
try {
    $db = \Vsys\Lib\Database::getInstance();
    echo "<h3>Ejecutando Migraciones MySQL (v17)...</h3>";

    $queries = [
        "CREATE TABLE IF NOT EXISTS transports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            contact_person VARCHAR(255),
            phone VARCHAR(100),
            email VARCHAR(255),
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS logistics_remitos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            quote_number VARCHAR(100),
            transport_id INT,
            remito_number VARCHAR(100) UNIQUE,
            status VARCHAR(50) DEFAULT 'Pending',
            tracking_url TEXT,
            signed_remito_path TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            dispatched_at TIMESTAMP NULL,
            delivered_at TIMESTAMP NULL,
            INDEX(quote_number),
            INDEX(transport_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS operation_documents (
            id INT AUTO_INCREMENT PRIMARY KEY,
            entity_id VARCHAR(100) NOT NULL,
            entity_type VARCHAR(50) NOT NULL,
            doc_type VARCHAR(50) NOT NULL,
            file_path VARCHAR(255) NOT NULL,
            notes TEXT,
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        // CORRECCIÓN: La tabla se llama 'quotations', no 'quotes'
        "ALTER TABLE quotations ADD COLUMN IF NOT EXISTS authorized_dispatch TINYINT(1) DEFAULT 0;",
        "ALTER TABLE quotations ADD COLUMN IF NOT EXISTS payment_status VARCHAR(50) DEFAULT 'Pending';"
    ];

    foreach ($queries as $q) {
        try {
            $db->exec($q);
            echo "<p style='color:blue'>SQL OK: " . substr($q, 0, 50) . "...</p>";
        } catch (Exception $ex) {
            echo "<p style='color:orange'>SQL Info: " . $ex->getMessage() . "</p>";
        }
    }
    echo "<p style='color:green'><b>Migraciones completadas.</b></p>";
} catch (Exception $e) {
    echo "<p style='color:red'>Error en BD: " . $e->getMessage() . "</p>";
}

// 2. Logistics Backend Fix
$contentLogisticsClass = <<<'PHP'
<?php
namespace Vsys\Modules\Logistica;
use Vsys\Lib\Database;
class Logistics {
    private $db;
    public function __construct() { $this->db = Database::getInstance(); }
    public function getOrdersForPreparation() {
        // Fix: Usar 'quotations' en lugar de 'quotes'
        return $this->db->query("SELECT q.*, c.name as client_name FROM quotations q LEFT JOIN clients c ON q.client_id = c.id WHERE q.payment_status = 'Paid' OR q.authorized_dispatch = 1 ORDER BY q.created_at DESC")->fetchAll();
    }
    public function getTransports($onlyActive = true) {
        $sql = "SELECT * FROM transports" . ($onlyActive ? " WHERE is_active = 1" : "") . " ORDER BY name";
        return $this->db->query($sql)->fetchAll();
    }
    public function saveTransport($data) {
        if (isset($data['id']) && $data['id']) {
            $stmt = $this->db->prepare("UPDATE transports SET name = ?, contact_person = ?, phone = ?, email = ?, is_active = ? WHERE id = ?");
            return $stmt->execute([$data['name'], $data['contact_person'], $data['phone'], $data['email'], $data['is_active'], $data['id']]);
        } else {
            $stmt = $this->db->prepare("INSERT INTO transports (name, contact_person, phone, email) VALUES (?, ?, ?, ?)");
            return $stmt->execute([$data['name'], $data['contact_person'], $data['phone'], $data['email']]);
        }
    }
    public function createRemito($quoteNumber, $transportId) {
        $remitoNum = 'REM-' . strtoupper(substr(uniqid(), -6));
        $stmt = $this->db->prepare("INSERT INTO logistics_remitos (quote_number, transport_id, remito_number, status) VALUES (?, ?, ?, 'Pending')");
        return $stmt->execute([$quoteNumber, $transportId, $remitoNum]) ? $remitoNum : false;
    }
}
PHP;
writeFile(__DIR__ . '/src/modules/logistica/Logistics.php', $contentLogisticsClass);

echo "<hr><p>¡Actualización v17 Completada! Error de tabla 'quotes' -> 'quotations' corregido.</p>";
?>