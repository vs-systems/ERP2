<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';

require_once __DIR__ . '/src/modules/analysis/OperationAnalysis.php';
require_once __DIR__ . '/src/modules/logistica/Logistics.php';
require_once __DIR__ . '/src/modules/dashboard/SellerDashboard.php';

$analysis = new \Vsys\Modules\Analysis\OperationAnalysis();
$logistics = new \Vsys\Modules\Logistica\Logistics();
$userRole = $_SESSION['role'] ?? 'Invitado';
$userId = $_SESSION['user_id'] ?? 0;
$userName = $_SESSION['user_name'] ?? 'Usuario';

$stats = ['total_sales' => 0, 'pending_collections' => 0, 'total_purchases' => 0, 'pending_payments' => 0, 'effectiveness' => 0];
$sellerStats = ['total' => 0, 'converted' => 0];
$shipStats = [];

if ($userRole === 'Vendedor') {
    $sellerDash = new \Vsys\Modules\Dashboard\SellerDashboard($userId);
    $sellerStats = $sellerDash->getEfficiencyStats() ?: $sellerStats;
    $recentQuotations = $sellerDash->getRecentQuotes();
    $recentShipments = $sellerDash->getClientShipments();
} else {
    $stats = $analysis->getDashboardSummary() ?: $stats;
    $shipStats = $logistics->getShippingStats() ?: $shipStats;
}
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VS System - Panel de Control</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <script src="js/theme_handler.js"></script>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#136dec",
                        "background-light": "#f6f7f8",
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

        ::-webkit-scrollbar-thumb:hover {
            background: #324867;
        }

        .glass-card {
            background: rgba(22, 32, 46, 0.8);
            backdrop-filter: blur(8px);
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
                <div class="flex items-center gap-4 lg:hidden">
                    <button class="dark:text-white text-slate-800"><span
                            class="material-symbols-outlined">menu</span></button>
                    <span class="dark:text-white text-slate-800 font-bold text-lg">VS System</span>
                </div>

                <div class="hidden lg:flex items-center flex-1 max-w-xl">
                    <div class="relative w-full">
                        <span
                            class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-xl">search</span>
                        <input type="text" placeholder="Buscar cliente, SKU o cotización..."
                            class="w-full bg-slate-100 dark:bg-[#16202e] border-none rounded-lg py-2 pl-10 pr-4 text-sm dark:text-white text-slate-800 placeholder-slate-500 focus:ring-2 focus:ring-[#136dec] outline-none">
                    </div>
                </div>

                <div class="flex items-center gap-4 ml-auto">
                    <div class="flex flex-col items-end mr-2">
                        <span
                            class="text-xs text-slate-500 uppercase font-bold tracking-tighter"><?php echo date('d M, Y'); ?></span>
                        <span class="text-[10px] text-[#136dec] font-medium"><?php echo $userRole; ?> session</span>
                    </div>
                    <div class="h-8 w-px bg-[#233348]"></div>
                    <button class="text-slate-400 hover:text-white transition-colors relative">
                        <span class="material-symbols-outlined">notifications</span>
                        <span
                            class="absolute top-0 right-0 size-2 bg-red-500 rounded-full border-2 border-[#101822]"></span>
                    </button>
                </div>
            </header>

            <!-- Scrollable Body -->
            <div class="flex-1 overflow-y-auto p-6 space-y-8">
                <div class="max-w-7xl mx-auto space-y-8">

                    <!-- Welcome Header -->
                    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                        <div>
                            <h2 class="text-3xl font-bold dark:text-white text-slate-800 tracking-tight">Panel de
                                Control</h2>
                            <p class="text-slate-400 mt-1">Bienvenido de nuevo, <span
                                    class="text-[#136dec] font-semibold"><?php echo $userName; ?></span>.</p>
                        </div>
                        <div class="flex gap-3">
                            <a href="cotizador.php"
                                class="flex items-center gap-2 bg-[#136dec] hover:bg-blue-600 text-white px-5 py-2.5 rounded-xl font-bold shadow-lg shadow-[#136dec]/20 transition-all text-sm">
                                <span class="material-symbols-outlined text-lg">add</span> NUEVA COTIZACIÓN
                            </a>
                        </div>
                    </div>

                    <!-- Stats Grid -->
                    <?php if ($userRole === 'Admin' || $userRole === 'Sistemas'): ?>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                            <div
                                class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-xl p-5 hover:border-[#136dec]/50 transition-all group shadow-sm dark:shadow-none">
                                <div class="flex justify-between items-start mb-3">
                                    <div
                                        class="p-2.5 bg-green-500/10 rounded-lg text-green-500 group-hover:bg-green-500 group-hover:text-white transition-colors">
                                        <span class="material-symbols-outlined">payments</span>
                                    </div>
                                    <span
                                        class="text-green-500 text-[10px] font-bold bg-green-500/10 px-2 py-1 rounded-full uppercase">Ventas
                                        Netas</span>
                                </div>
                                <h3 class="text-2xl font-bold dark:text-white text-slate-800 tracking-tight">USD
                                    <?php echo number_format($stats['total_sales'], 2); ?>
                                </h3>
                                <p class="text-slate-500 text-xs mt-2">Pendiente de cobro: <span
                                        class="text-slate-400 dark:text-slate-300 font-medium">$<?php echo number_format($stats['pending_collections'], 2); ?></span>
                                </p>
                            </div>

                            <div
                                class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-xl p-5 hover:border-[#136dec]/50 transition-all group shadow-sm dark:shadow-none">
                                <div class="flex justify-between items-start mb-3">
                                    <div
                                        class="p-2.5 bg-red-500/10 rounded-lg text-red-500 group-hover:bg-red-500 group-hover:text-white transition-colors">
                                        <span class="material-symbols-outlined">shopping_cart</span>
                                    </div>
                                    <span
                                        class="text-red-500 text-[10px] font-bold bg-red-500/10 px-2 py-1 rounded-full uppercase">Compras
                                        Totales</span>
                                </div>
                                <h3 class="text-2xl font-bold dark:text-white text-slate-800 tracking-tight">USD
                                    <?php echo number_format($stats['total_purchases'], 2); ?>
                                </h3>
                                <p class="text-slate-500 text-xs mt-2">Pendiente de pago: <span
                                        class="text-slate-400 dark:text-slate-300 font-medium">$<?php echo number_format($stats['pending_payments'], 2); ?></span>
                                </p>
                            </div>

                            <div
                                class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-xl p-5 hover:border-[#136dec]/50 transition-all group shadow-sm dark:shadow-none">
                                <div class="flex justify-between items-start mb-3">
                                    <div
                                        class="p-2.5 bg-[#136dec]/10 rounded-lg text-[#136dec] group-hover:bg-[#136dec] group-hover:text-white transition-colors">
                                        <span class="material-symbols-outlined">query_stats</span>
                                    </div>
                                    <span
                                        class="text-[#136dec] text-[10px] font-bold bg-[#136dec]/10 px-2 py-1 rounded-full uppercase">Eficiencia</span>
                                </div>
                                <h3 class="text-2xl font-bold dark:text-white text-slate-800 tracking-tight">
                                    <?php echo $stats['effectiveness']; ?>%
                                </h3>
                                <p class="text-slate-500 text-xs mt-2">Cierre de presupuestos</p>
                            </div>

                            <div
                                class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-xl p-5 hover:border-amber-500/50 transition-all group shadow-sm dark:shadow-none">
                                <div class="flex justify-between items-start mb-3">
                                    <div
                                        class="p-2.5 bg-amber-500/10 rounded-lg text-amber-500 group-hover:bg-amber-500 group-hover:text-white transition-colors">
                                        <span class="material-symbols-outlined">local_shipping</span>
                                    </div>
                                    <span
                                        class="text-amber-500 text-[10px] font-bold bg-amber-500/10 px-2 py-1 rounded-full uppercase">Logística</span>
                                </div>
                                <h3 class="text-2xl font-bold dark:text-white text-slate-800 tracking-tight">
                                    <?php echo $shipStats['pending'] ?? 0; ?> envíos
                                </h3>
                                <p class="text-slate-500 text-xs mt-2">Procesos pendientes este mes</p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Secondary Row: Charts & Tables -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Left: Main Chart -->
                        <div
                            class="lg:col-span-2 bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-6 shadow-xl dark:shadow-none transition-colors duration-300">
                            <div class="flex justify-between items-center mb-8">
                                <div>
                                    <h3 class="text-lg font-bold dark:text-white text-slate-800">Flujo Operativo (Real)
                                    </h3>
                                    <p class="text-slate-500 text-sm">Resumen de ingresos vs egresos acumulados</p>
                                </div>
                                <div class="flex gap-2">
                                    <span class="size-3 rounded-full bg-[#136dec]"></span>
                                    <span class="size-3 rounded-full bg-red-500"></span>
                                    <span class="size-3 rounded-full bg-green-500"></span>
                                </div>
                            </div>
                            <div class="h-[320px] w-full mt-4">
                                <canvas id="opsChart"></canvas>
                            </div>
                        </div>

                        <!-- Right: Quick Activity + Calendar -->
                        <div class="space-y-6">
                            <div
                                class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-6 shadow-sm dark:shadow-none transition-colors duration-300">
                                <h3 class="font-bold dark:text-white text-slate-800 mb-4 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[#136dec]">event_note</span> Agenda del
                                    Día
                                </h3>
                                <div class="rounded-xl overflow-hidden border border-slate-200 dark:border-[#233348]">
                                    <iframe
                                        src="https://calendar.google.com/calendar/embed?src=dmVjaW5vc2VndXJvMEBnbWFpbC5jb20&ctz=America%2FArgentina%2FBuenos_Aires&showTitle=0&showNav=0&showPrint=0&showTabs=0&showCalendars=0&showTz=0&mode=AGENDA"
                                        class="w-full h-[250px] bg-white dark:invert dark:hue-rotate-180"
                                        frameborder="0" scrolling="no"></iframe>
                                </div>
                                <a href="https://calendar.google.com" target="_blank"
                                    class="block text-center mt-3 text-xs text-slate-500 hover:text-[#136dec] transition-colors underline">Ver
                                    calendario completo</a>
                            </div>

                            <div
                                class="bg-gradient-to-br from-[#136dec]/20 to-transparent border border-[#136dec]/30 rounded-2xl p-6">
                                <h3 class="font-bold text-[#136dec] mb-2 uppercase text-xs tracking-widest">Acceso
                                    Directo</h3>
                                <p class="text-white text-sm font-medium mb-4">Consulta el inventario y actualiza
                                    precios de mercado en tiempo real.</p>
                                <a href="catalogo.php"
                                    class="inline-flex items-center gap-2 text-white bg-[#136dec] px-4 py-2 rounded-lg text-sm font-bold w-full justify-center hover:bg-blue-600 transition-colors shadow-lg shadow-blue-500/20">
                                    <span class="material-symbols-outlined text-lg">category</span> IR AL CATÁLOGO
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom: Recent Operations Table -->
                    <div
                        class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl overflow-hidden shadow-xl dark:shadow-none transition-colors duration-300">
                        <div
                            class="p-6 border-b border-slate-200 dark:border-[#233348] flex justify-between items-center">
                            <h3 class="text-lg font-bold dark:text-white text-slate-800">Cotizaciones Recientes</h3>
                            <a href="presupuestos.php" class="text-xs text-[#136dec] hover:underline font-bold">VER
                                TODAS</a>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="bg-slate-50 dark:bg-[#101822]/50 transition-colors">
                                    <tr>
                                        <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase">Referencia
                                        </th>
                                        <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase">Cliente
                                        </th>
                                        <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase text-right">
                                            Monto USD</th>
                                        <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase">Estado</th>
                                        <th
                                            class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase text-center">
                                            Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-[#233348] transition-colors">
                                    <?php
                                    $db = \Vsys\Lib\Database::getInstance();
                                    $quotations = ($userRole === 'Vendedor') ? $recentQuotations : $db->query("SELECT q.*, e.name as client_name FROM quotations q JOIN entities e ON q.client_id = e.id ORDER BY q.id DESC LIMIT 8")->fetchAll();
                                    foreach ($quotations as $r):
                                        $statusColor = ($r['status'] === 'Pedido') ? 'text-green-500 bg-green-500/10' : 'text-slate-400 bg-white/5';
                                        ?>
                                        <tr class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors group">
                                            <td class="px-6 py-4">
                                                <span
                                                    class="text-sm font-bold dark:text-white text-slate-800 group-hover:text-[#136dec] transition-colors"><?php echo $r['quote_number']; ?></span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="text-sm text-slate-400"><?php echo $r['client_name']; ?></span>
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <span
                                                    class="text-sm font-mono text-white">$<?php echo number_format($r['total_usd'], 2); ?></span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span
                                                    class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase <?php echo $statusColor; ?>">
                                                    <?php echo $r['status']; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <a href="analisys.php?id=<?php echo $r['id']; ?>"
                                                    class="p-1.5 text-slate-400 hover:text-[#136dec] transition-colors">
                                                    <span class="material-symbols-outlined text-xl">monitoring</span>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
                <!-- Spacing at bottom -->
                <div class="h-10"></div>
            </div>
        </main>
    </div>

    <!-- Charts Script -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctxOps = document.getElementById('opsChart').getContext('2d');
        new Chart(ctxOps, {
            type: 'bar',
            data: {
                labels: ['Ventas Realizadas', 'Compras Registradas', 'Margen Operativo'],
                datasets: [{
                    data: [
                        <?php echo $stats['total_sales']; ?>,
                        <?php echo $stats['total_purchases']; ?>,
                        <?php echo ($stats['total_sales'] - $stats['total_purchases']); ?>
                    ],
                    backgroundColor: ['rgba(19, 109, 236, 0.6)', 'rgba(239, 68, 68, 0.6)', 'rgba(16, 185, 129, 0.6)'],
                    borderColor: ['#136dec', '#ef4444', '#10b981'],
                    borderWidth: 2,
                    borderRadius: 8,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#94a3b8', font: { size: 10 } } },
                    x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 10, weight: 'bold' } } }
                },
                plugins: { legend: { display: false } }
            }
        });
    </script>
</body>

</html>