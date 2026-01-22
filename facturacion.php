<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

$userRole = $_SESSION['role'] ?? 'Invitado';
$userName = $_SESSION['full_name'] ?? ($_SESSION['user_name'] ?? 'Usuario');
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facturación Premium - VS System</title>
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
                    <button onclick="toggleVsysSidebar()" class="lg:hidden dark:text-white text-slate-800 p-1 mr-2">
                        <span class="material-symbols-outlined">menu</span>
                    </button>
                    <div class="bg-[#136dec]/20 p-2 rounded-lg text-[#136dec]">
                        <span class="material-symbols-outlined text-2xl">description</span>
                    </div>
                    <h2 class="dark:text-white text-slate-800 font-bold text-lg uppercase tracking-tight">Módulo de
                        Facturación</h2>
                </div>
            </header>

            <!-- Content Area -->
            <div class="flex-1 overflow-y-auto p-6 space-y-6">
                <div class="max-w-4xl mx-auto space-y-6">

                    <div
                        class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-8 shadow-xl dark:shadow-none transition-colors">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="p-3 bg-primary/10 rounded-xl text-primary">
                                <span class="material-symbols-outlined text-3xl">receipt_long</span>
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold dark:text-white text-slate-800">Gestión de Facturas</h1>
                                <p class="text-slate-400 text-sm">Administración de comprobantes y facturación
                                    electrónica.</p>
                            </div>
                        </div>

                        <div class="py-20 flex flex-col items-center justify-center text-center space-y-6">
                            <div class="relative">
                                <div class="absolute -inset-4 bg-primary/20 blur-2xl rounded-full"></div>
                                <span
                                    class="material-symbols-outlined text-7xl text-primary relative">construction</span>
                            </div>
                            <div class="space-y-2">
                                <h3 class="text-xl font-bold dark:text-white text-slate-800 tracking-tight">Módulo en
                                    Construcción</h3>
                                <p class="text-slate-500 max-w-md mx-auto">
                                    Estamos trabajando en la integración de <span
                                        class="text-primary font-semibold">Factura Electrónica AFIP</span> y el
                                    seguimiento automatizado de cobros.
                                </p>
                            </div>
                            <div class="flex gap-4">
                                <div
                                    class="px-4 py-2 bg-slate-100 dark:bg-white/5 rounded-lg border border-slate-200 dark:border-white/10 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-sm text-green-500">check_circle</span>
                                    <span class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Diseño
                                        UI Listo</span>
                                </div>
                                <div
                                    class="px-4 py-2 bg-slate-100 dark:bg-white/5 rounded-lg border border-slate-200 dark:border-white/10 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-sm text-amber-500">pending</span>
                                    <span class="text-[10px] font-bold uppercase tracking-widest text-slate-500">API
                                        Facturación</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Coming Soon Features Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div
                            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] p-6 rounded-2xl opacity-60">
                            <span class="material-symbols-outlined text-primary mb-3">cloud_sync</span>
                            <h4 class="font-bold mb-1">Sincronización AFIP</h4>
                            <p class="text-xs text-slate-500">Emisión de CAE y validación de comprobantes en tiempo
                                real.</p>
                        </div>
                        <div
                            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] p-6 rounded-2xl opacity-60">
                            <span class="material-symbols-outlined text-primary mb-3">account_balance_wallet</span>
                            <h4 class="font-bold mb-1">Cuentas Corrientes</h4>
                            <p class="text-xs text-slate-500">Seguimiento detallado de saldos y vencimientos de
                                clientes.</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>