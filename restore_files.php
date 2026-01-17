<?php
// restore_files.php - Restauración de Archivos Críticos v18 (Unified Sidebar)
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

echo "<h1>Restaurador de Archivos Críticos v18 (Unified Sidebar)</h1>";

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

// 1. Sidebar Component
$contentSidebar = <<<'PHP'
<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$navItems = [
    ['id' => 'index', 'href' => 'index.php', 'icon' => 'fas fa-home', 'label' => 'DASHBOARD'],
    ['id' => 'analisis', 'href' => 'analisis.php', 'icon' => 'fas fa-chart-line', 'label' => 'ANÁLISIS OP.'],
    ['id' => 'productos', 'href' => 'productos.php', 'icon' => 'fas fa-box-open', 'label' => 'PRODUCTOS'],
    ['id' => 'presupuestos', 'href' => 'presupuestos.php', 'icon' => 'fas fa-history', 'label' => 'PRESUPUESTOS'],
    ['id' => 'clientes', 'href' => 'clientes.php', 'icon' => 'fas fa-users', 'label' => 'CLIENTES'],
    ['id' => 'proveedores', 'href' => 'proveedores.php', 'icon' => 'fas fa-truck-loading', 'label' => 'PROVEEDORES'],
    ['id' => 'compras', 'href' => 'compras.php', 'icon' => 'fas fa-cart-arrow-down', 'label' => 'COMPRAS'],
    ['id' => 'crm', 'href' => 'crm.php', 'icon' => 'fas fa-handshake', 'label' => 'CRM'],
    ['id' => 'cotizador', 'href' => 'cotizador.php', 'icon' => 'fas fa-file-invoice-dollar', 'label' => 'COTIZADOR'],
    ['id' => 'logistica', 'href' => 'logistica.php', 'icon' => 'fas fa-truck', 'label' => 'LOGÍSTICA'],
    ['id' => 'facturacion', 'href' => 'facturacion.php', 'icon' => 'fas fa-file-invoice', 'label' => 'FACTURACIÓN'],
    ['id' => 'configuration', 'href' => 'configuration.php', 'icon' => 'fas fa-cogs', 'label' => 'CONFIGURACIÓN'],
];
?>
<nav class="sidebar">
    <?php foreach ($navItems as $item): ?>
        <a href="<?php echo $item['href']; ?>" class="nav-link <?php echo ($currentPage === $item['id']) ? 'active' : ''; ?>">
            <i class="<?php echo $item['icon']; ?>"></i> <?php echo $item['label']; ?>
        </a>
    <?php endforeach; ?>
    <a href="catalogo_publico.php" class="nav-link" target="_blank" style="color: #25d366; font-weight: 700; border-top: 1px solid rgba(255,255,255,0.1); margin-top: 10px; padding-top: 15px;">
        <i class="fas fa-external-link-alt"></i> VER CATÁLOGO
    </a>
</nav>
PHP;
writeFile(__DIR__ . '/sidebar.php', $contentSidebar);

// 2. Refreshing main files to use include 'sidebar.php'
$filesToUpdateSidebar = ['index.php', 'configuration.php', 'logistica.php', 'productos.php', 'presupuestos.php', 'clientes.php', 'proveedores.php', 'compras.php', 'crm.php', 'cotizador.php', 'analisis.php'];

foreach ($filesToUpdateSidebar as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        $content = file_get_contents(__DIR__ . '/' . $file);
        // Regex to replace <nav class="sidebar">...</nav> with include 'sidebar.php'
        $newContent = preg_replace('/<nav class="sidebar">.*?<\/nav>/s', '<?php include "sidebar.php"; ?>', $content);
        if ($newContent !== $content) {
            writeFile(__DIR__ . '/' . $file, $newContent);
        }
    }
}

// 3. Re-assert Logistics Classes
$contentLogisticsClass = <<<'PHP'
<?php
namespace Vsys\Modules\Logistica;
use Vsys\Lib\Database;
class Logistics {
    private $db;
    public function __construct() { $this->db = Database::getInstance(); }
    public function getOrdersForPreparation() {
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

echo "<hr><p>¡Actualización v18 Completa! Sidebar centralizado y navegación corregida.</p>";
?>