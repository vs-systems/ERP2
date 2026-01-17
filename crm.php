<?php
require_once 'auth_check.php';
/**
 * VS System ERP - Modern CRM & Pipeline Dashboard
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/crm/CRM.php';

use Vsys\Modules\CRM\CRM;

$crm = new CRM();
$today = date('Y-m-d');
$stats = $crm->getStats($today);
$recentItems = $crm->getRecentInteractions(10);

// Stages for Kanban
$stages = [
    'Nuevo' => ['icon' => 'fas fa-star', 'color' => '#3b82f6'],
    'Contactado' => ['icon' => 'fas fa-comments', 'color' => '#8b5cf6'],
    'Presupuestado' => ['icon' => 'fas fa-file-invoice-dollar', 'color' => '#f59e0b'],
    'Ganado' => ['icon' => 'fas fa-check-circle', 'color' => '#10b981'],
    'Perdido' => ['icon' => 'fas fa-times-circle', 'color' => '#f43f5e']
];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>CRM Moderno - VS System</title>
    <link rel="stylesheet" href="css/style_premium.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .kanban-board {
            display: flex;
            gap: 20px;
            overflow-x: auto;
            padding: 20px 0;
            min-height: calc(100vh - 300px);
        }

        .kanban-column {
            flex: 0 0 300px;
            background: rgba(15, 23, 42, 0.4);
            border-radius: 12px;
            padding: 15px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .column-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding: 0 5px;
        }

        .column-title {
            font-size: 0.9rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #94a3b8;
        }

        .lead-card {
            background: rgba(30, 41, 59, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .lead-card:hover {
            transform: translateY(-3px);
            border-color: var(--accent-violet);
            box-shadow: 0 10px 20px -10px rgba(139, 92, 246, 0.4);
            background: rgba(30, 41, 59, 1);
        }

        .lead-name {
            font-weight: 700;
            color: #f8fafc;
            margin-bottom: 5px;
            font-size: 1rem;
        }

        .lead-contact {
            font-size: 0.8rem;
            color: #94a3b8;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .lead-meta {
            margin-top: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.75rem;
            color: #64748b;
        }

        .interaction-pill {
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 700;
            background: rgba(139, 92, 246, 0.1);
            color: var(--accent-violet);
        }

        .metric-card {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.8), rgba(15, 23, 42, 0.9));
            border-radius: 16px;
            padding: 24px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
        }

        .metric-card::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(139, 92, 246, 0.1) 0%, transparent 70%);
        }
    </style>
</head>

<body>
    <header
        style="background: #020617; border-bottom: 2px solid var(--accent-violet); display: flex; justify-content: space-between; align-items: center; padding: 0 20px;">
        <div style="display: flex; align-items: center; gap: 20px;">
            <img src="logo_display.php?v=1" alt="VS System" style="height: 50px;">
            <div style="color: #fff; font-family: 'Inter', sans-serif; font-weight: 800; font-size: 1.5rem;">
                CRM <span
                    style="background: linear-gradient(90deg, #8b5cf6, #d946ef); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">INTELLIGENT</span>
            </div>
        </div>
        <div class="header-right"><span class="user-badge"><i class="fas fa-user-circle"></i> Admin</span></div>
    </header>

    <div class="dashboard-container">
        <?php include 'sidebar.php'; ?>

        <main class="content">
            <div class="grid-3" style="margin-bottom: 2rem;">
                <div class="metric-card">
                    <h4 style="color: #94a3b8; font-size: 0.8rem; text-transform: uppercase;">Presupuestos Activos</h4>
                    <div style="font-size: 2.5rem; font-weight: 800; color: #fff;">
                        <?php echo $stats['active_quotes']; ?></div>
                    <div style="font-size: 0.8rem; color: #10b981;"><i class="fas fa-arrow-up"></i> Pipeline saludable
                    </div>
                </div>
                <div class="metric-card">
                    <h4 style="color: #94a3b8; font-size: 0.8rem; text-transform: uppercase;">Cierres Técnicos Hoy</h4>
                    <div style="font-size: 2.5rem; font-weight: 800; color: var(--accent-violet);">
                        <?php echo $stats['orders_today']; ?></div>
                    <div style="font-size: 0.8rem; color: #94a3b8;">Conversión diaria</div>
                </div>
                <div class="metric-card">
                    <h4 style="color: #94a3b8; font-size: 0.8rem; text-transform: uppercase;">Eficiencia de Ventas</h4>
                    <div style="font-size: 2.5rem; font-weight: 800; color: #3b82f6;">
                        <?php echo $stats['efficiency']; ?>%</div>
                    <div
                        style="width: 100%; height: 4px; background: rgba(255,255,255,0.1); border-radius: 2px; margin-top: 10px;">
                        <div
                            style="width: <?php echo $stats['efficiency']; ?>%; height: 100%; background: #3b82f6; border-radius: 2px;">
                        </div>
                    </div>
                </div>
            </div>

            <div class="kanban-board">
                <?php foreach ($stages as $stage => $meta):
                    $leads = $crm->getLeadsByStatus($stage);
                    ?>
                    <div class="kanban-column">
                        <div class="column-header">
                            <span class="column-title"><i class="<?php echo $meta['icon']; ?>"
                                    style="color: <?php echo $meta['color']; ?>;"></i> <?php echo $stage; ?></span>
                            <span class="interaction-pill"><?php echo count($leads); ?></span>
                        </div>

                        <?php foreach ($leads as $lead): ?>
                            <div class="lead-card" onclick="openLead(<?php echo $lead['id']; ?>)">
                                <div class="lead-name"><?php echo $lead['name']; ?></div>
                                <div class="lead-contact">
                                    <i class="fas fa-user-tag" style="font-size: 0.7rem;"></i>
                                    <?php echo $lead['contact_person'] ?: 'Sin contacto'; ?>
                                </div>
                                <?php if ($lead['phone']): ?>
                                    <div class="lead-contact"><i class="fab fa-whatsapp"></i> <?php echo $lead['phone']; ?></div>
                                <?php endif; ?>

                                <div class="lead-meta">
                                    <span><i class="far fa-clock"></i>
                                        <?php echo date('d M', strtotime($lead['updated_at'])); ?></span>
                                    <div style="display: flex; gap: 5px;">
                                        <button onclick="event.stopPropagation(); moveLead(<?php echo $lead['id']; ?>, 'prev')"
                                            style="background:none; border:none; color:#64748b; cursor:pointer;"><i
                                                class="fas fa-chevron-left"></i></button>
                                        <button onclick="event.stopPropagation(); moveLead(<?php echo $lead['id']; ?>, 'next')"
                                            style="background:none; border:none; color:#64748b; cursor:pointer;"><i
                                                class="fas fa-chevron-right"></i></button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if (empty($leads)): ?>
                            <div
                                style="text-align: center; padding: 20px; color: #475569; font-size: 0.8rem; border: 1px dashed rgba(255,255,255,0.05); border-radius: 8px;">
                                Sin registros
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <script>
        function openLead(id) {
            // Modal implementation would go here
            console.log('Opening lead:', id);
        }

        async function moveLead(id, direction) {
            const formData = new FormData();
            formData.append('action', 'move_lead');
            formData.append('id', id);
            formData.append('direction', direction);

            try {
                const resp = await fetch('ajax_crm_actions.php', { method: 'POST', body: formData });
                const res = await resp.json();
                if (res.success) location.reload();
            } catch (e) {
                console.error(e);
            }
        }
    </script>
</body>

</html>