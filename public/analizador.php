<?php
/**
 * VS System ERP - Price Analyzer & Visual Insights
 */
require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/lib/Database.php';
require_once __DIR__ . '/../src/modules/analizador/PriceAnalyzer.php';

use Vsys\Modules\Analizador\PriceAnalyzer;

$analyzer = new PriceAnalyzer();
$products = $analyzer->getProductsForAnalysis(20);
$stats = $analyzer->getAnalyticsSummary();

// Prepare chart data
$catLabels = json_encode(array_column($stats['categories'], 'label'));
$catData = json_encode(array_column($stats['categories'], 'value'));

$brandLabels = json_encode(array_column($stats['brands'], 'label'));
$brandData = json_encode(array_column($stats['brands'], 'value'));
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Analizador de Precios - VS System ERP</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style_premium.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .chart-container {
            background: #1e293b;
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 2rem;
        }

        .diff-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.8rem;
        }

        .diff-high {
            background: #991b1b;
            color: #fecaca;
        }

        .diff-mid {
            background: #92400e;
            color: #fef3c7;
        }

        .diff-low {
            background: #065f46;
            color: #d1fae5;
        }

        .grid-charts {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-top: 1rem;
        }

        @media print {

            .sidebar,
            header,
            .btn-primary {
                display: none !important;
            }

            .dashboard-container {
                display: block;
            }

            .card {
                border: none;
            }
        }
    </style>
</head>

<body>
    <header>
        <img src="logo_display.php?v=1" class="logo-large">
        <div class="header-info"><span>Analizador Inteligente de Precios</span></div>
    </header>

    <div class="dashboard-container">
        <nav class="sidebar">
            <a href="index.php" class="nav-link"><i class="fas fa-home"></i> DASHBOARD</a>
            <a href="analizador.php" class="nav-link active"><i class="fas fa-chart-line"></i> ANALIZADOR</a>
            <a href="presupuestos.php" class="nav-link"><i class="fas fa-history"></i> PRESUPUESTOS</a>
            <a href="clientes.php" class="nav-link"><i class="fas fa-users"></i> CLIENTES</a>
            <a href="proveedores.php" class="nav-link"><i class="fas fa-truck-loading"></i> PROVEEDORES</a>
            <a href="importar.php" class="nav-link"><i class="fas fa-upload"></i> IMPORTAR</a>
            <a href="crm.php" class="nav-link"><i class="fas fa-handshake"></i> CRM</a>
            <a href="cotizador.php" class="nav-link"><i class="fas fa-file-invoice-dollar"></i> COTIZADOR</a>
        </nav>

        <main class="content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h1>Insights y Análisis de Precios</h1>
                <button onclick="window.print()" class="btn-primary"><i class="fas fa-print"></i> Imprimir
                    Informe</button>
            </div>

            <div class="grid-charts">
                <div class="chart-container">
                    <h3>Distribución por Categorías</h3>
                    <canvas id="categoryChart"></canvas>
                </div>
                <div class="chart-container">
                    <h3>Top 5 Marcas (Precio Promedio USD)</h3>
                    <canvas id="brandChart"></canvas>
                </div>
            </div>

            <div class="card">
                <h3>Comparativa Detallada de Productos</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Marca</th>
                                <th>Precio VS (USD)</th>
                                <th>Otros Proveedores (USD)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $p):
                                $mainCost = floatval($p['unit_cost_usd']);
                                $suppliersCount = count($p['suppliers']);
                                ?>
                                <tr>
                                    <td><strong><?php echo $p['sku']; ?></strong></td>
                                    <td><?php echo $p['brand']; ?></td>
                                    <td>$ <?php echo number_format($mainCost, 2); ?></td>
                                    <td colspan="3">
                                        <?php if ($suppliersCount > 0): ?>
                                            <div style="font-size: 0.85rem;">
                                                <?php foreach ($p['suppliers'] as $s):
                                                    $sCost = floatval($s['cost_usd']);
                                                    $diff = (($mainCost - $sCost) / $sCost) * 100;
                                                    $color = $diff > 0 ? '#ef4444' : '#10b981';
                                                    ?>
                                                    <div
                                                        style="margin-bottom: 5px; border-bottom: 1px solid #334155; padding-bottom: 3px;">
                                                        <span style="color: #818cf8;"><?php echo $s['supplier_name']; ?>:</span>
                                                        <strong>$ <?php echo number_format($sCost, 2); ?></strong>
                                                        (<span
                                                            style="color: <?php echo $color; ?>;"><?php echo ($diff > 0 ? '+' : '') . number_format($diff, 1); ?>%</span>)
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <span style="color: grey;">Sin otros proveedores registrados</span>
                                        <?php endif; ?>
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
        // Charts Initialization
        const catCtx = document.getElementById('categoryChart').getContext('2d');
        new Chart(catCtx, {
            type: 'pie',
            data: {
                labels: <?php echo $catLabels; ?>,
                datasets: [{
                    data: <?php echo $catData; ?>,
                    backgroundColor: ['#4f46e5', '#818cf8', '#6366f1', '#4338ca', '#3730a3']
                }]
            },
            options: { plugins: { legend: { labels: { color: 'white' } } } }
        });

        const brandCtx = document.getElementById('brandChart').getContext('2d');
        new Chart(brandCtx, {
            type: 'bar',
            data: {
                labels: <?php echo $brandLabels; ?>,
                datasets: [{
                    label: 'Precio Promedio USD',
                    data: <?php echo $brandData; ?>,
                    backgroundColor: '#818cf8'
                }]
            },
            options: {
                scales: {
                    y: { ticks: { color: 'white' }, grid: { color: 'rgba(255,255,255,0.1)' } },
                    x: { ticks: { color: 'white' }, grid: { display: false } }
                },
                plugins: { legend: { display: false } }
            }
        });
    </script>
</body>

</html>