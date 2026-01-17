<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/logistica/Logistics.php';
use Vsys\Modules\Logistica\Logistics;
$logistics = new Logistics();
$pending = $logistics->getOrdersForPreparation();
$transports = $logistics->getTransports();

// Map phases to colors and icons
$phases = [
    'En reserva' => ['color' => '#f59e0b', 'icon' => 'fas fa-clock', 'label' => 'En Reserva'],
    'En preparación' => ['color' => '#3b82f6', 'icon' => 'fas fa-tools', 'label' => 'En Preparación'],
    'Disponible' => ['color' => '#10b981', 'icon' => 'fas fa-check-circle', 'label' => 'Disponible'],
    'En su transporte' => ['color' => '#8b5cf6', 'icon' => 'fas fa-truck-loading', 'label' => 'En Transporte'],
    'Entregado' => ['color' => '#64748b', 'icon' => 'fas fa-flag-checkered', 'label' => 'Entregado']
];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Logística Premium - VS System</title>
    <link rel="stylesheet" href="css/style_premium.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .phase-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-transform: uppercase;
        }

        .process-flow {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .flow-step {
            flex: 1;
            height: 6px;
            border-radius: 3px;
            background: rgba(255, 255, 255, 0.1);
        }

        .flow-step.active {
            box-shadow: 0 0 10px currentColor;
        }

        .btn-action {
            background: #1e293b;
            border: 1px solid #334155;
            color: white;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-action:hover {
            background: #334155;
        }

        .cost-input {
            background: #0f172a;
            border: 1px solid #334155;
            color: white;
            padding: 5px;
            border-radius: 4px;
            width: 80px;
            font-size: 0.85rem;
        }

        .payment-warning {
            background: rgba(245, 158, 11, 0.1);
            border-left: 3px solid #f59e0b;
            padding: 10px;
            font-size: 0.85rem;
            color: #f59e0b;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <header
        style="background: #020617; border-bottom: 2px solid var(--accent-violet); display: flex; justify-content: space-between; align-items: center; padding: 0 20px;">
        <div style="display: flex; align-items: center; gap: 20px;">
            <img src="logo_display.php?v=1" alt="VS System" style="height: 50px;">
            <div style="color:white; font-weight:700; font-size:1.4rem;">GESTIÓN <span>LOGÍSTICA</span></div>
        </div>
    </header>

    <div class="dashboard-container">
        <?php include 'sidebar.php'; ?>
        <main class="content">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 30px;">
                <h1>Centro de Operaciones Logísticas</h1>
                <div class="stats-mini" style="display:flex; gap:20px;">
                    <div class="card" style="padding:10px 20px; text-align:center; min-width: 150px;">
                        <small style="color:#94a3b8">PENDIENTES</small>
                        <div style="font-size:1.5rem; font-weight:700; color: #f59e0b;"><?php echo count($pending); ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <table style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr style="text-align:left; color:#94a3b8; border-bottom:2px solid #334155;">
                            <th style="padding:15px;">REF. PEDIDO</th>
                            <th>FASE ACTUAL</th>
                            <th>ESTADO PAGO</th>
                            <th>DETALLES DESPACHO</th>
                            <th>ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending as $p):
                            $currPhase = $p['current_phase'] ?? 'En reserva';
                            $phaseData = $phases[$currPhase] ?? $phases['En reserva'];
                            $isPaid = ($p['payment_status'] === 'Pagado');
                            ?>
                            <tr style="border-bottom:1px solid #334155;" id="row-<?php echo $p['quote_number']; ?>">
                                <td style="padding:20px;">
                                    <div style="font-weight:700; color:white;"><?php echo $p['quote_number']; ?></div>
                                    <small style="color:#94a3b8;"><?php echo $p['client_name']; ?></small>
                                </td>
                                <td>
                                    <span class="phase-badge"
                                        style="background: <?php echo $phaseData['color']; ?>20; color: <?php echo $phaseData['color']; ?>">
                                        <i class="<?php echo $phaseData['icon']; ?>"></i> <?php echo $phaseData['label']; ?>
                                    </span>
                                    <div class="process-flow">
                                        <?php
                                        $pKeys = array_keys($phases);
                                        $found = false;
                                        foreach ($pKeys as $pk):
                                            $active = ($pk === $currPhase);
                                            $complete = !$found && !$active;
                                            if ($active)
                                                $found = true;
                                            ?>
                                            <div class="flow-step <?php echo $active ? 'active' : ''; ?>"
                                                style="background: <?php echo ($active || $complete) ? $phases[$pk]['color'] : 'rgba(255,255,255,0.1)'; ?>;">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($isPaid): ?>
                                        <span style="color:#10b981; font-weight:700;"><i class="fas fa-check-double"></i>
                                            PAGADO</span>
                                    <?php else: ?>
                                        <span style="color:#f59e0b; font-weight:700;"><i
                                                class="fas fa-exclamation-triangle"></i> PENDIENTE</span>
                                        <div class="payment-warning">Falta verificación de pago.</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display:flex; flex-direction:column; gap:8px;">
                                        <div style="display:flex; align-items:center; gap:10px;">
                                            <label style="font-size:0.75rem; color:#94a3b8; min-width:60px;">Bultos:</label>
                                            <input type="number" class="cost-input"
                                                id="qty-<?php echo $p['quote_number']; ?>" value="1">
                                        </div>
                                        <div style="display:flex; align-items:center; gap:10px;">
                                            <label style="font-size:0.75rem; color:#94a3b8; min-width:60px;">Flete
                                                USD:</label>
                                            <input type="number" class="cost-input"
                                                id="cost-<?php echo $p['quote_number']; ?>" value="0.00" step="0.01">
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="display:grid; grid-template-columns: 1fr; gap:10px;">
                                        <?php if (!$isPaid): ?>
                                            <button class="btn-action"
                                                onclick="subirPago('<?php echo $p['quote_number']; ?>')"><i
                                                    class="fas fa-file-upload"></i> Subir Pago</button>
                                        <?php elseif ($currPhase === 'En reserva'): ?>
                                            <button class="btn-action" style="color:#10b981;"
                                                onclick="avanzarFase('<?php echo $p['quote_number']; ?>', 'En preparación')"><i
                                                    class="fas fa-play"></i> Iniciar Prep.</button>
                                        <?php elseif ($currPhase === 'En preparación'): ?>
                                            <button class="btn-action" style="color:#3b82f6;"
                                                onclick="avanzarFase('<?php echo $p['quote_number']; ?>', 'Disponible')"><i
                                                    class="fas fa-box"></i> Marcar Listo</button>
                                        <?php elseif ($currPhase === 'Disponible'): ?>
                                            <button class="btn-remito btn-action"
                                                onclick="despachar('<?php echo $p['quote_number']; ?>')"><i
                                                    class="fas fa-truck-loading"></i> Despachar</button>
                                        <?php elseif ($currPhase === 'En su transporte'): ?>
                                            <button class="btn-action" style="color:#8b5cf6;"
                                                onclick="subirGuia('<?php echo $p['quote_number']; ?>')"><i
                                                    class="fas fa-file-image"></i> Subir Guía</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        async function avanzarFase(quoteNumber, phase) {
            if (!confirm(`¿Mover pedido ${quoteNumber} a fase ${phase}?`)) return;

            const formData = new FormData();
            formData.append('action', 'update_phase');
            formData.append('quote_number', quoteNumber);
            formData.append('phase', phase);

            const res = await fetch('ajax_logistics.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) location.reload();
            else alert(data.error);
        }

        async function despachar(quoteNumber) {
            const qty = document.getElementById('qty-' + quoteNumber).value;
            const cost = document.getElementById('cost-' + quoteNumber).value;

            // For now, prompt for transport since we don't have it in the row UI yet (add simplified picker)
            const transportId = prompt("Ingrese ID de Transportista (1-5):", "1");
            if (!transportId) return;

            const formData = new FormData();
            formData.append('action', 'despachar');
            formData.append('quote_number', quoteNumber);
            formData.append('transport_id', transportId);
            formData.append('packages_qty', qty);
            formData.append('freight_cost', cost);

            const res = await fetch('ajax_logistics.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                alert("Despacho registrado correctamente.");
                location.reload();
            } else {
                alert(data.error);
            }
        }

        function subirGuia(quoteNumber) {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/*';
            input.onchange = async (e) => {
                const file = e.target.files[0];
                if (!file) return;

                const formData = new FormData();
                formData.append('action', 'upload_guide');
                formData.append('quote_number', quoteNumber);
                formData.append('guide_photo', file);

                const res = await fetch('ajax_logistics.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    alert("Guía subida y pedido entregado.");
                    location.reload();
                } else {
                    alert("Error: " + data.error);
                }
            };
            input.click();
        }
    </script>
</body>

</html>