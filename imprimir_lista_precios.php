<?php
/**
 * VS System ERP - Optimized Price List Generator v2
 * Features: 9 Columns, Sidebar, Theme support, Fallback Logo.
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/lib/BCRAClient.php';
require_once 'auth_check.php';
require_once __DIR__ . '/src/modules/catalogo/Catalog.php';
require_once __DIR__ . '/src/modules/config/PriceList.php';

use Vsys\Modules\Catalogo\Catalog;
use Vsys\Modules\Config\PriceList;
use Vsys\Lib\BCRAClient;

$catalog = new Catalog();
$priceListModule = new PriceList();
$bcra = new BCRAClient();
$dolar = $bcra->getUSD();

$listName = $_GET['list'] ?? 'Gremio';
$lists = $priceListModule->getAll();
$activeList = null;
foreach ($lists as $l) {
    if ($l['name'] === $listName) {
        $activeList = $l;
        break;
    }
}
$margin = $activeList ? $activeList['margin_percent'] : 30;

$products = $catalog->getAllProducts();
$grouped = [];
foreach ($products as $p) {
    if ($p['stock_current'] <= 0 && ($_GET['hide_no_stock'] ?? 0))
        continue;
    $cat = $p['category'] ?: 'General';
    $grouped[$cat][] = $p;
}
ksort($grouped);
?>
<!DOCTYPE html>
<html lang="es" class="dark">

<head>
    <meta charset="UTF-8">
    <title>Lista de Precios <?php echo $listName; ?> - VS System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script src="js/theme_handler.js"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: { colors: { "primary": "#136dec" } }
            }
        }
    </script>
    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            aside {
                display: none !important;
            }

            main {
                margin-left: 0 !important;
                width: 100% !important;
                padding: 0 !important;
            }

            .print-full-width {
                width: 100% !important;
                max-width: none !important;
            }

            body {
                background: white !important;
            }
        }
    </style>
</head>

<body class="bg-white dark:bg-[#101822] text-slate-800 dark:text-white antialiased transition-colors duration-300">
    <div class="flex h-screen w-full">
        <!-- Sidebar Inclusion -->
        <?php include 'sidebar.php'; ?>

        <main class="flex-1 flex flex-col h-full overflow-hidden relative">
            <header
                class="h-16 flex items-center justify-between px-6 border-b border-slate-200 dark:border-[#233348] bg-white dark:bg-[#101822]/95 backdrop-blur z-10 sticky top-0 no-print">
                <div class="flex items-center gap-4 lg:hidden">
                    <button onclick="toggleVsysMobileMenu()" class="dark:text-white text-slate-800"><span
                            class="material-symbols-outlined">menu</span></button>
                    <span class="dark:text-white text-slate-800 font-bold text-lg uppercase tracking-tight">VS
                        System</span>
                </div>

                <div class="flex items-center gap-6">
                    <div class="flex items-center gap-3">
                        <div class="bg-primary/20 p-2 rounded-lg text-primary">
                            <span class="material-symbols-outlined text-2xl">lists</span>
                        </div>
                        <h2 class="dark:text-white text-slate-800 font-bold text-lg uppercase tracking-tight">Lista de
                            Precios</h2>
                    </div>

                    <div
                        class="flex items-center gap-3 bg-slate-50 dark:bg-white/5 p-2 px-4 rounded-xl border border-slate-200 dark:border-white/10">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Dólar BNA:</span>
                        <span class="text-sm font-black text-primary">$<?php echo number_format($dolar, 2); ?></span>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">OPCIONES DE
                            LISTA</span>
                        <select onchange="location.href='?list='+this.value"
                            class="bg-white dark:bg-[#16202e] border-slate-200 dark:border-[#233348] rounded-lg text-xs font-bold py-1.5 focus:ring-primary focus:border-primary">
                            <?php foreach ($lists as $l): ?>
                                <option value="<?php echo $l['name']; ?>" <?php echo $listName == $l['name'] ? 'selected' : ''; ?>><?php echo $l['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button onclick="window.print()"
                        class="bg-primary hover:bg-blue-600 text-white px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-2 transition-all shadow-lg shadow-primary/20 active:scale-95">
                        <span class="material-symbols-outlined text-sm">print</span> IMPRIMIR
                    </button>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-4 md:p-8">
                <div
                    class="print-full-width max-w-7xl mx-auto bg-white dark:bg-[#16202e] dark:border dark:border-[#233348] rounded-2xl shadow-sm md:shadow-xl p-6 md:p-10 transition-colors">

                    <div class="flex justify-between items-end border-b-2 border-primary pb-6 mb-8">
                        <div class="flex items-center gap-4">
                            <img src="https://vecinoseguro.com.ar/Logos/VSLogo.png" class="h-16 w-auto" alt="VS Logo"
                                onerror="this.outerHTML='<div class=\'bg-primary text-white font-black text-2xl p-4 rounded-xl shadow-lg\'>VS</div>'">
                            <div>
                                <h1 class="text-2xl font-black text-primary uppercase">VS System</h1>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em]">Soluciones en
                                    Seguridad</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <h2 class="text-xl font-black text-slate-800 dark:text-white uppercase leading-none mb-2">
                                Lista: <?php echo $listName; ?></h2>
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Emisión:
                                <?php echo date('d/m/Y'); ?> | Validez 24hs</p>
                        </div>
                    </div>

                    <div class="space-y-12">
                        <?php foreach ($grouped as $category => $items): ?>
                            <div class="category-block">
                                <h3
                                    class="bg-slate-50 dark:bg-white/5 border-l-4 border-primary px-4 py-2 text-xs font-black text-slate-600 dark:text-slate-300 uppercase tracking-widest mb-4">
                                    <?php echo $category; ?></h3>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left border-collapse">
                                        <thead>
                                            <tr class="border-b border-slate-100 dark:border-white/5">
                                                <th
                                                    class="py-3 px-2 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                                    Imagen</th>
                                                <th
                                                    class="py-3 px-2 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                                    SKU</th>
                                                <th
                                                    class="py-3 px-2 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                                    Descripción</th>
                                                <th
                                                    class="py-3 px-2 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">
                                                    Stock</th>
                                                <th
                                                    class="py-3 px-2 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">
                                                    Unit. USD</th>
                                                <th
                                                    class="py-3 px-2 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">
                                                    IVA %</th>
                                                <th
                                                    class="py-3 px-2 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">
                                                    Final USD</th>
                                                <th
                                                    class="py-3 px-2 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">
                                                    Unit. ARS</th>
                                                <th
                                                    class="py-3 px-2 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">
                                                    Final ARS</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-50 dark:divide-white/5">
                                            <?php foreach ($items as $item):
                                                $unit_usd = $item['unit_cost_usd'] * (1 + ($margin / 100));
                                                $iva_rate = $item['iva_rate'] ?? 21;
                                                $final_usd = $unit_usd * (1 + ($iva_rate / 100));
                                                $unit_ars = $unit_usd * $dolar;
                                                $final_ars = $final_usd * $dolar;
                                                $img = $item['image_url'];
                                                ?>
                                                <tr class="hover:bg-slate-50/50 dark:hover:bg-white/5 transition-colors">
                                                    <td class="py-3 px-2">
                                                        <?php if ($img): ?>
                                                            <img src="<?php echo $img; ?>"
                                                                class="size-10 object-contain rounded-lg bg-white p-0.5 border border-slate-100 dark:border-white/10"
                                                                onerror="this.outerHTML='<div class=\'size-10 rounded-lg bg-slate-100 dark:bg-[#101822] flex items-center justify-center text-primary font-bold text-[10px]\'>VS</div>'">
                                                        <?php else: ?>
                                                            <div
                                                                class="size-10 rounded-lg bg-slate-100 dark:bg-[#101822] flex items-center justify-center text-primary font-bold text-[10px]">
                                                                VS</div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="py-3 px-2 font-black text-primary text-[10px]">
                                                        <?php echo $item['sku']; ?></td>
                                                    <td
                                                        class="py-3 px-2 font-medium text-[11px] text-slate-600 dark:text-slate-300 max-w-xs leading-relaxed">
                                                        <?php echo $item['description']; ?></td>
                                                    <td class="py-3 px-2 text-center">
                                                        <?php if ($item['stock_current'] > 0): ?>
                                                            <span
                                                                class="text-[9px] font-black text-green-500 uppercase">Stock</span>
                                                        <?php else: ?>
                                                            <span
                                                                class="text-[9px] font-black text-red-400 uppercase">Consultar</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td
                                                        class="py-3 px-2 font-bold text-right text-slate-700 dark:text-slate-200">
                                                        <?php echo number_format($unit_usd, 2); ?></td>
                                                    <td class="py-3 px-2 text-center font-bold text-slate-400 text-[10px]">
                                                        <?php echo $iva_rate; ?>%</td>
                                                    <td class="py-3 px-2 font-black text-right text-slate-900 dark:text-white">
                                                        <?php echo number_format($final_usd, 2); ?></td>
                                                    <td class="py-3 px-2 font-bold text-right text-slate-500 italic">
                                                        <?php echo number_format($unit_ars, 0, ',', '.'); ?></td>
                                                    <td class="py-3 px-2 font-black text-right text-primary text-xs italic">
                                                        <?php echo number_format($final_ars, 0, ',', '.'); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <footer class="mt-16 pt-8 border-t border-slate-100 dark:border-white/5 text-center">
                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-[0.3em]">VS Sistemas by Javier
                            Gozzi | Impulsando la eficiencia técnica</p>
                    </footer>
                </div>
            </div>
        </main>
    </div>
</body>

</html>