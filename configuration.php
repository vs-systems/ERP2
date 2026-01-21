<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';

// Cargar ajustes del sistema (Simulado con archivo por ahora, o podróa ser DB)
$settingsFile = __DIR__ . '/src/config/settings.json';
$settings = ['default_theme' => 'auto'];
if (file_exists($settingsFile)) {
    $settings = json_decode(file_get_contents($settingsFile), true);
}

// Procesar guardado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_visuals') {
    $settings['default_theme'] = $_POST['default_theme'];
    file_put_contents($settingsFile, json_encode($settings));
    header('Location: configuration.php?saved=1');
    exit;
}

$userName = $_SESSION['user_name'] ?? 'Usuario';
$userRole = $_SESSION['role'] ?? 'Invitado';
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - VS System</title>
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
                    <div class="bg-[#136dec]/20 p-2 rounded-lg text-[#136dec]">
                        <span class="material-symbols-outlined text-2xl">settings</span>
                    </div>
                    <h2 class="dark:text-white text-slate-800 font-bold text-lg uppercase tracking-tight">Panel de
                        Configuración</h2>
                </div>
            </header>

            <!-- Content Area -->
            <div class="flex-1 overflow-y-auto p-6 space-y-8">
                <div class="max-w-7xl mx-auto">

                    <div class="mb-8">
                        <h1 class="text-3xl font-bold dark:text-white text-slate-800 tracking-tight">Ajustes del Sistema
                        </h1>
                        <p class="text-slate-500 mt-1">Gestione los parámetros maestros y la apariencia visual del ERP.
                        </p>
                    </div>

                    <?php if (isset($_GET['saved'])): ?>
                        <div
                            class="bg-green-500/10 border border-green-500/20 text-green-500 p-4 rounded-xl mb-6 flex items-center gap-3">
                            <span class="material-symbols-outlined">check_circle</span>
                            <span class="text-sm font-bold uppercase tracking-tight">Ajustes guardados correctamente</span>
                        </div>
                    <?php endif; ?>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                        <!-- Col 1 & 2: Main Business Config -->
                        <div class="lg:col-span-2 space-y-6">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <a href="config_precios.php"
                                    class="group bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] p-6 rounded-2xl hover:border-[#136dec]/50 transition-all shadow-sm dark:shadow-none">
                                    <div
                                        class="size-12 rounded-xl bg-[#136dec]/10 text-[#136dec] flex items-center justify-center mb-4 group-hover:bg-[#136dec] group-hover:text-white transition-colors">
                                        <span class="material-symbols-outlined text-2xl">sell</span>
                                    </div>
                                    <h3 class="font-bold text-lg mb-1">Listas de Precios</h3>
                                    <p class="text-slate-500 text-xs">Márgenes Gremio, Web y ML.</p>
                                </a>

                                <a href="config_transports.php"
                                    class="group bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] p-6 rounded-2xl hover:border-[#136dec]/50 transition-all shadow-sm dark:shadow-none">
                                    <div
                                        class="size-12 rounded-xl bg-amber-500/10 text-amber-500 flex items-center justify-center mb-4 group-hover:bg-amber-500 group-hover:text-white transition-colors">
                                        <span class="material-symbols-outlined text-2xl">local_shipping</span>
                                    </div>
                                    <h3 class="font-bold text-lg mb-1">Transportes</h3>
                                    <p class="text-slate-500 text-xs">Empresas de logística asociadas.</p>
                                </a>

                                <a href="importar.php"
                                    class="group bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] p-6 rounded-2xl hover:border-[#136dec]/50 transition-all shadow-sm dark:shadow-none">
                                    <div
                                        class="size-12 rounded-xl bg-blue-500/10 text-blue-500 flex items-center justify-center mb-4 group-hover:bg-blue-500 group-hover:text-white transition-colors">
                                        <span class="material-symbols-outlined text-2xl">file_upload</span>
                                    </div>
                                    <h3 class="font-bold text-lg mb-1">Carga Masiva</h3>
                                    <p class="text-slate-500 text-xs">Importar Productos/Clientes (CSV).</p>
                                </a>

                                <a href="config_entities.php?type=client"
                                    class="group bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] p-6 rounded-2xl hover:border-[#136dec]/50 transition-all shadow-sm dark:shadow-none">
                                    <div
                                        class="size-12 rounded-xl bg-purple-500/10 text-purple-500 flex items-center justify-center mb-4 group-hover:bg-purple-500 group-hover:text-white transition-colors">
                                        <span class="material-symbols-outlined text-2xl">person_add</span>
                                    </div>
                                    <h3 class="font-bold text-lg mb-1">Maestro de Entidades</h3>
                                    <p class="text-slate-500 text-xs">Gestión de Clientes y Proveedores.</p>
                                </a>
                            </div>
                        </div>

                        <!-- Col 3: Visual & Sidebar Preferences -->
                        <div class="space-y-6">
                            <div
                                class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-6 shadow-xl dark:shadow-none">
                                <h3 class="font-bold dark:text-white text-slate-800 mb-4 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[#136dec]">palette</span> Apariencia
                                    Visual
                                </h3>

                                <form method="POST" class="space-y-4">
                                    <input type="hidden" name="action" value="save_visuals">

                                    <div>
                                        <label
                                            class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Tema
                                            Predeterminado</label>
                                        <select name="default_theme"
                                            class="w-full bg-slate-50 dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-xl text-sm dark:text-white text-slate-800 focus:ring-[#136dec] transition-colors">
                                            <option value="auto" <?php echo $settings['default_theme'] === 'auto' ? 'selected' : ''; ?>>Seguir Sistema (Auto)</option>
                                            <option value="light" <?php echo $settings['default_theme'] === 'light' ? 'selected' : ''; ?>>Siempre Claro</option>
                                            <option value="dark" <?php echo $settings['default_theme'] === 'dark' ? 'selected' : ''; ?>>Siempre Oscuro</option>
                                        </select>
                                    </div>

                                    <div class="pt-2">
                                        <button type="submit"
                                            class="w-full bg-[#136dec] hover:bg-blue-600 text-white font-bold py-3 rounded-xl shadow-lg shadow-[#136dec]/20 transition-all active:scale-[0.98]">
                                            GUARDAR PREFERENCIAS
                                        </button>
                                    </div>
                                </form>

                                <div
                                    class="mt-6 p-4 bg-slate-50 dark:bg-white/5 rounded-xl border border-dashed border-slate-300 dark:border-white/10">
                                    <p class="text-[10px] text-slate-500 leading-relaxed font-medium">
                                        <span class="font-bold underline">Nota:</span> Esta configuración aplica como
                                        valor por defecto para nuevos usuarios o sesiones sin preferencia guardada
                                        localmente.
                                    </p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>