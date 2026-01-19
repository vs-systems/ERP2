<?php
// sidebar.php - Rediseño basado en Stitch (Tailwind + Material Symbols) con Soporte de Temas, Permisos y Menús Colapsables
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/lib/User.php';

$currentPage = basename($_SERVER['PHP_SELF'], '.php');

if (!isset($userAuth)) {
    $userAuth = new \Vsys\Lib\User();
}

$userName = $_SESSION['username'] ?? 'Usuario';
$userRole = $_SESSION['role'] ?? 'Invitado';

$menu = [
    ['id' => 'dashboard', 'href' => 'dashboard.php', 'icon' => 'dashboard', 'label' => 'Dashboard'],
    [
        'id' => 'group_ventas',
        'label' => 'Ventas',
        'icon' => 'receipt_long',
        'items' => [
            ['id' => 'presupuestos', 'href' => 'presupuestos.php', 'icon' => 'history', 'label' => 'Historial', 'perm' => 'quotes'],
            ['id' => 'cotizador', 'href' => 'cotizador.php', 'icon' => 'add_shopping_cart', 'label' => 'Generar Cotiz.', 'perm' => 'quotes'],
            ['id' => 'productos', 'href' => 'productos.php', 'icon' => 'inventory_2', 'label' => 'Productos/Stock', 'perm' => 'catalog'],
            ['id' => 'imprimir_lista_precios', 'href' => 'imprimir_lista_precios.php', 'icon' => 'lists', 'label' => 'Lista de Precios', 'perm' => 'catalog'],
            ['id' => 'config_productos_masivos', 'href' => 'config_productos_masivos.php', 'icon' => 'auto_fix_high', 'label' => 'Acciones Masivas', 'perm' => 'catalog'],
        ]
    ],
    [
        'id' => 'group_contabilidad',
        'label' => 'Contabilidad',
        'icon' => 'payments',
        'items' => [
            ['id' => 'compras', 'href' => 'compras.php', 'icon' => 'shopping_cart_checkout', 'label' => 'Compras', 'perm' => 'purchases'],
            ['id' => 'facturacion', 'href' => 'facturacion.php', 'icon' => 'description', 'label' => 'Facturaci&oacute;n', 'perm' => 'sales'],
            ['id' => 'analizador', 'href' => 'analizador.php', 'icon' => 'query_stats', 'label' => 'An&aacute;lisis OP.', 'perm' => 'sales'],
        ]
    ],
    ['id' => 'crm', 'href' => 'crm.php', 'icon' => 'group', 'label' => 'CRM', 'perm' => 'clients'],
    ['id' => 'logistica', 'href' => 'logistica.php', 'icon' => 'local_shipping', 'label' => 'Logística', 'perm' => 'logistics'],
    ['id' => 'clientes', 'href' => 'clientes.php', 'icon' => 'badge', 'label' => 'Clientes', 'perm' => 'clients'],
    ['id' => 'proveedores', 'href' => 'proveedores.php', 'icon' => 'factory', 'label' => 'Proveedores', 'perm' => 'suppliers'],
    [
        'id' => 'group_config',
        'label' => 'Configuraci&oacute;n',
        'icon' => 'settings',
        'perm' => 'admin',
        'items' => [
            ['id' => 'configuration', 'href' => 'configuration.php', 'icon' => 'tune', 'label' => 'General'],
            ['id' => 'usuarios', 'href' => 'usuarios.php', 'icon' => 'admin_panel_settings', 'label' => 'Usuarios'],
            ['id' => 'config_precios', 'href' => 'config_precios.php', 'icon' => 'universal_currency_alt', 'label' => 'Precios'],
            ['id' => 'config_transports', 'href' => 'config_transports.php', 'icon' => 'local_shipping', 'label' => 'Transportes'],
            ['id' => 'importar', 'href' => 'importar.php', 'icon' => 'upload_file', 'label' => 'Carga Inicial CSV'],
        ]
    ],
];

// Cargar preferencia del sistema
$settingsFile = __DIR__ . '/src/config/settings.json';
$sysSettings = ['default_theme' => 'auto'];
if (file_exists($settingsFile)) {
    $sysSettings = json_decode(file_get_contents($settingsFile), true);
}
$defaultTheme = $sysSettings['default_theme'] ?? 'auto';
?>

<style>
    .menu-group-content {
        transition: all 0.3s ease-in-out;
        max-height: 0;
        overflow: hidden;
    }

    .menu-group-content.expanded {
        max-height: 500px;
    }

    .chevron-icon {
        transition: transform 0.3s ease;
    }

    .expanded .chevron-icon {
        transform: rotate(180deg);
    }
</style>

<!-- Theme Handler -->
<script>
    window.vsys_default_theme = "<?php echo $defaultTheme; ?>";
</script>
<script src="js/theme_handler.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
    rel="stylesheet" />

<aside
    class="hidden md:flex flex-col w-64 h-full bg-[#101822] border-r border-[#233348] flex-shrink-0 overflow-y-auto transition-colors duration-300 dark:bg-[#101822] bg-white border-slate-200 dark:border-[#233348]">
    <div class="p-6 flex items-center gap-3">
        <div class="bg-[#136dec]/20 p-2 rounded-lg text-[#136dec] flex items-center justify-center">
            <span class="material-symbols-outlined text-2xl">shield</span>
        </div>
        <div>
            <h1 class="dark:text-white text-slate-800 text-lg font-bold leading-tight">VS System</h1>
            <p class="text-slate-400 text-[10px] font-normal uppercase tracking-wider">ERP & Seguridad</p>
        </div>
    </div>

    <nav class="flex-1 px-4 py-2 space-y-1">
        <?php foreach ($menu as $section): ?>
            <?php
            if (isset($section['perm']) && !$userAuth->hasPermission($section['perm']))
                continue;
            if (isset($section['role']) && !$userAuth->hasRole($section['role']))
                continue;

            if (isset($section['items'])):
                $visibleItems = array_filter($section['items'], function ($item) use ($userAuth) {
                    return !isset($item['perm']) || $userAuth->hasPermission($item['perm']);
                });
                if (empty($visibleItems))
                    continue;

                $isAnyChildActive = false;
                foreach ($visibleItems as $item) {
                    if ($currentPage === $item['id']) {
                        $isAnyChildActive = true;
                        break;
                    }
                }
                ?>
                <div class="menu-group pt-2">
                    <button onclick="toggleMenuGroup('<?php echo $section['id']; ?>')"
                        class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-slate-400 hover:text-[#136dec] hover:bg-slate-100 dark:hover:bg-[#233348] transition-all group">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-[20px]"><?php echo $section['icon']; ?></span>
                            <span
                                class="text-sm font-bold uppercase tracking-widest text-[10px]"><?php echo $section['label']; ?></span>
                        </div>
                        <span class="material-symbols-outlined text-[18px] chevron-icon"
                            id="chevron-<?php echo $section['id']; ?>">expand_more</span>
                    </button>

                    <div id="content-<?php echo $section['id']; ?>"
                        class="menu-group-content space-y-1 mt-1 ml-4 border-l border-slate-200 dark:border-[#233348] pl-2 <?php echo $isAnyChildActive ? 'expanded' : ''; ?>">
                        <?php foreach ($visibleItems as $item): ?>
                            <a href="<?php echo $item['href']; ?>"
                                class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?php echo ($currentPage === $item['id']) ? 'bg-[#136dec] text-white shadow-lg shadow-[#136dec]/20' : 'text-slate-400 hover:text-[#136dec] hover:bg-slate-100 dark:hover:bg-[#233348] dark:hover:text-white'; ?>">
                                <span class="material-symbols-outlined text-[18px]"><?php echo $item['icon']; ?></span>
                                <span class="text-sm font-medium"><?php echo $item['label']; ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?php echo $section['href']; ?>"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 <?php echo ($currentPage === $section['id']) ? 'bg-[#136dec] text-white shadow-lg shadow-[#136dec]/20' : 'text-slate-400 hover:text-[#136dec] hover:bg-slate-100 dark:hover:bg-[#233348] dark:hover:text-white'; ?>">
                    <span class="material-symbols-outlined text-[20px]"><?php echo $section['icon']; ?></span>
                    <span class="text-sm font-medium"><?php echo $section['label']; ?></span>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>

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
    function toggleMenuGroup(groupId) {
        const content = document.getElementById('content-' + groupId);
        const chevron = document.getElementById('chevron-' + groupId);

        content.classList.toggle('expanded');
        chevron.classList.toggle('rotate-180');

        // Guardar estado en localStorage
        const isExpanded = content.classList.contains('expanded');
        localStorage.setItem('menu_' + groupId, isExpanded ? '1' : '0');
    }

    // Restaurar estado al cargar
    document.addEventListener('DOMContentLoaded', () => {
        const groups = ['group_ventas', 'group_contabilidad', 'group_config'];
        groups.forEach(id => {
            const state = localStorage.getItem('menu_' + id);
            const content = document.getElementById('content-' + id);
            const chevron = document.getElementById('chevron-' + id);

            // Si el estado es 1 o si hay un hijo activo dentro
            if (state === '1' || content.classList.contains('expanded')) {
                content.classList.add('expanded');
                chevron.classList.add('rotate-180');
            }
        });
    });

    function toggleVsysTheme() {
        const current = localStorage.getItem('vsys_theme') || 'auto';
        let next = 'dark';
        if (current === 'dark') next = 'light';
        else if (current === 'light') next = 'dark';
        else next = document.documentElement.classList.contains('dark') ? 'light' : 'dark';
        window.setVsysTheme(next);
    }
</script>