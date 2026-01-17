<?php
// restore_files.php - Restauración de Archivos Críticos v9 (Fix CRM & Checkout)
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

echo "<h1>Restaurador de Archivos Críticos v9 (Fix CRM & Checkout)</h1>";

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

// 0. DB MIGRATION: Ensure crm_leads has 'source' and 'contact_details' is handled ?? 
// Actually we should match the Code schema: name, contact_person, email, phone, status, notes
// We will add 'source' column if missing.
try {
    $db = Vsys\Lib\Database::getInstance();
    $db->exec("ALTER TABLE crm_leads ADD COLUMN IF NOT EXISTS source VARCHAR(50) DEFAULT 'Manually'");
    echo "<p style='color:blue'>[DB] crm_leads schema updated (source column).</p>";
} catch (Exception $e) {
    echo "<p style='color:orange'>[DB Warning] " . $e->getMessage() . "</p>";
}

// 1. src/sync_bcra.php (Use DolarAPI for BNA/Oficial rate)
$contentSync = <<<'PHP'
<?php
/**
 * VS System ERP - Exchange Rate Sync
 * Uses DolarAPI (https://dolarapi.com) to get the "Oficial" Venta rate (Approx BNA Retail)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/Database.php';

use Vsys\Lib\Database;

try {
    // Fetch Oficial Rate (Standard Retail)
    $json = file_get_contents('https://dolarapi.com/v1/dolares/oficial');
    $data = json_decode($json, true);

    if ($data && isset($data['venta'])) {
        $rate = (float)$data['venta']; // e.g. 1455
        
        $db = Database::getInstance();
        $stmt = $db->prepare("INSERT INTO exchange_rates (rate, source, created_at) VALUES (?, 'DolarAPI_Oficial', NOW())");
        $stmt->execute([$rate]);
        
        echo "Successfully updated Rate: ARS " . $rate . " (Source: DolarAPI Oficial)\n";
    } else {
        echo "Error: Invalid response from DolarAPI.\n";
    }

} catch (Exception $e) {
    echo "Error syncing rate: " . $e->getMessage() . "\n";
}
?>
PHP;
writeFile(__DIR__ . '/src/sync_bcra.php', $contentSync);


// 2. src/modules/catalogo/PublicCatalog.php (Ensure it gets latest rate)
$contentPublicCat = <<<'PHP'
<?php
namespace Vsys\Modules\Catalogo;

use Vsys\Lib\Database;
use Vsys\Modules\Config\PriceList;

class PublicCatalog
{
    private $db;
    private $priceListModule;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->priceListModule = new PriceList();
    }

    public function getExchangeRate()
    {
        // Get the latest rate from DB
        $stmt = $this->db->prepare("SELECT rate FROM exchange_rates ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        $rate = $stmt->fetchColumn();
        // Fallback if DB empty
        return $rate ? (float)$rate : 1455.00; 
    }

    public function getProductsForWeb()
    {
        $rate = $this->getExchangeRate();
        
        $lists = $this->priceListModule->getAll();
        $webMargin = 40; 
        foreach ($lists as $l) {
            if ($l['name'] === 'Web') {
                $webMargin = (float)$l['margin_percent'];
                break;
            }
        }

        $stmt = $this->db->prepare("SELECT * FROM products ORDER BY brand, description");
        $stmt->execute();
        $products = $stmt->fetchAll();

        $webProducts = [];
        foreach ($products as $p) {
            $cost = (float)$p['unit_cost_usd'];
            $iva = (float)$p['iva_rate'];
            
            $priceUsd = $cost * (1 + ($webMargin / 100));
            $priceUsdWithIva = $priceUsd * (1 + ($iva / 100));
            $priceArs = $priceUsdWithIva * $rate;

            if ($priceArs > 0) {
                // Rounding
                $p['price_final_ars'] = round($priceArs, 0); 
                $p['price_final_formatted'] = number_format($p['price_final_ars'], 0, ',', '.');
                $p['image_url'] = !empty($p['image_url']) ? $p['image_url'] : 'https://placehold.co/300x300?text=No+Image';
                $webProducts[] = $p;
            }
        }

        return [
            'rate' => $rate,
            'products' => $webProducts
        ];
    }
}
?>
PHP;
writeFile(__DIR__ . '/src/modules/catalogo/PublicCatalog.php', $contentPublicCat);


// 3. ajax_checkout.php (Fix Schema Mismatch & Add Logging)
$contentAjax = <<<'PHP'
<?php
header('Content-Type: application/json');
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/checkout_errors.log');

require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

use Vsys\Lib\Database;

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('Invalid JSON Input');
    }

    $customer = $input['customer'] ?? [];
    $cart = $input['cart'] ?? [];
    $total = $input['total'] ?? 0;

    if (empty($cart) || empty($customer['name'])) {
        throw new Exception('Missing cart or customer name');
    }

    $db = Database::getInstance();

    $orderDetails = "PEDIDO WEB\n";
    $orderDetails .= "Fecha: " . date('d/m/Y H:i') . "\n";
    $orderDetails .= "Cliente: " . $customer['name'] . "\n";
    $orderDetails .= "DNI/CUIT: " . ($customer['dni'] ?? '-') . "\n";
    $orderDetails .= "------------- PRODUCTOS -------------\n";
    
    foreach ($cart as $item) {
        $orderDetails .= "- " . ($item['quantity']??1) . "x [{$item['sku']}] {$item['title']} ($ " . number_format($item['price'], 0, ',', '.') . ")\n";
    }
    
    $orderDetails .= "-------------------------------------\n";
    $orderDetails .= "TOTAL ESTIMADO: $ " . number_format($total, 0, ',', '.') . "\n";

    // Insert using correct columns: name, contact_person, email, phone, status, notes, source
    // We map 'dni' to contact_person or notes? Let's put DNI in notes/summary, or contact_person if usable.
    // Source requires the ALTER TABLE we did above.
    
    $stmt = $db->prepare("INSERT INTO crm_leads (name, email, phone, status, notes, source, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
    $stmt->execute([
        $customer['name'],
        $customer['email'] ?? '',
        $customer['phone'] ?? '',
        'Nuevo', 
        $orderDetails,
        'Web'
    ]);

    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    error_log("Checkout Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
PHP;
writeFile(__DIR__ . '/ajax_checkout.php', $contentAjax);


// 4. public/crm.php (The Pipeline UI)
$contentCRM = <<<'PHP'
<?php
/**
 * CRM Dashboard - Pipeline View
 */
require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/lib/Database.php';
require_once __DIR__ . '/../src/modules/crm/CRM.php';

use Vsys\Modules\CRM\CRM;

$crm = new CRM();
$stats = $crm->getStats();

// Fetch leads for columns
$leadsNuevo = $crm->getLeadsByStatus('Nuevo');
$leadsContactado = $crm->getLeadsByStatus('Contactado');
$leadsPresupuesto = $crm->getLeadsByStatus('Presupuestado');
$leadsGanado = $crm->getLeadsByStatus('Ganado');

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CRM Pipeline - VS System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg: #0f172a;
            --card: #1e293b;
            --text: #f8fafc;
            --primary: #8b5cf6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
        }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); margin: 0; padding: 20px; }
        
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 1.5rem; }
        
        .stats-bar { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 20px; }
        .stat-card { background: var(--card); padding: 15px; border-radius: 8px; border: 1px solid #334155; }
        .stat-val { font-size: 1.5rem; font-weight: bold; color: var(--primary); }
        .stat-label { font-size: 0.9rem; color: #94a3b8; }

        .pipeline { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; height: calc(100vh - 150px); }
        .column { background: rgba(30, 41, 59, 0.5); border-radius: 12px; padding: 10px; display: flex; flex-direction: column; }
        .col-header { 
            padding: 10px; font-weight: bold; border-bottom: 2px solid #334155; margin-bottom: 10px; 
            display: flex; justify-content: space-between;
        }
        .col-header .count { background: #334155; padding: 2px 8px; border-radius: 10px; font-size: 0.8rem; }
        
        .column.nuevo .col-header { border-color: var(--primary); }
        .column.contactado .col-header { border-color: var(--warning); }
        .column.presupuesto .col-header { border-color: #3b82f6; }
        .column.ganado .col-header { border-color: var(--success); }

        .cards-container { flex-grow: 1; overflow-y: auto; padding-right: 5px; }

        .lead-card {
            background: var(--card);
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: transform 0.2s;
            position: relative;
        }
        .lead-card:hover { transform: translateY(-2px); border-color: var(--primary); }
        
        .lead-name { font-weight: 600; margin-bottom: 5px; }
        .lead-contact { font-size: 0.85rem; color: #94a3b8; margin-bottom: 5px; }
        .lead-source { font-size: 0.75rem; background: #334155; padding: 2px 6px; border-radius: 4px; display: inline-block; }
        .lead-date { position: absolute; top: 10px; right: 10px; font-size: 0.7rem; color: #64748b; }
        
        .actions { margin-top: 10px; display: flex; gap: 5px; justify-content: flex-end; }
        .btn-sm { padding: 4px 8px; border-radius: 4px; border: none; cursor: pointer; font-size: 0.8rem; }
        .btn-move { background: #334155; color: white; }
        .btn-move:hover { background: var(--primary); }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-thumb { background: #475569; border-radius: 3px; }

    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-funnel-dollar"></i> CRM Pipeline</h1>
        <a href="index.php" style="color: #94a3b8; text-decoration: none;"><i class="fas fa-home"></i> Volver</a>
    </div>

    <div class="stats-bar">
        <div class="stat-card">
            <div class="stat-val"><?php echo $stats['active_quotes']; ?></div>
            <div class="stat-label">Presupuestos Activos</div>
        </div>
        <div class="stat-card">
            <div class="stat-val"><?php echo $stats['orders_today']; ?></div>
            <div class="stat-label">Pedidos Hoy</div>
        </div>
        <div class="stat-card">
            <div class="stat-val"><?php echo $stats['efficiency']; ?>%</div>
            <div class="stat-label">Eficiencia de Cierre</div>
        </div>
    </div>

    <div class="pipeline">
        <!-- Nuevos -->
        <div class="column nuevo">
            <div class="col-header">
                <span>NUEVO</span>
                <span class="count"><?php echo count($leadsNuevo); ?></span>
            </div>
            <div class="cards-container">
                <?php foreach ($leadsNuevo as $l): ?>
                    <?php renderCard($l); ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Contactados -->
        <div class="column contactado">
            <div class="col-header">
                <span>CONTACTADO</span>
                <span class="count"><?php echo count($leadsContactado); ?></span>
            </div>
            <div class="cards-container">
                <?php foreach ($leadsContactado as $l): ?>
                    <?php renderCard($l); ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Presupuestados -->
        <div class="column presupuesto">
            <div class="col-header">
                <span>PRESUPUESTADO</span>
                <span class="count"><?php echo count($leadsPresupuesto); ?></span>
            </div>
            <div class="cards-container">
                <?php foreach ($leadsPresupuesto as $l): ?>
                    <?php renderCard($l); ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Ganados -->
        <div class="column ganado">
            <div class="col-header">
                <span>GANADO</span>
                <span class="count"><?php echo count($leadsGanado); ?></span>
            </div>
            <div class="cards-container">
                <?php foreach ($leadsGanado as $l): ?>
                    <?php renderCard($l); ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

</body>
</html>

<?php
function renderCard($lead) {
    $date = date('d/m', strtotime($lead['created_at']));
    $source = isset($lead['source']) ? $lead['source'] : 'Manual';
    $notes = substr($lead['notes'] ?? '', 0, 80) . '...';
    echo "
    <div class='lead-card' onclick='alert(\"Detalles: " . htmlspecialchars($notes) . "\")'>
        <div class='lead-date'>$date</div>
        <div class='lead-name'>{$lead['name']}</div>
        <div class='lead-contact'>
            <i class='fas fa-envelope'></i> {$lead['email']}<br>
            <i class='fas fa-phone'></i> {$lead['phone']}
        </div>
        <div class='lead-source'>$source</div>
        <div class='actions'>
            <button class='btn-sm btn-move' onclick='event.stopPropagation(); moveLead({$lead['id']}, \"next\")'>></button>
        </div>
    </div>
    ";
}
?>
<script>
function moveLead(id, direction) {
    // Implement AJAX move (Not implemented in this view yet, just mock)
    // alert('Mover lead ' + id);
    // Ideally we call an endpoint to update status
}
</script>
PHP;
writeFile(__DIR__ . '/public/crm.php', $contentCRM);

echo "<hr><p>¡Restauración Completa v9! CRM Pipeline + Checkout Fix + DolarAPI.</p>";
echo "<p>Por favor ejecute <a href='public/crm.php'>CRM Update</a> para verificar.</p>";
?>