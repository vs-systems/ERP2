<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/clientes/Client.php';

use Vsys\Modules\Clientes\Client;
$clientModule = new Client();
$db = Vsys\Lib\Database::getInstance();

$type = $_GET['type'] ?? 'client'; // 'client' or 'supplier'
$id = $_GET['id'] ?? $_GET['edit'] ?? null;
$message = '';
$messageType = 'success';

// Data for editing
$editData = [
    'id' => '',
    'name' => '',
    'fantasy_name' => '',
    'tax_id' => '',
    'document_number' => '',
    'contact_person' => '',
    'email' => '',
    'phone' => '',
    'mobile' => '',
    'address' => '',
    'delivery_address' => '',
    'tax_category' => ($type == 'client' ? 'Consumidor Final' : 'No Aplica'),
    'default_voucher_type' => 'Factura',
    'payment_condition' => 'Contado',
    'preferred_payment_method' => 'Transferencia',
    'is_enabled' => 1,
    'is_retention_agent' => 0,
    'seller_id' => null,
    'client_profile' => 'Otro',
    'is_verified' => 0
];

if ($id) {
    $stmt = $db->prepare("SELECT * FROM entities WHERE id = ?");
    $stmt->execute([$id]);
    $res = $stmt->fetch();
    if ($res)
        $editData = $res;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'id' => $_POST['id'] ?? null,
        'type' => $type,
        'tax_id' => $_POST['tax_id'],
        'document_number' => $_POST['document_number'],
        'name' => $_POST['name'],
        'fantasy_name' => $_POST['fantasy_name'],
        'contact' => $_POST['contact_person'],
        'email' => $_POST['email'],
        'phone' => $_POST['phone'],
        'mobile' => $_POST['mobile'],
        'address' => $_POST['address'],
        'delivery_address' => $_POST['delivery_address'],
        'default_voucher' => $_POST['default_voucher_type'],
        'tax_category' => $_POST['tax_category'],
        'is_enabled' => isset($_POST['is_enabled']) ? 1 : 0,
        'retention' => isset($_POST['is_retention_agent']) ? 1 : 0,
        'payment_condition' => $_POST['payment_condition'],
        'payment_method' => $_POST['payment_method'],
        'seller_id' => !empty($_POST['seller_id']) ? $_POST['seller_id'] : null,
        'client_profile' => $_POST['client_profile'] ?? 'Otro',
        'is_verified' => isset($_POST['is_verified']) ? 1 : 0
    ];

    if ($clientModule->saveClient($data)) {
        header("Location: " . ($type == 'client' ? 'clientes.php' : 'proveedores.php') . "?success=1");
        exit;
    } else {
        $message = "Error al guardar.";
        $messageType = "error";
    }
}
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $id ? 'Editar' : 'Nuevo'; ?> <?php echo $type == 'client' ? 'Cliente' : 'Proveedor'; ?> - VS
        System</title>
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
                        <span
                            class="material-symbols-outlined text-2xl"><?php echo $type == 'client' ? 'person' : 'factory'; ?></span>
                    </div>
                    <h2 class="dark:text-white text-slate-800 font-bold text-lg uppercase tracking-tight">
                        <?php echo $id ? 'Editar' : 'Nuevo'; ?>
                        <?php echo $type == 'client' ? 'Cliente' : 'Proveedor'; ?>
                    </h2>
                </div>
            </header>

            <!-- Content Area -->
            <div class="flex-1 overflow-y-auto p-6 space-y-6">
                <div class="max-w-[1200px] mx-auto">

                    <form method="POST"
                        class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl shadow-xl dark:shadow-none transition-colors p-8 space-y-8">
                        <input type="hidden" name="id" value="<?php echo $editData['id']; ?>">

                        <!-- Section: Identidad -->
                        <div class="space-y-6">
                            <div class="flex items-center gap-2 border-b border-slate-100 dark:border-[#233348] pb-2">
                                <span class="material-symbols-outlined text-primary text-sm">badge</span>
                                <h3 class="text-xs font-bold uppercase tracking-widest text-slate-400">Datos de
                                    Identidad</h3>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="col-span-1 md:col-span-1">
                                    <label
                                        class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Razón
                                        Social / Nombre</label>
                                    <input type="text" name="name" value="<?php echo $editData['name']; ?>" required
                                        class="form-input" placeholder="Nombre completo">
                                </div>
                                <div>
                                    <label
                                        class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Nombre
                                        de Fantasía</label>
                                    <input type="text" name="fantasy_name"
                                        value="<?php echo $editData['fantasy_name']; ?>" class="form-input"
                                        placeholder="Nombre comercial">
                                </div>
                                <div>
                                    <label
                                        class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">CUIT
                                        / CUIL</label>
                                    <input type="text" name="tax_id" value="<?php echo $editData['tax_id']; ?>"
                                        class="form-input" placeholder="00-00000000-0">
                                </div>
                                <div>
                                    <label
                                        class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">DNI
                                        / Documento</label>
                                    <input type="text" name="document_number"
                                        value="<?php echo $editData['document_number']; ?>" class="form-input">
                                </div>
                                <div>
                                    <label
                                        class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Persona
                                        de Contacto</label>
                                    <input type="text" name="contact_person"
                                        value="<?php echo $editData['contact_person']; ?>" class="form-input">
                                </div>
                                <div>
                                    <label
                                        class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Categoría
                                        Fiscal</label>
                                    <select name="tax_category" class="form-input">
                                        <?php if ($type == 'client'): ?>
                                            <option value="Responsable Inscripto" <?php echo $editData['tax_category'] == 'Responsable Inscripto' ? 'selected' : ''; ?>>
                                                Responsable Inscripto</option>
                                            <option value="Monotributo" <?php echo $editData['tax_category'] == 'Monotributo' ? 'selected' : ''; ?>>Monotributo</option>
                                            <option value="Exento" <?php echo $editData['tax_category'] == 'Exento' ? 'selected' : ''; ?>>Exento</option>
                                            <option value="Consumidor Final" <?php echo $editData['tax_category'] == 'Consumidor Final' ? 'selected' : ''; ?>>
                                                Consumidor Final</option>
                                        <?php else: ?>
                                            <option value="Responsable Inscripto" <?php echo $editData['tax_category'] == 'Responsable Inscripto' ? 'selected' : ''; ?>>
                                                Responsable Inscripto</option>
                                            <option value="Monotributo" <?php echo $editData['tax_category'] == 'Monotributo' ? 'selected' : ''; ?>>Monotributo</option>
                                            <option value="No Aplica" <?php echo $editData['tax_category'] == 'No Aplica' ? 'selected' : ''; ?>>No Aplica</option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Section: Contacto -->
                        <div class="space-y-6">
                            <div class="flex items-center gap-2 border-b border-slate-100 dark:border-[#233348] pb-2">
                                <span class="material-symbols-outlined text-primary text-sm">contact_mail</span>
                                <h3 class="text-xs font-bold uppercase tracking-widest text-slate-400">Contacto y
                                    Localización</h3>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label
                                        class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Email</label>
                                    <input type="email" name="email" value="<?php echo $editData['email']; ?>"
                                        class="form-input" placeholder="correo@ejemplo.com">
                                </div>
                                <div>
                                    <label
                                        class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Teléfono</label>
                                    <input type="text" name="phone" value="<?php echo $editData['phone']; ?>"
                                        class="form-input">
                                </div>
                                <div>
                                    <label
                                        class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Móvil
                                        / WhatsApp</label>
                                    <input type="text" name="mobile" value="<?php echo $editData['mobile']; ?>"
                                        class="form-input">
                                </div>
                                <div class="md:col-span-3 grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label
                                            class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Domicilio
                                            Fiscal/Legal</label>
                                        <textarea name="address" rows="2"
                                            class="form-input"><?php echo $editData['address']; ?></textarea>
                                    </div>
                                    <div>
                                        <label
                                            class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Lugar
                                            de Entrega</label>
                                        <textarea name="delivery_address" rows="2"
                                            class="form-input"><?php echo $editData['delivery_address']; ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section: Comercial -->
                        <div class="space-y-6">
                            <div class="flex items-center gap-2 border-b border-slate-100 dark:border-[#233348] pb-2">
                                <span class="material-symbols-outlined text-primary text-sm">payments</span>
                                <h3 class="text-xs font-bold uppercase tracking-widest text-slate-400">Configuración
                                    Comercial</h3>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label
                                        class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Comprobante
                                        Defecto</label>
                                    <select name="default_voucher_type" class="form-input">
                                        <option value="Factura" <?php echo $editData['default_voucher_type'] == 'Factura' ? 'selected' : ''; ?>>Factura</option>
                                        <option value="Remito" <?php echo $editData['default_voucher_type'] == 'Remito' ? 'selected' : ''; ?>>Remito</option>
                                        <option value="Ninguno" <?php echo $editData['default_voucher_type'] == 'Ninguno' ? 'selected' : ''; ?>>Ninguno</option>
                                    </select>
                                </div>
                                <div>
                                    <label
                                        class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Condición
                                        Pago</label>
                                    <select name="payment_condition" class="form-input">
                                        <option value="Contado" <?php echo $editData['payment_condition'] == 'Contado' ? 'selected' : ''; ?>>Contado</option>
                                        <option value="Cta Cte" <?php echo strpos($editData['payment_condition'], 'Cta Cte') !== false ? 'selected' : ''; ?>>Cta Cte</option>
                                    </select>
                                </div>
                                <div>
                                    <label
                                        class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Forma
                                        de Pago</label>
                                    <select name="payment_method" class="form-input">
                                        <option value="Transferencia" <?php echo ($editData['preferred_payment_method'] ?? '') == 'Transferencia' ? 'selected' : ''; ?>>Transferencia</option>
                                        <option value="Efectivo" <?php echo ($editData['preferred_payment_method'] ?? '') == 'Efectivo' ? 'selected' : ''; ?>>Efectivo</option>
                                        <option value="Mercado Pago" <?php echo ($editData['preferred_payment_method'] ?? '') == 'Mercado Pago' ? 'selected' : ''; ?>>Mercado Pago</option>
                                    </select>
                                </div>
                                <?php if ($type == 'client'): ?>
                                    <div>
                                        <label
                                            class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Vendedor
                                            Asignado</label>
                                        <select name="seller_id" class="form-input">
                                            <option value="">-- Sin Vendedor --</option>
                                            <?php
                                            $sellers = $db->query("SELECT id, username FROM users WHERE role = 'Vendedor' OR role = 'Ventas'")->fetchAll();
                                            foreach ($sellers as $s):
                                                ?>
                                                <option value="<?php echo $s['id']; ?>" <?php echo $editData['seller_id'] == $s['id'] ? 'selected' : ''; ?>>
                                                    <?php echo $s['username']; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label
                                            class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Perfil
                                            de Cliente</label>
                                        <select name="client_profile" class="form-input">
                                            <option value="Otro" <?php echo $editData['client_profile'] == 'Otro' ? 'selected' : ''; ?>>Otro</option>
                                            <option value="Gremio" <?php echo $editData['client_profile'] == 'Gremio' ? 'selected' : ''; ?>>Gremio</option>
                                            <option value="Web" <?php echo $editData['client_profile'] == 'Web' ? 'selected' : ''; ?>>Web</option>
                                            <option value="ML" <?php echo $editData['client_profile'] == 'ML' ? 'selected' : ''; ?>>Mercado Libre</option>
                                        </select>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-6 pt-4">
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input type="checkbox" name="is_enabled" <?php echo $editData['is_enabled'] ? 'checked' : ''; ?>
                                    class="rounded text-primary focus:ring-primary bg-slate-100 dark:bg-[#101822] border-slate-200 dark:border-[#233348]">
                                <span
                                    class="text-xs font-bold uppercase tracking-widest text-slate-500 group-hover:text-primary transition-colors">Habilitado</span>
                            </label>

                            <?php if ($type == 'client'): ?>
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <input type="checkbox" name="is_retention_agent" <?php echo $editData['is_retention_agent'] ? 'checked' : ''; ?>
                                        class="rounded text-primary focus:ring-primary bg-slate-100 dark:bg-[#101822] border-slate-200 dark:border-[#233348]">
                                    <span
                                        class="text-xs font-bold uppercase tracking-widest text-slate-500 group-hover:text-primary transition-colors">Agente
                                        Retención</span>
                                </label>
                            <?php endif; ?>
                        </div>

                        <div class="flex gap-4 pt-6 border-t border-slate-100 dark:border-[#233348]">
                            <button type="submit"
                                class="flex-1 bg-primary hover:bg-blue-600 text-white font-bold py-4 rounded-2xl text-xs uppercase tracking-widest shadow-xl shadow-primary/20 active:scale-[0.98] transition-all flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined">save</span> GUARDAR
                                <?php echo strtoupper($type == 'client' ? 'Cliente' : 'Proveedor'); ?>
                            </button>
                            <a href="<?php echo $type == 'client' ? 'clientes.php' : 'proveedores.php'; ?>"
                                class="flex-1 bg-slate-100 dark:bg-white/5 border border-slate-200 dark:border-[#233348] text-slate-500 font-bold py-4 rounded-2xl text-center text-xs uppercase tracking-widest hover:bg-slate-200 transition-all flex items-center justify-center">CANCELAR</a>
                        </div>
                    </form>

                </div>
            </div>
        </main>
    </div>
</body>

</html>