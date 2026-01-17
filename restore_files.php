<?php
// restore_files.php - Restauración de Archivos Críticos v11 (Fix CRM Layout)
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

echo "<h1>Restaurador de Archivos Críticos v11 (Fix CRM Layout)</h1>";

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

// 4. crm.php (Rewritten with Standard Layout)
$contentCRM = <<<'PHP'
<?php
/**
 * CRM Dashboard - Pipeline View - Standard Layout
 */
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/crm/CRM.php';

use Vsys\Modules\CRM\CRM;

$crm = new CRM();
$stats = $crm->getStats();

// Fetch leads
$leadsNuevo = $crm->getLeadsByStatus('Nuevo');
$leadsContactado = $crm->getLeadsByStatus('Contactado');
$leadsPresupuesto = $crm->getLeadsByStatus('Presupuestado');
$leadsGanado = $crm->getLeadsByStatus('Ganado');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CRM Pipeline - <?php echo APP_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style_premium.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* CRM Specific Styles */
        .pipeline-container { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; height: calc(100vh - 250px); min-height: 500px; }
        .pipeline-col { background: rgba(30, 41, 59, 0.5); border-radius: 12px; padding: 10px; display: flex; flex-direction: column; }
        
        .col-header { 
            padding: 10px; font-weight: bold; border-bottom: 2px solid #334155; margin-bottom: 10px; 
            display: flex; justify-content: space-between; align-items: center;
        }
        .col-header .count { background: #334155; padding: 2px 8px; border-radius: 10px; font-size: 0.8rem; }
        
        .pipeline-col.nuevo .col-header { border-color: #8b5cf6; color: #8b5cf6; }
        .pipeline-col.contactado .col-header { border-color: #f59e0b; color: #f59e0b; }
        .pipeline-col.presupuesto .col-header { border-color: #3b82f6; color: #3b82f6; }
        .pipeline-col.ganado .col-header { border-color: #10b981; color: #10b981; }

        .cards-container { flex-grow: 1; overflow-y: auto; padding-right: 5px; }

        .lead-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
        }
        .lead-card:hover { transform: translateY(-2px); border-color: #8b5cf6; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        
        .lead-name { font-weight: 600; margin-bottom: 5px; color: #f8fafc; }
        .lead-contact { font-size: 0.85rem; color: #94a3b8; margin-bottom: 8px; }
        .lead-source { font-size: 0.70rem; background: #334155; padding: 2px 6px; border-radius: 4px; display: inline-block; color: #cbd5e1; }
        .lead-date { position: absolute; top: 10px; right: 10px; font-size: 0.7rem; color: #64748b; }
        
        .btn-move { padding: 4px 8px; border-radius: 4px; border: none; cursor: pointer; font-size: 0.8rem; background: #334155; color: white; transition: background 0.2s; }
        .btn-move:hover { background: #8b5cf6; }
        
        /* Stats Specific */
        .crm-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
        .crm-stat-card { background: #1e293b; padding: 1rem; border-radius: 8px; border: 1px solid #334155; display: flex; flex-direction: column; }
        .crm-stat-val { font-size: 1.8rem; font-weight: 800; color: #f8fafc; }
        .crm-stat-label { font-size: 0.85rem; color: #94a3b8; margin-top: 5px; }
    </style>
</head>
<body>
    <!-- HEADER -->
    <header
        style="background: #020617; border-bottom: 2px solid var(--accent-violet); display: flex; justify-content: space-between; align-items: center; padding: 0 20px;">
        <div style="display: flex; align-items: center; gap: 20px;">
            <img src="logo_display.php?v=1" alt="VS System" class="logo-large" style="height: 50px; width: auto;">
            <div
                style="color: #fff; font-family: 'Inter', sans-serif; font-weight: 700; font-size: 1.4rem; letter-spacing: 1px; text-shadow: 0 0 10px rgba(139, 92, 246, 0.4);">
                Vecino Seguro <span
                    style="background: linear-gradient(90deg, #8b5cf6, #d946ef); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Sistemas</span>
            </div>
        </div>
        <div class="header-right" style="color: #cbd5e1;">
            <span class="user-badge"><i class="fas fa-user-circle"></i> Admin</span>
        </div>
    </header>

    <div class="dashboard-container">
        <!-- SIDEBAR -->
        <nav class="sidebar">
            <a href="index.php" class="nav-link"><i class="fas fa-home"></i> DASHBOARD</a>
            <a href="analisis.php" class="nav-link"><i class="fas fa-chart-line"></i> AN&Aacute;LISIS OP.</a>
            <a href="productos.php" class="nav-link"><i class="fas fa-box-open"></i> PRODUCTOS</a>
            <a href="presupuestos.php" class="nav-link"><i class="fas fa-history"></i> PRESUPUESTOS</a>
            <a href="clientes.php" class="nav-link"><i class="fas fa-users"></i> CLIENTES</a>
            <a href="proveedores.php" class="nav-link"><i class="fas fa-truck-loading"></i> PROVEEDORES</a>
            <a href="compras.php" class="nav-link"><i class="fas fa-cart-arrow-down"></i> COMPRAS</a>
            <a href="importar.php" class="nav-link"><i class="fas fa-upload"></i> IMPORTAR</a>
            <a href="crm.php" class="nav-link active"><i class="fas fa-handshake"></i> CRM</a>
            <a href="cotizador.php" class="nav-link"><i class="fas fa-file-invoice-dollar"></i> COTIZADOR</a>
            <a href="catalogo.php" class="nav-link" target="_blank" style="color: #25d366; font-weight: 700;"><i
                    class="fas fa-external-link-alt"></i> VER CAT&Aacute;LOGO</a>
        </nav>

        <!-- CONTENT -->
        <main class="content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h1><i class="fas fa-funnel-dollar" style="color: #8b5cf6; margin-right: 10px;"></i> CRM Pipeline</h1>
                <button onclick="location.reload()" class="btn-primary" style="background: #334155;"><i class="fas fa-sync-alt"></i> Actualizar</button>
            </div>

            <!-- Stats Bar -->
            <div class="crm-stats">
                <div class="crm-stat-card">
                    <div class="crm-stat-val" style="color: #3b82f6;"><?php echo $stats['active_quotes']; ?></div>
                    <div class="crm-stat-label">Presupuestos Activos</div>
                </div>
                <div class="crm-stat-card">
                    <div class="crm-stat-val" style="color: #10b981;"><?php echo $stats['orders_today']; ?></div>
                    <div class="crm-stat-label">Pedidos de Hoy</div>
                </div>
                <div class="crm-stat-card">
                    <div class="crm-stat-val" style="color: #8b5cf6;"><?php echo $stats['efficiency']; ?>%</div>
                    <div class="crm-stat-label">Eficiencia de Cierre</div>
                </div>
            </div>

            <!-- Pipeline -->
            <div class="pipeline-container">
                <!-- Nuevo -->
                <div class="pipeline-col nuevo">
                    <div class="col-header"><span>NUEVO</span> <span class="count"><?php echo count($leadsNuevo); ?></span></div>
                    <div class="cards-container">
                        <?php foreach ($leadsNuevo as $l) renderCard($l); ?>
                    </div>
                </div>

                <!-- Contactado -->
                <div class="pipeline-col contactado">
                    <div class="col-header"><span>CONTACTADO</span> <span class="count"><?php echo count($leadsContactado); ?></span></div>
                    <div class="cards-container">
                        <?php foreach ($leadsContactado as $l) renderCard($l); ?>
                    </div>
                </div>

                <!-- Presupuestado -->
                <div class="pipeline-col presupuesto">
                    <div class="col-header"><span>PRESUPUESTADO</span> <span class="count"><?php echo count($leadsPresupuesto); ?></span></div>
                    <div class="cards-container">
                        <?php foreach ($leadsPresupuesto as $l) renderCard($l); ?>
                    </div>
                </div>

                <!-- Ganado -->
                <div class="pipeline-col ganado">
                    <div class="col-header"><span>GANADO</span> <span class="count"><?php echo count($leadsGanado); ?></span></div>
                    <div class="cards-container">
                        <?php foreach ($leadsGanado as $l) renderCard($l); ?>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <!-- Scripts -->
    <script>
    function moveLead(id, direction) {
        // Here you would implement the AJAX call to move status
        alert('Funcionalidad de mover en desarrollo.');
    }
    </script>
</body>
</html>
<?php
function renderCard($lead) {
    if (!isset($lead['id'])) return;
    $date = date('d/m', strtotime($lead['created_at']));
    $source = isset($lead['source']) ? $lead['source'] : 'Manual';
    $notes = isset($lead['notes']) ? substr($lead['notes'], 0, 80) . '...' : '';
    
    echo "
    <div class='lead-card' onclick='alert(\"Detalles: " . htmlspecialchars(json_encode($lead['notes'] ?? '')) . "\")'>
        <div class='lead-date'>$date</div>
        <div class='lead-name'>{$lead['name']}</div>
        <div class='lead-contact'>
            <i class='fas fa-envelope'></i> ".($lead['email']??'')."<br>
            <i class='fas fa-phone'></i> ".($lead['phone']??'')."
        </div>
        <div class='lead-source'>$source</div>
        <div class='actions' style='margin-top:10px; text-align:right;'>
            <button class='btn-move' onclick='event.stopPropagation(); moveLead({$lead['id']}, \"next\")'>Mover ></button>
        </div>
    </div>
    ";
}
?>
PHP;
writeFile(__DIR__ . '/crm.php', $contentCRM);


echo "<hr><p>¡Restauración Completa v11! CRM Layout Fixed (Sidebar + Header).</p>";
echo "<p>Por favor ejecute <a href='crm.php'>CRM Pipeline</a>.</p>";
?>