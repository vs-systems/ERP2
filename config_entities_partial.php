<?php
// Extracted from original configuration.php to keep it modular
$tab = $_GET['tab'] ?? 'entidades';
$db = Vsys\Lib\Database::getInstance();
$isAdmin = ($_SESSION['role'] === 'Admin' || $_SESSION['role'] === 'admin');

// Use the entityType variable set by the parent
$typeFilter = $entityType ?? 'client';
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h3 class="text-xl font-bold flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">group</span> Directorio de Entidades
        </h3>
        <div class="flex gap-2">
            <?php if ($typeFilter === 'transport'): ?>
                <a href="config_entities.php?type=transport"
                    class="bg-primary hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-xs font-bold transition-all shadow-lg active:scale-95 flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">add</span>
                    NUEVO TRANSPORTE</a>
            <?php else: ?>
                <a href="config_entities.php?type=client"
                    class="bg-primary/10 text-primary px-4 py-2 rounded-lg text-xs font-bold hover:bg-primary hover:text-white transition-all">+
                    NUEVO CLIENTE</a>
                <a href="config_entities.php?type=supplier"
                    class="bg-amber-500/10 text-amber-500 px-4 py-2 rounded-lg text-xs font-bold hover:bg-amber-500 hover:text-white transition-all">+
                    NUEVO PROVEEDOR</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-slate-50 dark:bg-white/5">
                <tr
                    class="text-[10px] font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100 dark:border-white/5">
                    <th class="px-6 py-4">Tipo</th>
                    <th class="px-6 py-4">Nombre / Razón Social</th>
                    <th class="px-6 py-4">CUIT / DNI</th>
                    <th class="px-6 py-4">Contacto</th>
                    <th class="px-6 py-4 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                <?php
                $stmt = $db->prepare("SELECT * FROM entities WHERE type = ? ORDER BY name ASC LIMIT 50");
                $stmt->execute([$typeFilter]);
                $entities = $stmt->fetchAll();

                foreach ($entities as $e): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors">
                        <td class="px-6 py-4">
                            <span
                                class="px-2 py-1 rounded text-[9px] font-bold uppercase <?php echo $e['type'] === 'client' ? 'bg-blue-500/10 text-blue-500' : 'bg-amber-500/10 text-amber-500'; ?>">
                                <?php echo $e['type']; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-slate-800 dark:text-white">
                                <?php echo $e['name']; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-xs">
                            <?php echo $e['tax_id']; ?>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-xs font-medium">
                                <?php echo $e['email']; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right flex justify-end gap-2">
                            <a href="config_entities.php?type=<?php echo $e['type']; ?>&edit=<?php echo $e['id']; ?>"
                                class="text-primary hover:underline font-bold text-xs uppercase">Editar</a>

                            <?php if ($isAdmin): ?>
                                <form method="POST" onsubmit="return confirm('¿Estás seguro de eliminar esta entidad?');">
                                    <input type="hidden" name="action" value="delete_entity">
                                    <input type="hidden" name="id" value="<?php echo $e['id']; ?>">
                                    <button class="text-red-500 hover:underline font-bold text-xs uppercase">Eliminar</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>