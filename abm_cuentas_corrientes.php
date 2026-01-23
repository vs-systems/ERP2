<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';

if (($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'admin')) {
    header('Location: dashboard.php');
    exit;
}

$db = Vsys\Lib\Database::getInstance();
$message = '';

if (isset($_POST['action'])) {
    if ($_POST['action'] === 'delete_movement') {
        $id = $_POST['id'];
        $type = $_POST['category']; // 'client' or 'provider'
        $table = ($type === 'client') ? 'client_movements' : 'provider_movements';

        $db->prepare("DELETE FROM $table WHERE id = ?")->execute([$id]);
        $message = "Movimiento eliminado con éxito. El saldo histórico se verá afectado.";
    }
}

$clientMovements = $db->query("SELECT cm.*, e.name as entity_name FROM client_movements cm JOIN entities e ON cm.client_id = e.id ORDER BY cm.date DESC LIMIT 50")->fetchAll();
$providerMovements = $db->query("SELECT pm.*, e.name as entity_name FROM provider_movements pm JOIN entities e ON pm.provider_id = e.id ORDER BY pm.date DESC LIMIT 50")->fetchAll();
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestión de Cuentas Corrientes - VS System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-[#101822] text-white p-6">
    <div class="max-w-7xl mx-auto">
        <header class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-bold uppercase tracking-tight flex items-center gap-3">
                <span class="material-symbols-outlined text-amber-500">account_balance_wallet</span>
                Gestión de Movimientos (CC)
            </h1>
            <a href="configuration.php"
                class="text-slate-400 hover:text-white flex items-center gap-1 text-xs font-bold uppercase">
                <span class="material-symbols-outlined text-sm">arrow_back</span> Volver
            </a>
        </header>

        <?php if ($message): ?>
            <div class="bg-amber-500/10 border border-amber-500/20 text-amber-500 p-4 rounded-xl mb-6 font-bold text-sm">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Client Movements -->
            <div>
                <h3 class="font-bold text-sm text-slate-400 uppercase mb-4 px-2">Movimientos de Clientes</h3>
                <div class="bg-[#16202e] border border-[#233348] rounded-2xl overflow-hidden shadow-xl">
                    <table class="w-full text-left text-[11px]">
                        <thead class="bg-[#1c2a3b] text-slate-500 font-bold uppercase">
                            <tr>
                                <th class="px-4 py-3">Fecha</th>
                                <th class="px-4 py-3">Cliente</th>
                                <th class="px-4 py-3">Tipo</th>
                                <th class="px-4 py-3 text-right">D / H</th>
                                <th class="px-4 py-3 text-center">X</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#233348]">
                            <?php foreach ($clientMovements as $m): ?>
                                <tr class="hover:bg-white/5 transition-colors">
                                    <td class="px-4 py-3 opacity-50">
                                        <?php echo date('d/m/y H:i', strtotime($m['date'])); ?>
                                    </td>
                                    <td class="px-4 py-3 font-bold truncate max-w-[150px]">
                                        <?php echo htmlspecialchars($m['entity_name']); ?>
                                    </td>
                                    <td class="px-4 py-3 uppercase font-black text-[9px]">
                                        <?php echo $m['type']; ?>
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono">
                                        <?php echo $m['debit'] > 0 ? '+' . $m['debit'] : '-' . $m['credit']; ?>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button onclick="confirmDelete(<?php echo $m['id']; ?>, 'client')"
                                            class="text-red-500/50 hover:text-red-500">
                                            <span class="material-symbols-outlined text-lg">delete</span>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Provider Movements -->
            <div>
                <h3 class="font-bold text-sm text-slate-400 uppercase mb-4 px-2">Movimientos de Proveedores</h3>
                <div class="bg-[#16202e] border border-[#233348] rounded-2xl overflow-hidden shadow-xl">
                    <table class="w-full text-left text-[11px]">
                        <thead class="bg-[#1c2a3b] text-slate-500 font-bold uppercase">
                            <tr>
                                <th class="px-4 py-3">Fecha</th>
                                <th class="px-4 py-3">Proveedor</th>
                                <th class="px-4 py-3">Tipo</th>
                                <th class="px-4 py-3 text-right">D / H</th>
                                <th class="px-4 py-3 text-center">X</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#233348]">
                            <?php foreach ($providerMovements as $m): ?>
                                <tr class="hover:bg-white/5 transition-colors">
                                    <td class="px-4 py-3 opacity-50">
                                        <?php echo date('d/m/y H:i', strtotime($m['date'])); ?>
                                    </td>
                                    <td class="px-4 py-3 font-bold truncate max-w-[150px]">
                                        <?php echo htmlspecialchars($m['entity_name']); ?>
                                    </td>
                                    <td class="px-4 py-3 uppercase font-black text-[9px]">
                                        <?php echo $m['type']; ?>
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono">
                                        <?php echo $m['debit'] > 0 ? '+' . $m['debit'] : '-' . $m['credit']; ?>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button onclick="confirmDelete(<?php echo $m['id']; ?>, 'provider')"
                                            class="text-red-500/50 hover:text-red-500">
                                            <span class="material-symbols-outlined text-lg">delete</span>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete(id, category) {
            Swal.fire({
                title: '¿Eliminar movimiento?',
                text: "Esto afectará el saldo del histórico pero no revertirá facturas o pagos asociados.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'SÍ, BORRAR',
                cancelButtonText: 'SALIR'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `<input type="hidden" name="action" value="delete_movement"><input type="hidden" name="id" value="${id}"><input type="hidden" name="category" value="${category}">`;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>
</body>

</html>