<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/lib/Logger.php';
require_once __DIR__ . '/src/modules/config/PriceList.php';
require_once __DIR__ . '/src/modules/logistica/Logistics.php';

use Vsys\Lib\Database;
use Vsys\Lib\Logger;
use Vsys\Modules\Config\PriceList;
use Vsys\Modules\Logistica\Logistics;

// Role check - Only admin can access this page
$userRole = strtolower($_SESSION['role'] ?? '');
if ($userRole !== 'admin' && $userRole !== 'sistemas') {
    header("Location: dashboard.php");
    exit;
}

$db = Database::getInstance();
$priceListModule = new PriceList();
$logisticsModule = new Logistics();

$activeTab = $_GET['tab'] ?? 'general';
$message = '';
$messageType = 'success';

// Consolidated POST Handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'save_visuals':
                    $settingsFile = __DIR__ . '/src/config/settings.json';
                    $settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : ['default_theme' => 'auto'];
                    $settings['default_theme'] = $_POST['default_theme'];
                    file_put_contents($settingsFile, json_encode($settings));
                    $message = "Preferencias visuales guardadas.";
                    break;

                case 'save_user':
                    $uid = $_POST['user_id'] ?? null;
                    $username = $_POST['username'];
                    $password = $_POST['password'];
                    $role = $_POST['role'];
                    $status = $_POST['status'];
                    $discount_cap = $_POST['discount_cap'] ?? 0;
                    $perms = isset($_POST['perms']) ? json_encode($_POST['perms']) : json_encode([]);

                    if ($uid) {
                        if (!empty($password)) {
                            $hashed = password_hash($password, PASSWORD_DEFAULT);
                            $stmt = $db->prepare("UPDATE users SET username = ?, password_hash = ?, role = ?, status = ?, permissions = ?, discount_cap = ? WHERE id = ? AND company_id = ?");
                            $stmt->execute([$username, $hashed, $role, $status, $perms, $discount_cap, $uid, $_SESSION['company_id']]);
                        } else {
                            $stmt = $db->prepare("UPDATE users SET username = ?, role = ?, status = ?, permissions = ?, discount_cap = ? WHERE id = ? AND company_id = ?");
                            $stmt->execute([$username, $role, $status, $perms, $discount_cap, $uid, $_SESSION['company_id']]);
                        }
                        $message = "Usuario actualizado correctamente.";
                    } else {
                        $hashed = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $db->prepare("INSERT INTO users (username, password_hash, role, status, permissions, company_id, discount_cap) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$username, $hashed, $role, $status, $perms, $_SESSION['company_id'], $discount_cap]);
                        $message = "Usuario creado correctamente.";
                    }
                    break;

                case 'update_lists':
                    foreach ($_POST['lists'] as $id => $data) {
                        $priceListModule->updatePriceList($id, $data['name'], $data['margin']);
                    }
                    $message = "Configuración de listas actualizada.";
                    break;

                case 'save_transport':
                    $tdata = [
                        'id' => $_POST['transport_id'] ?? null,
                        'name' => $_POST['name'] ?? '',
                        'contact_person' => $_POST['contact_person'] ?? '',
                        'phone' => $_POST['phone'] ?? '',
                        'email' => $_POST['email'] ?? '',
                        'is_active' => isset($_POST['is_active']) ? 1 : 0
                    ];
                    $logisticsModule->saveTransport($tdata);
                    $message = $tdata['id'] ? "Transportista actualizado." : "Transportista registrado.";
                    break;

                case 'reset_module':
                    $module = $_POST['module'];
                    if ($_POST['confirm_word'] !== 'ELIMINAR') {
                        throw new Exception("Palabra de confirmación incorrecta.");
                    }
                    $cid = $_SESSION['company_id'];
                    switch ($module) {
                        case 'crm':
                            // Clear interactions first (FK)
                            $db->prepare("DELETE FROM crm_interactions WHERE company_id = ?")->execute([$cid]);
                            $db->prepare("DELETE FROM crm_leads WHERE company_id = ?")->execute([$cid]);
                            break;
                        case 'quotations':
                            // Clear analysis and items first
                            $db->prepare("DELETE FROM price_analysis_items WHERE analysis_id IN (SELECT id FROM price_analysis WHERE company_id = ?)")->execute([$cid]);
                            $db->prepare("DELETE FROM price_analysis WHERE company_id = ?")->execute([$cid]);
                            $db->prepare("DELETE FROM quotation_items WHERE quotation_id IN (SELECT id FROM quotations WHERE company_id = ?)")->execute([$cid]);
                            $db->prepare("DELETE FROM quotations WHERE company_id = ?")->execute([$cid]);
                            break;
                        case 'products':
                            // Clear serials and items referencing products
                            $db->prepare("DELETE FROM product_serials WHERE company_id = ?")->execute([$cid]);
                            $db->prepare("DELETE FROM price_analysis_items WHERE product_id IN (SELECT id FROM products WHERE company_id = ?)")->execute([$cid]);
                            $db->prepare("DELETE FROM products WHERE company_id = ?")->execute([$cid]);
                            break;
                        case 'purchases':
                            $db->prepare("DELETE FROM purchase_items WHERE purchase_id IN (SELECT id FROM purchases WHERE company_id = ?)")->execute([$cid]);
                            $db->prepare("DELETE FROM purchases WHERE company_id = ?")->execute([$cid]);
                            break;
                    }
                    $message = "Módulo reseteado correctamente.";
                    Logger::event('RESET_MODULE', 'module', 0, "Reseteo de módulo: $module");
                    break;
            }
        }

        // Handle User Delete separately if needed
        if (isset($_GET['delete_user'])) {
            $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND username != ? AND company_id = ?");
            $stmt->execute([$_GET['delete_user'], $_SESSION['username'], $_SESSION['company_id']]);
            header("Location: configuration.php?tab=usuarios&deleted=1");
            exit;
        }

    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = 'error';
    }
}

// Data Fetching
$settingsFile = __DIR__ . '/src/config/settings.json';
$sysSettings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : ['default_theme' => 'auto'];

$users = $db->prepare("SELECT * FROM users WHERE company_id = ? ORDER BY username ASC");
$users->execute([$_SESSION['company_id']]);
$users = $users->fetchAll();

$editUser = null;
if (isset($_GET['edit_user'])) {
    foreach ($users as $u)
        if ($u['id'] == $_GET['edit_user'])
            $editUser = $u;
}

$availablePerms = [
    'catalog' => ['label' => 'Catálogo', 'icon' => 'inventory_2'],
    'quotes' => ['label' => 'Cotizaciones', 'icon' => 'request_quote'],
    'sales' => ['label' => 'Facturación', 'icon' => 'receipt_long'],
    'purchases' => ['label' => 'Compras', 'icon' => 'shopping_cart'],
    'logistics' => ['label' => 'Logística', 'icon' => 'local_shipping'],
    'clients' => ['label' => 'Clientes', 'icon' => 'group'],
    'suppliers' => ['label' => 'Proveedores', 'icon' => 'factory'],
    'admin' => ['label' => 'Configuración', 'icon' => 'settings']
];

$lists = $priceListModule->getAll();

$transports = $logisticsModule->getTransports(false);
$editTransport = null;
if (isset($_GET['edit_transport'])) {
    foreach ($transports as $t)
        if ($t['id'] == $_GET['edit_transport'])
            $editTransport = $t;
}

$logs = $db->prepare("SELECT l.*, u.username FROM system_logs l JOIN users u ON l.user_id = u.id WHERE l.company_id = ? ORDER BY l.created_at DESC LIMIT 200");
$logs->execute([$_SESSION['company_id']]);
$auditLogs = $logs->fetchAll();

?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - VS System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <script src="js/theme_handler.js"></script>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: { colors: { "primary": "#136dec" } }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .tab-btn {
            @apply px-6 py-3 text-sm font-bold uppercase tracking-widest border-b-2 transition-all flex items-center gap-2;
        }

        .tab-btn.active {
            @apply border-primary text-primary bg-primary/5;
        }

        .tab-btn:not(.active) {
            @apply border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300;
        }

        .form-input {
            @apply w-full bg-slate-50 dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-xl text-sm dark:text-white text-slate-800 focus:ring-primary focus:border-primary transition-colors py-2.5 px-4;
        }

        .perm-card {
            @apply flex items-center gap-3 p-3 rounded-xl border border-slate-100 dark:border-[#233348] hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-all cursor-pointer;
        }

        .perm-card.active {
            @apply border-primary/50 bg-primary/5;
        }
    </style>
</head>

<body
    class="bg-white dark:bg-[#101822] text-slate-800 dark:text-white antialiased overflow-hidden transition-colors duration-300">
    <div class="flex h-screen w-full">
        <?php include 'sidebar.php'; ?>

        <main class="flex-1 flex flex-col h-full overflow-hidden relative">
            <header
                class="h-16 flex items-center justify-between px-6 border-b border-slate-200 dark:border-[#233348] bg-white dark:bg-[#101822]/95 backdrop-blur z-10 sticky top-0 transition-colors duration-300">
                <div class="flex items-center gap-3">
                    <div class="bg-[#136dec]/20 p-2 rounded-lg text-[#136dec]">
                        <span class="material-symbols-outlined text-2xl">settings</span>
                    </div>
                    <h2 class="dark:text-white text-slate-800 font-bold text-lg uppercase tracking-tight">Configuración
                        del Sistema</h2>
                </div>
            </header>

            <!-- Tabs Navigation -->
            <div
                class="bg-slate-50 dark:bg-[#16202e] border-b border-slate-200 dark:border-[#233348] px-6 overflow-x-auto">
                <div class="flex gap-2">
                    <a href="?tab=general" class="tab-btn <?php echo $activeTab === 'general' ? 'active' : ''; ?>">
                        <span class="material-symbols-outlined text-[18px]">tune</span> General
                    </a>
                    <a href="?tab=usuarios" class="tab-btn <?php echo $activeTab === 'usuarios' ? 'active' : ''; ?>">
                        <span class="material-symbols-outlined text-[18px]">group</span> Usuarios
                    </a>
                    <a href="?tab=precios" class="tab-btn <?php echo $activeTab === 'precios' ? 'active' : ''; ?>">
                        <span class="material-symbols-outlined text-[18px]">universal_currency_alt</span> Precios
                    </a>
                    <a href="?tab=transportes"
                        class="tab-btn <?php echo $activeTab === 'transportes' ? 'active' : ''; ?>">
                        <span class="material-symbols-outlined text-[18px]">local_shipping</span> Transportes
                    </a>
                    <a href="?tab=auditoria" class="tab-btn <?php echo $activeTab === 'auditoria' ? 'active' : ''; ?>">
                        <span class="material-symbols-outlined text-[18px]">history_edu</span> Auditoría
                    </a>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-6 transition-all duration-300">
                <div class="max-w-7xl mx-auto space-y-6">

                    <?php if ($message): ?>
                        <div
                            class="flex items-center gap-3 p-4 rounded-xl border <?php echo $messageType === 'success' ? 'bg-green-500/10 border-green-500/20 text-green-500' : 'bg-red-500/10 border-red-500/20 text-red-500'; ?> animate-in fade-in slide-in-from-top-4 duration-300">
                            <span
                                class="material-symbols-outlined"><?php echo $messageType === 'success' ? 'check_circle' : 'error'; ?></span>
                            <span class="text-sm font-bold uppercase tracking-widest"><?php echo $message; ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($activeTab === 'general'): ?>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <!-- Visual Settings -->
                            <div
                                class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-6 shadow-sm">
                                <h3 class="font-bold text-lg mb-4 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary">palette</span> Apariencia Visual
                                </h3>
                                <form method="POST" class="space-y-4">
                                    <input type="hidden" name="action" value="save_visuals">
                                    <div>
                                        <label
                                            class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Tema
                                            Predeterminado</label>
                                        <select name="default_theme" class="form-input">
                                            <option value="auto" <?php echo $sysSettings['default_theme'] === 'auto' ? 'selected' : ''; ?>>Seguir Sistema (Auto)</option>
                                            <option value="light" <?php echo $sysSettings['default_theme'] === 'light' ? 'selected' : ''; ?>>Siempre Claro</option>
                                            <option value="dark" <?php echo $sysSettings['default_theme'] === 'dark' ? 'selected' : ''; ?>>Siempre Oscuro</option>
                                        </select>
                                    </div>
                                    <button type="submit"
                                        class="w-full bg-primary hover:bg-blue-600 text-white font-bold py-3 rounded-xl shadow-lg shadow-primary/20 transition-all">
                                        GUARDAR PREFERENCIAS
                                    </button>
                                </form>
                            </div>

                            <!-- Danger Zone / Reset -->
                            <div class="bg-red-500/5 border border-red-500/20 rounded-2xl p-6">
                                <h3
                                    class="font-bold text-lg mb-2 text-red-500 flex items-center gap-2 uppercase tracking-tighter">
                                    <span class="material-symbols-outlined">warning</span> Zona de Peligro: Reset de Datos
                                </h3>
                                <p class="text-xs text-slate-500 mb-6">Esta acción es irreversible y borrará todos los datos
                                    del módulo seleccionado.</p>

                                <form method="POST" class="space-y-4">
                                    <input type="hidden" name="action" value="reset_module">
                                    <div>
                                        <label
                                            class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Módulo
                                            a Resetear</label>
                                        <select name="module" class="form-input border-red-500/20">
                                            <option value="crm">CRM (Leads y Embudo)</option>
                                            <option value="quotations">Presupuestos (Cotizaciones y Análisis)</option>
                                            <option value="purchases">Compras (Ordenes y Pagos)</option>
                                            <option value="products">Catálogo (Productos y Series)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label
                                            class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Escriba
                                            <span class="text-red-500 font-black">ELIMINAR</span> para confirmar</label>
                                        <input type="text" name="confirm_word" required placeholder="Confirmación..."
                                            class="form-input border-red-500/30 text-center font-black tracking-widest">
                                    </div>
                                    <button type="submit"
                                        class="w-full bg-red-600 hover:bg-red-500 text-white font-black py-4 rounded-xl uppercase tracking-tighter shadow-xl shadow-red-500/20">
                                        BORRAR DATOS SELECCIONADOS
                                    </button>
                                </form>
                            </div>
                        </div>

                    <?php elseif ($activeTab === 'usuarios'): ?>
                        <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">
                            <!-- User Form -->
                            <div class="xl:col-span-4 h-fit">
                                <div
                                    class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-6 shadow-xl dark:shadow-none">
                                    <h3 class="font-bold text-lg mb-6 flex items-center gap-2">
                                        <span
                                            class="material-symbols-outlined text-primary"><?php echo $editUser ? 'edit' : 'person_add'; ?></span>
                                        <?php echo $editUser ? 'Editar Usuario' : 'Nuevo Usuario'; ?>
                                    </h3>
                                    <form method="POST" class="space-y-4">
                                        <input type="hidden" name="action" value="save_user">
                                        <input type="hidden" name="user_id" value="<?php echo $editUser['id'] ?? ''; ?>">

                                        <div>
                                            <label
                                                class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Nombre
                                                de Usuario</label>
                                            <input type="text" name="username"
                                                value="<?php echo $editUser['username'] ?? ''; ?>" required
                                                class="form-input">
                                        </div>
                                        <div>
                                            <label
                                                class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Contraseña
                                                <?php echo $editUser ? '(Dejar en blanco para no cambiar)' : ''; ?></label>
                                            <input type="password" name="password" <?php echo $editUser ? '' : 'required'; ?> class="form-input">
                                        </div>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label
                                                    class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Rol</label>
                                                <select name="role" class="form-input">
                                                    <option value="admin" <?php echo ($editUser['role'] ?? '') == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                    <option value="Vendedor" <?php echo ($editUser['role'] ?? '') == 'Vendedor' ? 'selected' : ''; ?>>Vendedor</option>
                                                    <option value="Logística" <?php echo ($editUser['role'] ?? '') == 'Logística' ? 'selected' : ''; ?>>Logística</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label
                                                    class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Estado</label>
                                                <select name="status" class="form-input">
                                                    <option value="active" <?php echo strtolower($editUser['status'] ?? '') == 'active' ? 'selected' : ''; ?>>Activo</option>
                                                    <option value="inactive" <?php echo strtolower($editUser['status'] ?? '') == 'inactive' ? 'selected' : ''; ?>>Inactivo</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div>
                                            <label
                                                class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Tope
                                                de Descuento (%)</label>
                                            <input type="number" step="0.1" name="discount_cap"
                                                value="<?php echo $editUser['discount_cap'] ?? '0'; ?>" class="form-input">
                                        </div>

                                        <div class="space-y-2">
                                            <label
                                                class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Permisos
                                                de Módulo</label>
                                            <div class="grid grid-cols-1 gap-1">
                                                <?php
                                                $userPerms = json_decode($editUser['permissions'] ?? '[]', true);
                                                foreach ($availablePerms as $key => $info):
                                                    $checked = in_array($key, $userPerms) ? 'checked' : '';
                                                    ?>
                                                    <label class="perm-card <?php echo $checked ? 'active' : ''; ?>">
                                                        <input type="checkbox" name="perms[]" value="<?php echo $key; ?>" <?php echo $checked; ?> class="hidden peer"
                                                            onchange="this.parentElement.classList.toggle('active', this.checked)">
                                                        <span
                                                            class="material-symbols-outlined text-slate-400 group-hover:text-primary transition-colors"><?php echo $info['icon']; ?></span>
                                                        <span class="text-xs font-semibold"><?php echo $info['label']; ?></span>
                                                        <span
                                                            class="material-symbols-outlined ml-auto text-primary opacity-0 peer-checked:opacity-100 transition-opacity">check_circle</span>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>

                                        <button type="submit"
                                            class="w-full bg-primary hover:bg-blue-600 text-white font-bold py-4 rounded-xl text-xs uppercase tracking-widest shadow-lg shadow-primary/20">
                                            GUARDAR USUARIO
                                        </button>
                                        <?php if ($editUser): ?>
                                            <a href="?tab=usuarios"
                                                class="block w-full text-center text-slate-500 text-[10px] font-bold uppercase py-2">Cancelar
                                                Edición</a>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </div>

                            <!-- Users Table -->
                            <div class="xl:col-span-8">
                                <div
                                    class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl overflow-hidden shadow-sm">
                                    <table class="w-full text-left">
                                        <thead
                                            class="bg-slate-50 dark:bg-[#101822]/50 border-b border-slate-200 dark:border-[#233348]">
                                            <tr class="text-slate-500 text-[10px] font-bold uppercase tracking-widest">
                                                <th class="px-6 py-4">Usuario / Rol</th>
                                                <th class="px-6 py-4">Descuento</th>
                                                <th class="px-6 py-4">Estado</th>
                                                <th class="px-6 py-4 text-center">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 dark:divide-[#233348]">
                                            <?php foreach ($users as $u): ?>
                                                <tr
                                                    class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors group">
                                                    <td class="px-6 py-4">
                                                        <div
                                                            class="font-bold dark:text-white text-slate-800 flex items-center gap-2">
                                                            <?php echo $u['username']; ?>
                                                            <?php if ($u['role'] == 'admin'): ?><span
                                                                    class="material-symbols-outlined text-[14px] text-yellow-500">verified_user</span><?php endif; ?>
                                                        </div>
                                                        <div
                                                            class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                                                            <?php echo $u['role']; ?>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 font-mono font-bold text-orange-500 text-sm">
                                                        <?php echo $u['discount_cap'] ?? '0'; ?>%
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <span
                                                            class="text-[9px] font-black px-2 py-0.5 rounded-full <?php echo strtolower($u['status']) == 'active' ? 'bg-green-500/10 text-green-500' : 'bg-red-500/10 text-red-500'; ?>">
                                                            <?php echo strtolower($u['status']) == 'active' ? 'ACTIVO' : 'INACTIVO'; ?>
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 text-center">
                                                        <div
                                                            class="flex items-center justify-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                            <a href="?tab=usuarios&edit_user=<?php echo $u['id']; ?>"
                                                                class="p-2 text-slate-400 hover:text-primary"><span
                                                                    class="material-symbols-outlined text-[18px]">edit</span></a>
                                                            <?php if ($u['username'] != $_SESSION['username']): ?>
                                                                <a href="?tab=usuarios&delete_user=<?php echo $u['id']; ?>"
                                                                    onclick="return confirm('¿Eliminar usuario?')"
                                                                    class="p-2 text-slate-400 hover:text-red-500"><span
                                                                        class="material-symbols-outlined text-[18px]">delete</span></a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    <?php elseif ($activeTab === 'precios'): ?>
                        <div
                            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl overflow-hidden shadow-sm">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_lists">
                                <table class="w-full text-left">
                                    <thead class="bg-slate-50 dark:bg-[#101822]/50">
                                        <tr class="text-slate-500 text-[10px] font-bold uppercase tracking-widest">
                                            <th class="px-6 py-4">Lista de Precios</th>
                                            <th class="px-6 py-4">Margen (%)</th>
                                            <th class="px-6 py-4">Simulación ($100 Costo)</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-[#233348]">
                                        <?php foreach ($lists as $l): ?>
                                            <tr>
                                                <td class="px-6 py-4">
                                                    <input type="text" name="lists[<?php echo $l['id']; ?>][name]"
                                                        value="<?php echo htmlspecialchars($l['name']); ?>"
                                                        class="form-input font-bold">
                                                </td>
                                                <td class="px-6 py-4">
                                                    <div class="flex items-center gap-2">
                                                        <input type="number" step="0.01"
                                                            name="lists[<?php echo $l['id']; ?>][margin]"
                                                            value="<?php echo $l['margin_percent']; ?>"
                                                            class="form-input w-24 text-center font-bold">
                                                        <span class="text-slate-400 font-bold">%</span>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 text-green-500 font-bold">
                                                    $<?php echo number_format(100 * (1 + $l['margin_percent'] / 100), 2); ?>
                                                    <span class="text-[10px] text-slate-400 font-normal">USD</span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <div
                                    class="p-6 bg-slate-50 dark:bg-[#101822]/20 border-t border-slate-200 dark:border-[#233348] flex justify-end">
                                    <button type="submit"
                                        class="bg-primary hover:bg-blue-600 text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-primary/20">GUARDAR
                                        CAMBIOS</button>
                                </div>
                            </form>
                        </div>

                    <?php elseif ($activeTab === 'transportes'): ?>
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                            <div class="lg:col-span-4 h-fit">
                                <div
                                    class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-6 shadow-sm">
                                    <h3 class="font-bold text-lg mb-6 flex items-center gap-2"><span
                                            class="material-symbols-outlined text-primary">local_shipping</span>
                                        <?php echo $editTransport ? 'Editar' : 'Nuevo'; ?> Transporte</h3>
                                    <form method="POST" class="space-y-4">
                                        <input type="hidden" name="action" value="save_transport">
                                        <input type="hidden" name="transport_id"
                                            value="<?php echo $editTransport['id'] ?? ''; ?>">
                                        <div>
                                            <label
                                                class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Nombre</label>
                                            <input type="text" name="name"
                                                value="<?php echo $editTransport['name'] ?? ''; ?>" required
                                                class="form-input">
                                        </div>
                                        <div>
                                            <label
                                                class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Contacto</label>
                                            <input type="text" name="contact_person"
                                                value="<?php echo $editTransport['contact_person'] ?? ''; ?>"
                                                class="form-input">
                                        </div>
                                        <div>
                                            <label
                                                class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Teléfono</label>
                                            <input type="text" name="phone"
                                                value="<?php echo $editTransport['phone'] ?? ''; ?>" class="form-input">
                                        </div>
                                        <div class="flex items-center gap-3 pt-2">
                                            <input type="checkbox" name="is_active" id="t_active" <?php echo ($editTransport['is_active'] ?? 1) ? 'checked' : ''; ?>
                                                class="rounded text-primary">
                                            <label for="t_active"
                                                class="text-sm font-medium text-slate-500">Habilitado</label>
                                        </div>
                                        <button type="submit"
                                            class="w-full bg-primary hover:bg-blue-600 text-white font-bold py-3 rounded-xl shadow-lg shadow-primary/20">GUARDAR</button>
                                        <?php if ($editTransport): ?><a href="?tab=transportes"
                                                class="block text-center text-[10px] font-bold py-2 text-slate-500 uppercase tracking-widest">Cancelar</a><?php endif; ?>
                                    </form>
                                </div>
                            </div>
                            <div class="lg:col-span-8">
                                <div
                                    class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl overflow-hidden shadow-sm">
                                    <table class="w-full text-left font-sm">
                                        <thead class="bg-slate-50 dark:bg-[#101822]/50">
                                            <tr class="text-slate-500 text-[10px] font-bold uppercase tracking-widest">
                                                <th class="px-6 py-4">Empresa</th>
                                                <th class="px-6 py-4">Contacto</th>
                                                <th class="px-6 py-4">Estado</th>
                                                <th class="px-6 py-4 text-center">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 dark:divide-[#233348]">
                                            <?php foreach ($transports as $t): ?>
                                                <tr class="group hover:bg-slate-50 dark:hover:bg-white/[0.02]">
                                                    <td class="px-6 py-4 font-bold"><?php echo $t['name']; ?></td>
                                                    <td class="px-6 py-4"><?php echo $t['contact_person']; ?>
                                                        <div class="text-[10px] text-slate-500 font-mono">
                                                            <?php echo $t['phone']; ?>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4"><span
                                                            class="text-[9px] font-black px-2 py-0.5 rounded-full <?php echo $t['is_active'] ? 'bg-green-500/10 text-green-500' : 'bg-red-500/10 text-red-500'; ?>"><?php echo $t['is_active'] ? 'ACTIVO' : 'NO'; ?></span>
                                                    </td>
                                                    <td class="px-6 py-4 text-center"><a
                                                            href="?tab=transportes&edit_transport=<?php echo $t['id']; ?>"
                                                            class="p-2 text-slate-400 opacity-0 group-hover:opacity-100 hover:text-primary transition-all"><span
                                                                class="material-symbols-outlined text-[18px]">edit</span></a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    <?php elseif ($activeTab === 'auditoria'): ?>
                        <div
                            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl overflow-hidden shadow-sm">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left">
                                    <thead class="bg-slate-50 dark:bg-[#101822]/50">
                                        <tr class="text-slate-500 text-[10px] font-bold uppercase tracking-widest">
                                            <th class="px-6 py-4">Fecha/Hora</th>
                                            <th class="px-6 py-4">Usuario</th>
                                            <th class="px-6 py-4">Acción</th>
                                            <th class="px-6 py-4">Detalles</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-[#233348]">
                                        <?php foreach ($auditLogs as $l): ?>
                                            <tr class="hover:bg-slate-50 dark:hover:bg-white/[0.02] text-xs transition-colors">
                                                <td class="px-6 py-4 font-mono text-slate-400">
                                                    <?php echo date('d/m/y H:i', strtotime($l['created_at'])); ?>
                                                </td>
                                                <td class="px-6 py-4 font-bold text-primary">
                                                    <?php echo htmlspecialchars($l['username']); ?>
                                                </td>
                                                <td class="px-6 py-4"><span
                                                        class="text-[9px] font-black uppercase px-2 py-0.5 rounded bg-slate-100 dark:bg-white/5 text-slate-500"><?php echo $l['action']; ?></span>
                                                </td>
                                                <td class="px-6 py-4 text-slate-500 truncate max-w-xs">
                                                    <?php echo htmlspecialchars($l['details']); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </main>
    </div>
</body>

</html>