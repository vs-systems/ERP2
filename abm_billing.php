<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/modules/billing/Billing.php';

use Vsys\Modules\Billing\Billing;

if (($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'admin')) {
    header('Location: dashboard.php');
    exit;
}

$billing = new Billing();
$db = Vsys\Lib\Database::getInstance();

// Handle Actions
$message = '';
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'delete_invoice') {
        $id = $_POST['id'];

        // Before deleting, find the quote_id to optionally un-confirm it or just log
        $stmt = $db->prepare("SELECT quote_id FROM invoices WHERE id = ?");
        $stmt->execute([$id]);
        $quoteId = $stmt->fetchColumn();

        // Delete movements associated with this invoice (reference_id = $id and type = 'Factura' or similar)
        // Note: reference_id in movements is the invoice ID or quote ID depending on how it was logged.
        // If logged by createInvoice, it uses $invoiceId.
        $db->prepare("DELETE FROM client_movements WHERE (reference_id = ? AND type = 'Factura') OR (reference_id = ? AND type = 'Saldo Inicial')")->execute([$id, $id]);

        // Delete the invoice itself
        $db->prepare("DELETE FROM invoices WHERE id = ?")->execute([$id]);
        $db->prepare("DELETE FROM invoice_items WHERE invoice_id = ?")->execute([$id]);

        $message = "Factura e impactos en cuenta corriente eliminados.";
    }
}

$invoices = $billing->getRecentInvoices(200); // Get more for management
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <title>Administración de Facturación - VS System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-[#101822] text-white p-6">
    <div class="max-w-6xl mx-auto">
        <header class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-bold uppercase tracking-tight flex items-center gap-3">
                <span class="material-symbols-outlined text-blue-500">receipt_long</span>
                Administración de Facturación
            </h1>
            <a href="configuration.php"
                class="text-slate-400 hover:text-white flex items-center gap-1 text-xs font-bold uppercase">
                <span class="material-symbols-outlined text-sm">arrow_back</span> Volver a Configuración
            </a>
        </header>

        <?php if ($message): ?>
            <div class="bg-blue-500/10 border border-blue-500/20 text-blue-400 p-4 rounded-xl mb-6 font-bold text-sm">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="bg-[#16202e] border border-[#233348] rounded-2xl overflow-hidden shadow-2xl">
            <table class="w-full text-left">
                <thead class="bg-[#1c2a3b] text-[10px] uppercase font-bold text-slate-500">
                    <tr>
                        <th class="px-6 py-4">N° Factura</th>
                        <th class="px-6 py-4">Fecha</th>
                        <th class="px-6 py-4">Cliente</th>
                        <th class="px-6 py-4 text-right">Monto</th>
                        <th class="px-6 py-4 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#233348]">
                    <?php foreach ($invoices as $inv): ?>
                        <tr class="hover:bg-white/5 transition-colors">
                            <td class="px-6 py-4 font-mono text-sm font-bold text-blue-400">
                                <?php echo $inv['invoice_number']; ?>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-400">
                                <?php echo date('d/m/Y', strtotime($inv['date'])); ?>
                            </td>
                            <td class="px-6 py-4 font-bold">
                                <?php echo htmlspecialchars($inv['client_name']); ?>
                            </td>
                            <td class="px-6 py-4 text-right font-mono font-bold text-sm">$
                                <?php echo number_format($inv['total_amount'], 2, ',', '.'); ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button
                                    onclick="confirmDelete(<?php echo $inv['id']; ?>, '<?php echo $inv['invoice_number']; ?>')"
                                    class="text-red-500/50 hover:text-red-500 transition-colors">
                                    <span class="material-symbols-outlined">delete</span>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function confirmDelete(id, number) {
            Swal.fire({
                title: '¿Eliminar Factura ' + number + '?',
                text: "Esto también borrará el cargo en la cuenta corriente del cliente. Esta acción no se puede deshacer.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'SÍ, ELIMINAR',
                cancelButtonText: 'CANCELAR'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `<input type="hidden" name="action" value="delete_invoice"><input type="hidden" name="id" value="${id}">`;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>
</body>

</html>