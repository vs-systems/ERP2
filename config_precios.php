<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/config/PriceList.php';

use Vsys\Modules\Config\PriceList;

$priceListModule = new PriceList();
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['update_margins'])) {
            foreach ($_POST['margins'] as $id => $margin) {
                $priceListModule->updateMargin($id, $margin);
            }
            $message = "Márgenes actualizados correctamente.";
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = 'error';
    }
}

$lists = $priceListModule->getAll();
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración de Precios - VS System</title>
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
                        "background-dark": "#101822",
                        "surface-dark": "#16202e",
                        "surface-border": "#233348",
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
    </style>
</head>

<body
    class="bg-white dark:bg-[#101822] text-slate-800 dark:text-white antialiased overflow-hidden transition-colors duration-300">
    <div class="flex h-screen w-full">
        <?php include 'sidebar.php'; ?>

        <main class="flex-1 flex flex-col h-full overflow-hidden relative">
            <!-- Header -->
            <header
                class="h-16 flex items-center justify-between px-6 border-b border-slate-200 dark:border-[#233348] bg-white dark:bg-[#101822]/95 backdrop-blur z-10 transition-colors duration-300">
                <div class="flex items-center gap-3">
                    <div class="bg-[#136dec]/20 p-2 rounded-lg text-[#136dec]">
                        <span class="material-symbols-outlined text-2xl">universal_currency_alt</span>
                    </div>
                    <h2 class="dark:text-white text-slate-800 font-bold text-lg uppercase tracking-tight">
                        Configuraci&oacute;n de M&aacute;rgenes de Venta</h2>
                </div>
            </header>

            <!-- Content Area -->
            <div class="flex-1 overflow-y-auto p-6 space-y-6">
                <div class="max-w-[1400px] mx-auto space-y-6">
                    <div class="flex justify-between items-end">
                        <div class="space-y-1">
                            <h1 class="text-2xl font-bold dark:text-white text-slate-800 tracking-tight">Gestión de
                                Márgenes por Lista</h1>
                            <h3 class="font-bold text-slate-500 dark:text-slate-400 uppercase text-xs tracking-widest">
                                Ajuste de M&aacute;rgenes por Lista</h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Defina el porcentaje de ganancia sobre
                                el costo (USD) para cada lista de precios.</p>
                        </div>
                    </div>

                    <?php if ($message): ?>
                        <div
                            class="flex items-center gap-3 p-4 rounded-2xl border <?php echo $messageType === 'success' ? 'bg-green-500/10 border-green-500/20 text-green-500' : 'bg-red-500/10 border-red-500/20 text-red-500'; ?> animate-in fade-in slide-in-from-top-4 duration-300">
                            <span
                                class="material-symbols-outlined"><?php echo $messageType === 'success' ? 'check_circle' : 'error'; ?></span>
                            <span class="text-sm font-bold uppercase tracking-widest"><?php echo $message; ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="POST"
                        class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl overflow-hidden shadow-xl dark:shadow-none transition-colors">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="bg-slate-50 dark:bg-[#101822]/50 transition-colors">
                                    <tr class="text-slate-500 text-[10px] font-bold uppercase tracking-widest">
                                        <th class="px-6 py-4">Lista de Precios</th>
                                        <th class="px-6 py-4">Margen Actual (%)</th>
                                        <th class="px-6 py-4">Simulación (Costo $100 USD)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-[#233348] transition-colors">
                                    <?php foreach ($lists as $list): ?>
                                        <tr class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors group">
                                            <td class="px-6 py-5">
                                                <div
                                                    class="font-bold dark:text-white text-slate-800 group-hover:text-primary transition-colors">
                                                    <?php echo $list['name']; ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-5">
                                                <div class="flex items-center gap-2">
                                                    <input type="number" step="0.01"
                                                        name="margins[<?php echo $list['id']; ?>]"
                                                        value="<?php echo $list['margin_percent']; ?>"
                                                        class="bg-slate-50 dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-lg px-3 py-1.5 text-sm w-24 focus:ring-primary focus:border-primary transition-all">
                                                    <span class="text-slate-400 font-bold">%</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-5">
                                                <div class="flex items-center gap-2">
                                                    <span
                                                        class="material-symbols-outlined text-green-500 text-sm">trending_up</span>
                                                    <span class="text-green-500 font-bold text-lg">
                                                        $<?php echo number_format(100 * (1 + $list['margin_percent'] / 100), 2); ?>
                                                    </span>
                                                    <span
                                                        class="text-slate-400 text-xs tracking-tighter uppercase font-bold ml-1">USD</span>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div
                            class="p-6 bg-slate-50 dark:bg-[#101822]/30 border-t border-slate-100 dark:border-[#233348] flex justify-end">
                            <button type="submit" name="update_margins"
                                class="bg-primary hover:bg-blue-600 text-white font-bold py-3 px-8 rounded-xl text-xs uppercase tracking-widest shadow-lg shadow-primary/20 active:scale-95 transition-all flex items-center gap-2">
                                <span class="material-symbols-outlined text-sm">save</span> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>

</html>