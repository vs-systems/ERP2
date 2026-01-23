<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/catalogo/Catalog.php';
require_once __DIR__ . '/src/modules/config/PriceList.php';
require_once __DIR__ . '/src/lib/User.php';

use Vsys\Lib\Database;
use Vsys\Modules\Catalogo\Catalog;
use Vsys\Modules\Config\PriceList;
use Vsys\Lib\User;

$db = Database::getInstance();
$catalog = new Catalog();
$priceListModule = new PriceList();
$userLib = new User();

// Routing - Robust Default
$currentSection = (isset($_GET['section']) && trim($_GET['section']) !== '') ? trim($_GET['section']) : 'main';
$action = $_POST['action'] ?? '';
// ...
// (Find and replace all $section usages below with $currentSection in similar chunks)

$message = '';
$status = '';

// Check admin role
$isAdmin = ($_SESSION['role'] === 'Admin' || $_SESSION['role'] === 'admin');

// --- HELPER FUNCTIONS ---
function loadCompanyConfig()
{
    $file = __DIR__ . '/config_company.json';
    if (file_exists($file))
        return json_decode(file_get_contents($file), true);
    return [
        'company_name' => 'Mi Empresa',
        'fantasy_name' => 'VS System',
        'tax_id' => '',
        'address' => '',
        'email' => '',
        'phone' => '',
        'logo_url' => 'logo_v2.jpg'
    ];
}

function saveCompanyConfig($data)
{
    unset($data['action']);
    file_put_contents(__DIR__ . '/config_company.json', json_encode($data, JSON_PRETTY_PRINT));
}

// --- ACTIONS HANDLER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Save Company Data
    if ($action === 'save_company' && $isAdmin) {
        saveCompanyConfig($_POST);
        $message = "Datos de empresa guardados correctamente.";
        $status = 'success';
    }

    // Save Budget Config
    if ($action === 'save_budget_config' && $isAdmin) {
        $config = [
            'validity_hours' => $_POST['validity_hours'] ?? 48,
            'legal_notes' => $_POST['legal_notes'] ?? ''
        ];
        file_put_contents(__DIR__ . '/config_budget.json', json_encode($config, JSON_PRETTY_PRINT));
        $message = "Configuración de presupuestos actualizada.";
        $status = 'success';
    }

    // ABM Users
    if ($action === 'create_user' && $isAdmin) {
        try {
            $sql = "INSERT INTO users (username, full_name, email, role, password_hash, status) VALUES (?, ?, ?, ?, ?, 'Active')";
            $passHash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $db->prepare($sql);
            $stmt->execute([$_POST['username'], $_POST['full_name'], $_POST['email'], $_POST['role'], $passHash]);
            $message = "Usuario creado con éxito.";
            $status = 'success';
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
            $status = 'error';
        }
    }

    if ($action === 'update_user' && $isAdmin) {
        try {
            $id = $_POST['id'];
            $role = $_POST['role'];
            $statusVal = $_POST['status']; // Active/Inactive

            $sql = "UPDATE users SET full_name = ?, email = ?, role = ?, status = ? WHERE id = ?";
            $params = [$_POST['full_name'], $_POST['email'], $role, $statusVal, $id];

            if (!empty($_POST['password'])) {
                $sql = "UPDATE users SET full_name = ?, email = ?, role = ?, status = ?, password_hash = ? WHERE id = ?";
                $params = [$_POST['full_name'], $_POST['email'], $role, $statusVal, password_hash($_POST['password'], PASSWORD_DEFAULT), $id];
            }

            $db->prepare($sql)->execute($params);
            $message = "Usuario actualizado con éxito.";
            $status = 'success';
        } catch (Exception $e) {
            $message = "Error al actualizar: " . $e->getMessage();
            $status = 'error';
        }
    }

    if ($action === 'delete_user' && $isAdmin) {
        $db->prepare("DELETE FROM users WHERE id = ?")->execute([$_POST['id']]);
        $message = "Usuario eliminado correctamente.";
        $status = 'success';
    }

    // ABM Brands
    if ($action === 'create_brand') {
        try {
            $db->prepare("INSERT INTO brands (name) VALUES (?)")->execute([$_POST['name']]);
            $message = "Marca agregada con éxito.";
            $status = 'success';
        } catch (Exception $e) {
            $message = "Error al crear marca.";
            $status = 'error';
        }
    }

    if ($action === 'delete_brand' && $isAdmin) {
        $db->prepare("DELETE FROM brands WHERE id = ?")->execute([$_POST['id']]);
        $message = "Marca eliminada correctamente.";
        $status = 'success';
    }

    // Save Price Config
    if ($action === 'save_price_config' && $isAdmin) {
        $priceConfig = [
            'gremio' => floatval($_POST['gremio']),
            'web' => floatval($_POST['web']),
            'mostrador' => floatval($_POST['mostrador'])
        ];
        file_put_contents(__DIR__ . '/config_prices.json', json_encode($priceConfig));
        $message = 'Porcentajes de precios actualizados.';
        $status = 'success';
    }

}
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
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: { extend: { colors: { "primary": "#136dec" } } }
        }
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-50 dark:bg-[#101822] text-slate-800 dark:text-white antialiased">
    <div class="flex h-screen w-full">
        <?php include 'sidebar.php'; ?>

        <main class="flex-1 flex flex-col h-full overflow-hidden">
            <!-- Header -->
            <header
                class="h-16 flex items-center justify-between px-6 border-b border-slate-200 dark:border-[#233348] bg-white dark:bg-[#101822]/95 backdrop-blur">
                <div class="flex items-center gap-3">
                    <?php if ($currentSection !== 'main'): ?>
                        <a href="configuration.php?section=main"
                            class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-white/5 transition-colors">
                            <span class="material-symbols-outlined">arrow_back</span>
                        </a>
                    <?php endif; ?>
                    <h2 class="font-bold text-lg uppercase tracking-tight">Centro de Configuración</h2>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-6">
                <div class="max-w-7xl mx-auto space-y-6">

                    <?php if ($message): ?>
                        <div
                            class="p-4 rounded-xl flex items-center gap-3 <?php echo $status === 'success' ? 'bg-green-500/10 text-green-500 border-green-500/20' : 'bg-red-500/10 text-red-500 border-red-500/20'; ?> border">
                            <span
                                class="material-symbols-outlined"><?php echo $status === 'success' ? 'check_circle' : 'error'; ?></span>
                            <span class="text-sm font-bold uppercase"><?php echo $message; ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($currentSection === 'main'): ?>
                            <!-- MAIN GRID LAYOUT -->
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                            
                            <!-- Datos Empresa -->
                            <a href="?section=company" class="group bg-white dark:bg-[#16202e] p-6 rounded-2xl border border-slate-200 dark:border-[#233348] hover:border-primary/50 transition-all hover:shadow-lg hover:shadow-primary/10">
                                <div class="w-12 h-12 rounded-xl bg-blue-500/10 text-blue-500 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <span class="material-symbols-outlined text-3xl">domain</span>
                                </div>
                                <h3 class="font-bold text-lg mb-1">Datos Empresa</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Razón social, logo, dirección y contacto.</p>
                            </a>

                            <!-- ABM Presupuestos -->
                            <a href="?section=budget" class="group bg-white dark:bg-[#16202e] p-6 rounded-2xl border border-slate-200 dark:border-[#233348] hover:border-primary/50 transition-all hover:shadow-lg hover:shadow-primary/10">
                                <div class="w-12 h-12 rounded-xl bg-purple-500/10 text-purple-500 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <span class="material-symbols-outlined text-3xl">request_quote</span>
                                </div>
                                <h3 class="font-bold text-lg mb-1">ABM Presupuestos</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Configuración, validez y notas legales.</p>
                            </a>

                            <!-- ABM Clientes -->
                            <a href="?section=clients" class="group bg-white dark:bg-[#16202e] p-6 rounded-2xl border border-slate-200 dark:border-[#233348] hover:border-primary/50 transition-all hover:shadow-lg hover:shadow-primary/10">
                                <div class="w-12 h-12 rounded-xl bg-green-500/10 text-green-500 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <span class="material-symbols-outlined text-3xl">groups</span>
                                </div>
                                <h3 class="font-bold text-lg mb-1">ABM Clientes</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Gestión de base de datos de clientes.</p>
                            </a>

                            <!-- ABM Usuarios -->
                            <a href="?section=users" class="group bg-white dark:bg-[#16202e] p-6 rounded-2xl border border-slate-200 dark:border-[#233348] hover:border-primary/50 transition-all hover:shadow-lg hover:shadow-primary/10">
                                <div class="w-12 h-12 rounded-xl bg-orange-500/10 text-orange-500 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <span class="material-symbols-outlined text-3xl">manage_accounts</span>
                                </div>
                                <h3 class="font-bold text-lg mb-1">ABM Usuarios</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Administrar usuarios y permisos.</p>
                            </a>

                            <!-- ABM CRM -->
                            <a href="?section=crm" class="group bg-white dark:bg-[#16202e] p-6 rounded-2xl border border-slate-200 dark:border-[#233348] hover:border-primary/50 transition-all hover:shadow-lg hover:shadow-primary/10">
                                <div class="w-12 h-12 rounded-xl bg-pink-500/10 text-pink-500 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <span class="material-symbols-outlined text-3xl">filter_alt</span>
                                </div>
                                <h3 class="font-bold text-lg mb-1">ABM CRM</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Pipelines, estados y orígenes.</p>
                            </a>

                            <!-- ABM Compras (NUEVO) -->
                            <a href="?section=purchases" class="group bg-white dark:bg-[#16202e] p-6 rounded-2xl border border-slate-200 dark:border-[#233348] hover:border-primary/50 transition-all hover:shadow-lg hover:shadow-primary/10">
                                <div class="w-12 h-12 rounded-xl bg-teal-500/10 text-teal-500 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <span class="material-symbols-outlined text-3xl">shopping_cart</span>
                                </div>
                                <h3 class="font-bold text-lg mb-1">ABM Compras</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Gestión de compras y proveedores.</p>
                            </a>

                             <!-- ABM Marcas -->
                            <a href="?section=brands" class="group bg-white dark:bg-[#16202e] p-6 rounded-2xl border border-slate-200 dark:border-[#233348] hover:border-primary/50 transition-all hover:shadow-lg hover:shadow-primary/10">
                                <div class="w-12 h-12 rounded-xl bg-indigo-500/10 text-indigo-500 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <span class="material-symbols-outlined text-3xl">sell</span>
                                </div>
                                <h3 class="font-bold text-lg mb-1">ABM Marcas</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Gestión de marcas de productos.</p>
                            </a>
                            
                            <!-- Listas de Precios (NUEVO) -->
                            <a href="?section=prices" class="group bg-white dark:bg-[#16202e] p-6 rounded-2xl border border-slate-200 dark:border-[#233348] hover:border-primary/50 transition-all hover:shadow-lg hover:shadow-primary/10">
                                <div class="w-12 h-12 rounded-xl bg-yellow-500/10 text-yellow-500 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <span class="material-symbols-outlined text-3xl">price_change</span>
                                </div>
                                <h3 class="font-bold text-lg mb-1">Listas de Precios</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Configurar porcentajes Gremio, Web, Mostrador.</p>
                            </a>

                             <!-- Informes -->
                             <a href="?section=reports" class="group bg-white dark:bg-[#16202e] p-6 rounded-2xl border border-slate-200 dark:border-[#233348] hover:border-primary/50 transition-all hover:shadow-lg hover:shadow-primary/10">
                                <div class="w-12 h-12 rounded-xl bg-cyan-500/10 text-cyan-500 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <span class="material-symbols-outlined text-3xl">bar_chart</span>
                                </div>
                                <h3 class="font-bold text-lg mb-1">Informes</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Configuración de reportes del sistema.</p>
                            </a>
                        </div>
                    
                    <?php elseif ($currentSection === 'company'):
                        $company = loadCompanyConfig();
                        ?>
                            <!-- DATOS EMPRESA -->
                            <div class="bg-white dark:bg-[#16202e] p-8 rounded-2xl border border-slate-200 dark:border-[#233348] max-w-3xl">
                                <h3 class="text-xl font-bold mb-6">Datos de la Empresa</h3>
                                <form method="POST" class="space-y-4">
                                    <input type="hidden" name="action" value="save_company">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Nombre Fantasía</label>
                                            <input type="text" name="fantasy_name" value="<?php echo $company['fantasy_name']; ?>" class="w-full bg-slate-50 dark:bg-[#101822] rounded-lg border-none">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Razón Social</label>
                                            <input type="text" name="company_name" value="<?php echo $company['company_name']; ?>" class="w-full bg-slate-50 dark:bg-[#101822] rounded-lg border-none">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold uppercase text-slate-500 mb-1">CUIT</label>
                                            <input type="text" name="tax_id" value="<?php echo $company['tax_id']; ?>" class="w-full bg-slate-50 dark:bg-[#101822] rounded-lg border-none">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Email Sistema</label>
                                            <input type="email" name="email" value="<?php echo $company['email']; ?>" class="w-full bg-slate-50 dark:bg-[#101822] rounded-lg border-none">
                                        </div>
                                        <div class="col-span-2">
                                            <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Dirección</label>
                                            <input type="text" name="address" value="<?php echo $company['address']; ?>" class="w-full bg-slate-50 dark:bg-[#101822] rounded-lg border-none">
                                        </div>
                                    </div>
                                    <div class="pt-4">
                                        <button class="bg-primary text-white px-6 py-3 rounded-xl font-bold uppercase text-sm shadow-lg hover:scale-105 transition-transform">Guardar Cambios</button>
                                    </div>
                                </form>
                            </div>

                    <?php elseif ($currentSection === 'budget'):
                        $budgetConfig = json_decode(file_get_contents(__DIR__ . '/config_budget.json') ?: '{}', true);
                        ?>
                            <!-- ABM PRESUPUESTOS -->
                            <div class="bg-white dark:bg-[#16202e] p-8 rounded-2xl border border-slate-200 dark:border-[#233348] max-w-3xl">
                                <h3 class="text-xl font-bold mb-6">Configuración de Presupuestos</h3>
                                <form method="POST" class="space-y-4">
                                    <input type="hidden" name="action" value="save_budget_config">
                                    <div>
                                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Validez por defecto (Horas)</label>
                                        <input type="number" name="validity_hours" value="<?php echo $budgetConfig['validity_hours'] ?? 48; ?>" class="w-32 bg-slate-50 dark:bg-[#101822] rounded-lg border-none">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Notas Legales / Términos</label>
                                        <textarea name="legal_notes" rows="5" class="w-full bg-slate-50 dark:bg-[#101822] rounded-lg border-none"><?php echo $budgetConfig['legal_notes'] ?? ''; ?></textarea>
                                    </div>
                                    <div class="pt-4">
                                        <button class="bg-primary text-white px-6 py-3 rounded-xl font-bold uppercase text-sm shadow-lg hover:scale-105 transition-transform">Guardar Configuración</button>
                                    </div>
                                </form>
                            </div>

                    <?php elseif ($currentSection === 'users'):
                        $users = $db->query("SELECT * FROM users ORDER BY username")->fetchAll();

                        // Check for Edit Mode
                        $editUser = null;
                        if (isset($_GET['edit'])) {
                            foreach ($users as $u) {
                                if ($u['id'] == $_GET['edit']) {
                                    $editUser = $u;
                                    break;
                                }
                            }
                        }
                        ?>
                            <!-- ABM USUARIOS -->
                             <div class="bg-white dark:bg-[#16202e] p-8 rounded-2xl border border-slate-200 dark:border-[#233348]">
                                <h3 class="text-xl font-bold mb-6 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary">manage_accounts</span>
                                    Gestión de Usuarios
                                </h3>
                            
                                <!-- User Form (Create/Edit) -->
                                <form method="POST" class="bg-slate-50 dark:bg-[#101822] p-6 rounded-xl mb-8 border border-slate-100 dark:border-white/5">
                                    <input type="hidden" name="action" value="<?php echo $editUser ? 'update_user' : 'create_user'; ?>">
                                    <?php if ($editUser): ?>
                                            <input type="hidden" name="id" value="<?php echo $editUser['id']; ?>">
                                            <div class="mb-4 flex items-center justify-between">
                                                <h4 class="text-sm font-bold uppercase text-primary">Editando Usuario: <?php echo $editUser['username']; ?></h4>
                                                <a href="configuration.php?section=users" class="text-xs bg-slate-200 dark:bg-slate-700 px-3 py-1 rounded-lg">Cancelar Edición</a>
                                            </div>
                                    <?php endif; ?>

                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                        <div class="">
                                            <label class="text-[10px] font-bold uppercase text-slate-500 mb-1 block">Usuario (Login)</label>
                                            <input type="text" name="username" 
                                                value="<?php echo $editUser['username'] ?? ''; ?>" 
                                                <?php echo $editUser ? 'readonly class="bg-slate-200 dark:bg-slate-800 text-slate-500"' : 'required'; ?> 
                                                class="w-full rounded-lg text-xs border-none bg-white dark:bg-[#16202e]">
                                        </div>
                                        <div class="">
                                            <label class="text-[10px] font-bold uppercase text-slate-500 mb-1 block">Nombre Completo</label>
                                            <input type="text" name="full_name" value="<?php echo $editUser['full_name'] ?? ''; ?>" required class="w-full rounded-lg text-xs border-none bg-white dark:bg-[#16202e]">
                                        </div>
                                        <div class="">
                                            <label class="text-[10px] font-bold uppercase text-slate-500 mb-1 block">Email</label>
                                            <input type="email" name="email" value="<?php echo $editUser['email'] ?? ''; ?>" required class="w-full rounded-lg text-xs border-none bg-white dark:bg-[#16202e]">
                                        </div>
                                        <div class="">
                                            <label class="text-[10px] font-bold uppercase text-slate-500 mb-1 block">Rol (Permisos)</label>
                                            <select name="role" class="w-full rounded-lg text-xs border-none bg-white dark:bg-[#16202e]">
                                                <option value="vendedor" <?php echo ($editUser['role'] ?? '') === 'vendedor' ? 'selected' : ''; ?>>Vendedor (Limitado)</option>
                                                <option value="admin" <?php echo ($editUser['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Administrador (Total)</option>
                                                <option value="logistica" <?php echo ($editUser['role'] ?? '') === 'logistica' ? 'selected' : ''; ?>>Logística (Envíos)</option>
                                            </select>
                                        </div>
                                        <div class="">
                                            <label class="text-[10px] font-bold uppercase text-slate-500 mb-1 block">Estado</label>
                                            <select name="status" class="w-full rounded-lg text-xs border-none bg-white dark:bg-[#16202e]">
                                                <option value="Active" <?php echo ($editUser['status'] ?? '') === 'Active' ? 'selected' : ''; ?>>Activo</option>
                                                <option value="Inactive" <?php echo ($editUser['status'] ?? '') === 'Inactive' ? 'selected' : ''; ?>>Inactivo (Bloqueado)</option>
                                            </select>
                                        </div>
                                        <div class="">
                                            <label class="text-[10px] font-bold uppercase text-slate-500 mb-1 block">Contraseña <?php echo $editUser ? '(Dejar en blanco para mantener)' : ''; ?></label>
                                            <input type="password" name="password" <?php echo $editUser ? '' : 'required'; ?> class="w-full rounded-lg text-xs border-none bg-white dark:bg-[#16202e]">
                                        </div>
                                    </div>
                                    <div class="mt-4 text-right">
                                        <button class="bg-primary text-white px-6 py-2.5 rounded-xl font-bold text-xs uppercase shadow hover:scale-105 transition-transform">
                                            <?php echo $editUser ? 'Actualizar Usuario' : 'Crear Usuario'; ?>
                                        </button>
                                    </div>
                                </form>

                                <table class="w-full text-left">
                                    <thead class="border-b border-slate-200 dark:border-white/10 uppercase text-xs font-bold text-slate-500">
                                        <tr>
                                            <th class="pb-3 px-4">Usuario</th>
                                            <th class="pb-3 px-4">Nombre</th>
                                            <th class="pb-3 px-4">Rol</th>
                                            <th class="pb-3 px-4">Estado</th>
                                            <th class="pb-3 px-4 text-right">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                                        <?php foreach ($users as $u): ?>
                                            <tr class="hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                                                <td class="py-3 px-4 text-sm font-bold"><?php echo $u['username']; ?></td>
                                                <td class="py-3 px-4 text-sm"><?php echo $u['full_name']; ?></td>
                                                <td class="py-3 px-4"><span class="px-2 py-1 bg-primary/10 text-primary rounded text-[10px] font-bold uppercase"><?php echo $u['role']; ?></span></td>
                                                <td class="py-3 px-4">
                                                    <span class="px-2 py-1 rounded text-[10px] font-bold uppercase <?php echo $u['status'] === 'Active' ? 'bg-emerald-500/10 text-emerald-500' : 'bg-red-500/10 text-red-500'; ?>">
                                                        <?php echo $u['status']; ?>
                                                    </span>
                                                </td>
                                                <td class="py-3 px-4 text-right">
                                                    <a href="?section=users&edit=<?php echo $u['id']; ?>" class="text-primary font-bold text-xs uppercase hover:underline mr-3">Editar</a>
                                                    <?php if ($isAdmin): ?>
                                                        <form method="POST" class="inline" onsubmit="return confirm('¿Eliminar usuario definitivamente?');">
                                                            <input type="hidden" name="action" value="delete_user">
                                                            <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                                            <button class="text-red-500 font-bold text-xs uppercase hover:underline">Eliminar</button>
                                                        </form>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                             </div>

                    <?php elseif ($currentSection === 'brands'):
                        $brands = $db->query("SELECT * FROM brands ORDER BY name")->fetchAll();
                        ?>
                            <!-- ABM MARCAS -->
                            <div class="bg-white dark:bg-[#16202e] p-8 rounded-2xl border border-slate-200 dark:border-[#233348] max-w-2xl">
                                <h3 class="text-xl font-bold mb-6">Gestión de Marcas</h3>
                            
                                <form method="POST" class="flex gap-4 mb-8">
                                    <input type="hidden" name="action" value="create_brand">
                                    <input type="text" name="name" placeholder="Nueva Marca..." required class="flex-1 bg-slate-50 dark:bg-[#101822] rounded-xl border-none">
                                    <button class="bg-primary text-white px-6 py-3 rounded-xl font-bold uppercase text-sm shadow">Agregar</button>
                                </form>

                                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                    <?php foreach ($brands as $b): ?>
                                        <div class="bg-slate-50 dark:bg-white/5 p-3 rounded-lg flex items-center justify-between group">
                                            <span class="font-bold text-sm"><?php echo $b['name']; ?></span>
                                            <?php if ($isAdmin): ?>
                                                <form method="POST" onsubmit="return confirm('¿Borrar?');">
                                                    <input type="hidden" name="action" value="delete_brand">
                                                    <input type="hidden" name="id" value="<?php echo $b['id']; ?>">
                                                    <button class="text-red-500 opacity-0 group-hover:opacity-100 transition-opacity"><span class="material-symbols-outlined text-lg">delete</span></button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                    <?php elseif ($currentSection === 'clients'): ?>
                            <!-- ABM CLIENTES (Redirect or Include) -->
                            <?php include 'config_entities_partial.php'; ?>

                    <?php elseif ($currentSection === 'reports'): ?>
                            <div class="bg-white dark:bg-[#16202e] p-8 rounded-2xl border border-slate-200 dark:border-[#233348] text-center py-20">
                                <div class="inline-flex bg-cyan-500/10 p-4 rounded-full text-cyan-500 mb-4">
                                    <span class="material-symbols-outlined text-4xl">bar_chart</span>
                                </div>
                                <h3 class="text-xl font-bold uppercase mb-2">Informes del Sistema</h3>
                                <p class="text-slate-500 text-sm max-w-md mx-auto">Selecciona un informe desde el panel lateral "Contabilidad > Informes" para ver las estadísticas detalladas.</p>
                            </div>

                    <?php elseif ($currentSection === 'prices'): 
                            // Load Price Config
                            $priceConfig = json_decode(file_get_contents(__DIR__ . '/config_prices.json') ?: '{"gremio": 25, "web": 40, "mostrador": 55}', true);
                    ?>
                            <!-- CONFIGURACION PRECIOS -->
                            <div class="bg-white dark:bg-[#16202e] p-8 rounded-2xl border border-slate-200 dark:border-[#233348] max-w-3xl">
                                <h3 class="text-xl font-bold mb-6 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-yellow-500">price_change</span>
                                    Configuración de Listas de Precios
                                </h3>
                                
                                <form method="POST" class="space-y-6">
                                    <input type="hidden" name="action" value="save_price_config">
                                    
                                    <div class="p-4 bg-blue-50 dark:bg-blue-900/10 rounded-xl border border-blue-100 dark:border-blue-900/20 text-xs text-blue-600 dark:text-blue-400 mb-4">
                                        <strong>Referencia:</strong> Los precios se calculan automáticamente sumando el porcentaje configurado al <strong>Costo de Compra</strong> del producto.
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                        <div>
                                            <label class="block text-xs font-bold uppercase text-slate-500 mb-2">Lista Gremio (%)</label>
                                            <div class="relative">
                                                <input type="number" name="gremio" step="0.1" value="<?php echo $priceConfig['gremio']; ?>" class="w-full pl-4 pr-8 py-2 bg-slate-50 dark:bg-[#101822] rounded-lg border-none font-bold">
                                                <span class="absolute right-3 top-2 text-slate-400">%</span>
                                            </div>
                                            <p class="text-[10px] text-slate-400 mt-1">Costo + Margen</p>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold uppercase text-slate-500 mb-2">Lista Web (%)</label>
                                            <div class="relative">
                                                <input type="number" name="web" step="0.1" value="<?php echo $priceConfig['web']; ?>" class="w-full pl-4 pr-8 py-2 bg-slate-50 dark:bg-[#101822] rounded-lg border-none font-bold">
                                                <span class="absolute right-3 top-2 text-slate-400">%</span>
                                            </div>
                                            <p class="text-[10px] text-slate-400 mt-1">Transf. / Efectivo</p>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold uppercase text-slate-500 mb-2">Lista Mostrador (%)</label>
                                            <div class="relative">
                                                <input type="number" name="mostrador" step="0.1" value="<?php echo $priceConfig['mostrador']; ?>" class="w-full pl-4 pr-8 py-2 bg-slate-50 dark:bg-[#101822] rounded-lg border-none font-bold">
                                                <span class="absolute right-3 top-2 text-slate-400">%</span>
                                            </div>
                                            <p class="text-[10px] text-slate-400 mt-1">Público General</p>
                                        </div>
                                    </div>

                                    <div class="pt-4 border-t border-slate-100 dark:border-white/5 flex justify-end">
                                        <button class="bg-primary text-white px-6 py-3 rounded-xl font-bold uppercase text-sm shadow-lg hover:scale-105 transition-transform">Guardar Porcentajes</button>
                                    </div>
                                </form>
                            </div>

                    <?php elseif ($currentSection === 'crm'): ?>
                             <div class="bg-white dark:bg-[#16202e] p-8 rounded-2xl border border-slate-200 dark:border-[#233348] text-center py-20">
                                <h3 class="text-xl font-bold uppercase text-slate-400">ABM CRM - Próximamente</h3>
                            </div>
                    
                    <?php elseif ($currentSection === 'purchases'): 
                         // Logic for Purchases ABM (placeholder for now)
                    ?>
                             <div class="bg-white dark:bg-[#16202e] p-8 rounded-2xl border border-slate-200 dark:border-[#233348] text-center py-20">
                                <h3 class="text-xl font-bold uppercase text-slate-400">ABM Compras - Próximamente</h3>
                            </div>

                    <?php else: ?>
                            <!-- DEFAULT / FALLBACK -->
                            <div class="text-center py-20">
                                <h3 class="text-xl text-slate-400 font-bold uppercase">Sección no encontrada: <?php echo htmlspecialchars($currentSection); ?></h3>
                                <a href="configuration.php?section=main" class="text-primary mt-4 inline-block font-bold">Volver al Centro de Configuración</a>
                            </div>
                    <?php endif; ?>

                </div>
            </div>
        </main>
    </div>
</body>
</html>