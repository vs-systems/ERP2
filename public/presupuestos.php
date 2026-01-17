<?php
require_once 'auth_check.php';
/**
 * VS System ERP - Quotation Management (History)
 */
require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/lib/Database.php';
require_once __DIR__ . '/../src/modules/cotizador/Cotizador.php';

use Vsys\Modules\Cotizador\Cotizador;

$cot = new Cotizador();
$quotes = $cot->getAllQuotations(100);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Historial de Presupuestos - VS System ERP</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style_premium.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-draft {
            background: #334155;
            color: #cbd5e1;
        }

        .status-sent {
            background: #1e3a8a;
            color: #93c5fd;
        }

        .status-accepted {
            background: #064e3b;
            color: #6ee7b7;
        }
    </style>
</head>

<body>
    <header
        style="background: #020617; border-bottom: 2px solid var(--accent-violet); display: flex; justify-content: space-between; align-items: center; padding: 0 20px;">
        <div style="display: flex; align-items: center; gap: 20px;">
            <img src="logo_display.php?v=1" alt="VS System" class="logo-large" style="height: 50px; width: auto;">
            <div
                style="color: #fff; font-family: 'Inter', sans-serif; font-weight: 700; font-size: 1.4rem; letter-spacing: 1px; text-shadow: 0 0 10px rgba(139, 92, 246, 0.4);">
                Vecino Seguro <span
                    style="background: linear-gradient(90deg, #8b5cf6, #d946ef); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Sistemas</span>
                by Javier Gozzi - 2026
            </div>
        </div>
        <div class="header-right" style="color: #cbd5e1;">
            <span class="user-badge"><i class="fas fa-user-circle"></i> Admin</span>
        </div>
    </header>

    <div class="dashboard-container">
        <nav class="sidebar">
            <a href="index.php" class="nav-link"><i class="fas fa-home"></i> DASHBOARD</a>
            <a href="analisis.php" class="nav-link"><i class="fas fa-chart-line"></i> AN&Aacute;LISIS OP.</a>
            <a href="productos.php" class="nav-link"><i class="fas fa-box-open"></i> PRODUCTOS</a>
            <a href="presupuestos.php" class="nav-link active"><i class="fas fa-history"></i> PRESUPUESTOS</a>
            <a href="clientes.php" class="nav-link"><i class="fas fa-users"></i> CLIENTES</a>
            <a href="proveedores.php" class="nav-link"><i class="fas fa-truck-loading"></i> PROVEEDORES</a>
            <a href="compras.php" class="nav-link"><i class="fas fa-cart-arrow-down"></i> COMPRAS</a>
            <a href="importar.php" class="nav-link"><i class="fas fa-upload"></i> IMPORTAR</a>
            <a href="crm.php" class="nav-link"><i class="fas fa-handshake"></i> CRM</a>
            <a href="cotizador.php" class="nav-link"><i class="fas fa-file-invoice-dollar"></i> COTIZADOR</a>
        </nav>

        <main class="content">
            <div class="card" style="display: flex; justify-content: space-between; align-items: center;">
                <h1>Historial de Cotizaciones</h1>
                <a href="cotizador.php" class="btn-primary" style="text-decoration: none;"><i class="fas fa-plus"></i>
                    Nueva Cotización</a>
            </div>

            <div class="card">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Número</th>
                                <th>Cliente</th>
                                <th>Total USD</th>
                                <th>Total ARS</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($quotes as $q): ?>
                                <tr>
                                    <td>
                                        <?php echo date('d/m/Y H:i', strtotime($q['created_at'])); ?>
                                    </td>
                                    <td><strong>
                                            <?php echo $q['quote_number']; ?>
                                        </strong></td>
                                    <td>
                                        <?php echo $q['client_name']; ?>
                                    </td>
                                    <td>$
                                        <?php echo number_format($q['total_usd'], 2); ?>
                                    </td>
                                    <td>$
                                        <?php echo number_format($q['total_ars'], 2, ',', '.'); ?>
                                    </td>
                                    <td><span class="status-badge status-draft">Borrador</span></td>
                                    <td>
                                        <div style="display: flex; gap: 10px;">
                                            <a href="javascript:void(0)"
                                                onclick="openQuote(<?php echo $q['id']; ?>, <?php echo $q['client_id']; ?>, '<?php echo $q['quote_number']; ?>')"
                                                class="btn-secondary" title="Imprimir / PDF">
                                                <i class="fas fa-print"></i>
                                            </a>
                                            <button class="btn-secondary"
                                                onclick="toggleStatus(<?php echo $q['id']; ?>, 'quotation', 'is_confirmed', <?php echo $q['is_confirmed'] ? 0 : 1; ?>)"
                                                title="<?php echo $q['is_confirmed'] ? 'Desmarcar Confirmado' : 'Marcar Confirmado'; ?>"
                                                style="color: <?php echo $q['is_confirmed'] ? '#10b981' : '#64748b'; ?>">
                                                <i class="fas fa-check-double"></i>
                                            </button>
                                            <button class="btn-secondary"
                                                onclick="toggleStatus(<?php echo $q['id']; ?>, 'quotation', 'payment_status', '<?php echo $q['payment_status'] === 'Pagado' ? 'Pendiente' : 'Pagado'; ?>')"
                                                title="<?php echo $q['payment_status'] === 'Pagado' ? 'Marcar como Pendiente' : 'Marcar como Pagado'; ?>"
                                                style="color: <?php echo $q['payment_status'] === 'Pagado' ? '#8b5cf6' : '#64748b'; ?>">
                                                <i class="fas fa-hand-holding-usd"></i>
                                            </button>
                                            <button class="btn-secondary"
                                                onclick="alert('Funcionalidad de edición en desarrollo')"
                                                title="Versionar / Editar">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                            <button class="btn-secondary" onclick="sendEmail(<?php echo $q['id']; ?>)"
                                                title="Enviar por Email">
                                                <i class="fas fa-envelope"></i>
                                            </button>
                                            <a href="https://calendar.google.com/calendar/render?action=TEMPLATE&text=Vencimiento+Presupuesto:+<?php echo urlencode($q['quote_number']); ?>&details=Cliente:+<?php echo urlencode($q['client_name']); ?>+-+Total:+<?php echo urlencode($q['total_usd']); ?>+USD+-+Ver:+http://<?php echo $_SERVER['HTTP_HOST']; ?>/Vsys_ERP/public/imprimir_cotizacion.php?id=<?php echo $q['id']; ?>"
                                                target="_blank" class="btn-secondary" title="Agendar en Calendar">
                                                <i class="fas fa-calendar-plus"></i>
                                            </a>
                                            <button class="btn-secondary" style="background: rgba(239, 68, 68, 0.2);"
                                                onclick="deleteQuote(<?php echo $q['id']; ?>, '<?php echo $q['quote_number']; ?>')"
                                                title="Eliminar Prueba">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        function sendEmail(id) {
            if (!confirm('¿Desea enviar este presupuesto por email al cliente?')) return;
            const btn = event.currentTarget;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            fetch('ajax_send_email.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ type: 'quotation', id: id })
            })
                .then(res => res.json())
                .then(res => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-envelope"></i>';
                    if (res.success) alert('Email enviado correctamente.');
                    else alert('Error: ' + res.error);
                });
        }

        function openQuote(id, entityId, quoteNo) {
            // Log to CRM
            fetch('ajax_log_crm.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    entity_id: entityId,
                    type: 'Email/PDF',
                    description: `Re-impresión / Visualización de presupuesto ${quoteNo}`
                })
            });
            window.open('imprimir_cotizacion.php?id=' + id, '_blank');
        }

        function deleteQuote(id, number) {
            if (!confirm(`¿Está seguro de eliminar el presupuesto ${number}? Esta acción no se puede deshacer.`)) return;

            fetch('ajax_delete_quotation.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + res.error);
                    }
                });
        }

        function toggleStatus(id, type, field, val) {
            fetch('ajax_update_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id, type: type, field: field, value: val })
            })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + res.error);
                    }
                });
        }
    </script>
</body>

</html>