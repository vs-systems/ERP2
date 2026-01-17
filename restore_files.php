<?php
// restore_files.php - Restauración de Archivos Críticos v16 (Full Logistics MySQL)
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

echo "<h1>Restaurador de Archivos Críticos v16 (Logística Completa)</h1>";

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

// 1. MySQL Database Migrations (Phase 4)
try {
    $db = \Vsys\Lib\Database::getInstance();
    echo "<h3>Ejecutando Migraciones MySQL...</h3>";

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

        "ALTER TABLE quotes ADD COLUMN IF NOT EXISTS authorized_dispatch TINYINT(1) DEFAULT 0;",
        "ALTER TABLE quotes ADD COLUMN IF NOT EXISTS payment_status VARCHAR(50) DEFAULT 'Pending';"
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

// --- FILE CONTENTS ---

// 2. Logistics Backend
$contentLogisticsClass = <<<'PHP'
<?php
namespace Vsys\Modules\Logistica;
use Vsys\Lib\Database;
class Logistics {
    private $db;
    public function __construct() { $this->db = Database::getInstance(); }
    public function getOrdersForPreparation() {
        return $this->db->query("SELECT q.*, c.name as client_name FROM quotes q LEFT JOIN clients c ON q.client_id = c.id WHERE q.payment_status = 'Paid' OR q.authorized_dispatch = 1 ORDER BY q.created_at DESC")->fetchAll();
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

// 3. AJAX Backend
$contentAjax = <<<'PHP'
<?php
header('Content-Type: application/json');
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/logistica/Logistics.php';
use Vsys\Modules\Logistica\Logistics;
$logistics = new Logistics();
$action = $_POST['action'] ?? '';
try {
    if ($action === 'create_remito') {
        $q = $_POST['quote_number'] ?? '';
        $t = $_POST['transport_id'] ?? '';
        if (!$q || !$t) throw new Exception("Faltan datos.");
        $remito = $logistics->createRemito($q, $t);
        echo json_encode(['success' => true, 'remito_number' => $remito, 'message' => "Remito $remito generado."]);
    } else { throw new Exception("Acción inválida."); }
} catch (Exception $e) { echo json_encode(['success' => false, 'error' => $e->getMessage()]); }
PHP;
writeFile(__DIR__ . '/ajax_logistics.php', $contentAjax);

// 4. Transport ABM UI
$contentConfigTrans = <<<'PHP'
<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/logistica/Logistics.php';
use Vsys\Modules\Logistica\Logistics;
$logistics = new Logistics();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = ['id' => $_POST['id'] ?? null, 'name' => $_POST['name'], 'contact_person' => $_POST['contact_person'], 'phone' => $_POST['phone'], 'email' => $_POST['email'], 'is_active' => isset($_POST['is_active']) ? 1 : 0];
    $logistics->saveTransport($data);
    header("Location: config_transports.php?success=1"); exit;
}
$transports = $logistics->getTransports(false);
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Transportes - VS System</title><link rel="stylesheet" href="css/style_premium.css"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></head>
<body>
    <header style="background:#020617; border-bottom:2px solid var(--accent-violet); text-align:center; padding:15px;"><div style="color:white; font-weight:700;">GESTIÓN DE <span>TRANSPORTES</span></div></header>
    <div class="dashboard-container">
        <nav class="sidebar"><a href="configuration.php" class="nav-link active"><i class="fas fa-arrow-left"></i> VOLVER</a></nav>
        <main class="content"><div class="card">
            <form action="config_transports.php" method="POST" style="background:rgba(255,255,255,0.05); padding:20px; border-radius:10px; margin-bottom:20px;">
                <input type="hidden" name="id" value="<?php echo $_GET['edit'] ?? ''; ?>">
                <input type="text" name="name" placeholder="Nombre Empresa" required style="padding:10px; margin-right:10px; background:#0f172a; color:white; border:1px solid #334155;">
                <button type="submit" class="btn-primary">Guardar Transportista</button>
            </form>
            <table>
                <thead><tr><th>Nombre</th><th>Estado</th><th>Acciones</th></tr></thead>
                <tbody><?php foreach($transports as $t): ?><tr><td><?php echo $t['name']; ?></td><td><?php echo $t['is_active']?'ACTIVO':'INACTIVO'; ?></td><td><a href="?edit=<?php echo $t['id']; ?>">Editar</a></td></tr><?php endforeach; ?></tbody>
            </table>
        </div></main>
    </div>
</body></html>
PHP;
writeFile(__DIR__ . '/config_transports.php', $contentConfigTrans);

echo "<hr><p>¡Actualización v16 Completada! Logística activa y MySQL configurado.</p>";
?>