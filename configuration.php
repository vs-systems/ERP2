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

// Routing
$section = $_GET['section'] ?? 'main';
$action = $_POST['action'] ?? '';
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
    // Unset action
    unset($data['action']);
    file_put_contents(__DIR__ . '/config_company.json', json_encode($data, JSON_PRETTY_PRINT));
}

// --- ACTIONS HANDLER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Save Company Data
    if ($action === 'save_company' && $isAdmin) {
        saveCompanyConfig($_POST);
        $message = "Datos de empresa guardados.";
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
            // Simplified user creation logic
            $sql = "INSERT INTO users (username, full_name, email, role, password_hash, status) VALUES (?, ?, ?, ?, ?, 'Active')";
            $passHash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $db->prepare($sql);
            $stmt->execute([$_POST['username'], $_POST['full_name'], $_POST['email'], $_POST['role'], $passHash]);
            $message = "Usuario creado exiting.";
            $status = 'success';
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
            $status = 'error';
        }
    }

    if ($action === 'delete_user' && $isAdmin) {
        $db->prepare("DELETE FROM users WHERE id = ?")->execute([$_POST['id']]);
        $message = "Usuario eliminado.";
        $status = 'success';
    }

    // ABM Brands
    if ($action === 'create_brand') {
        try {
            $db->prepare("INSERT INTO brands (name) VALUES (?)")->execute([$_POST['name']]);
            $message = "Marca agregada.";
            $status = 'success';
        } catch (Exception $e) {
            $message = "Error al crear marca.";
            $status = 'error';
        }
    }

    if ($action === 'delete_brand' && $isAdmin) {
        $db->prepare("DELETE FROM brands WHERE id = ?")->execute([$_POST['id']]);
        $message = "Marca eliminada.";
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
                    <?php if ($section !== 'main'): ?>
                        <a href="configuration.php"
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

                    <?php if ($section === 'main'): ?>
                        <!-- MAIN GRID LAYOUT -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">

                            <!-- Datos Empresa -->
                            <a href="?section=company"
                                class="group bg-white dark:bg-[#16202e] p-6 rounded-2xl border border-slate-200 dark:border-[#233348] hover:border-primary/50 transition-all hover:shadow-lg hover:shadow-primary/10">
                                <div
                                    class="w-12 h-12 rounded-xl bg-blue-500/10 text-blue-500 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <span class="material-symbols-outlined text-3xl">domain</span>
                                </div>
                                <h3 class="font-bold text-lg mb-1">Datos Empresa</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Razón social, logo, dirección y
                                    contacto.</p>
                            </a>

                            <!-- ABM Presupuestos -->
                            <a href="?section=budget"
                                class="group bg-white dark:bg-[#16202e] p-6 rounded-2xl border border-slate-200 dark:border-[#233348] hover:border-primary/50 transition-all hover:shadow-lg hover:shadow-primary/10">
                                <div
                                    class="w-12 h-12 rounded-xl bg-purple-500/10 text-purple-500 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <span class="material-symbols-outlined text-3xl">request_quote</span>
                                </div>
                                <h3 class="font-bold text-lg mb-1">ABM Presupuestos</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Configuración, validez y notas
                                    legales.</p>
                            </a>

                            <!-- ABM Clientes (Ahora redirige a la logica de entidades) -->
                            <a href="?section=clients"
                                class="group bg-white dark:bg-[#16202e] p-6 rounded-2xl border border-slate-200 dark:border-[#233348] hover:border-primary/50 transition-all hover:shadow-lg hover:shadow-primary/10">
                                <div
                                    class="w-12 h-12 rounded-xl bg-green-500/10 text-green-500 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <span class="material-symbols-outlined text-3xl">groups</span>
                                </div>
                                <h3 class="font-bold text-lg mb-1">ABM Clientes</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Gestión de base de datos de clientes.
                                </p>
                            </a>

                            <!-- ABM Usuarios -->
                            <a href="?section=users"
                                class="group bg-white dark:bg-[#16202e] p-6 rounded-2xl border border-slate-200 dark:border-[#233348] hover:border-primary/50 transition-all hover:shadow-lg hover:shadow-primary/10">
                                <div
                                    class="w-12 h-12 rounded-xl bg-orange-500/10 text-orange-500 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <span class="material-symbols-outlined text-3xl">manage_accounts</span>
                                </div>
                                <h3 class="font-bold text-lg mb-1">ABM Usuarios</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Administrar usuarios y permisos.</p>
                            </a>

                            <!-- ABM CRM -->
                            <a href="?section=crm"
                                class="group bg-white dark:bg-[#16202e] p-6 rounded-2xl border border-slate-200 dark:border-[#233348] hover:border-primary/50 transition-all hover:shadow-lg hover:shadow-primary/10">
                                <div
                                    class="w-12 h-12 rounded-xl bg-pink-500/10 text-pink-500 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <span class="material-symbols-outlined text-3xl">filter_alt</span>
                                </div>
                                <h3 class="font-bold text-lg mb-1">ABM CRM</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Pipelines, estados y orígenes.</p>
                            </a>

                            <!-- ABM Marcas -->
                            <a href="?section=brands"
                                class="group bg-white dark:bg-[#16202e] p-6 rounded-2xl border border-slate-200 dark:border-[#233348] hover:border-primary/50 transition-all hover:shadow-lg hover:shadow-primary/10">
                                <div
                                    class="w-12 h-12 rounded-xl bg-indigo-500/10 text-indigo-500 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <span class="material-symbols-outlined text-3xl">sell</span>
                                </div>
                                <h3 class="font-bold text-lg mb-1">ABM Marcas</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Gestión de marcas de productos.</p>
                            </a>

                            <!-- Informes -->
                            <a href="?section=reports"
                                class="group bg-white dark:bg-[#16202e] p-6 rounded-2xl border border-slate-200 dark:border-[#233348] hover:border-primary/50 transition-all hover:shadow-lg hover:shadow-primary/10">
                                <div
                                    class="w-12 h-12 rounded-xl bg-cyan-500/10 text-cyan-500 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <span class="material-symbols-outlined text-3xl">bar_chart</span>
                                </div>
                                <h3 class="font-bold text-lg mb-1">Informes</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Configuración de reportes del sistema.
                                </p>
                            </a>
                        </div>

                    <?php elseif ($section === 'company'):
                        $company = loadCompanyConfig();
                        ?>
                        <!-- DATOS EMPRESA -->
                        <div
                            class="bg-white dark:bg-[#16202e] p-8 rounded-2xl border border-slate-200 dark:border-[#233348] max-w-3xl">
                            <h3 class="text-xl font-bold mb-6">Datos de la Empresa</h3>
                            <form method="POST" class="space-y-4">
                                <input type="hidden" name="action" value="save_company">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Nombre
                                            Fantasía</label>
                                        <input type="text" name="fantasy_name"
                                            value="<?php echo $company['fantasy_name']; ?>"
                                            class="w-full bg-slate-50 dark:bg-[#101822] rounded-lg border-none">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Razón
                                            Social</label>
                                        <input type="text" name="company_name"
                                            value="<?php echo $company['company_name']; ?>"
                                            class="w-full bg-slate-50 dark:bg-[#101822] rounded-lg border-none">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">CUIT</label>
                                        <input type="text" name="tax_id" value="<?php echo $company['tax_id']; ?>"
                                            class="w-full bg-slate-50 dark:bg-[#101822] rounded-lg border-none">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Email
                                            Sistema</label>
                                        <input type="email" name="email" value="<?php echo $company['email']; ?>"
                                            class="w-full bg-slate-50 dark:bg-[#101822] rounded-lg border-none">
                                    </div>
                                    <div class="col-span-2">
                                        <label
                                            class="block text-xs font-bold uppercase text-slate-500 mb-1">Dirección</label>
                                        <input type="text" name="address" value="<?php echo $company['address']; ?>"
                                            class="w-full bg-slate-50 dark:bg-[#101822] rounded-lg border-none">
                                    </div>
                                </div>
                                <div class="pt-4">
                                    <button
                                        class="bg-primary text-white px-6 py-3 rounded-xl font-bold uppercase text-sm shadow-lg hover:scale-105 transition-transform">Guardar
                                        Cambios</button>
                                </div>
                            </form>
                        </div>

                    <?php elseif ($section === 'budget'):
                        $budgetConfig = json_decode(file_get_contents(__DIR__ . '/config_budget.json') ?: '{}', true);
                        ?>
                        <!-- ABM PRESUPUESTOS -->
                        <div
                            class="bg-white dark:bg-[#16202e] p-8 rounded-2xl border border-slate-200 dark:border-[#233348] max-w-3xl">
                            <h3 class="text-xl font-bold mb-6">Configuración de Presupuestos</h3>
                            <form method="POST" class="space-y-4">
                                <input type="hidden" name="action" value="save_budget_config">
                                <div>
                                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Validez por defecto
                                        (Horas)</label>
                                    <input type="number" name="validity_hours"
                                        value="<?php echo $budgetConfig['validity_hours'] ?? 48; ?>"
                                        class="w-32 bg-slate-50 dark:bg-[#101822] rounded-lg border-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Notas Legales /
                                        Términos</label>
                                    <textarea name="legal_notes" rows="5"
                                        class="w-full bg-slate-50 dark:bg-[#101822] rounded-lg border-none"><?php echo $budgetConfig['legal_notes'] ?? ''; ?></textarea>
                                </div>
                                <div class="pt-4">
                                    <button
                                        class="bg-primary text-white px-6 py-3 rounded-xl font-bold uppercase text-sm shadow-lg hover:scale-105 transition-transform">Guardar
                                        Configuración</button>
                                </div>
                            </form>
                        </div>

                    <?php elseif ($section === 'users'):
                        $users = $db->query("SELECT * FROM users")->fetchAll();
                        ?>
                        <!-- ABM USUARIOS -->
                        <div
                            class="bg-white dark:bg-[#16202e] p-8 rounded-2xl border border-slate-200 dark:border-[#233348]">
                            <h3 class="text-xl font-bold mb-6">Gestión de Usuarios</h3>

                            <!-- New User Form -->
                            <form method="POST"
                                class="bg-slate-50 dark:bg-[#101822] p-4 rounded-xl mb-8 flex gap-4 items-end">
                                <input type="hidden" name="action" value="create_user">
                                <div class="flex-1">
                                    <label class="text-[10px] font-bold uppercase text-slate-500">Usuario</label>
                                    <input type="text" name="username" required
                                        class="w-full rounded-lg text-xs border-none">
                                </div>
                                <div class="flex-1">
                                    <label class="text-[10px] font-bold uppercase text-slate-500">Nombre</label>
                                    <input type="text" name="full_name" required
                                        class="w-full rounded-lg text-xs border-none">
                                </div>
                                <div class="flex-1">
                                    <label class="text-[10px] font-bold uppercase text-slate-500">Email</label>
                                    <input type="email" name="email" required class="w-full rounded-lg text-xs border-none">
                                </div>
                                <div class="flex-1">
                                    <label class="text-[10px] font-bold uppercase text-slate-500">Rol</label>
                                    <select name="role" class="w-full rounded-lg text-xs border-none">
                                        <option value="vendedor">Vendedor</option>
                                        <option value="admin">Admin</option>
                                        <option value="logistica">Logística</option>
                                    </select>
                                </div>
                                <div class="flex-1">
                                    <label class="text-[10px] font-bold uppercase text-slate-500">Contraseña</label>
                                    <input type="password" name="password" required
                                        class="w-full rounded-lg text-xs border-none">
                                </div>
                                <button
                                    class="bg-primary text-white px-4 py-2 rounded-lg font-bold text-xs uppercase shadow">Crear</button>
                            </form>

                            <table class="w-full text-left">
                                <thead
                                    class="border-b border-slate-200 dark:border-white/10 uppercase text-xs font-bold text-slate-500">
                                    <tr>
                                        <th class="pb-3">Usuario</th>
                                        <th class="pb-3">Nombre</th>
                                        <th class="pb-3">Rol</th>
                                        <th class="pb-3 text-right">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                                    <?php foreach ($users as $u): ?>
                                        <tr>
                                            <td class="py-3"><?php echo $u['username']; ?></td>
                                            <td class="py-3"><?php echo $u['full_name']; ?></td>
                                            <td class="py-3"><span
                                                    class="px-2 py-1 bg-primary/10 text-primary rounded text-xs font-bold uppercase"><?php echo $u['role']; ?></span>
                                            </td>
                                            <td class="py-3 text-right">
                                                <?php if ($isAdmin): ?>
                                                    <form method="POST" class="inline"
                                                        onsubmit="return confirm('¿Eliminar usuario?');">
                                                        <input type="hidden" name="action" value="delete_user">
                                                        <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                                        <button
                                                            class="text-red-500 font-bold text-xs uppercase hover:underline">Eliminar</button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                    <?php elseif ($section === 'brands'):
                        $brands = $db->query("SELECT * FROM brands ORDER BY name")->fetchAll();
                        ?>
                        <!-- ABM MARCAS -->
                        <div
                            class="bg-white dark:bg-[#16202e] p-8 rounded-2xl border border-slate-200 dark:border-[#233348] max-w-2xl">
                            <h3 class="text-xl font-bold mb-6">Gestión de Marcas</h3>

                            <form method="POST" class="flex gap-4 mb-8">
                                <input type="hidden" name="action" value="create_brand">
                                <input type="text" name="name" placeholder="Nueva Marca..." required
                                    class="flex-1 bg-slate-50 dark:bg-[#101822] rounded-xl border-none">
                                <button
                                    class="bg-primary text-white px-6 py-3 rounded-xl font-bold uppercase text-sm shadow">Agregar</button>
                            </form>

                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                <?php foreach ($brands as $b): ?>
                                    <div
                                        class="bg-slate-50 dark:bg-white/5 p-3 rounded-lg flex items-center justify-between group">
                                        <span class="font-bold text-sm"><?php echo $b['name']; ?></span>
                                        <?php if ($isAdmin): ?>
                                            <form method="POST" onsubmit="return confirm('¿Borrar?');">
                                                <input type="hidden" name="action" value="delete_brand">
                                                <input type="hidden" name="id" value="<?php echo $b['id']; ?>">
                                                <button
                                                    class="text-red-500 opacity-0 group-hover:opacity-100 transition-opacity"><span
                                                        class="material-symbols-outlined text-lg">delete</span></button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                    <?php elseif ($section === 'clients'): ?>
                        <!-- ABM CLIENTES (Redirect or Include) -->
                        <?php include 'config_entities_partial.php'; ?>

                    <?php else: ?>
                        <div class="text-center py-20">
                            <h3 class="text-xl text-slate-400 font-bold uppercase">Sección en Construcción</h3>
                            <a href="configuration.php" class="text-primary mt-4 inline-block font-bold">Volver</a>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </main>
    </div>
</body>

</html>