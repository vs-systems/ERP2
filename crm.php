<?php
require_once 'auth_check.php';
/**
 * VS System ERP - CRM & Sales Pipeline
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/crm/CRM.php';

use Vsys\Modules\CRM\CRM;

$crm = new CRM();
$today = date('Y-m-d');
$stats = $crm->getStats($today);
$interactions = $crm->getRecentInteractions(20);
$funnel = $crm->getFunnelStats();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>CRM & Pipeline - VS System ERP</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style_premium.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        <?php include 'sidebar.php'; ?>

        <main class="content">

            <div class="grid-3">
                <div class="card">
                    <h3>Presupuestos Activos</h3>
                    <div class="metric" style="color: var(--accent-blue);">
                        <?php echo $stats['active_quotes']; ?>
                    </div>
                </div>
                <div class="card">
                    <h3>Pedidos Cerrados (Hoy)</h3>
                    <div class="metric" style="color: #10b981;">
                        <?php echo $stats['orders_today']; ?>
                    </div>
                </div>
                <div class="card">
                    <h3>Eficiencia de Cierre</h3>
                    <div class="metric" style="color: #f59e0b;">
                        <?php echo $stats['efficiency']; ?>%
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 2rem;">
                <div class="card">
                    <h3><i class="fas fa-history"></i> Historial de Actividad Reciente</h3>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Fecha/Hora</th>
                                    <th>Cliente</th>
                                    <th>Actividad</th>
                                    <th>Detalle</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($interactions as $i): ?>
                                    <tr>
                                        <td>
                                            <?php echo date('d/m H:i', strtotime($i['created_at'])); ?>
                                        </td>
                                        <td><strong>
                                                <?php echo $i['client_name']; ?>
                                            </strong></td>
                                        <td>
                                            <?php
                                            $icon = 'fa-circle';
                                            $color = '#94a3b8';
                                            if ($i['interaction_type'] === 'WhatsApp') {
                                                $icon = 'fa-whatsapp';
                                                $color = '#25d366';
                                            }
                                            if ($i['interaction_type'] === 'Email/PDF') {
                                                $icon = 'fa-envelope-open-text';
                                                $color = '#ef4444';
                                            }
                                            if ($i['interaction_type'] === 'Llamada') {
                                                $icon = 'fa-phone';
                                                $color = '#3b82f6';
                                            }
                                            ?>
                                            <span class="badge"
                                                style="background: <?php echo $color; ?>20; color: <?php echo $color; ?>;">
                                                <i class="fab <?php echo $icon; ?>"></i>
                                                <?php echo $i['interaction_type']; ?>
                                            </span>
                                        </td>
                                        <td style="font-size: 0.9rem; color: #fff;">
                                            <?php echo $i['description']; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <h3><i class="fas fa-funnel-dollar"></i> Embudo de Ventas (30 Días)</h3>
                    <div
                        style="display: flex; flex-direction: column; gap: 15px; margin-top: 20px; text-align: center;">

                        <!-- Step 1 -->
                        <div
                            style="background: #1e3a8a; padding: 15px; border-radius: 8px; position: relative; width: 100%;">
                            <h4 style="margin:0;">Interesados (Clicks)</h4>
                            <strong style="font-size: 1.5rem;">
                                <?php echo $funnel['clicks']; ?>
                            </strong>
                        </div>
                        <i class="fas fa-arrow-down" style="color: #64748b;"></i>

                        <!-- Step 2 -->
                        <div
                            style="background: #4338ca; padding: 15px; border-radius: 8px; width: 85%; margin: 0 auto;">
                            <h4 style="margin:0;">Presupuestados</h4>
                            <strong style="font-size: 1.5rem;">
                                <?php echo $funnel['quoted']; ?>
                            </strong>
                        </div>
                        <i class="fas fa-arrow-down" style="color: #64748b;"></i>

                        <!-- Step 3 -->
                        <div
                            style="background: #064e3b; padding: 15px; border-radius: 8px; width: 70%; margin: 0 auto;">
                            <h4 style="margin:0;">Ventas Cerradas</h4>
                            <strong style="font-size: 1.5rem;">
                                <?php echo $funnel['sold']; ?>
                            </strong>
                        </div>

                    </div>
                </div>
            </div>
    </div>
    </main>
    </div>
</body>

</html>