<?php
// sidebar.php - Componente de navegación unificado
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$navItems = [
    ['id' => 'index', 'href' => 'index.php', 'icon' => 'fas fa-home', 'label' => 'DASHBOARD'],
    ['id' => 'analisis', 'href' => 'analisis.php', 'icon' => 'fas fa-chart-line', 'label' => 'ANÁLISIS OP.'],
    ['id' => 'productos', 'href' => 'productos.php', 'icon' => 'fas fa-box-open', 'label' => 'PRODUCTOS'],
    ['id' => 'presupuestos', 'href' => 'presupuestos.php', 'icon' => 'fas fa-history', 'label' => 'PRESUPUESTOS'],
    ['id' => 'clientes', 'href' => 'clientes.php', 'icon' => 'fas fa-users', 'label' => 'CLIENTES'],
    ['id' => 'proveedores', 'href' => 'proveedores.php', 'icon' => 'fas fa-truck-loading', 'label' => 'PROVEEDORES'],
    ['id' => 'compras', 'href' => 'compras.php', 'icon' => 'fas fa-cart-arrow-down', 'label' => 'COMPRAS'],
    ['id' => 'crm', 'href' => 'crm.php', 'icon' => 'fas fa-handshake', 'label' => 'CRM'],
    ['id' => 'cotizador', 'href' => 'cotizador.php', 'icon' => 'fas fa-file-invoice-dollar', 'label' => 'COTIZADOR'],
    ['id' => 'logistica', 'href' => 'logistica.php', 'icon' => 'fas fa-truck', 'label' => 'LOGÍSTICA'],
    ['id' => 'facturacion', 'href' => 'facturacion.php', 'icon' => 'fas fa-file-invoice', 'label' => 'FACTURACIÓN'],
    ['id' => 'configuration', 'href' => 'configuration.php', 'icon' => 'fas fa-cogs', 'label' => 'CONFIGURACIÓN'],
];
?>
<nav class="sidebar">
    <?php foreach ($navItems as $item): ?>
        <a href="<?php echo $item['href']; ?>"
            class="nav-link <?php echo ($currentPage === $item['id']) ? 'active' : ''; ?>">
            <i class="<?php echo $item['icon']; ?>"></i>
            <?php echo $item['label']; ?>
        </a>
    <?php endforeach; ?>
    <a href="catalogo_publico.php" class="nav-link" target="_blank"
        style="color: #25d366; font-weight: 700; border-top: 1px solid rgba(255,255,255,0.1); margin-top: 10px; padding-top: 15px;">
        <i class="fas fa-external-link-alt"></i> VER CATÁLOGO
    </a>
</nav>