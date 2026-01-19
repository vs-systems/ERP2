<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Facturación - VS System</title>
    <link rel="stylesheet" href="css/style_premium.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <header
        style="background: #020617; border-bottom: 2px solid var(--accent-violet); display: flex; justify-content: space-between; align-items: center; padding: 0 20px;">
        <div style="display: flex; align-items: center; gap: 20px;">
            <img src="logo_display.php?v=2" alt="VS System" class="logo-large" style="height: 50px;">
            <div style="color:white; font-weight:700; font-size:1.4rem;">MÓDULO DE <span>FACTURACIÓN</span></div>
        </div>
    </header>
    <div class="dashboard-container">
        <?php include 'sidebar.php'; ?>
        <main class="content">
            <div class="card">
                <h2><i class="fas fa-file-invoice" style="color: var(--accent-violet);"></i> Gestión de Facturas</h2>
                <p style="color:#94a3b8; margin-bottom: 30px;">Próximamente: Integración de facturación electrónica y
                    seguimiento de cobros.</p>
                <div style="text-align:center; padding:100px; color:#1e293b;">
                    <i class="fas fa-tools" style="font-size:5rem; margin-bottom:20px;"></i>
                    <h3 style="color:#94a3b8;">Módulo en construcción</h3>
                </div>
            </div>
        </main>
    </div>
</body>

</html>