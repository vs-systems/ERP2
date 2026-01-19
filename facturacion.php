<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facturación - VS System</title>
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
                        <span class="material-symbols-outlined text-2xl">description</span>
                    </div>
                    <h2 class="dark:text-white text-slate-800 font-bold text-lg uppercase tracking-tight">Módulo de
                        Facturación</h2>
                </div>
            </header>

            <!-- Content Area -->
                <div class="flex-1 overflow-y-auto p-6 space-y-6">
            <div class="max-w-[1400px] mx-auto space-y-6">
                <div class="flex justify-between items-end">
                    <h1 class="text-2xl font-bold dark:text-white text-slate-800 tracking-tight">Gestión de Facturas
                    </h1>
                </div>

                <div
                    class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-12 shadow-xl dark:shadow-none transition-colors text-center">
                    <div class="flex flex-col items-center justify-center space-y-6 max-w-lg mx-auto">
                        <div class="bg-[#136dec]/10 p-6 rounded-full text-[#136dec]">
                            <span class="material-symbols-outlined text-[80px]">engineering</span>
                        </div>
                        <h3 class="text-2xl font-bold">Módulo en Construcción</h3>
                        <p class="text-slate-500 dark:text-slate-400">
                            Estamos trabajando para integrar la facturación electrónica y el seguimiento automático de
                            cobros directamente desde el ERP.
                        </p>
                        <div class="flex gap-3">
                            <span
                                class="px-4 py-2 rounded-full border border-slate-200 dark:border-[#233348] text-xs font-bold uppercase tracking-widest text-slate-500">Próximamente</span>
                            <span
                                class="px-4 py-2 rounded-full bg-[#136dec]/10 text-[#136dec] text-xs font-bold uppercase tracking-widest">v2.0</span>
                        </div>
                    </div>
                </div>
            </div>
    </div>
    </main>
    </div>
</body>

</html>