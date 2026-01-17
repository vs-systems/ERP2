<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style_premium.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <header
        style="background: #020617; border-bottom: 2px solid var(--accent-violet); display: flex; justify-content: space-between; align-items: center; padding: 0 20px;">
        <div style="display: flex; align-items: center; gap: 20px;">
            <img src="logo_display.php?v=1" alt="VS System" class="logo-large" style="height: 50px; width: auto;">
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
        <?php include 'sidebar.php'; ?>

        <main class="content">
            <?php
            require_once __DIR__ . '/src/modules/analysis/OperationAnalysis.php';
            $analysis = new \Vsys\Modules\Analysis\OperationAnalysis();
            $stats = $analysis->getDashboardSummary();
            ?>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h1>Dashboard de Control Operativo</h1>
                <a href="catalogo.php" target="_blank" class="btn-primary"
                    style="background: var(--gradient-premium); text-decoration: none;">
                    <i class="fas fa-eye"></i> VISTA P&Uacute;BLICA CAT&Aacute;LOGO
                </a>
            </div>

            <div class="grid-3">
                <div class="card">
                    <h3>Ventas Netas (Realizadas)</h3>
                    <div class="metric" style="color: var(--accent-blue);">USD
                        <?php echo number_format($stats['total_sales'], 2); ?>
                    </div>
                    <small style="opacity: 0.7;">Cobro Pendiente: <strong>USD
                            <?php echo number_format($stats['pending_collections'], 2); ?></strong></small>
                </div>
                <div class="card">
                    <h3>Compras Netas (Pagadas)</h3>
                    <div class="metric" style="color: #ef4444;">USD
                        <?php echo number_format($stats['total_purchases'], 2); ?>
                    </div>
                    <small style="opacity: 0.7;">Pago Pendiente: <strong>USD
                            <?php echo number_format($stats['pending_payments'], 2); ?></strong></small>
                </div>
                <div class="card">
                    <h3>Eficiencia Comercial</h3>
                    <div class="metric" style="color: #10b981;"><?php echo $stats['effectiveness']; ?>%</div>
                    <small style="opacity: 0.7;">Cierre de Presupuestos</small>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 2rem;">
                <div class="card">
                    <h3>Flujo de Operaciones (USD)</h3>
                    <canvas id="opsChart" style="max-height: 300px;"></canvas>
                </div>
                <div class="card">
                    <h3>Efectividad (Cotiz vs Pedidos)</h3>
                    <canvas id="crmChart" style="max-height: 300px;"></canvas>
                </div>
            </div>

            <div class="card" style="margin-top: 2rem;">
                <h3>Cotizaciones Recientes</h3>
                <div class="table-responsive">
                    <table class="table-compact">
                        <thead>
                            <tr>
                                <th>N&uacute;mero</th>
                                <th>Cliente</th>
                                <th style="text-align: right;">Total USD</th>
                                <th>Estado</th>
                                <th style="text-align: center;">An&aacute;lisis</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $db = Vsys\Lib\Database::getInstance();
                            $recent = $db->query("SELECT q.*, e.name as client_name FROM quotations q JOIN entities e ON q.client_id = e.id ORDER BY q.id DESC LIMIT 5")->fetchAll();
                            foreach ($recent as $r):
                                ?>
                                <tr>
                                    <td><?php echo $r['quote_number']; ?></td>
                                    <td><?php echo $r['client_name']; ?></td>
                                    <td style="text-align: right;">$ <?php echo number_format($r['total_usd'], 2); ?></td>
                                    <td><span class="badge"
                                            style="background: rgba(255,255,255,0.1);"><?php echo $r['status']; ?></span>
                                    </td>
                                    <td style="text-align: center;">
                                        <a href="analisis.php?id=<?php echo $r['id']; ?>" class="btn-primary"
                                            style="padding: 4px 8px; font-size: 0.8rem;">
                                            <i class="fas fa-chart-pie"></i>
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

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Ops Chart
        const ctxOps = document.getElementById('opsChart').getContext('2d');
        new Chart(ctxOps, {
            type: 'bar',
            data: {
                labels: ['Ventas', 'Compras', 'Resultado'],
                datasets: [{
                    label: 'USD (Neto)',
                    data: [<?php echo $stats['total_sales']; ?>, <?php echo $stats['total_purchases']; ?>, <?php echo $stats['total_profit']; ?>],
                    backgroundColor: [
                        'rgba(99, 102, 241, 0.5)',
                        'rgba(239, 68, 68, 0.5)',
                        'rgba(16, 185, 129, 0.5)'
                    ],
                    borderColor: [
                        '#6366f1', '#ef4444', '#10b981'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' } }
                },
                plugins: { legend: { display: false } }
            }
        });

        // CRM Chart
        const ctxCRM = document.getElementById('crmChart').getContext('2d');
        new Chart(ctxCRM, {
            type: 'doughnut',
            data: {
                labels: ['Pedidos', 'Pendientes'],
                datasets: [{
                    data: [<?php echo $stats['orders_total']; ?>, <?php echo $stats['quotations_total'] - $stats['orders_total']; ?>],
                    backgroundColor: ['#10b981', 'rgba(255,255,255,0.1)'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom', labels: { color: 'white' } } }
            }
        });
    </script>
</body>

</html>