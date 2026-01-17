<?php
// sidebar.php - Componente de navegación premium agrupado
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

$menu = [
    ['id' => 'index', 'href' => 'index.php', 'icon' => 'fas fa-home', 'label' => 'DASHBOARD'],
    [
        'label' => 'VENTAS',
        'icon' => 'fas fa-shopping-cart',
        'items' => [
            ['id' => 'presupuestos', 'href' => 'presupuestos.php', 'icon' => 'fas fa-history', 'label' => 'Historial Ventas'],
            ['id' => 'cotizador', 'href' => 'cotizador.php', 'icon' => 'fas fa-file-invoice-dollar', 'label' => 'Generar Cotiz.'],
            ['id' => 'productos', 'href' => 'productos.php', 'icon' => 'fas fa-box-open', 'label' => 'Productos/Stock'],
        ]
    ],
    [
        'label' => 'CONTABILIDAD',
        'icon' => 'fas fa-calculator',
        'items' => [
            ['id' => 'compras', 'href' => 'compras.php', 'icon' => 'fas fa-cart-arrow-down', 'label' => 'Compras'],
            ['id' => 'facturacion', 'href' => 'facturacion.php', 'icon' => 'fas fa-file-invoice', 'label' => 'Facturación'],
            ['id' => 'analisis', 'href' => 'analisis.php', 'icon' => 'fas fa-chart-line', 'label' => 'Análisis OP.'],
        ]
    ],
    ['id' => 'crm', 'href' => 'crm.php', 'icon' => 'fas fa-handshake', 'label' => 'CRM'],
    ['id' => 'logistica', 'href' => 'logistica.php', 'icon' => 'fas fa-truck', 'label' => 'LOGÍSTICA'],
    ['id' => 'calendar', 'href' => 'https://calendar.google.com/calendar/u/0/r?cid=dmVjaW5vc2VndXJvMEBnbWFpbC5jb20', 'icon' => 'fas fa-calendar-alt', 'label' => 'CALENDARIO', 'external' => true],
    ['id' => 'clientes', 'href' => 'clientes.php', 'icon' => 'fas fa-users', 'label' => 'CLIENTES'],
    ['id' => 'proveedores', 'href' => 'proveedores.php', 'icon' => 'fas fa-truck-loading', 'label' => 'PROVEEDORES'],
    ['id' => 'configuration', 'href' => 'configuration.php', 'icon' => 'fas fa-cogs', 'label' => 'CONFIGURACIÓN'],
];

// Determine if a group should be expanded
function isGroupActive($group, $currentPage)
{
    if (!isset($group['items']))
        return false;
    foreach ($group['items'] as $item) {
        if ($item['id'] === $currentPage)
            return true;
    }
    return false;
}
?>
<style>
    .nav-group-label {
        color: #64748b;
        font-size: 0.75rem;
        font-weight: 700;
        padding: 20px 20px 10px;
        letter-spacing: 1px;
    }

    .nav-submenu {
        padding-left: 15px;
        background: rgba(0, 0, 0, 0.1);
    }

    .sidebar .nav-link {
        padding: 12px 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 0.9rem;
    }

    .sidebar .nav-link i {
        width: 20px;
        text-align: center;
    }
</style>

<nav class="sidebar">
    <div
        style="padding: 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.05); margin-bottom: 10px;">
        <img src="logo_display.php?v=2" alt="VS System"
            style="height: 60px; filter: drop-shadow(0 0 5px rgba(139, 92, 246, 0.3));">
    </div>

    <?php foreach ($menu as $section): ?>
        <?php if (isset($section['items'])): ?>
            <div class="nav-group-label"><?php echo $section['label']; ?></div>
            <?php foreach ($section['items'] as $item): ?>
                <a href="<?php echo $item['href']; ?>"
                    class="nav-link <?php echo ($currentPage === $item['id']) ? 'active' : ''; ?>">
                    <i class="<?php echo $item['icon']; ?>"></i> <?php echo $item['label']; ?>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <a href="<?php echo $section['href']; ?>"
                class="nav-link <?php echo ($currentPage === $section['id']) ? 'active' : ''; ?>">
                <i class="<?php echo $section['icon']; ?>"></i> <?php echo $section['label']; ?>
            </a>
        <?php endif; ?>
    <?php endforeach; ?>

    <a href="https://calendar.google.com/calendar/u/0/r?cid=dmVjaW5vc2VndXJvMEBnbWFpbC5jb20" target="_blank"
        class="nav-link" style="color: #6366f1; font-weight: 700;">
        <i class="fas fa-calendar-alt"></i> MI CALENDARIO
    </a>
    <a href="catalogo_publico.php" class="nav-link" target="_blank"
        style="color: #10b981; font-weight: 700; border-top: 1px solid rgba(255,255,255,0.1); margin-top: 5px; padding-top: 10px;">
        <i class="fas fa-external-link-alt"></i> CATÁLOGO PÚBLICO
    </a>
</nav>
PHP;