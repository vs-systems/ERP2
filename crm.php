<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/crm/CRM.php';

use Vsys\Modules\CRM\CRM;

$crm = new CRM();
$today = date('Y-m-d');
$stats = $crm->getStats($today);

// Stages for Kanban (Material Symbols)
$stages = [
    'Nuevo' => ['icon' => 'star', 'color' => '#3b82f6', 'label' => 'Nuevos Leads'],
    'Contactado' => ['icon' => 'forum', 'color' => '#8b5cf6', 'label' => 'En Contacto'],
    'Presupuestado' => ['icon' => 'query_stats', 'color' => '#f59e0b', 'label' => 'Cotizados'],
    'Ganado' => ['icon' => 'check_circle', 'color' => '#10b981', 'label' => 'Ganados'],
    'Perdido' => ['icon' => 'cancel', 'color' => '#f43f5e', 'label' => 'Perdidos']
];
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM Moderno - VS System</title>
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
                        "surface-dark": "#16202e",
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

        .kanban-container {
            display: flex;
            gap: 1.25rem;
            padding-bottom: 1rem;
            min-height: calc(100vh - 350px);
        }

        .kanban-col {
            flex: 0 0 320px;
            @apply bg-slate-50 dark:bg-white/5 rounded-2xl p-4 border border-slate-200 dark:border-[#233348] flex flex-col gap-4;
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
                        <span class="material-symbols-outlined text-2xl">dynamic_feed</span>
                    </div>
                    <h2 class="dark:text-white text-slate-800 font-bold text-lg uppercase tracking-tight">CRM
                        Intelligent</h2>
                </div>
                <div class="flex items-center gap-4">
                    <button onclick="document.getElementById('newLeadModal').style.display='flex'"
                        class="bg-primary hover:bg-blue-600 text-white px-4 py-2 rounded-xl text-sm font-bold flex items-center gap-2 transition-all shadow-lg shadow-primary/20 active:scale-95">
                        <span class="material-symbols-outlined text-sm">person_add</span>
                        NUEVO LEAD
                    </button>
                </div>
            </header>

            <!-- Content Area -->
            <div class="flex-1 overflow-y-auto p-6 space-y-8">
                <div class="max-w-[1600px] mx-auto space-y-8">

                    <!-- Top Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div
                            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] p-6 rounded-2xl shadow-sm dark:shadow-none transition-all group hover:border-primary/50 relative overflow-hidden">
                            <div
                                class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                                <span class="material-symbols-outlined text-6xl">request_quote</span>
                            </div>
                            <h4 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Presupuestos
                                Activos</h4>
                            <div class="text-3xl font-bold dark:text-white text-slate-800">
                                <?php echo $stats['active_quotes']; ?>
                            </div>
                            <div class="mt-2 flex items-center gap-1 text-[10px] font-bold text-green-500 uppercase">
                                <span class="material-symbols-outlined text-sm">trending_up</span> Pipeline Saludable
                            </div>
                        </div>

                        <div
                            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] p-6 rounded-2xl shadow-sm dark:shadow-none transition-all group hover:border-primary/50 relative overflow-hidden">
                            <div
                                class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity text-primary">
                                <span class="material-symbols-outlined text-6xl">shopping_cart_checkout</span>
                            </div>
                            <h4 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Cierres
                                Técnicos Hoy</h4>
                            <div class="text-3xl font-bold text-primary"><?php echo $stats['orders_today']; ?></div>
                            <div class="mt-2 text-[10px] font-bold text-slate-400 uppercase">Conversión diaria en tiempo
                                real</div>
                        </div>

                        <div
                            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] p-6 rounded-2xl shadow-sm dark:shadow-none transition-all group hover:border-primary/50 relative overflow-hidden">
                            <h4 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Eficiencia
                                de Ventas</h4>
                            <div class="text-3xl font-bold dark:text-white text-slate-800">
                                <?php echo $stats['efficiency']; ?>%
                            </div>
                            <div class="mt-4 w-full h-1.5 bg-slate-100 dark:bg-white/10 rounded-full overflow-hidden">
                                <div class="h-full bg-primary rounded-full transition-all duration-1000"
                                    style="width: <?php echo $stats['efficiency']; ?>%;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Follow-up Alerts -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div
                            class="bg-amber-500/5 border border-amber-500/20 p-4 rounded-2xl flex items-start gap-4 hover:bg-amber-500/10 transition-colors">
                            <div class="bg-amber-500/20 p-3 rounded-xl text-amber-500">
                                <span class="material-symbols-outlined text-xl">call</span>
                            </div>
                            <div class="flex-1">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-[10px] font-bold text-amber-500 uppercase tracking-widest">Llamar
                                        Cliente</span>
                                    <span class="text-[10px] text-slate-500 font-medium">Hace 2 días</span>
                                </div>
                                <h5 class="text-sm font-bold dark:text-slate-200 text-slate-700">Seguimiento Presupuesto
                                    #882</h5>
                                <p class="text-[11px] text-slate-500 truncate mb-3">Cliente: Juan Perez - Pendiente de
                                    feedback</p>
                                <button
                                    class="w-full bg-amber-500 text-white text-[10px] font-bold py-1.5 rounded-lg active:scale-95 transition-transform uppercase tracking-widest">Llamar
                                    Ahora</button>
                            </div>
                        </div>
                        <!-- More dynamic alerts would be rendered here -->
                    </div>

                    <!-- Kanban Board -->
                    <div class="overflow-x-auto pb-4 custom-scrollbar">
                        <div class="kanban-container">
                            <?php foreach ($stages as $stage => $meta):
                                $leads = $crm->getLeadsByStatus($stage);
                                ?>
                                <div class="kanban-col">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center gap-2">
                                            <span class="material-symbols-outlined text-lg"
                                                style="color: <?php echo $meta['color']; ?>;"><?php echo $meta['icon']; ?></span>
                                            <h3
                                                class="text-xs font-bold dark:text-slate-400 text-slate-500 uppercase tracking-widest">
                                                <?php echo $meta['label']; ?>
                                            </h3>
                                        </div>
                                        <span
                                            class="bg-slate-200 dark:bg-white/10 px-2 py-0.5 rounded-full text-[10px] font-bold text-slate-500 dark:text-slate-400"><?php echo count($leads); ?></span>
                                    </div>

                                    <div class="flex flex-col gap-3 min-h-[50px]">
                                        <?php foreach ($leads as $lead): ?>
                                            <div onclick="openLead(<?php echo $lead['id']; ?>)"
                                                class="group bg-white dark:bg-[#101822] border border-slate-200 dark:border-white/5 p-4 rounded-xl hover:border-primary/50 dark:hover:border-primary/50 transition-all cursor-pointer shadow-sm hover:shadow-lg hover:shadow-primary/5 flex flex-col gap-2">
                                                <div class="flex justify-between items-start">
                                                    <h4
                                                        class="text-sm font-bold dark:text-white text-slate-800 group-hover:text-primary transition-colors leading-tight">
                                                        <?php echo $lead['name']; ?>
                                                    </h4>
                                                    <span
                                                        class="text-[9px] font-bold dark:text-slate-500 text-slate-400 uppercase"><?php echo date('d M', strtotime($lead['updated_at'])); ?></span>
                                                </div>

                                                <div class="space-y-1">
                                                    <div class="flex items-center gap-2 text-slate-500 dark:text-slate-400">
                                                        <span class="material-symbols-outlined text-[14px]">person</span>
                                                        <span
                                                            class="text-[10px] leading-none"><?php echo $lead['contact_person'] ?: 'Sin contacto'; ?></span>
                                                    </div>
                                                    <?php if ($lead['phone']): ?>
                                                        <div
                                                            class="flex items-center gap-2 text-green-500 font-medium leading-none">
                                                            <span class="material-symbols-outlined text-[14px]">smartphone</span>
                                                            <span
                                                                class="text-[10px] leading-none"><?php echo $lead['phone']; ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>

                                                <div
                                                    class="flex justify-between items-center mt-2 pt-2 border-t border-slate-50 dark:border-white/5 opacity-0 group-hover:opacity-100 transition-opacity">
                                                    <button
                                                        onclick="event.stopPropagation(); moveLead(<?php echo $lead['id']; ?>, 'prev')"
                                                        class="p-1 hover:bg-slate-100 dark:hover:bg-white/10 rounded-lg text-slate-400 hover:text-primary transition-all">
                                                        <span class="material-symbols-outlined text-lg">chevron_left</span>
                                                    </button>
                                                    <span
                                                        class="text-[9px] font-bold uppercase text-slate-400 tracking-tighter">Mover</span>
                                                    <button
                                                        onclick="event.stopPropagation(); moveLead(<?php echo $lead['id']; ?>, 'next')"
                                                        class="p-1 hover:bg-slate-100 dark:hover:bg-white/10 rounded-lg text-slate-400 hover:text-primary transition-all">
                                                        <span class="material-symbols-outlined text-lg">chevron_right</span>
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>

                                        <?php if (empty($leads)): ?>
                                            <div
                                                class="py-12 flex flex-col items-center justify-center border border-dashed border-slate-200 dark:border-white/5 rounded-xl text-slate-300 dark:text-slate-800">
                                                <span class="material-symbols-outlined text-3xl">inbox</span>
                                                <span class="text-[10px] font-bold uppercase tracking-widest mt-2">Vacío</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <script>
        function openLead(id) {
            console.log('Opening lead details:', id);
            // Modal expansion planned for phase 14
        }

        async function moveLead(id, direction) {
            const formData = new FormData();
            formData.append('action', 'move_lead');
            formData.append('id', id);
            formData.append('direction', direction);

            try {
                const resp = await fetch('ajax_crm_actions.php', { method: 'POST', body: formData });
                const res = await resp.json();
                if (res.success) location.reload();
                else alert(res.error);
            } catch (e) {
                console.error(e);
                alert('Error al mover lead');
            }
        }
    </script>
</body>

</html>