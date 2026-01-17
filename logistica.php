<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/logistica/Logistics.php';
use Vsys\Modules\Logistica\Logistics;
$logistics = new Logistics();
$pending = $logistics->getOrdersForPreparation();
$transports = $logistics->getTransports();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Logística - VS System</title>
    <link rel="stylesheet" href="css/style_premium.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .status-pill {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            background: #10b981;
            color: white;
        }

        .btn-remito {
            background: var(--accent-violet);
            border: none;
            color: white;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-remito:hover {
            background: #7c3aed;
            transform: scale(1.02);
        }

        .btn-remito:disabled {
            background: #475569;
            cursor: not-allowed;
        }

        .card h2 {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 25px;
            border-bottom: 1px solid #334155;
            padding-bottom: 15px;
        }
    </style>
</head>

<body>
    <header
        style="background: #020617; border-bottom: 2px solid var(--accent-violet); display: flex; justify-content: space-between; align-items: center; padding: 0 20px;">
        <div style="display: flex; align-items: center; gap: 20px;">
            <img src="logo_display.php?v=1" alt="VS System" style="height: 50px;">
            <div style="color:white; font-weight:700; font-size:1.4rem;">CENTRO DE <span>LOGÍSTICA</span></div>
        </div>
    </header>
    <div class="dashboard-container">
        <nav class="sidebar">
            <a href="index.php" class="nav-link"><i class="fas fa-home"></i> DASHBOARD</a>
            <a href="analisis.php" class="nav-link"><i class="fas fa-chart-line"></i> ANÁLISIS OP.</a>
            <a href="productos.php" class="nav-link"><i class="fas fa-box-open"></i> PRODUCTOS</a>
            <a href="presupuestos.php" class="nav-link"><i class="fas fa-history"></i> PRESUPUESTOS</a>
            <a href="clientes.php" class="nav-link"><i class="fas fa-users"></i> CLIENTES</a>
            <a href="proveedores.php" class="nav-link"><i class="fas fa-truck-loading"></i> PROVEEDORES</a>
            <a href="compras.php" class="nav-link"><i class="fas fa-cart-arrow-down"></i> COMPRAS</a>
            <a href="crm.php" class="nav-link"><i class="fas fa-handshake"></i> CRM</a>
            <a href="cotizador.php" class="nav-link"><i class="fas fa-file-invoice-dollar"></i> COTIZADOR</a>
            <a href="logistica.php" class="nav-link active"><i class="fas fa-truck"></i> LOGÍSTICA</a>
            <a href="facturacion.php" class="nav-link"><i class="fas fa-file-invoice"></i> FACTURACIÓN</a>
            <a href="configuration.php" class="nav-link"><i class="fas fa-cogs"></i> CONFIGURACIÓN</a>
            <a href="catalogo_publico.php" class="nav-link" target="_blank" style="color: #25d366; font-weight: 700;"><i
                    class="fas fa-external-link-alt"></i> VER CATÁLOGO</a>
        </nav>
        <main class="content">
            <div class="card">
                <h2><i class="fas fa-shipping-fast" style="color: var(--accent-violet);"></i> Pendientes de Despacho
                </h2>
                <p style="color:#94a3b8; margin-bottom: 30px;">Pedidos con pago verificado o autorización técnica,
                    listos para la emisión de remitos.</p>
                <table style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr style="text-align:left; color:#94a3b8; border-bottom:2px solid #334155;">
                            <th style="padding:15px;">REF. PEDIDO</th>
                            <th>CLIENTE</th>
                            <th>ESTADO PAGO</th>
                            <th style="width: 250px;">TRANSPORTISTA</th>
                            <th>ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending as $p): ?>
                            <tr style="border-bottom:1px solid #334155; transition: background 0.2s;"
                                id="row-<?php echo $p['quote_number']; ?>">
                                <td style="padding:20px; font-weight:700;">
                                    <?php echo $p['quote_number']; ?>
                                </td>
                                <td>
                                    <?php echo $p['client_name']; ?>
                                </td>
                                <td><span class="status-pill">
                                        <?php echo ($p['payment_status'] === 'Paid') ? 'PAGADO' : 'AUTORIZADO'; ?>
                                    </span></td>
                                <td>
                                    <select id="transport-<?php echo $p['quote_number']; ?>"
                                        style="width:100%; background:#0f172a; color:white; border:1px solid #334155; padding:8px; border-radius:6px; font-family:inherit;">
                                        <?php foreach ($transports as $t): ?>
                                            <option value="<?php echo $t['id']; ?>">
                                                <?php echo $t['name']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                        <?php if (empty($transports)): ?>
                                            <option value="">Configurar transportes...</option>
                                        <?php endif; ?>
                                    </select>
                                </td>
                                <td>
                                    <button onclick="generarRemito('<?php echo $p['quote_number']; ?>')" class="btn-remito"
                                        id="btn-<?php echo $p['quote_number']; ?>" <?php echo empty($transports) ? 'disabled' : ''; ?>>
                                        <i class="fas fa-file-invoice"></i> Generar Remito
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($pending)): ?>
                            <tr>
                                <td colspan="5" style="text-align:center; padding:60px; color:#94a3b8;"><i
                                        class="fas fa-check-circle"
                                        style="font-size:3rem; margin-bottom:15px; display:block; color:#1e293b;"></i> No
                                    hay pedidos pendientes de despacho.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        async function generarRemito(quoteNumber) {
            const transportId = document.getElementById('transport-' + quoteNumber).value;
            const btn = document.getElementById('btn-' + quoteNumber);

            if (!transportId) { alert('Seleccione un transportista primero.'); return; }
            if (!confirm('¿Confirmar despacho del pedido ' + quoteNumber + '?')) return;

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-sync fa-spin"></i> Emitiendo...';

            const formData = new FormData();
            formData.append('action', 'create_remito');
            formData.append('quote_number', quoteNumber);
            formData.append('transport_id', transportId);

            try {
                const response = await fetch('ajax_logistics.php', { method: 'POST', body: formData });
                const result = await response.json();

                if (result.success) {
                    alert('Remito Emitido: ' + result.remito_number);
                    const row = document.getElementById('row-' + quoteNumber);
                    row.style.background = 'rgba(16, 185, 129, 0.05)';
                    row.style.opacity = '0.5';
                    btn.innerHTML = '<i class="fas fa-check"></i> Despachado';
                    btn.style.background = '#10b981';
                } else {
                    alert('Error: ' + result.error);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-file-invoice"></i> Generar Remito';
                }
            } catch (error) {
                console.error(error);
                alert('Fallo de red al intentar generar el remito.');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-file-invoice"></i> Generar Remito';
            }
        }
    </script>
</body>

</html>
PHP;