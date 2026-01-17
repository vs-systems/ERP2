<?php
require_once 'auth_check.php';
/**
 * VS System ERP - Listado de Productos
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/catalogo/Catalog.php';
require_once __DIR__ . '/src/modules/config/PriceList.php';

use Vsys\Modules\Catalogo\Catalog;
use Vsys\Modules\Config\PriceList;

$catalog = new Catalog();
$priceListModule = new PriceList();

$products = $catalog->getAllProducts();
$lists = $priceListModule->getAll();

// Map lists by ID or Name for easy access
$listsByName = [];
foreach ($lists as $l) {
    $listsByName[$l['name']] = $l['margin_percent'];
}

// Defaults if missing
$gremioMargin = $listsByName['Gremio'] ?? 30;
$webMargin = $listsByName['Web'] ?? 40;
$mlMargin = $listsByName['MercadoLibre'] ?? 50;

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Productos - VS System</title>
    <link rel="stylesheet" href="css/style_premium.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <header
        style="background: #020617; border-bottom: 2px solid var(--accent-violet); display: flex; justify-content: space-between; align-items: center; padding: 0 20px;">
        <div style="display: flex; align-items: center; gap: 20px;">
            <img src="logo_display.php?v=1" alt="VS System" class="logo-large" style="height: 50px; width: auto;">
            <div style="color: #fff; font-family: 'Inter', sans-serif; font-weight: 700; font-size: 1.4rem;">
                Listado de <span style="color: var(--accent-violet);">Productos</span>
            </div>
        </div>
        <div class="header-right" style="color: #cbd5e1;">
            <span class="user-badge"><i class="fas fa-user-circle"></i> Admin</span>
        </div>
    </header>

    <div class="dashboard-container">
        <nav class="sidebar">
            <a href="index.php" class="nav-link"><i class="fas fa-home"></i> DASHBOARD</a>
            <a href="analisis.php" class="nav-link"><i class="fas fa-chart-line"></i> AN&Aacute;LISIS OP.</a>
            <a href="productos.php" class="nav-link active"><i class="fas fa-box-open"></i> PRODUCTOS</a>
            <a href="presupuestos.php" class="nav-link"><i class="fas fa-history"></i> PRESUPUESTOS</a>
            <a href="clientes.php" class="nav-link"><i class="fas fa-users"></i> CLIENTES</a>
            <a href="proveedores.php" class="nav-link"><i class="fas fa-truck-loading"></i> PROVEEDORES</a>
            <a href="compras.php" class="nav-link"><i class="fas fa-cart-arrow-down"></i> COMPRAS</a>
            <a href="crm.php" class="nav-link"><i class="fas fa-handshake"></i> CRM</a>
            <a href="cotizador.php" class="nav-link"><i class="fas fa-file-invoice-dollar"></i> COTIZADOR</a>
            <!-- Link to Config -->
            <a href="configuration.php" class="nav-link"><i class="fas fa-cogs"></i> CONFIGURACIÓN</a>
        </nav>

        <main class="content">
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3><i class="fas fa-list"></i> Cat&aacute;logo General</h3>
                    <div style="position: relative;">
                        <input type="text" id="tableSearch" placeholder="Buscar por SKU o Desc..."
                            style="padding: 8px 12px; border-radius: 20px; border: 1px solid var(--accent-violet); background: rgba(255,255,255,0.05); color: white; width: 300px;">
                        <i class="fas fa-search" style="position: absolute; right: 15px; top: 12px; opacity: 0.5;"></i>
                    </div>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr style="font-size: 0.85rem; text-transform: uppercase; color: #94a3b8;">
                                <th>SKU</th>
                                <th>Descripci&oacute;n / Marca</th>
                                <th>Rubro</th> <!-- Cat/Subcat -->
                                <th style="text-align: right; color: var(--accent-violet);">Costo USD</th>
                                <th style="text-align: right;">Gremio (+<?php echo (int) $gremioMargin; ?>%)</th>
                                <th style="text-align: right;">Web (+<?php echo (int) $webMargin; ?>%)</th>
                                <th style="text-align: right;">ML (+<?php echo (int) $mlMargin; ?>%)</th>
                                <th style="text-align: center;">IVA</th>
                                <th style="text-align: center;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $p):
                                $cost = $p['unit_cost_usd'];
                                $priceGremio = $cost * (1 + ($gremioMargin / 100));
                                $priceWeb = $cost * (1 + ($webMargin / 100));
                                $priceML = $cost * (1 + ($mlMargin / 100));
                                ?>
                                <tr class="product-row" data-sku="<?php echo strtolower($p['sku']); ?>"
                                    data-desc="<?php echo strtolower($p['description']); ?>">
                                    <td style="font-weight: bold; color: #fff;"><?php echo $p['sku']; ?></td>
                                    <td>
                                        <div style="font-weight: 500;"><?php echo $p['description']; ?></div>
                                        <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;">
                                            <i class="fas fa-tag"></i> <?php echo $p['brand']; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div><?php echo $p['category'] ?? '-'; ?></div>
                                        <div style="font-size: 0.8rem; color: #64748b;">
                                            <?php echo $p['subcategory'] ?? ''; ?>
                                        </div>
                                    </td>

                                    <!-- Cost Highlighted -->
                                    <td
                                        style="text-align: right; color: var(--accent-violet); font-weight: 700; background: rgba(139, 92, 246, 0.05);">
                                        $ <?php echo number_format($cost, 2); ?>
                                    </td>

                                    <!-- Calculated Prices -->
                                    <td style="text-align: right; color: #cbd5e1;">$
                                        <?php echo number_format($priceGremio, 2); ?></td>
                                    <td style="text-align: right; color: #cbd5e1;">$
                                        <?php echo number_format($priceWeb, 2); ?></td>
                                    <td style="text-align: right; color: #cbd5e1;">$
                                        <?php echo number_format($priceML, 2); ?></td>

                                    <td style="text-align: center;">
                                        <span class="badge badge-info"><?php echo $p['iva_rate']; ?>%</span>
                                    </td>

                                    <td style="text-align: center;">
                                        <!-- Edit button now goes to config_productos_add.php with ID? Or we keep edit modal logic here? 
                                             User asked to move logic to Config, so ideally 'Edit' redirects there or logic is shared.
                                             Let's make it redirect to the add form with query params or leave basic edit here? 
                                             Simpler: Pass data to JS function that redirects to manual load page (if we implement edit mode there).
                                             For now, let's keep it simple: Action button -> Edit Link. 
                                        -->
                                        <!-- CURRENT IMPLEMENTATION: Edit Logic was inline. 
                                             STRATEGY: I didn't verify if config_productos_add.php accepts GET params to pre-fill. 
                                             Wait, I didn't add pre-fill logic to config_productos_add.php. 
                                             I will add a simple JS redirect that passes params, OR I will just leave the Edit button as "TODO" or link to the new page.
                                             Let's TRY to pass basic data via URL is messy. 
                                             Better: The user said "move manual load to config". They didn't explicitly safeguard "Editing". 
                                             I will leave the Edit button functional by linking to `config_productos_add.php?sku=SKU` and updating that file to read it.
                                        -->
                                        <a href="config_productos_add.php?sku=<?php echo urlencode($p['sku']); ?>"
                                            class="btn-edit" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Live Search Filter
        document.getElementById('tableSearch').addEventListener('input', function (e) {
            const q = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.product-row');
            rows.forEach(row => {
                const sku = row.getAttribute('data-sku');
                const desc = row.getAttribute('data-desc');
                if (sku.includes(q) || desc.includes(q)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    </script>
</body>

</html>