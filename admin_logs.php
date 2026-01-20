<?php
header("Location: configuration.php?tab=auditoria");
exit;
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Acceso denegado. Solo administradores.");
}

$db = Vsys\Lib\Database::getInstance();
$logs = $db->prepare("
    SELECT l.*, u.username 
    FROM system_logs l
    JOIN users u ON l.user_id = u.id
    WHERE l.company_id = ?
    ORDER BY l.created_at DESC
    LIMIT 200
");
$logs->execute([$_SESSION['company_id']]);
$results = $logs->fetchAll();
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <title>Logs del Sistema - VS System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>
        tailwind.config = { darkMode: "class" };
    </script>
</head>

<body class="bg-[#101822] text-white antialiased p-6">
    <div class="max-w-7xl mx-auto space-y-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="bg-primary/20 p-2 rounded-lg text-blue-500">
                    <span class="material-symbols-outlined text-2xl">history_edu</span>
                </div>
                <h2 class="font-bold text-xl uppercase tracking-tighter">Auditoría del Sistema</h2>
            </div>
            <button onclick="window.close()"
                class="text-slate-500 hover:text-white transition-all underline text-xs">Cerrar Ventana</button>
        </div>

        <div class="bg-[#16202e] border border-[#233348] rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-[#101822]/50 border-b border-[#233348]">
                        <tr class="text-slate-500 text-[10px] font-bold uppercase tracking-widest">
                            <th class="px-6 py-4">Fecha/Hora</th>
                            <th class="px-6 py-4">Usuario</th>
                            <th class="px-6 py-4">Acción</th>
                            <th class="px-6 py-4">Entidad</th>
                            <th class="px-6 py-4">Detalles</th>
                            <th class="px-6 py-4">IP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#233348]">
                        <?php foreach ($results as $l): ?>
                            <tr class="hover:bg-white/[0.02] transition-colors">
                                <td class="px-6 py-4 text-xs font-mono text-slate-400">
                                    <?php echo date('d/m/Y H:i:s', strtotime($l['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-bold text-sm text-blue-400">
                                        <?php echo htmlspecialchars($l['username']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="text-[10px] font-black uppercase px-2 py-0.5 rounded bg-slate-800 text-slate-300">
                                        <?php echo htmlspecialchars($l['action']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-500">
                                    <?php echo $l['entity_type'] ? ($l['entity_type'] . " #" . $l['entity_id']) : '-'; ?>
                                </td>
                                <td class="px-6 py-4 text-[11px] text-slate-400 max-w-xs truncate">
                                    <?php echo htmlspecialchars($l['details']); ?>
                                </td>
                                <td class="px-6 py-4 text-xs font-mono text-slate-500">
                                    <?php echo $l['ip_address']; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>