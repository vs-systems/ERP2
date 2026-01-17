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
        <nav class="sidebar">
            <a href="index.php" class="nav-link"><i class="fas fa-home"></i> DASHBOARD</a>
            <a href="productos.php" class="nav-link"><i class="fas fa-box-open"></i> PRODUCTOS</a>
            <a href="presupuestos.php" class="nav-link"><i class="fas fa-history"></i> PRESUPUESTOS</a>
            <a href="clientes.php" class="nav-link"><i class="fas fa-users"></i> CLIENTES</a>
            <a href="proveedores.php" class="nav-link"><i class="fas fa-truck-loading"></i> PROVEEDORES</a>
            <a href="compras.php" class="nav-link"><i class="fas fa-cart-arrow-down"></i> COMPRAS</a>
            <a href="crm.php" class="nav-link"><i class="fas fa-handshake"></i> CRM</a>
            <!-- New Config Link Active -->
            <a href="configuration.php" class="nav-link active"><i class="fas fa-cogs"></i> CONFIGURACIÓN</a>
        </nav>

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

                    <!-- Config 4: Database Actions -->
                    <a href="update_images_bigdipper.php" class="config-card">
                        <i class="fas fa-images config-icon" style="color: #3b82f6;"></i>
                        <h3>Imágenes</h3>
                        <p style="font-size: 0.9rem; color: #cbd5e1; margin-top: 10px;">
                            Actualizar vínculos con BigDipper.
                        </p>
                    </a>
                </div>
            </div>
        </main>
    </div>
</body>

</html>