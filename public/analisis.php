<?php
/**
 * VS System ERP - Análisis de Operaciones
 */
require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/lib/Database.php';
require_once __DIR__ . '/../src/modules/analysis/OperationAnalysis.php';

use Vsys\Modules\Analysis\OperationAnalysis;

$analysisModule = new OperationAnalysis();
$db = Vsys\Lib\Database::getInstance();

$quoteId = $_GET['id'] ?? null;
$analysisData = $quoteId ? $analysisModule->getQuotationAnalysis($quoteId) : null;

// Fetch all quotes for selection
$quotesQuery = "SELECT q.id, q.quote_number, q.total_usd, e.name as client_name 
               FROM quotations q 
               LEFT JOIN entities e ON q.client_id = e.id 
               ORDER BY q.id DESC LIMIT 100";
$quotes = $db->query($quotesQuery)->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Análisis de Operaciones - VS System</title>
    <link rel="stylesheet" href="css/style_premium.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .analysis-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .profit-card {
            background: var(--gradient-premium);
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
        }

        .profit-value {
            font-size: 3rem;
            font-weight: 800;
            margin: 10px 0;
            display: block;
        }

        .expense-input {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--accent-violet);
            color: white;
            padding: 8px;
            border-radius: 4px;
            width: 100%;
        }
    </style>
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
        <nav class="sidebar">
            <a href="index.php" class="nav-link"><i class="fas fa-home"></i> DASHBOARD</a>
            <a href="analizador.php" class="nav-link active"><i class="fas fa-chart-line"></i> ANALIZADOR</a>
            <a href="productos.php" class="nav-link"><i class="fas fa-box-open"></i> PRODUCTOS</a>
            <a href="presupuestos.php" class="nav-link"><i class="fas fa-history"></i> PRESUPUESTOS</a>
            <a href="clientes.php" class="nav-link"><i class="fas fa-users"></i> CLIENTES</a>
            <a href="proveedores.php" class="nav-link"><i class="fas fa-truck-loading"></i> PROVEEDORES</a>
            <a href="compras.php" class="nav-link"><i class="fas fa-cart-arrow-down"></i> COMPRAS</a>
            <a href="crm.php" class="nav-link"><i class="fas fa-handshake"></i> CRM</a>
            <a href="cotizador.php" class="nav-link"><i class="fas fa-file-invoice-dollar"></i> COTIZADOR</a>
        </nav>

        <main class="content">
            <div class="card">
                <form method="GET" style="display: flex; gap: 1rem; align-items: center;">
                    <div style="position: relative; flex-grow: 1;">
                        <input type="text" id="quoteSearch" placeholder="🔍 Filtrar por Nro o Cliente..."
                            style="width: 100%; border-radius: 4px; padding: 10px; border: 1px solid var(--accent-violet); background: rgba(255,255,255,0.05); color: white; margin-bottom: 5px;">
                        <select name="id" id="quoteSelect" required style="width: 100%;">
                            <option value="">Seleccione una operaci&oacute;n...</option>
                            <?php foreach ($quotes as $q): ?>
                                <option value="<?php echo $q['id']; ?>" <?php echo $quoteId == $q['id'] ? 'selected' : ''; ?>
                                    data-text="<?php echo strtolower($q['quote_number'] . ' ' . ($q['client_name'] ?? '')); ?>">
                                    <?php echo $q['quote_number']; ?> - <?php echo $q['client_name'] ?? 'S/D'; ?> (USD
                                    <?php echo number_format($q['total_usd'], 2); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-primary" style="height: fit-content;">ANALIZAR</button>
                </form>
            </div>

            <script>
                document.getElementById('quoteSearch').addEventListener('input', function (e) {
                    const q = e.target.value.toLowerCase();
                    const options = document.getElementById('quoteSelect').options;
                    for (let i = 1; i < options.length; i++) {
                        const txt = options[i].getAttribute('data-text');
                        options[i].style.display = txt.includes(q) ? '' : 'none';
                    }
                });
            </script>

            <?php if ($analysisData): ?>
                <?php
                $h = $analysisData['header'];
                $tc = $h['exchange_rate_usd'];
                ?>
                <div class="analysis-grid" style="margin-top: 2rem;">
                    <div class="card">
                        <h3><i class="fas fa-shopping-cart"></i> Costos de Operaci&oacute;n (NETO)</h3>
                        <table class="table-compact">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th style="text-align: right;">Costo USD</th>
                                    <th style="text-align: right;">Costo ARS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $totalCostUsd = 0;
                                foreach ($analysisData['items'] as $it):
                                    $cost = $it['catalog_cost'] ?: 0;
                                    $qty = $it['quantity'] ?: 0;
                                    $totalCostUsd += ($cost * $qty);
                                    ?>
                                    <tr>
                                        <td>
                                            <?php echo $it['description'] ?: 'Sin descripción'; ?> (x
                                            <?php echo $qty; ?>)
                                        </td>
                                        <td style="text-align: right;">$
                                            <?php echo number_format($cost, 2); ?>
                                        </td>
                                        <td style="text-align: right; color: #94a3b8;">$
                                            <?php echo number_format($cost * $tc, 2); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <h4 style="margin-top: 2rem;">Gastos Generales (Fletes, Remises, etc.)</h4>
                        <div id="extra-expenses">
                            <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                                <input type="text" placeholder="Descripci&oacute;n" class="expense-input"
                                    value="Fletes / Logistic">
                                <input type="number" id="extra-ars" placeholder="ARS" class="expense-input"
                                    style="width: 150px;" value="0" onchange="calculateRealProfit()">
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <h3><i class="fas fa-file-invoice-dollar"></i> Venta y Margen (NETO)</h3>
                        <div style="margin-bottom: 1rem;">
                            <p>Total Presupuestado (Neto): <strong id="total-sale-usd">USD
                                    <?php echo number_format($h['subtotal_usd'], 2); ?>
                                </strong></p>
                            <p>Total en Pesos (Neto): <strong id="total-sale-ars">ARS
                                    <?php echo number_format($h['subtotal_usd'] * $tc, 2); ?>
                                </strong></p>
                        </div>

                        <div class="profit-card">
                            <span style="opacity: 0.8;">GANANCIA ESTIMADA (NETA)</span>
                            <span class="profit-value" id="final-profit-ars">ARS 0.00</span>
                            <span id="final-profit-usd" style="font-weight: 600; color: var(--accent-blue);">USD 0.00</span>
                        </div>

                        <div style="margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem;">
                            <p><i class="fas fa-info-circle"></i> Esta operaci&oacute;n se calcul&oacute; con TC: <strong>
                                    <?php echo $tc; ?>
                                </strong></p>
                            <p><i class="fas fa-university"></i> Agente Retenci&oacute;n: <strong>
                                    <?php echo $h['is_retention_agent'] ? 'SI (7%)' : 'NO'; ?>
                                </strong></p>
                        </div>
                    </div>
                </div>

                <script>
                    const saleNetUsd = <?php echo $h['subtotal_usd']; ?>;
                    const costNetUsd = <?php echo $totalCostUsd; ?>;
                    const exchangeRate = <?php echo $tc; ?>;
                    const isRetention = <?php echo $h['is_retention_agent'] ? 'true' : 'false'; ?>;

                    function calculateRealProfit() {
                        const extraArs = parseFloat(document.getElementById('extra-ars').value) || 0;

                        const saleArs = saleNetUsd * exchangeRate;
                        const costArs = costNetUsd * exchangeRate;

                        // Calculation: Profit = (Sale Net - Cost Net - Extra Expenses)
                        // We also need to account for retention/bank fees if those are lost money
                        // But user said: "iva e impuestos son perdida", so we stick to net.

                        const profitArs = saleArs - costArs - extraArs;
                        const profitUsd = profitArs / exchangeRate;

                        document.getElementById('final-profit-ars').innerText = 'ARS ' + profitArs.toLocaleString('es-AR', { minimumFractionDigits: 2 });
                        document.getElementById('final-profit-usd').innerText = 'USD ' + profitUsd.toLocaleString('en-US', { minimumFractionDigits: 2 });

                        const profitValue = document.getElementById('final-profit-ars');
                        if (profitArs < 0) profitValue.style.color = '#ef4444';
                        else profitValue.style.color = '#10b981';
                    }

                    window.onload = calculateRealProfit;
                </script>
            <?php endif; ?>
        </main>
    </div>
</body>

</html>