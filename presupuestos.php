<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/cotizador/Cotizador.php';

// Auto-migration for new logistics authorization fields
try {
    $db = Vsys\Lib\Database::getInstance();
    $db->exec("ALTER TABLE quotations ADD COLUMN IF NOT EXISTS logistics_authorized_by VARCHAR(100) DEFAULT NULL");
    $db->exec("ALTER TABLE quotations ADD COLUMN IF NOT EXISTS logistics_authorized_at DATETIME DEFAULT NULL");
} catch (Exception $e) {
    // Ignore if already exists or other non-critical errors
}

use Vsys\Modules\Cotizador\Cotizador;

$cot = new Cotizador();
$quotes = $cot->getAllQuotations(100);
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Presupuestos - VS System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <script src="js/theme_handler.js"></script>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#136dec",
                    },
                },
            },
        }
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            text-transform: uppercase !important;
        }

        .normal-case {
            text-transform: none !important;
        }

        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        .dark ::-webkit-scrollbar-track {
            background: #101822;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }

        .dark ::-webkit-scrollbar-thumb {
            background: #233348;
        }

        .table-container {
            background: rgba(255, 255, 255, 1);
        }

        .dark .table-container {
            background: rgba(22, 32, 46, 0.7);
            backdrop-filter: blur(10px);
        }
    </style>
</head>

<body
    class="bg-white dark:bg-[#020617] text-slate-800 dark:text-slate-200 antialiased overflow-hidden transition-colors duration-300">
    <div class="flex h-screen w-full">
        <?php include 'sidebar.php'; ?>

        <main class="flex-1 flex flex-col h-full overflow-hidden relative">
            <!-- Header -->
            <header
                class="h-20 flex items-center justify-between px-8 border-b border-slate-200 dark:border-white/5 bg-white/80 dark:bg-[#020617]/80 backdrop-blur-xl z-20">
                <div class="flex items-center gap-4">
                    <button onclick="toggleVsysSidebar()" class="lg:hidden dark:text-white text-slate-800 p-1 mr-2">
                        <span class="material-symbols-outlined">menu</span>
                    </button>
                    <div class="bg-primary/20 p-2 rounded-xl text-primary">
                        <span class="material-symbols-outlined text-2xl">history</span>
                    </div>
                    <div>
                        <h2
                            class="dark:text-white text-slate-800 font-bold text-lg uppercase tracking-tight leading-none">
                            Presupuestos y Ventas
                        </h2>
                        <p class="text-[10px] text-slate-500 font-bold tracking-widest uppercase mt-1.5">Registro
                            histórico y autorizaciones rápidas</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <a href="cotizador.php"
                        class="bg-primary hover:bg-blue-600 text-white px-6 py-3 rounded-xl text-xs font-black uppercase tracking-widest flex items-center gap-2 transition-all shadow-lg shadow-primary/20 active:scale-95">
                        <span class="material-symbols-outlined text-sm">add</span>
                        NUEVA COTIZACIÓN
                    </a>
                </div>
            </header>

            <!-- Content Area -->
            <div class="flex-1 overflow-y-auto p-8 custom-scrollbar">
                <div class="max-w-[1500px] mx-auto space-y-8">

                    <div class="flex justify-between items-end px-2">
                        <div>
                            <h1 class="text-3xl font-black dark:text-white text-slate-800 tracking-tighter">GESTIÓN DE
                                OPERACIONES</h1>
                        </div>
                        <div class="flex gap-2">
                            <div
                                class="bg-white dark:bg-white/5 border border-slate-200 dark:border-white/5 px-5 py-3 rounded-2xl flex items-center gap-4 shadow-sm">
                                <span
                                    class="text-[10px] font-black text-slate-500 uppercase tracking-widest leading-none">Total
                                    Items</span>
                                <span
                                    class="text-xl font-black text-primary leading-none"><?php echo count($quotes); ?></span>
                            </div>
                        </div>
                    </div>

                    <div
                        class="table-container border border-slate-200 dark:border-white/5 rounded-[2.5rem] overflow-hidden shadow-2xl transition-colors">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead
                                    class="bg-slate-50 dark:bg-white/5 border-b border-slate-100 dark:border-white/5">
                                    <tr class="text-slate-500 text-[10px] font-black uppercase tracking-widest">
                                        <th class="px-8 py-6">Fecha / Hora</th>
                                        <th class="px-8 py-6">Referencia</th>
                                        <th class="px-8 py-6">Cliente Entidad</th>
                                        <th class="px-8 py-6 text-right">Total USD</th>
                                        <th class="px-8 py-6 text-right">Total ARS</th>
                                        <th class="px-8 py-6 text-center">Estado Comercial</th>
                                        <th class="px-8 py-6 text-center">Gestión</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                                    <?php foreach ($quotes as $q): ?>
                                        <tr class="hover:bg-slate-50 dark:hover:bg-white/[0.03] transition-all group">
                                            <td class="px-8 py-6">
                                                <div class="text-[11px] font-bold dark:text-slate-300 text-slate-600">
                                                    <?php echo date('d/m/Y', strtotime($q['created_at'])); ?>
                                                </div>
                                                <div
                                                    class="text-[9px] font-black text-slate-500 opacity-50 tracking-widest">
                                                    <?php echo date('H:i', strtotime($q['created_at'])); ?>
                                                </div>
                                            </td>
                                            <td class="px-8 py-6">
                                                <span
                                                    class="font-black dark:text-white text-slate-800 group-hover:text-primary transition-colors tracking-tight text-sm">
                                                    <?php echo $q['quote_number']; ?>
                                                </span>
                                            </td>
                                            <td class="px-8 py-6 whitespace-nowrap">
                                                <div class="text-[11px] font-black dark:text-slate-100 text-slate-800">
                                                    <?php echo $q['client_name']; ?>
                                                </div>
                                            </td>
                                            <td
                                                class="px-8 py-6 text-right font-mono text-sm dark:text-white text-slate-800 font-black">
                                                $ <?php echo number_format($q['total_usd'], 2); ?>
                                            </td>
                                            <td class="px-8 py-6 text-right font-mono text-[11px] text-slate-500 font-bold">
                                                $ <?php echo number_format($q['total_ars'], 2, ',', '.'); ?>
                                            </td>
                                            <td class="px-8 py-6 text-center">
                                                <?php if ($q['status'] === 'Perdido'): ?>
                                                    <span
                                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-red-500/10 text-red-500 text-[9px] font-black uppercase tracking-widest border border-red-500/20">
                                                        <span class="material-symbols-outlined text-[14px] fill-1">cancel</span>
                                                        Perdido
                                                    </span>
                                                <?php elseif ($q['is_confirmed']): ?>
                                                    <span
                                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-green-500/10 text-green-500 text-[9px] font-black uppercase tracking-widest border border-green-500/20">
                                                        <span
                                                            class="material-symbols-outlined text-[14px] fill-1">verified</span>
                                                        Confirmado
                                                    </span>
                                                <?php else: ?>
                                                    <span
                                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-slate-500/10 text-slate-500 text-[9px] font-black uppercase tracking-widest border border-slate-500/10">
                                                        <span class="material-symbols-outlined text-[14px]">draft</span>
                                                        Pendiente
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-8 py-6">
                                                <div class="flex items-center justify-center gap-1">
                                                    <!-- Print -->
                                                    <button
                                                        onclick="openQuote(<?php echo $q['id']; ?>, <?php echo $q['client_id']; ?>, '<?php echo $q['quote_number']; ?>')"
                                                        class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-white/10 text-slate-400 hover:text-primary transition-all"
                                                        title="Ver / Imprimir">
                                                        <span class="material-symbols-outlined text-lg">print</span>
                                                    </button>

                                                    <!-- Edit (New Version) -->
                                                    <a href="cotizador.php?id=<?php echo $q['id']; ?>"
                                                        class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-white/10 text-slate-400 hover:text-emerald-500 transition-all font-bold"
                                                        title="Editar / Nueva Versión">
                                                        <span class="material-symbols-outlined text-lg">edit</span>
                                                    </a>

                                                    <!-- Analysis / Summary -->
                                                    <a href="resumen_pedido.php?id=<?php echo $q['id']; ?>"
                                                        class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-white/10 text-slate-400 hover:text-blue-500 transition-all"
                                                        title="Resumen e Historial de Cambios">
                                                        <span class="material-symbols-outlined text-lg">history_edu</span>
                                                    </a>

                                                    <!-- Confirm Toggle -->
                                                    <button
                                                        onclick="toggleStatus(<?php echo $q['id']; ?>, 'quotation', 'is_confirmed', <?php echo $q['is_confirmed'] ? 0 : 1; ?>)"
                                                        class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-white/10 transition-all <?php echo $q['is_confirmed'] ? 'text-green-500' : 'text-slate-400'; ?>"
                                                        title="<?php echo $q['is_confirmed'] ? 'Desmarcar' : 'Confirmar'; ?>">
                                                        <span
                                                            class="material-symbols-outlined text-lg <?php echo $q['is_confirmed'] ? 'fill-1' : ''; ?>">check_circle</span>
                                                    </button>

                                                    <!-- Upload Payment -->
                                                    <button
                                                        onclick="openPaymentUpload(<?php echo $q['id']; ?>, '<?php echo $q['quote_number']; ?>')"
                                                        class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-white/10 text-slate-400 hover:text-purple-500 transition-all"
                                                        title="Subir archivo de Pago (Verificación)">
                                                        <span class="material-symbols-outlined text-lg">upload_file</span>
                                                    </button>

                                                    <!-- Authorize Logistics (Conditional) -->
                                                    <?php if ($q['payment_status'] !== 'Pagado' && empty($q['logistics_authorized_by'])): ?>
                                                        <button
                                                            onclick="openAuthModal(<?php echo $q['id']; ?>, '<?php echo $q['quote_number']; ?>')"
                                                            class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-white/10 text-slate-400 hover:text-amber-500 transition-all"
                                                            title="Autorizar envío sin cobro">
                                                            <span class="material-symbols-outlined text-lg">verified_user</span>
                                                        </button>
                                                    <?php elseif (!empty($q['logistics_authorized_by'])): ?>
                                                        <span class="p-2 text-amber-500"
                                                            title="Autorizado por: <?php echo $q['logistics_authorized_by']; ?>">
                                                            <span
                                                                class="material-symbols-outlined text-lg fill-1">verified_user</span>
                                                        </span>
                                                    <?php endif; ?>

                                                    <!-- Payment Toggle -->
                                                    <button
                                                        onclick="toggleStatus(<?php echo $q['id']; ?>, 'quotation', 'payment_status', '<?php echo $q['payment_status'] === 'Pagado' ? 'Pendiente' : 'Pagado'; ?>')"
                                                        class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-white/10 transition-all <?php echo $q['payment_status'] === 'Pagado' ? 'text-purple-500' : 'text-slate-400'; ?>"
                                                        title="Estado Pago: <?php echo $q['payment_status']; ?>">
                                                        <span
                                                            class="material-symbols-outlined text-lg <?php echo $q['payment_status'] === 'Pagado' ? 'fill-1' : ''; ?>">payments</span>
                                                    </button>

                                                    <!-- Email -->
                                                    <button onclick="sendEmail(<?php echo $q['id']; ?>)"
                                                        class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-white/10 text-slate-400 hover:text-blue-500 transition-all"
                                                        title="Enviar Email">
                                                        <span class="material-symbols-outlined text-lg">mail</span>
                                                    </button>

                                                    <!-- Delete -->
                                                    <button
                                                        onclick="deleteQuote(<?php echo $q['id']; ?>, '<?php echo $q['quote_number']; ?>')"
                                                        class="p-2 rounded-xl hover:bg-red-500/10 text-red-500/20 hover:text-red-500 transition-all"
                                                        title="Eliminar">
                                                        <span class="material-symbols-outlined text-lg">delete</span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Authorization Modal -->
    <div id="authModal"
        class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-[100] flex items-center justify-center p-4">
        <div
            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-white/5 rounded-3xl w-full max-w-md p-8 shadow-2xl animate-in fade-in zoom-in duration-300">
            <h3 class="text-xl font-black mb-4 dark:text-white text-slate-800 tracking-tight uppercase">AUTORIZAR ENVÍO
                SIN PAGO</h3>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-6 px-1">Presupuesto <span
                    id="authQuoteNumber" class="text-primary font-black tracking-tight normal-case"></span></p>

            <form id="authForm" class="space-y-4">
                <input type="hidden" name="id" id="authQuoteId">
                <div>
                    <label
                        class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 ml-1">Responsable
                        de Autorización</label>
                    <input type="text" name="authorized_by" required placeholder="EJ: AUTORIZA JAVIER"
                        class="w-full bg-slate-50 dark:bg-[#020617] border border-slate-200 dark:border-white/5 rounded-2xl px-5 py-4 font-black text-xs focus:ring-2 focus:ring-primary outline-none transition-all placeholder:opacity-30">
                </div>

                <div class="flex gap-4 pt-4">
                    <button type="button" onclick="closeAuthModal()"
                        class="flex-1 py-4 rounded-2xl border border-slate-200 dark:border-white/5 text-slate-500 font-black text-[10px] hover:bg-slate-50 dark:hover:bg-white/5 uppercase tracking-widest transition-all">CANCELAR</button>
                    <button type="submit"
                        class="flex-1 py-4 rounded-2xl bg-amber-500 text-white font-black text-[10px] hover:scale-[1.02] transition-transform shadow-xl shadow-amber-500/30 uppercase tracking-widest">AUTORIZAR
                        ENVÍO</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Payment Upload Modal -->
    <div id="paymentModal"
        class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[110] flex items-center justify-center p-4">
        <div
            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-white/5 rounded-3xl w-full max-w-md p-8 shadow-2xl animate-in fade-in zoom-in duration-300">
            <h3 class="text-xl font-black mb-1 dark:text-white text-slate-800 tracking-tight uppercase">SUBIR
                COMPROBANTE</h3>
            <p id="modalQuoteNumber" class="text-[10px] font-bold text-primary uppercase tracking-widest mb-6"></p>

            <form id="paymentUploadForm" class="space-y-6">
                <input type="hidden" name="quote_number" id="uploadQuoteNumber">
                <div class="border-2 border-dashed border-slate-200 dark:border-white/5 rounded-3xl p-10 text-center hover:border-primary/50 transition-colors group cursor-pointer"
                    onclick="document.getElementById('paymentFile').click()">
                    <span
                        class="material-symbols-outlined text-4xl text-slate-300 group-hover:text-primary mb-3">cloud_upload</span>
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">CLIC PARA SELECCIONAR
                        ARCHIVO</p>
                    <p class="text-[9px] text-slate-400 mt-1 uppercase">PDF, JPG o PNG</p>
                    <input type="file" name="payment_file" id="paymentFile" class="hidden" accept=".pdf,image/*"
                        onchange="updateFileName(this)">
                    <div id="fileNameDisplay" class="mt-4 text-[10px] font-mono text-primary font-bold break-all"></div>
                </div>

                <div class="flex gap-4 pt-4">
                    <button type="button" onclick="closePaymentModal()"
                        class="flex-1 py-4 rounded-2xl border border-slate-200 dark:border-white/5 text-slate-500 font-black text-[10px] hover:bg-slate-50 dark:hover:bg-white/5 uppercase tracking-widest transition-all">CANCELAR</button>
                    <button type="submit"
                        class="flex-1 py-4 rounded-2xl bg-primary text-white font-black text-[10px] hover:scale-[1.02] transition-transform shadow-xl shadow-primary/30 uppercase tracking-widest">SUBIR
                        COMPROBANTE</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openPaymentUpload(id, quoteNo) {
            document.getElementById('modalQuoteNumber').innerText = 'Presupuesto: ' + quoteNo;
            document.getElementById('uploadQuoteNumber').value = quoteNo;
            document.getElementById('paymentModal').classList.remove('hidden');
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').classList.add('hidden');
            document.getElementById('paymentUploadForm').reset();
            document.getElementById('fileNameDisplay').innerText = '';
        }

        function updateFileName(input) {
            if (input.files && input.files[0]) {
                document.getElementById('fileNameDisplay').innerText = input.files[0].name.toUpperCase();
            }
        }

        document.getElementById('paymentUploadForm').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            Swal.fire({ title: 'Subiendo...', allowOutsideClick: false, didOpen: () => { Swal.showLoading() } });
            try {
                const res = await fetch('ajax_upload_payment.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'SUBIDO', text: 'El comprobante se guardó correctamente.', timer: 1500, showConfirmButton: false });
                    closePaymentModal();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    Swal.fire('Error', data.error, 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'Error de conexión', 'error');
            }
        };

        async function sendEmail(id) {
            Swal.fire({
                title: '¿ENVIAR POR EMAIL?',
                text: 'Se enviará el presupuesto en PDF al cliente.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#136dec',
                confirmButtonText: 'SÍ, ENVIAR',
                cancelButtonText: 'CANCELAR',
                background: document.documentElement.classList.contains('dark') ? '#16202e' : '#fff',
                color: document.documentElement.classList.contains('dark') ? '#fff' : '#000'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    Swal.fire({ title: 'Enviando...', allowOutsideClick: false, didOpen: () => { Swal.showLoading() } });
                    try {
                        const res = await fetch('ajax_send_email.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ type: 'quotation', id: id })
                        });
                        const data = await res.json();
                        if (data.success) Swal.fire('Enviado', 'El correo se envió con éxito.', 'success');
                        else Swal.fire('Error', data.error, 'error');
                    } catch (e) {
                        Swal.fire('Error', 'Error de conexión', 'error');
                    }
                }
            });
        }

        function openQuote(id, entityId, quoteNo) {
            fetch('ajax_log_crm.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    entity_id: entityId,
                    type: 'Email/PDF',
                    description: `Visualización de presupuesto ${quoteNo}`
                })
            });
            window.open('imprimir_cotizacion.php?id=' + id, '_blank');
        }

        async function deleteQuote(id, number) {
            Swal.fire({
                title: '¿ELIMINAR PRESUPUESTO?',
                text: `¿Está seguro de eliminar el comprobante ${number}?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'SÍ, ELIMINAR',
                background: document.documentElement.classList.contains('dark') ? '#16202e' : '#fff',
                color: document.documentElement.classList.contains('dark') ? '#fff' : '#000'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    const res = await fetch('ajax_delete_quotation.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: id })
                    });
                    const data = await res.json();
                    if (data.success) location.reload();
                    else alert('Error: ' + data.error);
                }
            });
        }

        async function toggleStatus(id, type, field, val) {
            const res = await fetch('ajax_update_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id, type: type, field: field, value: val })
            });
            const data = await res.json();
            if (data.success) location.reload();
            else alert('Error: ' + data.error);
        }

        function openAuthModal(id, number) {
            document.getElementById('authQuoteId').value = id;
            document.getElementById('authQuoteNumber').innerText = number;
            document.getElementById('authModal').classList.remove('hidden');
        }

        function closeAuthModal() {
            document.getElementById('authModal').classList.add('hidden');
        }

        document.getElementById('authForm').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            try {
                const res = await fetch('ajax_authorize_logistics.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'ENVÍO AUTORIZADO',
                        text: 'El pedido ahora es visible en logística.',
                        background: document.documentElement.classList.contains('dark') ? '#16202e' : '#fff',
                        color: document.documentElement.classList.contains('dark') ? '#fff' : '#000',
                        customClass: { popup: 'rounded-3xl border border-white/5' }
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Error', data.error, 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'Error de conexión', 'error');
            }
        };
    </script>
</body>

</html>