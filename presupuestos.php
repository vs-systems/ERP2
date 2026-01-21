<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/cotizador/Cotizador.php';

use Vsys\Modules\Cotizador\Cotizador;

$cot = new Cotizador();
$quotes = $cot->getAllQuotations(100);
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Presupuestos - VS System</title>
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
            background: transparent;
        }

        .dark ::-webkit-scrollbar-track {
            background: #101822;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }

        .dark ::-webkit-scrollbar-thumb {
            background: #233348;
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
                class="h-16 flex items-center justify-between px-6 border-b border-slate-200 dark:border-[#233348] bg-white dark:bg-[#101822]/95 backdrop-blur z-10 sticky top-0 transition-colors duration-300">
                <div class="flex items-center gap-3">
                    <button onclick="toggleVsysSidebar()" class="lg:hidden dark:text-white text-slate-800 p-1 mr-2">
                        <span class="material-symbols-outlined">menu</span>
                    </button>
                    <div class="bg-primary/20 p-2 rounded-lg text-primary">
                        <span class="material-symbols-outlined text-2xl">history</span>
                    </div>
                    <h2 class="dark:text-white text-slate-800 font-bold text-lg uppercase tracking-tight">Historial de
                        Presupuestos</h2>
                </div>
                <div class="flex items-center gap-4">
                    <a href="cotizador.php"
                        class="bg-primary hover:bg-blue-600 text-white px-4 py-2 rounded-xl text-sm font-bold flex items-center gap-2 transition-all shadow-lg shadow-primary/20 active:scale-95">
                        <span class="material-symbols-outlined text-sm">add</span>
                        NUEVA COTIZACIÓN
                    </a>
                </div>
            </header>

            <!-- Content Area -->
            <div class="flex-1 overflow-y-auto p-6">
                <div class="max-w-[1400px] mx-auto space-y-6">

                    <div class="flex justify-between items-end">
                        <h1 class="text-2xl font-bold dark:text-white text-slate-800 tracking-tight">Gestión de
                            Presupuestos</h1>
                        <div class="flex gap-2">
                            <div
                                class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] px-4 py-2 rounded-xl flex items-center gap-3 shadow-sm dark:shadow-none">
                                <span
                                    class="text-[10px] font-bold text-slate-500 uppercase tracking-widest leading-none">Total</span>
                                <span class="text-lg font-bold text-primary"><?php echo count($quotes); ?></span>
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl overflow-hidden shadow-xl dark:shadow-none transition-colors">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead
                                    class="bg-slate-50 dark:bg-[#101822]/50 transition-colors border-b border-slate-100 dark:border-[#233348]">
                                    <tr class="text-slate-500 text-[10px] font-bold uppercase tracking-widest">
                                        <th class="px-6 py-4">Fecha</th>
                                        <th class="px-6 py-4">Ref. Número</th>
                                        <th class="px-6 py-4">Cliente</th>
                                        <th class="px-6 py-4 text-right">Total USD</th>
                                        <th class="px-6 py-4 text-right">Total ARS</th>
                                        <th class="px-6 py-4 text-center">Estado</th>
                                        <th class="px-6 py-4 text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-[#233348] transition-colors">
                                    <?php foreach ($quotes as $q): ?>
                                        <tr class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors group">
                                            <td class="px-6 py-5 whitespace-nowrap">
                                                <div class="text-xs font-medium dark:text-slate-300 text-slate-600">
                                                    <?php echo date('d/m/Y', strtotime($q['created_at'])); ?>
                                                </div>
                                                <div class="text-[10px] text-slate-500">
                                                    <?php echo date('H:i', strtotime($q['created_at'])); ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-5">
                                                <span
                                                    class="font-bold dark:text-white text-slate-800 group-hover:text-primary transition-colors">
                                                    <?php echo $q['quote_number']; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-5">
                                                <div class="text-xs font-semibold dark:text-slate-200 text-slate-700">
                                                    <?php echo $q['client_name']; ?>
                                                </div>
                                            </td>
                                            <td
                                                class="px-6 py-5 text-right font-mono text-sm dark:text-white text-slate-800">
                                                $ <?php echo number_format($q['total_usd'], 2); ?>
                                            </td>
                                            <td class="px-6 py-5 text-right font-mono text-sm text-slate-500">
                                                $ <?php echo number_format($q['total_ars'], 2, ',', '.'); ?>
                                            </td>
                                            <td class="px-6 py-5 text-center">
                                                <?php if ($q['is_confirmed']): ?>
                                                    <span
                                                        class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full bg-green-500/10 text-green-500 text-[10px] font-bold uppercase tracking-tight border border-green-500/20">
                                                        <span
                                                            class="material-symbols-outlined text-[12px] fill-1">verified</span>
                                                        Confirmado
                                                    </span>
                                                <?php else: ?>
                                                    <span
                                                        class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full bg-slate-500/10 text-slate-500 text-[10px] font-bold uppercase tracking-tight border border-slate-500/10">
                                                        <span class="material-symbols-outlined text-[12px]">draft</span>
                                                        Borrador
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-5">
                                                <div class="flex items-center justify-center gap-1">
                                                    <!-- Print -->
                                                    <button
                                                        onclick="openQuote(<?php echo $q['id']; ?>, <?php echo $q['client_id']; ?>, '<?php echo $q['quote_number']; ?>')"
                                                        class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-white/10 text-slate-500 hover:text-primary transition-all"
                                                        title="Ver / Imprimir">
                                                        <span class="material-symbols-outlined text-[18px]">print</span>
                                                    </button>

                                                    <!-- Confirm Toggle -->
                                                    <button
                                                        onclick="toggleStatus(<?php echo $q['id']; ?>, 'quotation', 'is_confirmed', <?php echo $q['is_confirmed'] ? 0 : 1; ?>)"
                                                        class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-white/10 transition-all <?php echo $q['is_confirmed'] ? 'text-green-500' : 'text-slate-400'; ?>"
                                                        title="<?php echo $q['is_confirmed'] ? 'Desmarcar' : 'Confirmar'; ?>">
                                                        <span
                                                            class="material-symbols-outlined text-[18px] <?php echo $q['is_confirmed'] ? 'fill-1' : ''; ?>">check_circle</span>
                                                    </button>

                                                    <!-- Payment Toggle -->
                                                    <button
                                                        onclick="toggleStatus(<?php echo $q['id']; ?>, 'quotation', 'payment_status', '<?php echo $q['payment_status'] === 'Pagado' ? 'Pendiente' : 'Pagado'; ?>')"
                                                        class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-white/10 transition-all <?php echo $q['payment_status'] === 'Pagado' ? 'text-purple-500' : 'text-slate-400'; ?>"
                                                        title="Estado Pago: <?php echo $q['payment_status']; ?>">
                                                        <span
                                                            class="material-symbols-outlined text-[18px] <?php echo $q['payment_status'] === 'Pagado' ? 'fill-1' : ''; ?>">payments</span>
                                                    </button>

                                                    <!-- Email -->
                                                    <button onclick="sendEmail(<?php echo $q['id']; ?>)"
                                                        class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-white/10 text-slate-400 hover:text-blue-500 transition-all"
                                                        title="Enviar Email">
                                                        <span class="material-symbols-outlined text-[18px]">mail</span>
                                                    </button>

                                                    <!-- Calendar -->
                                                    <a href="https://calendar.google.com/calendar/render?action=TEMPLATE&text=Cotización:+<?php echo urlencode($q['quote_number']); ?>&details=Cliente:+<?php echo urlencode($q['client_name']); ?>+-+Total:+<?php echo urlencode($q['total_usd']); ?>+USD&dates=<?php echo date('Ymd\THis\Z'); ?>/<?php echo date('Ymd\THis\Z', strtotime('+1 hour')); ?>"
                                                        target="_blank"
                                                        class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-white/10 text-slate-400 hover:text-amber-500 transition-all"
                                                        title="Calendar">
                                                        <span
                                                            class="material-symbols-outlined text-[18px]">calendar_add_on</span>
                                                    </a>

                                                    <!-- Delete -->
                                                    <button
                                                        onclick="deleteQuote(<?php echo $q['id']; ?>, '<?php echo $q['quote_number']; ?>')"
                                                        class="p-2 rounded-lg hover:bg-red-500/10 text-red-500/40 hover:text-red-500 transition-all"
                                                        title="Eliminar">
                                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                                    </button>
                                                </div>
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

    <script>
        async function sendEmail(id) {
            if (!confirm('¿Desea enviar este presupuesto por email al cliente?')) return;
            const btn = event.currentTarget;
            const oldIcon = btn.innerHTML;
            btn.innerHTML = '<span class="material-symbols-outlined animate-spin">sync</span>';
            btn.disabled = true;

            try {
                const res = await fetch('ajax_send_email.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ type: 'quotation', id: id })
                });
                const data = await res.json();
                if (data.success) alert('Email enviado correctamente.');
                else alert('Error: ' + data.error);
            } catch (e) {
                alert('Error de conexión');
            } finally {
                btn.innerHTML = oldIcon;
                btn.disabled = false;
            }
        }

        function openQuote(id, entityId, quoteNo) {
            fetch('ajax_log_crm.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    entity_id: entityId,
                    type: 'Email/PDF',
                    description: `Visualización de presupuesto ${quoteNo}`
                })
            });
            window.open('imprimir_cotizacion.php?id=' + id, '_blank');
        }

        async function deleteQuote(id, number) {
            if (!confirm(`¿Está seguro de eliminar el presupuesto ${number}?`)) return;
            const res = await fetch('ajax_delete_quotation.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            const data = await res.json();
            if (data.success) location.reload();
            else alert('Error: ' + data.error);
        }

        async function toggleStatus(id, type, field, val) {
            const res = await fetch('ajax_update_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id, type: type, field: field, value: val })
            });
            const data = await res.json();
            if (data.success) location.reload();
            else alert('Error: ' + data.error);
        }
    </script>
</body>

</html>