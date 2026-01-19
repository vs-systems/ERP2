<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

// Role check - Only admin can access this page
if (strtolower($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: index.php");
    exit;
}

$db = Vsys\Lib\Database::getInstance();
$message = '';
$messageType = 'success';

// Handle delete
if (isset($_GET['delete'])) {
    $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND username != ?");
    $stmt->execute([$_GET['delete'], $_SESSION['username']]);
    $message = "Usuario eliminado.";
}

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    $status = $_POST['status'];

    // Process permissions array to JSON
    $perms = isset($_POST['perms']) ? json_encode($_POST['perms']) : json_encode([]);

    if ($id) {
        // Update
        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET username = ?, password_hash = ?, role = ?, status = ?, permissions = ? WHERE id = ?");
            $stmt->execute([$username, $hashed, $role, $status, $perms, $id]);
        } else {
            $stmt = $db->prepare("UPDATE users SET username = ?, role = ?, status = ?, permissions = ? WHERE id = ?");
            $stmt->execute([$username, $role, $status, $perms, $id]);
        }
        $message = "Usuario actualizado correctamente.";
    } else {
        // Create
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, password_hash, role, status, permissions) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$username, $hashed, $role, $status, $perms]);
        $message = "Usuario creado correctamente.";
    }
}

$users = $db->query("SELECT * FROM users ORDER BY username ASC")->fetchAll();
$editUser = null;
if (isset($_GET['edit'])) {
    foreach ($users as $u)
        if ($u['id'] == $_GET['edit'])
            $editUser = $u;
}

$availablePerms = [
    'catalog' => ['label' => 'Cat&aacute;logo', 'icon' => 'inventory_2'],
    'quotes' => ['label' => 'Cotizaciones', 'icon' => 'request_quote'],
    'sales' => ['label' => 'Facturaci&oacute;n', 'icon' => 'receipt_long'],
    'purchases' => ['label' => 'Compras', 'icon' => 'shopping_cart'],
    'logistics' => ['label' => 'Log&iacute;stica', 'icon' => 'local_shipping'],
    'clients' => ['label' => 'Clientes', 'icon' => 'group'],
    'suppliers' => ['label' => 'Proveedores', 'icon' => 'factory'],
    'admin' => ['label' => 'Configuraci&oacute;n', 'icon' => 'settings']
];
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - VS System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <script src="js/theme_handler.js"></script>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: { "primary": "#136dec" },
                },
            },
        }
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #101822;
        }

        ::-webkit-scrollbar-thumb {
            background: #233348;
            border-radius: 3px;
        }

        .form-input {
            @apply w-full bg-slate-50 dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-xl text-sm dark:text-white text-slate-800 focus:ring-primary focus:border-primary transition-colors py-2.5;
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
            <!-- Header -->
            <header
                class="h-16 flex items-center justify-between px-6 border-b border-slate-200 dark:border-[#233348] bg-white dark:bg-[#101822]/95 backdrop-blur z-10 transition-colors duration-300">
                <div class="flex items-center gap-3">
                    <div class="bg-[#136dec]/20 p-2 rounded-lg text-[#136dec]">
                        <span class="material-symbols-outlined text-2xl">manage_accounts</span>
                    </div>
                    <h2 class="dark:text-white text-slate-800 font-bold text-lg uppercase tracking-tight">Gesti&oacute;n
                        de Usuarios y Permisos</h2>
                </div>
                <a href="usuarios.php"
                    class="bg-primary hover:bg-blue-600 text-white px-4 py-2 rounded-xl text-[10px] font-bold uppercase tracking-widest shadow-lg shadow-primary/20 transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">person_add</span> NUEVO USUARIO
                </a>
            </header>

            <!-- Content Area -->
            <div class="flex-1 overflow-y-auto p-6 space-y-8">
                <div class="max-w-[1400px] mx-auto space-y-8">

                    <?php if ($message): ?>
                        <div
                            class="flex items-center gap-3 p-4 rounded-2xl border <?php echo $messageType === 'success' ? 'bg-green-500/10 border-green-500/20 text-green-500' : 'bg-red-500/10 border-red-500/20 text-red-500'; ?> animate-in fade-in slide-in-from-top-4 duration-300">
                            <span class="material-symbols-outlined">check_circle</span>
                            <span class="text-sm font-bold uppercase tracking-widest"><?php echo $message; ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">
                        <!-- Form Section -->
                        <div class="xl:col-span-4 h-fit">
                            <div
                                class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-6 shadow-xl dark:shadow-none transition-colors sticky top-0">
                                <h3
                                    class="font-bold text-lg mb-6 border-b border-slate-100 dark:border-[#233348] pb-4 flex items-center gap-2">
                                    <span
                                        class="material-symbols-outlined text-primary"><?php echo $editUser ? 'edit' : 'person_add'; ?></span>
                                    <?php echo $editUser ? 'Editar Usuario' : 'Crear Usuario'; ?>
                                </h3>

                                <form method="POST" class="space-y-6">
                                    <input type="hidden" name="id" value="<?php echo $editUser['id'] ?? ''; ?>">

                                    <div>
                                        <label
                                            class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Nombre
                                            de Usuario</label>
                                        <input type="text" name="username"
                                            value="<?php echo $editUser['username'] ?? ''; ?>" required
                                            class="form-input">
                                    </div>

                                    <div>
                                        <label
                                            class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Contraseña
                                            <?php echo $editUser ? '(Dejar en blanco para no cambiar)' : ''; ?></label>
                                        <input type="password" name="password" <?php echo $editUser ? '' : 'required'; ?> class="form-input">
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label
                                                class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Rol</label>
                                            <select name="role" class="form-input">
                                                <option value="admin" <?php echo ($editUser['role'] ?? '') == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                <option value="Vendedor" <?php echo ($editUser['role'] ?? '') == 'Vendedor' ? 'selected' : ''; ?>>Vendedor</option>
                                                <option value="Logística" <?php echo ($editUser['role'] ?? '') == 'Logística' ? 'selected' : ''; ?>>Logística</option>
                                                <option value="Cajero" <?php echo ($editUser['role'] ?? '') == 'Cajero' ? 'selected' : ''; ?>>Cajero</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label
                                                class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Estado</label>
                                            <select name="status" class="form-input">
                                                <option value="active" <?php echo strtolower($editUser['status'] ?? '') == 'active' ? 'selected' : ''; ?>>Activo</option>
                                                <option value="inactive" <?php echo strtolower($editUser['status'] ?? '') == 'inactive' ? 'selected' : ''; ?>>Inactivo</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Permissions Matrix -->
                                    <div class="space-y-3">
                                        <label
                                            class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1 ml-1">Permisos
                                            de Módulo</label>
                                        <div class="grid grid-cols-1 gap-2">
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
                                                    <span
                                                        class="text-xs font-semibold text-slate-600 dark:text-slate-300"><?php echo $info['label']; ?></span>
                                                    <span
                                                        class="material-symbols-outlined ml-auto text-primary opacity-0 peer-checked:opacity-100 transition-opacity">check_circle</span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <button type="submit"
                                        class="w-full bg-primary hover:bg-blue-600 text-white font-bold py-4 rounded-2xl text-xs uppercase tracking-widest shadow-xl shadow-primary/20 active:scale-[0.98] transition-all flex items-center justify-center gap-2">
                                        <span class="material-symbols-outlined">save</span>
                                        <?php echo $editUser ? 'ACTUALIZAR' : 'CREAR USUARIO'; ?>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Table Section -->
                        <div class="xl:col-span-8">
                            <div
                                class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl overflow-hidden shadow-xl dark:shadow-none transition-colors">
                                <table class="w-full text-left">
                                    <thead
                                        class="bg-slate-50 dark:bg-[#101822]/50 border-b border-slate-200 dark:border-[#233348]">
                                        <tr class="text-slate-500 text-[10px] font-bold uppercase tracking-widest">
                                            <th class="px-6 py-4">Usuario / Rol</th>
                                            <th class="px-6 py-4">Permisos</th>
                                            <th class="px-6 py-4 text-center">Estado</th>
                                            <th class="px-6 py-4 text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-[#233348]">
                                        <?php foreach ($users as $u): ?>
                                            <tr
                                                class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors group">
                                                <td class="px-6 py-5">
                                                    <div
                                                        class="font-bold dark:text-white text-slate-800 flex items-center gap-2">
                                                        <?php echo $u['username']; ?>
                                                        <?php if ($u['role'] == 'admin'): ?>
                                                            <span class="material-symbols-outlined text-[14px] text-yellow-500"
                                                                title="Super Admin">verified_user</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div
                                                        class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                                                        <?php echo $u['role']; ?>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-5">
                                                    <div class="flex flex-wrap gap-1">
                                                        <?php
                                                        $up = json_decode($u['permissions'] ?? '[]', true);
                                                        if (empty($up))
                                                            echo '<span class="text-[10px] text-slate-500 italic">Sin permisos específicos</span>';
                                                        foreach ($up as $p) {
                                                            if (isset($availablePerms[$p])) {
                                                                echo '<span class="px-2 py-0.5 rounded-full bg-slate-100 dark:bg-white/5 text-[9px] font-bold text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-white/10 uppercase tracking-tighter">' . $availablePerms[$p]['label'] . '</span>';
                                                            }
                                                        }
                                                        ?>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-5 text-center">
                                                    <span
                                                        class="text-[10px] font-bold uppercase py-1 px-2 rounded-full <?php echo strtolower($u['status']) == 'active' ? 'bg-green-500/10 text-green-500' : 'bg-red-500/10 text-red-500'; ?>">
                                                        <?php echo strtolower($u['status']) == 'active' ? 'ACTIVO' : 'INACTIVO'; ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-5 text-center">
                                                    <div
                                                        class="flex items-center justify-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                        <a href="usuarios.php?edit=<?php echo $u['id']; ?>"
                                                            class="p-2 rounded-lg hover:bg-primary/10 text-slate-400 hover:text-primary transition-all"
                                                            title="Editar">
                                                            <span class="material-symbols-outlined text-[18px]">edit</span>
                                                        </a>
                                                        <?php if ($u['username'] != $_SESSION['username']): ?>
                                                            <a href="usuarios.php?delete=<?php echo $u['id']; ?>"
                                                                onclick="return confirm('¿Eliminar usuario?')"
                                                                class="p-2 rounded-lg hover:bg-red-500/10 text-slate-400 hover:text-red-500 transition-all"
                                                                title="Eliminar">
                                                                <span
                                                                    class="material-symbols-outlined text-[18px]">delete</span>
                                                            </a>
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
                </div>
            </div>
        </main>
    </div>
</body>

</html>