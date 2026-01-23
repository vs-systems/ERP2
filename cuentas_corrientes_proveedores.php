<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/modules/billing/ProviderAccounts.php';

use Vsys\Modules\Billing\ProviderAccounts;

$providerAccounts = new ProviderAccounts();
$providers = $providerAccounts->getProvidersWithBalances();
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuentas Corrientes Proveedores - VS System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <script src="js/theme_handler.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            text-transform: uppercase;
        }

        .normal-case {
            text-transform: none;
        }
    </style>
</head>

<body class="bg-white dark:bg-[#101822] text-slate-800 dark:text-white transition-colors duration-300">
    <div class="flex h-screen w-full">
        <?php include 'sidebar.php'; ?>

        <main class="flex-1 flex flex-col h-full overflow-hidden relative">
            <header
                class="h-16 flex items-center justify-between px-6 border-b border-slate-200 dark:border-[#233348] bg-white dark:bg-[#101822]">
                <div class="flex items-center gap-3">
                    <button onclick="toggleVsysSidebar()" class="lg:hidden dark:text-white text-slate-800 p-1 mr-2">
                        <span class="material-symbols-outlined">menu</span>
                    </button>
                    <h2 class="dark:text-white text-slate-800 font-bold text-lg uppercase tracking-tight">Cuentas
                        Corrientes Proveedores</h2>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-6">
                <div class="max-w-7xl mx-auto space-y-6">

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <?php
                        $totalDebt = 0;
                        foreach ($providers as $p) {
                            if ($p['balance'] > 0)
                                $totalDebt += $p['balance'];
                        }
                        ?>
                        <div
                            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-6">
                            <h3 class="text-slate-500 text-[10px] font-bold uppercase tracking-widest mb-2">Total a
                                Pagar</h3>
                            <p class="text-3xl font-bold text-amber-500">
                                $
                                <?php echo number_format($totalDebt, 2, ',', '.'); ?>
                            </p>
                        </div>
                    </div>

                    <div
                        class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl overflow-hidden">
                        <div
                            class="p-6 border-b border-slate-200 dark:border-[#233348] flex justify-between items-center">
                            <h3 class="font-bold text-lg">Deuda con Proveedores</h3>
                            <input type="text" placeholder="Buscar proveedor..."
                                class="bg-slate-50 dark:bg-[#101822] border-none rounded-lg px-4 py-2 text-xs focus:ring-2 focus:ring-primary normal-case">
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr
                                        class="text-[10px] uppercase text-slate-500 border-b border-slate-200 dark:border-[#233348]">
                                        <th class="px-6 py-4 font-bold">Proveedor</th>
                                        <th class="px-6 py-4 font-bold text-right">Compras (H)</th>
                                        <th class="px-6 py-4 font-bold text-right">Pagos (D)</th>
                                        <th class="px-6 py-4 font-bold text-right">Saldo</th>
                                        <th class="px-6 py-4 font-bold text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-[#233348]">
                                    <?php foreach ($providers as $provider): ?>
                                        <tr class="hover:bg-slate-50 dark:hover:bg-[#1c2a3b] transition-colors">
                                            <td class="px-6 py-4">
                                                <div class="font-bold">
                                                    <?php echo htmlspecialchars($provider['name']); ?>
                                                </div>
                                                <div class="text-[10px] text-slate-500">
                                                    <?php echo htmlspecialchars($provider['contact_person']); ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-right text-slate-500 font-mono">
                                                $
                                                <?php echo number_format($provider['total_debit'], 2, ',', '.'); ?>
                                            </td>
                                            <td class="px-6 py-4 text-right text-green-500 font-mono">
                                                $
                                                <?php echo number_format($provider['total_credit'], 2, ',', '.'); ?>
                                            </td>
                                            <td
                                                class="px-6 py-4 text-right font-bold font-mono <?php echo $provider['balance'] > 0 ? 'text-amber-500' : 'text-green-500'; ?>">
                                                $
                                                <?php echo number_format($provider['balance'], 2, ',', '.'); ?>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <a href="cuenta_corriente_proveedor_detalle.php?id=<?php echo $provider['id']; ?>"
                                                    class="text-primary hover:underline text-[10px] font-bold">Ver
                                                    Detalle</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>