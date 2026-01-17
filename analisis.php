<?php
require_once 'auth_check.php';
/**
 * VS System ERP - Análisis de Operaciones
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/analysis/OperationAnalysis.php';

use Vsys\Modules\Analysis\OperationAnalysis;

$analyzer = new OperationAnalysis();
$quotationId = $_GET['id'] ?? null;
$analysis = null;
if ($quotationId) {
    try {
        $analysis = $analyzer->getQuotationAnalysis($quotationId);
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Análisis de Operación - VS System</title>
    <link rel="stylesheet" href="css/style_premium.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .metric-big {
            font-size: 2.5rem;
            font-weight: 800;
        }

        .metric-label {
            font-size: 0.9rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .profit-positive {
            color: #10b981;
        }

        .profit-negative {
            color: #ef4444;
        }

        .profit-neutral {
            color: #f59e0b;
        }

        .cost-breakdown {
            margin-top: 1rem;
        }

        .cost-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
    </style>
</head>

<body>
    <header
        style="background: #020617; border-bottom: 2px solid var(--accent-violet); display: flex; justify-content: space-between; align-items: center; padding: 0 20px;">
        <div style="display: flex; align-items: center; gap: 20px;">
            <a href="index.php" style="text-decoration:none;">
                <img src="logo_display.php?v=1" alt="VS System" class="logo-large" style="height: 50px; width: auto;">
            </a>
            <div
                style="color: #fff; font-family: 'Inter', sans-serif; font-weight: 700; font-size: 1.4rem; letter-spacing: 1px; text-shadow: 0 0 10px rgba(139, 92, 246, 0.4);">
                Vecino Seguro <span
                    style="background: linear-gradient(90deg, #8b5cf6, #d946ef); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Sistemas</span>
                by Javier Gozzi - 2026
            </div>
        </div>
        <div class="header-right" style="color: #cbd5e1;">
            <span class="user-badge"><i class="fas fa-user-circle"></i> Admin</span>
        </div>
    </header>

    <div class="dashboard-container">
        <nav class="sidebar">
            <a href="index.php" class="nav-link"><i class="fas fa-home"></i> DASHBOARD</a>
            <a href="analisis.php" class="nav-link active"><i class="fas fa-chart-line"></i> AN&Aacute;LISIS OP.</a>
            <a href="productos.php" class="nav-link"><i class="fas fa-box-open"></i> PRODUCTOS</a>
            <a href="presupuestos.php" class="nav-link"><i class="fas fa-history"></i> PRESUPUESTOS</a>
            <a href="clientes.php" class="nav-link"><i class="fas fa-users"></i> CLIENTES</a>
            <a href="proveedores.php" class="nav-link"><i class="fas fa-truck-loading"></i> PROVEEDORES</a>
            <a href="compras.php" class="nav-link"><i class="fas fa-cart-arrow-down"></i> COMPRAS</a>
            <a href="importar.php" class="nav-link"><i class="fas fa-upload"></i> IMPORTAR</a>
            <a href="crm.php" class="nav-link"><i class="fas fa-handshake"></i> CRM</a>
            <a href="cotizador.php" class="nav-link"><i class="fas fa-file-invoice-dollar"></i> COTIZADOR</a>
        </nav>

        <main class="content">
            <?php if (!$quotationId): ?>
                <div class="card" style="text-align: center; padding: 50px;">
                    <h3><i class="fas fa-search-dollar"></i> Seleccione una Cotización</h3>
                    <p>Ingrese el ID de la cotización para ver su análisis de rentabilidad.</p>
                    <div style="display: flex; gap: 10px;">
                        <input type="number" name="id" placeholder="ID Cotización"
                            style="padding: 10px; width: 150px; border-radius: 6px; border: 1px solid var(--accent-violet); background: #1e293b; color: white;">
                        <button type="submit" class="btn-primary">BUSCAR</button>
                    </div>
                    </form>
                </div>

                <div class="card" style="margin-top: 2rem;">
                    <h3><i class="fas fa-history"></i> &Uacute;ltimas Operaciones Disponibles</h3>
                    <div class="table-responsive">
                        <table class="table-compact">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Cliente</th>
                                    <th>Fecha</th>
                                    <th>Monto (USD)</th>
                                    <th style="text-align: center;">Acci&oacute;n</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $db = Vsys\Lib\Database::getInstance();
                                $recentOps = $db->query("SELECT q.id, q.quote_number, q.created_at, q.subtotal_usd, e.name as client_name 
                                                         FROM quotations q 
                                                         JOIN entities e ON q.client_id = e.id 
                                                         ORDER BY q.id DESC LIMIT 10")->fetchAll();
                                foreach ($recentOps as $op):
                                    ?>
                                    <tr>
                                        <td>#<?php echo $op['quote_number']; ?></td>
                                        <td><?php echo $op['client_name']; ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($op['created_at'])); ?></td>
                                        <td>$ <?php echo number_format($op['subtotal_usd'], 2); ?></td>
                                        <td style="text-align: center;">
                                            <a href="analisis.php?id=<?php echo $op['id']; ?>" class="btn-primary"
                                                style="padding: 5px 10px; font-size: 0.8rem;">
                                                ANALIZAR <i class="fas fa-arrow-right"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php elseif (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php else: ?>

                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div>
                            <h1>An&aacute;lisis de Rentabilidad #<?php echo $analysis['quote_number']; ?></h1>
                            <p style="color: #94a3b8;">Cliente: <strong><?php echo $analysis['client_name']; ?></strong> |
                                Fecha: <?php echo $analysis['date']; ?></p>
                        </div>
                        <div style="text-align: right;">
                            <span class="badge" style="font-size: 1rem; background: rgba(139, 92, 246, 0.2);">Margen:
                                <?php echo number_format($analysis['margin_percent'], 2); ?>%</span>
                        </div>
                    </div>
                </div>

                <div class="grid-3" style="margin-top: 2rem;">
                    <!-- Revenue -->
                    <div class="card"
                        style="background: linear-gradient(145deg, rgba(30, 41, 59, 0.8), rgba(99, 102, 241, 0.1)); border: 1px solid rgba(99, 102, 241, 0.3);">
                        <div class="metric-label">Ingresos Totales (Neto)</div>
                        <div class="metric-big" style="color: #a5b4fc;">$
                            <?php echo number_format($analysis['total_revenue'], 2); ?>
                        </div>
                        <small>Facturación proyectada sin IVA</small>
                    </div>

                    <!-- Cost -->
                    <div class="card"
                        style="background: linear-gradient(145deg, rgba(30, 41, 59, 0.8), rgba(239, 68, 68, 0.1)); border: 1px solid rgba(239, 68, 68, 0.3);">
                        <div class="metric-label">Costo Mercadería (CMV)</div>
                        <div class="metric-big" style="color: #fca5a5;">$
                            <?php echo number_format($analysis['total_cost'], 2); ?>
                        </div>
                        <small>Costo de reposición estimado</small>
                    </div>

                    <!-- Profit -->
                    <div class="card"
                        style="background: linear-gradient(145deg, rgba(30, 41, 59, 0.8), rgba(16, 185, 129, 0.1)); border: 1px solid rgba(16, 185, 129, 0.3);">
                        <div class="metric-label">Utilidad Bruta</div>
                        <div
                            class="metric-big <?php echo $analysis['profit'] >= 0 ? 'profit-positive' : 'profit-negative'; ?>">
                            $ <?php echo number_format($analysis['profit'], 2); ?>
                        </div>
                        <small>Ganancia neta de la operación</small>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 2rem;">
                    <div class="card">
                        <h3>Desglose de Productos</h3>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th style="text-align: right;">Venta Unit</th>
                                        <th style="text-align: right;">Costo Unit</th>
                                        <th style="text-align: right;">Utilidad</th>
                                        <th style="text-align: center;">% Margen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($analysis['items'] as $item):
                                        $margin = ($item['unit_price'] > 0) ? (($item['unit_price'] - $item['unit_cost']) / $item['unit_price']) * 100 : 0;
                                        ?>
                                        <tr>
                                            <td><strong><?php echo $item['sku']; ?></strong><br><small><?php echo $item['description']; ?></small>
                                            </td>
                                            <td style="text-align: right;">$
                                                <?php echo number_format($item['unit_price'], 2); ?>
                                            </td>
                                            <td style="text-align: right;">$ <?php echo number_format($item['unit_cost'], 2); ?>
                                            </td>
                                            <td style="text-align: right; color: #10b981;">$
                                                <?php echo number_format(($item['unit_price'] - $item['unit_cost']) * $item['qty'], 2); ?>
                                            </td>
                                            <td style="text-align: center;">
                                                <span class="badge"
                                                    style="background: <?php echo $margin < 20 ? 'rgba(239, 68, 68, 0.2)' : 'rgba(16, 185, 129, 0.2)'; ?>; color: <?php echo $margin < 20 ? '#ef4444' : '#10b981'; ?>;">
                                                    <?php echo number_format($margin, 1); ?>%
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card">
                        <h3>Estructura de Margen</h3>
                        <canvas id="marginChart"></canvas>
                        <div class="cost-breakdown">
                            <div class="cost-item">
                                <span>Costo Mercadería</span>
                                <strong><?php echo number_format(($analysis['total_cost'] / $analysis['total_revenue']) * 100, 1); ?>%</strong>
                            </div>
                            <div class="cost-item">
                                <span>Impuestos (Est. IIBB 3.5%)</span>
                                <strong><?php echo number_format($analysis['taxes'], 2); ?> (3.5%)</strong>
                            </div>
                            <div class="cost-item" style="border-top: 1px solid white; margin-top: 5px; padding-top: 5px;">
                                <span>Utilidad Neta Real</span>
                                <strong
                                    style="color: #10b981;"><?php echo number_format($analysis['profit'] - $analysis['taxes'], 2); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    const ctx = document.getElementById('marginChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Costo', 'Utilidad', 'Impuestos'],
                            datasets: [{
                                data: [
                                    <?php echo $analysis['total_cost']; ?>,
                                    <?php echo $analysis['profit'] - $analysis['taxes']; ?>,
                                    <?php echo $analysis['taxes']; ?>
                                ],
                                backgroundColor: [
                                    '#ef4444',
                                    '#10b981',
                                    '#f59e0b'
                                ],
                                borderWidth: 0
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: { color: 'white' }
                                }
                            }
                        }
                    });
                </script>

            <?php endif; ?>
        </main>
    </div>
</body>

</html>