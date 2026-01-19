<?php
require_once 'auth_check.php';
/**
 * VS System ERP - Análisis de Operaciones (Rediseño Premium)
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
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Análisis de Operación - VS System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <script src="js/theme_handler.js"></script>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#136dec",
                        "background-dark": "#101822",
                        "surface-dark": "#16202e",
                        "surface-border": "#233348",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                },
            },
        }
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #101822;
        }

        ::-webkit-scrollbar-thumb {
            background: #233348;
            border-radius: 3px;
        }

        .glass-card {
            background: rgba(22, 32, 46, 0.8);
            backdrop-filter: blur(8px);
        }

        .gradient-text {
            background: linear-gradient(90deg, #136dec, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>

<body
    class="bg-white dark:bg-[#101822] text-slate-800 dark:text-white antialiased overflow-hidden transition-colors duration-300">
    <div class="flex h-screen w-full">
        <!-- Sidebar Inclusion -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col h-full overflow-hidden relative">
            <!-- Top Header -->
            <header
                class="h-16 flex items-center justify-between px-6 border-b border-slate-200 dark:border-[#233348] bg-white dark:bg-[#101822]/95 backdrop-blur z-10 sticky top-0 transition-colors duration-300">
                <div class="flex items-center gap-3">
                    <div class="bg-[#136dec]/20 p-2 rounded-lg text-[#136dec]">
                        <span class="material-symbols-outlined text-2xl">query_stats</span>
                    </div>
                    <h2 class="dark:text-white text-slate-800 font-bold text-lg uppercase tracking-tight">Análisis de
                        Rentabilidad</h2>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-6 space-y-8">
                <div class="max-w-7xl mx-auto space-y-8">

                    <?php if (!$quotationId): ?>
                        <div
                            class="glass-card border border-slate-200 dark:border-[#233348] p-12 rounded-3xl text-center space-y-6">
                            <div
                                class="bg-primary/10 size-20 rounded-full flex items-center justify-center mx-auto text-primary">
                                <span class="material-symbols-outlined text-4xl">search_check</span>
                            </div>
                            <h3 class="text-2xl font-extrabold tracking-tight">Seleccione una Cotización</h3>
                            <p class="text-slate-500 max-w-md mx-auto">Ingrese el número de cotización para visualizar el
                                desglose detallado de costos y márgenes de ganancia.</p>

                            <form method="GET" class="flex items-center justify-center gap-2 max-w-sm mx-auto">
                                <input type="number" name="id" placeholder="ID de Cotización" required
                                    class="flex-1 bg-slate-50 dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-xl px-4 py-3 text-sm focus:ring-primary focus:border-primary">
                                <button type="submit"
                                    class="bg-primary hover:bg-blue-600 text-white px-6 py-3 rounded-xl font-bold text-xs uppercase tracking-widest transition-all">BUSCAR</button>
                            </form>
                        </div>

                        <div
                            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-3xl overflow-hidden">
                            <div
                                class="p-6 border-b border-slate-200 dark:border-[#233348] flex items-center justify-between">
                                <h3 class="font-bold text-sm uppercase tracking-widest text-slate-500">Últimas Operaciones
                                </h3>
                            </div>
                            <div class="table-responsive">
                                <table class="w-full text-left">
                                    <thead
                                        class="bg-slate-50 dark:bg-[#101822]/50 text-slate-500 text-[10px] font-bold uppercase tracking-widest">
                                        <tr>
                                            <th class="px-6 py-4">Referencia</th>
                                            <th class="px-6 py-4">Cliente</th>
                                            <th class="px-6 py-4">Fecha</th>
                                            <th class="px-6 py-4 text-right">Monto (USD)</th>
                                            <th class="px-6 py-4 text-center">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-[#233348]">
                                        <?php
                                        $db = Vsys\Lib\Database::getInstance();
                                        $recentOps = $db->prepare("SELECT q.id, q.quote_number, q.created_at, q.subtotal_usd, e.name as client_name 
                                                                 FROM quotations q 
                                                                 JOIN entities e ON q.client_id = e.id 
                                                                 WHERE q.company_id = ?
                                                                 ORDER BY q.id DESC LIMIT 10");
                                        $recentOps->execute([$_SESSION['company_id']]);
                                        foreach ($recentOps->fetchAll() as $op): ?>
                                            <tr class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors group">
                                                <td class="px-6 py-4 font-bold text-primary">#<?php echo $op['quote_number']; ?>
                                                </td>
                                                <td class="px-6 py-4 font-medium"><?php echo $op['client_name']; ?></td>
                                                <td class="px-6 py-4 text-slate-500 text-xs">
                                                    <?php echo date('d/m/Y', strtotime($op['created_at'])); ?></td>
                                                <td class="px-6 py-4 text-right font-bold text-emerald-500">$
                                                    <?php echo number_format($op['subtotal_usd'], 2); ?></td>
                                                <td class="px-6 py-4 text-center">
                                                    <a href="analisis.php?id=<?php echo $op['id']; ?>"
                                                        class="inline-flex items-center gap-2 text-[10px] font-bold uppercase tracking-widest text-[#136dec] border border-[#136dec]/20 px-3 py-1.5 rounded-lg hover:bg-[#136dec] hover:text-white transition-all">
                                                        Analizar <span
                                                            class="material-symbols-outlined text-sm">arrow_forward</span>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php elseif (isset($error)): ?>
                        <div
                            class="bg-red-500/10 border border-red-500/20 p-6 rounded-2xl text-red-500 flex items-center gap-4">
                            <span class="material-symbols-outlined text-3xl">error</span>
                            <div>
                                <h4 class="font-bold">Error de Operación</h4>
                                <p class="text-sm"><?php echo $error; ?></p>
                            </div>
                        </div>
                    <?php else: ?>

                        <!-- Analysis Header Card -->
                        <div
                            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] p-8 rounded-3xl shadow-sm flex flex-col md:flex-row justify-between items-center gap-6">
                            <div class="space-y-1 text-center md:text-left">
                                <div class="flex items-center gap-3 justify-center md:justify-start">
                                    <h1 class="text-2xl font-extrabold tracking-tight">Análisis de Rentabilidad</h1>
                                    <span
                                        class="bg-primary/10 text-primary px-3 py-1 rounded-full text-xs font-bold uppercase tracking-widest border border-primary/20">#<?php echo $analysis['quote_number']; ?></span>
                                </div>
                                <p class="text-slate-500">Cliente: <strong
                                        class="text-slate-700 dark:text-slate-300"><?php echo $analysis['client_name']; ?></strong>
                                    | Fecha: <?php echo $analysis['date']; ?></p>
                            </div>
                            <div class="bg-emerald-500/10 border border-emerald-500/20 px-8 py-4 rounded-2xl text-center">
                                <span
                                    class="block text-[10px] font-extrabold uppercase tracking-widest text-emerald-600 mb-1">Margen
                                    Operativo</span>
                                <span
                                    class="text-3xl font-black text-emerald-500"><?php echo number_format($analysis['margin_percent'], 2); ?>%</span>
                            </div>
                        </div>

                        <!-- Top Metrics -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div
                                class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] p-8 rounded-3xl group hover:border-primary/30 transition-all duration-300">
                                <div class="flex items-center justify-between mb-4">
                                    <span class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Ingresos
                                        Netos</span>
                                    <span
                                        class="material-symbols-outlined text-primary group-hover:scale-110 transition-transform">receipt_long</span>
                                </div>
                                <div class="text-3xl font-black dark:text-white text-slate-800 mb-1">USD
                                    <?php echo number_format($analysis['total_revenue'], 2); ?></div>
                                <p class="text-[10px] text-slate-400 font-medium leading-relaxed">Facturación proyectada sin
                                    impuestos directos.</p>
                            </div>

                            <div
                                class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] p-8 rounded-3xl group hover:border-red-500/30 transition-all duration-300">
                                <div class="flex items-center justify-between mb-4">
                                    <span class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Costo
                                        Mercadería</span>
                                    <span
                                        class="material-symbols-outlined text-red-400 group-hover:scale-110 transition-transform">inventory_2</span>
                                </div>
                                <div class="text-3xl font-black dark:text-white text-slate-800 mb-1">USD
                                    <?php echo number_format($analysis['total_cost'], 2); ?></div>
                                <p class="text-[10px] text-slate-400 font-medium leading-relaxed">Costo total de reposición
                                    de los productos.</p>
                            </div>

                            <div
                                class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] p-8 rounded-3xl group hover:border-emerald-500/30 transition-all duration-300">
                                <div class="flex items-center justify-between mb-4">
                                    <span class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Utilidad
                                        Bruta</span>
                                    <span
                                        class="material-symbols-outlined text-emerald-400 group-hover:scale-110 transition-transform">payments</span>
                                </div>
                                <div
                                    class="text-3xl font-black <?php echo $analysis['profit'] >= 0 ? 'text-emerald-500' : 'text-red-500'; ?> mb-1">
                                    USD <?php echo number_format($analysis['profit'], 2); ?></div>
                                <p class="text-[10px] text-slate-400 font-medium leading-relaxed">Ganancia estimada de la
                                    operación comercial.</p>
                            </div>
                        </div>

                        <!-- Details Grid -->
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                            <!-- Product Breakdown -->
                            <div
                                class="lg:col-span-2 bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-3xl overflow-hidden shadow-sm">
                                <div class="p-6 border-b border-slate-200 dark:border-[#233348] flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary">view_list</span>
                                    <h3 class="font-bold text-sm uppercase tracking-widest">Desglose por Producto</h3>
                                </div>
                                <div class="table-responsive">
                                    <table class="w-full text-left">
                                        <thead
                                            class="bg-slate-50 dark:bg-[#101822]/50 text-slate-500 text-[10px] font-bold uppercase tracking-widest">
                                            <tr>
                                                <th class="px-6 py-4">Producto</th>
                                                <th class="px-6 py-4 text-right">Venta Unit</th>
                                                <th class="px-6 py-4 text-right">Costo Unit</th>
                                                <th class="px-6 py-4 text-right">Utilidad</th>
                                                <th class="px-6 py-4 text-center">% Margen</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 dark:divide-[#233348]">
                                            <?php foreach ($analysis['items'] as $item):
                                                $margin = ($item['unit_price'] > 0) ? (($item['unit_price'] - $item['unit_cost']) / $item['unit_price']) * 100 : 0;
                                                ?>
                                                <tr class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors">
                                                    <td class="px-6 py-4">
                                                        <div class="font-bold text-sm text-slate-800 dark:text-white">
                                                            <?php echo $item['sku']; ?></div>
                                                        <div class="text-[10px] text-slate-500 truncate max-w-[200px]">
                                                            <?php echo $item['description']; ?></div>
                                                    </td>
                                                    <td class="px-6 py-4 text-right font-medium">$
                                                        <?php echo number_format($item['unit_price'], 2); ?></td>
                                                    <td class="px-6 py-4 text-right text-slate-500 text-xs">$
                                                        <?php echo number_format($item['unit_cost'], 2); ?></td>
                                                    <td class="px-6 py-4 text-right font-bold text-emerald-500">$
                                                        <?php echo number_format(($item['unit_price'] - $item['unit_cost']) * $item['qty'], 2); ?>
                                                    </td>
                                                    <td class="px-6 py-4 text-center">
                                                        <span
                                                            class="inline-block text-[10px] font-bold px-3 py-1 rounded-full <?php echo $margin < 20 ? 'bg-red-500/10 text-red-500 border border-red-500/10' : 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/10'; ?>">
                                                            <?php echo number_format($margin, 1); ?>%
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Margin Chart & Breakdown -->
                            <div class="space-y-6 h-fit">
                                <div
                                    class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] p-8 rounded-3xl shadow-sm">
                                    <h3 class="font-bold text-sm uppercase tracking-widest mb-6 flex items-center gap-2">
                                        <span class="material-symbols-outlined text-primary">pie_chart</span>
                                        Estructura
                                    </h3>
                                    <div class="relative aspect-square mb-8">
                                        <canvas id="marginChart"></canvas>
                                    </div>
                                    <div class="space-y-4">
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="flex items-center gap-2 text-slate-500">
                                                <div class="size-2 rounded-full bg-red-400"></div> Costo Mercadería
                                            </span>
                                            <span
                                                class="font-bold"><?php echo number_format(($analysis['total_cost'] / $analysis['total_revenue']) * 100, 1); ?>%</span>
                                        </div>
                                        <div
                                            class="flex items-center justify-between text-xs border-b border-dashed border-slate-200 dark:border-[#233348] pb-4">
                                            <span class="flex items-center gap-2 text-slate-500">
                                                <div class="size-2 rounded-full bg-amber-400"></div> IIBB (Est. 3.5%)
                                            </span>
                                            <span class="font-bold">USD
                                                <?php echo number_format($analysis['taxes'], 2); ?></span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-bold">Utilidad Neta Real</span>
                                            <span class="text-sm font-black text-emerald-500">USD
                                                <?php echo number_format($analysis['profit'] - $analysis['taxes'], 2); ?></span>
                                        </div>
                                    </div>
                                </div>

                                <button
                                    class="w-full bg-primary hover:bg-blue-600 text-white py-4 rounded-2xl font-bold text-xs uppercase tracking-widest transition-all shadow-xl shadow-primary/20 flex items-center justify-center gap-3">
                                    <span class="material-symbols-outlined">print</span> Imprimir Informe
                                </button>
                            </div>
                        </div>

                        <script>
                            const ctx = document.getElementById('marginChart').getContext('2d');
                            new Chart(ctx, {
                                type: 'doughnut',
                                data: {
                                    labels: ['Costo', 'Neto', 'Taxes'],
                                    datasets: [{
                                        data: [
                                            <?php echo $analysis['total_cost']; ?>,
                                            <?php echo max(0, $analysis['profit'] - $analysis['taxes']); ?>,
                                            <?php echo $analysis['taxes']; ?>
                                        ],
                                        backgroundColor: [
                                            '#fb7185', // Rose 400
                                            '#34d399', // Emerald 400
                                            '#fbbf24'  // Amber 400
                                        ],
                                        borderWidth: 0,
                                        cutout: '80%'
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: true,
                                    plugins: {
                                        legend: { display: false }
                                    }
                                }
                            });
                        </script>

                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>

</html>