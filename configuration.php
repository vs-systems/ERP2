<?php
require_once 'auth_check.php';
/**
 * Centro de Configuración - VS System ERP
 */
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Configuración - VS System</title>
    <link rel="stylesheet" href="css/style_premium.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .config-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .config-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: white;
            display: block;
        }

        .config-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--accent-violet);
            box-shadow: 0 10px 30px -10px rgba(139, 92, 246, 0.3);
        }

        .config-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--accent-violet);
        }
    </style>
</head>

<body>
    <header
        style="background: #020617; border-bottom: 2px solid var(--accent-violet); display: flex; justify-content: space-between; align-items: center; padding: 0 20px;">
        <div style="display: flex; align-items: center; gap: 20px;">
            <img src="logo_display.php?v=1" alt="VS System" class="logo-large" style="height: 50px; width: auto;">
            <div style="color: #fff; font-family: 'Inter', sans-serif; font-weight: 700; font-size: 1.4rem;">
                Centro de <span style="color: var(--accent-violet);">Configuración</span>
            </div>
        </div>
        <div class="header-right" style="color: #cbd5e1;">
            <span class="user-badge"><i class="fas fa-user-circle"></i> Admin</span>
        </div>
    </header>

    <div class="dashboard-container">
        <?php include 'sidebar.php'; ?>

        <main class="content">
            <div class="card">
                <h2><i class="fas fa-sliders-h"></i> Panel de Control del Sistema</h2>
                <p style="color: #94a3b8;">Administre los datos maestros, precios y parámetros del sistema desde aquí.
                </p>

                <div class="config-grid">
                    <!-- Config 1: Price Lists -->
                    <a href="config_precios.php" class="config-card">
                        <i class="fas fa-tags config-icon"></i>
                        <h3>Listas de Precios</h3>
                        <p style="font-size: 0.9rem; color: #cbd5e1; margin-top: 10px;">
                            Defina márgenes de ganancia para Gremio, Web y MercadoLibre.
                        </p>
                    </a>

                    <!-- Config 2: Add Product -->
                    <a href="config_productos_add.php" class="config-card">
                        <i class="fas fa-plus-circle config-icon" style="color: #10b981;"></i>
                        <h3>Carga Manual</h3>
                        <p style="font-size: 0.9rem; color: #cbd5e1; margin-top: 10px;">
                            Añada nuevos productos individualmente al catálogo.
                        </p>
                    </a>

                    <!-- Config 3: Import -->
                    <a href="importar.php" class="config-card">
                        <i class="fas fa-file-csv config-icon" style="color: #f59e0b;"></i>
                        <h3>Importar Datos</h3>
                        <p style="font-size: 0.9rem; color: #cbd5e1; margin-top: 10px;">
                            Carga masiva de Productos, Clientes o Proveedores desde CSV.
                        </p>
                    </a>

                    <!-- Config 4: Transports ABM -->
                    <a href="config_transports.php" class="config-card">
                        <i class="fas fa-truck config-icon" style="color: #3b82f6;"></i>
                        <h3>Transportes</h3>
                        <p style="font-size: 0.9rem; color: #cbd5e1; margin-top: 10px;">Gestione las empresas de
                            transporte asociadas.</p>
                    </a>

                    <!-- Config 5: Clientes ABM -->
                    <a href="config_entities.php?type=client" class="config-card">
                        <i class="fas fa-user-plus config-icon" style="color: #a855f7;"></i>
                        <h3>Nuevo Cliente</h3>
                        <p style="font-size: 0.9rem; color: #cbd5e1; margin-top: 10px;">Alta y edición de datos maestros
                            de clientes.</p>
                    </a>

                    <!-- Config 6: Proveedores ABM -->
                    <a href="config_entities.php?type=supplier" class="config-card">
                        <i class="fas fa-truck-monster config-icon" style="color: #f43f5e;"></i>
                        <h3>Nuevo Proveedor</h3>
                        <p style="font-size: 0.9rem; color: #cbd5e1; margin-top: 10px;">Gestión de proveedores y
                            condiciones de compra.</p>
                    </a>
                </div>
            </div>
        </main>
    </div>
</body>

</html>