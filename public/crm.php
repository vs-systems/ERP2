<?php
/**
 * VS System ERP - CRM Dashboard (Pipeline & Leads)
 */
require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/lib/Database.php';
require_once __DIR__ . '/../src/modules/crm/CRM.php';

use Vsys\Modules\CRM\CRM;

$crm = new CRM();
$stats = $crm->getLeadsStats();
$statuses = ['Nuevo', 'Contactado', 'Presupuestado', 'Ganado', 'Perdido'];
$interactions = $crm->getRecentInteractions(10);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>CRM Dashboard - VS System</title>
    <link rel="stylesheet" href="css/style_premium.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .pipeline {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .pipeline-col {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            padding: 1rem;
            border-top: 4px solid var(--accent-blue);
            min-height: 400px;
        }

        .status-Nuevo {
            border-color: #94a3b8;
        }

        .status-Contactado {
            border-color: #6366f1;
        }

        .status-Presupuestado {
            border-color: #eab308;
        }

        .status-Ganado {
            border-color: #22c55e;
        }

        .status-Perdido {
            border-color: #ef4444;
        }

        .lead-card {
            background: #1e293b;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 12px;
            cursor: pointer;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: transform 0.2s;
        }

        .lead-card:hover {
            transform: translateY(-2px);
            border-color: var(--accent-violet);
        }

        .interaction-card {
            background: rgba(255, 255, 255, 0.05);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 3px solid var(--accent-blue);
        }

        /* Modal Style Override */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: #0f172a;
            margin: 10% auto;
            padding: 2rem;
            border-radius: 15px;
            width: 500px;
            border: 1px solid var(--accent-violet);
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #94a3b8;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            padding: 8px;
            border-radius: 4px;
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
            <a href="presupuestos.php" class="nav-link"><i class="fas fa-history"></i> PRESUPUESTOS</a>
            <a href="clientes.php" class="nav-link"><i class="fas fa-users"></i> CLIENTES</a>
            <a href="proveedores.php" class="nav-link"><i class="fas fa-truck-loading"></i> PROVEEDORES</a>
            <a href="compras.php" class="nav-link"><i class="fas fa-cart-arrow-down"></i> COMPRAS</a>
            <a href="importar.php" class="nav-link"><i class="fas fa-upload"></i> IMPORTAR</a>
            <a href="crm.php" class="nav-link active"><i class="fas fa-handshake"></i> CRM</a>
            <a href="cotizador.php" class="nav-link"><i class="fas fa-file-invoice-dollar"></i> COTIZADOR</a>
        </nav>

        <main class="content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h1>Panel de Seguimiento (CRM)</h1>
                <div style="display: flex; gap: 10px;">
                    <button class="btn-primary" onclick="openLeadModal()"><i class="fas fa-user-plus"></i> NUEVO
                        LEAD</button>
                    <button class="btn-secondary" onclick="openInteractionModal()"><i class="fas fa-phone"></i>
                        REGISTRAR ACCI&Oacute;N</button>
                </div>
            </div>

            <div class="pipeline" id="pipeline-container">
                <?php foreach ($statuses as $s): ?>
                    <div class="pipeline-col status-<?php echo $s; ?>" id="col-<?php echo $s; ?>">
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                            <h4 style="margin: 0; color: #e2e8f0;"><?php echo strtoupper($s); ?></h4>
                            <span class="badge"
                                style="background: rgba(255,255,255,0.1);"><?php echo $stats[$s] ?? 0; ?></span>
                        </div>
                        <div class="lead-list">
                            <?php
                            $leads = $crm->getLeadsByStatus($s);
                            foreach ($leads as $l):
                                ?>
                                <div class="lead-card">
                                    <div onclick="editLead(<?php echo htmlspecialchars(json_encode($l)); ?>)">
                                        <div style="font-weight: 600; margin-bottom: 5px;"><?php echo $l['name']; ?></div>
                                        <div style="font-size: 0.8rem; opacity: 0.7;">
                                            <i class="fas fa-user"></i> <?php echo $l['contact_person']; ?><br>
                                            <i class="fas fa-phone"></i> <?php echo $l['phone']; ?>
                                        </div>
                                    </div>
                                    <div
                                        style="display: flex; justify-content: flex-end; gap: 5px; margin-top: 8px; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 5px;">
                                        <?php if ($s !== 'Nuevo'): ?>
                                            <button class="btn-secondary" style="padding: 2px 5px; font-size: 0.7rem;"
                                                onclick="moveLead(<?php echo $l['id']; ?>, 'prev')" title="Retroceder"><i
                                                    class="fas fa-chevron-left"></i></button>
                                        <?php endif; ?>
                                        <button class="btn-secondary" style="padding: 2px 5px; font-size: 0.7rem;"
                                            onclick="sendLeadEmail(<?php echo $l['id']; ?>)" title="Enviar Mail"><i
                                                class="fas fa-envelope"></i></button>
                                        <a href="https://calendar.google.com/calendar/render?action=TEMPLATE&text=Seguimiento+Lead:+<?php echo urlencode($l['name']); ?>&details=Contacto:+<?php echo urlencode($l['contact_person'] ?? ''); ?>+-+Tel:+<?php echo urlencode($l['phone'] ?? ''); ?>+-+Email:+<?php echo urlencode($l['email'] ?? ''); ?>"
                                            target="_blank" class="btn-secondary" style="padding: 2px 5px; font-size: 0.7rem;"
                                            title="Agendar Seguimiento"><i class="fas fa-calendar-plus"></i></a>
                                        <button class="btn-secondary" style="padding: 2px 5px; font-size: 0.7rem;"
                                            onclick="openInteractionModal(<?php echo $l['id']; ?>, '<?php echo addslashes($l['name']); ?>', 'lead')"
                                            title="Registrar Acción"><i class="fas fa-comment"></i></button>
                                        <?php if ($s !== 'Ganado' && $s !== 'Perdido'): ?>
                                            <button class="btn-secondary" style="padding: 2px 5px; font-size: 0.7rem;"
                                                onclick="moveLead(<?php echo $l['id']; ?>, 'next')" title="Avanzar"><i
                                                    class="fas fa-chevron-right"></i></button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="card">
                <h3><i class="fas fa-comments"></i> &Uacute;ltimas Interacciones</h3>
                <?php if (empty($interactions)): ?>
                    <p style="color: grey;">No hay interacciones recientes registradas.</p>
                <?php else: ?>
                    <div class="grid-3" style="grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                        <?php foreach ($interactions as $i): ?>
                            <div class="interaction-card">
                                <div
                                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                                    <strong style="color: var(--accent-blue);"><?php echo $i['entity_name']; ?></strong>
                                    <span class="badge"
                                        style="font-size: 0.7rem; background: rgba(99,102,241,0.2);"><?php echo $i['type']; ?></span>
                                </div>
                                <p style="margin: 5px 0; font-size: 0.9rem;"><?php echo $i['description']; ?></p>
                                <div style="font-size: 0.75rem; opacity: 0.5; margin-top: 10px;">
                                    <i class="fas fa-calendar"></i>
                                    <?php echo date('d/m/Y H:i', strtotime($i['interaction_date'])); ?>
                                    | <i class="fas fa-user-edit"></i> <?php echo $i['user_name']; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modals -->
    <div id="leadModal" class="modal">
        <div class="modal-content">
            <h3 id="modalTitle">Nuevo Lead / Oportunidad</h3>
            <form id="leadForm">
                <input type="hidden" id="lead_id" name="id">
                <div class="form-group">
                    <label>Empresa / Nombre</label>
                    <input type="text" name="name" required placeholder="Ej: Seguridad Integral S.A.">
                </div>
                <div class="form-group">
                    <label>Contacto</label>
                    <input type="text" name="contact_person" placeholder="Nombre de la persona">
                </div>
                <div class="grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email">
                    </div>
                    <div class="form-group">
                        <label>Tel&eacute;fono</label>
                        <input type="text" name="phone">
                    </div>
                </div>
                <div class="form-group">
                    <label>Estado</label>
                    <select name="status">
                        <option value="Nuevo">Nuevo</option>
                        <option value="Contactado">Contactado</option>
                        <option value="Presupuestado">Presupuestado</option>
                        <option value="Ganado">Ganado</option>
                        <option value="Perdido">Perdido</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Notas / Comentarios</label>
                    <textarea name="notes" rows="3"></textarea>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 1rem;">
                    <button type="submit" class="btn-primary" style="flex: 1;">GUARDAR</button>
                    <button type="button" class="btn-secondary" onclick="closeModal('leadModal')">CANCELAR</button>
                </div>
            </form>
        </div>
    </div>

    <div id="interactionModal" class="modal">
        <div class="modal-content">
            <h3>Registrar Acci&oacute;n / Seguimiento</h3>
            <form id="interactionForm">
                <div class="form-group">
                    <label>Cliente / Lead</label>
                    <input type="text" id="int_entity_search" placeholder="Buscar cliente o lead..."
                        onkeyup="searchEntities(this.value)" autocomplete="off">
                    <input type="hidden" id="int_entity_id" name="entity_id" required>
                    <input type="hidden" id="int_entity_type" name="entity_type" value="entity">
                    <div id="search_results"
                        style="display: none; position: absolute; background: #1e293b; border: 1px solid var(--accent-violet); width: 430px; z-index: 10; border-radius: 4px; max-height: 200px; overflow-y: auto;">
                    </div>
                </div>
                <div class="form-group">
                    <label>Tipo de Acci&oacute;n</label>
                    <select name="type" required>
                        <option value="Llamada">Llamada</option>
                        <option value="Email">Email</option>
                        <option value="Whatsapp">Whatsapp</option>
                        <option value="Reuni&oacute;n">Reuni&oacute;n</option>
                        <option value="Presupuesto">Env&iacute;o Presupuesto</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Descripci&oacute;n de lo conversado</label>
                    <textarea name="description" rows="4" required
                        placeholder="Escriba aqu&iacute; los detalles..."></textarea>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 1rem;">
                    <button type="submit" class="btn-primary" style="flex: 1;">REGISTRAR</button>
                    <button type="button" class="btn-secondary"
                        onclick="closeModal('interactionModal')">CANCELAR</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openLeadModal() {
            document.getElementById('modalTitle').innerText = 'Nuevo Lead / Oportunidad';
            document.getElementById('leadForm').reset();
            document.getElementById('lead_id').value = '';
            document.getElementById('leadModal').style.display = 'block';
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        function editLead(lead) {
            document.getElementById('modalTitle').innerText = 'Editar Lead';
            const form = document.getElementById('leadForm');
            form.id.value = lead.id;
            form.name.value = lead.name;
            form.contact_person.value = lead.contact_person;
            form.email.value = lead.email;
            form.phone.value = lead.phone;
            form.status.value = lead.status;
            form.notes.value = lead.notes;
            document.getElementById('leadModal').style.display = 'block';
        }


        function sendLeadEmail(id) {
            if (!confirm('¿Desea enviar un email de contacto a este lead?')) return;
            fetch('ajax_send_email.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ type: 'lead', id: id })
            })
                .then(res => res.json())
                .then(res => {
                    if (res.success) alert('Email enviado correctamente.');
                    else alert('Error: ' + res.error);
                });
        }

        function openInteractionModal(entityId = null, entityName = null, entityType = 'entity') {
            document.getElementById('interactionForm').reset();
            if (entityId) {
                document.getElementById('int_entity_id').value = entityId;
                document.getElementById('int_entity_search').value = entityName;
                document.getElementById('int_entity_type').value = entityType;
            } else {
                document.getElementById('int_entity_id').value = '';
                document.getElementById('int_entity_type').value = 'entity';
            }
            document.getElementById('interactionModal').style.display = 'block';
        }

        function moveLead(id, direction) {
            fetch('ajax_crm_actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'move_lead', id: id, direction: direction })
            })
                .then(res => res.json())
                .then(res => {
                    if (res.success) location.reload();
                    else alert('Error: ' + res.error);
                });
        }

        document.getElementById('leadForm').onsubmit = function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            data.action = 'save_lead';

            fetch('ajax_crm_actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
                .then(res => res.json())
                .then(res => {
                    if (res.success) location.reload();
                    else alert('Error: ' + res.error);
                });
        };

        document.getElementById('interactionForm').onsubmit = function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            data.action = 'log_interaction';

            fetch('ajax_crm_actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
                .then(res => res.json())
                .then(res => {
                    if (res.success) location.reload();
                    else alert('Error: ' + res.error);
                });
        };

        function searchEntities(query) {
            if (query.length < 2) {
                document.getElementById('search_results').style.display = 'none';
                return;
            }
            fetch('ajax_search_clients.php?type=all&q=' + query)
                .then(res => res.json())
                .then(data => {
                    const resDiv = document.getElementById('search_results');
                    resDiv.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(ent => {
                            const d = document.createElement('div');
                            d.style.padding = '8px';
                            d.style.cursor = 'pointer';
                            d.style.borderBottom = '1px solid rgba(255,255,255,0.05)';
                            d.innerHTML = `<strong>${ent.name}</strong> <small>(${ent.type})</small> <span class="badge" style="background: ${ent.origin === 'lead' ? 'var(--accent-violet)' : '#64748b'}; font-size: 0.6rem;">${ent.origin.toUpperCase()}</span>`;
                            d.onclick = () => {
                                document.getElementById('int_entity_id').value = ent.id;
                                document.getElementById('int_entity_search').value = ent.name;
                                document.getElementById('int_entity_type').value = ent.origin;
                                resDiv.style.display = 'none';
                            };
                            resDiv.appendChild(d);
                        });
                        resDiv.style.display = 'block';
                    }
                });
        }
    </script>
</body>

</html>