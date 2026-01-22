<?php
// sidebar.php - Rediseño basado en Stitch (Tailwind + Material Symbols) con Soporte de Temas
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/lib/User.php';

$currentPage = basename($_SERVER['PHP_SELF'], '.php');

if (!isset($userAuth)) {
    $userAuth = new \Vsys\Lib\User();
}

$userName = $_SESSION['full_name'] ?? ($_SESSION['user_name'] ?? 'Usuario');
$userRole = $_SESSION['role'] ?? 'Invitado';

$menu = [
    ['id' => 'index', 'href' => 'dashboard.php', 'icon' => 'dashboard', 'label' => 'Inicio'],
    [
        'label' => 'Ventas',
        'icon' => 'receipt_long',
        'items' => [
            ['id' => 'presupuestos', 'href' => 'presupuestos.php', 'icon' => 'history', 'label' => 'Historial'],
            ['id' => 'cotizador', 'href' => 'cotizador.php', 'icon' => 'add_shopping_cart', 'label' => 'Generar Cotiz.'],
            ['id' => 'productos', 'href' => 'productos.php', 'icon' => 'inventory_2', 'label' => 'Productos/Stock'],
        ]
    ],
    [
        'label' => 'Contabilidad',
        'icon' => 'payments',
        'items' => [
            ['id' => 'compras', 'href' => 'compras.php', 'icon' => 'shopping_cart_checkout', 'label' => 'Compras'],
            ['id' => 'facturacion', 'href' => 'facturacion.php', 'icon' => 'description', 'label' => 'Facturación'],
            ['id' => 'analizador', 'href' => 'analizador.php', 'icon' => 'query_stats', 'label' => 'Análisis OP.'],
        ]
    ],
    ['id' => 'crm', 'href' => 'crm.php', 'icon' => 'group', 'label' => 'CRM'],
    ['id' => 'logistica', 'href' => 'logistica.php', 'icon' => 'local_shipping', 'label' => 'Logística'],
    ['id' => 'clientes', 'href' => 'clientes.php', 'icon' => 'badge', 'label' => 'Clientes'],
    ['id' => 'proveedores', 'href' => 'proveedores.php', 'icon' => 'factory', 'label' => 'Proveedores'],
    ['id' => 'informes', 'href' => 'informes.php', 'icon' => 'insert_chart', 'label' => 'Informes'],
    ['id' => 'configuration', 'href' => 'configuration.php', 'icon' => 'settings', 'label' => 'Configuración', 'role' => 'Admin'],
    ['id' => 'usuarios', 'href' => 'usuarios.php', 'icon' => 'admin_panel_settings', 'label' => 'Usuarios', 'role' => 'Admin'],
];
// Cargar preferencia del sistema
$settingsFile = __DIR__ . '/src/config/settings.json';
$sysSettings = ['default_theme' => 'auto'];
if (file_exists($settingsFile)) {
    $sysSettings = json_decode(file_get_contents($settingsFile), true);
}
$defaultTheme = $sysSettings['default_theme'] ?? 'auto';
?>

<!-- Theme Handler (Prevent FOUC) -->
<script>
    // Inyectar preferencia del sistema para que theme_handler.js la use
    window.vsys_default_theme = "<?php echo $defaultTheme; ?>";
</script>
<script src="js/theme_handler.js"></script>

<!-- Material Symbols Font -->
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
    rel="stylesheet" />

<!-- Mobile Overlay -->
<div id="sidebarOverlay" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[40] md:hidden hidden"
    onclick="toggleVsysSidebar()"></div>

<aside id="mainSidebar"
    class="fixed md:relative inset-y-0 left-0 w-64 h-full bg-[#101822] border-r border-[#233348] flex-shrink-0 overflow-y-auto transition-all duration-300 z-[50] -translate-x-full md:translate-x-0 dark:bg-[#101822] bg-white border-slate-200 dark:border-[#233348] flex flex-col">
    <!-- Brand Logo Section -->
    <div class="p-6 flex items-center gap-3">
        <div class="bg-[#136dec]/20 p-2 rounded-lg text-[#136dec] flex items-center justify-center">
            <span class="material-symbols-outlined text-2xl">shield</span>
        </div>
        <div>
            <h1 class="dark:text-white text-slate-800 text-lg font-bold leading-tight">VS System</h1>
            <p class="text-slate-400 text-[10px] font-normal uppercase tracking-wider">ERP & Seguridad</p>
        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="flex-1 px-4 py-2 space-y-1">
        <?php foreach ($menu as $section): ?>
            <?php
            if (isset($section['role']) && !$userAuth->hasRole($section['role']))
                continue;

            $isActiveParent = false;
            if (isset($section['items'])) {
                foreach ($section['items'] as $sub) {
                    if ($currentPage === $sub['id']) {
                        $isActiveParent = true;
                        break;
                    }
                }
            } else {
                $isActiveParent = ($currentPage === $section['id']);
            }
            ?>

            <?php if (isset($section['items'])): ?>
                <div class="pt-4 pb-1">
                    <p class="px-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2 font-display">
                        <?php echo $section['label']; ?>
                    </p>
                    <?php foreach ($section['items'] as $item): ?>
                        <?php if (isset($item['role']) && !$userAuth->hasRole($item['role']))
                            continue; ?>
                        <a href="<?php echo $item['href']; ?>"
                            class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 <?php echo ($currentPage === $item['id']) ? 'bg-[#136dec] text-white shadow-lg shadow-[#136dec]/20' : 'text-slate-400 hover:text-[#136dec] hover:bg-slate-100 dark:hover:bg-[#233348] dark:hover:text-white'; ?>">
                            <span class="material-symbols-outlined text-[20px]"><?php echo $item['icon']; ?></span>
                            <span class="text-sm font-medium"><?php echo $item['label']; ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <a href="<?php echo $section['href']; ?>"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 <?php echo $isActiveParent ? 'bg-[#136dec] text-white shadow-lg shadow-[#136dec]/20' : 'text-slate-400 hover:text-[#136dec] hover:bg-slate-100 dark:hover:bg-[#233348] dark:hover:text-white'; ?>">
                    <span class="material-symbols-outlined text-[20px]"><?php echo $section['icon']; ?></span>
                    <span class="text-sm font-medium"><?php echo $section['label']; ?></span>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>

        <!-- External Links -->
        <div class="pt-6 border-t border-slate-200 dark:border-[#233348] mt-4">
            <a href="catalogo_publico.php" target="_blank"
                class="flex items-center gap-3 px-3 py-2 rounded-lg text-[#10b981] hover:bg-[#10b981]/10 transition-all font-bold">
                <span class="material-symbols-outlined text-[20px]">open_in_new</span>
                <span class="text-xs uppercase tracking-tight">Catálogo Público</span>
            </a>
            <a href="https://calendar.google.com/calendar/u/0/r?cid=dmVjaW5vc2VndXJvMEBnbWFpbC5jb20" target="_blank"
                class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-400 hover:text-[#136dec] hover:bg-slate-100 dark:hover:bg-[#233348] transition-all">
                <span class="material-symbols-outlined text-[20px]">calendar_month</span>
                <span class="text-sm font-medium">Calendario</span>
            </a>
        </div>
    </nav>

    <!-- User Section -->
    <div class="p-4 border-t border-slate-200 dark:border-[#233348]">
        <div class="flex items-center gap-3 px-2 py-3">
            <div
                class="size-9 rounded-full bg-[#136dec]/20 border border-[#136dec]/30 flex items-center justify-center text-[#136dec]">
                <span class="material-symbols-outlined text-[20px]">account_circle</span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="dark:text-white text-slate-800 text-sm font-bold truncate"><?php echo $userName; ?></p>
                <p class="text-slate-500 text-[10px] uppercase font-bold tracking-tighter"><?php echo $userRole; ?></p>
            </div>

            <!-- Theme Toggle -->
            <button onclick="toggleVsysTheme()" class="text-slate-400 hover:text-[#136dec] transition-colors p-1"
                title="Cambiar Tema">
                <span class="material-symbols-outlined text-[20px] dark:hidden">dark_mode</span>
                <span class="material-symbols-outlined text-[20px] hidden dark:block">light_mode</span>
            </button>

            <a href="logout.php" class="ml-2 text-slate-500 hover:text-red-400 transition-colors" title="Cerrar Sesión">
                <span class="material-symbols-outlined text-[18px]">logout</span>
            </a>
        </div>
    </div>
</aside>

<script>
    function toggleVsysTheme() {
        const current = localStorage.getItem('vsys_theme') || 'auto';
        let next = 'dark';

        if (current === 'dark') next = 'light';
        else if (current === 'light') next = 'dark';
        else {
            next = document.documentElement.classList.contains('dark') ? 'light' : 'dark';
        }

        window.setVsysTheme(next);
    }

    function toggleVsysSidebar() {
        const sidebar = document.getElementById('mainSidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const isOpen = !sidebar.classList.contains('-translate-x-full');

        if (isOpen) {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        } else {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
        }
    }
</script>