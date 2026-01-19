<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/logistica/Logistics.php';

use Vsys\Modules\Logistica\Logistics;
$logistics = new Logistics();
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'id' => $_POST['id'] ?? null,
        'name' => $_POST['name'] ?? '',
        'contact_person' => $_POST['contact_person'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'email' => $_POST['email'] ?? '',
        'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];
    $logistics->saveTransport($data);
    $message = $data['id'] ? "Transportista actualizado." : "Transportista registrado.";
}

$transports = $logistics->getTransports(false);
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Transportes - VS System</title>
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
        }

        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #101822;
        }

        ::-webkit-scrollbar-thumb {
            background: #233348;
            border-radius: 3px;
        }

        .form-input {
            @apply w-full bg-slate-50 dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-xl text-sm dark:text-white text-slate-800 focus:ring-primary focus:border-primary transition-colors;
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
                        <span class="material-symbols-outlined text-2xl">shipping</span>
                    </div>
                    <h2 class="dark:text-white text-slate-800 font-bold text-lg uppercase tracking-tight">Gestión de
                        Transportes</h2>
                </div>
            </header>

            <!-- Content Area -->
            <div class="flex-1 overflow-y-auto p-6 space-y-8">
                <div class="max-w-[1400px] mx-auto space-y-8">

                    <?php if ($message): ?>
                        <div
                            class="flex items-center gap-3 p-4 rounded-2xl border bg-green-500/10 border-green-500/20 text-green-500 animate-in fade-in slide-in-from-top-4 duration-300">
                            <span class="material-symbols-outlined">check_circle</span>
                            <span class="text-sm font-bold uppercase tracking-widest"><?php echo $message; ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                        <!-- Form Section -->
                        <div class="lg:col-span-4 h-fit">
                            <div
                                class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-6 shadow-xl dark:shadow-none transition-colors">
                                <div
                                    class="flex items-center gap-2 mb-6 border-b border-slate-100 dark:border-[#233348] pb-4">
                                    <span class="material-symbols-outlined text-primary"
                                        id="form-icon">local_shipping</span>
                                    <h3 class="font-bold text-lg dark:text-white text-slate-800" id="form-title">Nueva
                                        Empresa</h3>
                                </div>

                                <form method="POST" class="space-y-4">
                                    <?php
                                    $editData = ['id' => '', 'name' => '', 'contact_person' => '', 'phone' => '', 'email' => '', 'is_active' => 1];
                                    if (isset($_GET['edit'])) {
                                        foreach ($transports as $t)
                                            if ($t['id'] == $_GET['edit'])
                                                $editData = $t;
                                    }
                                    ?>
                                    <input type="hidden" name="id" value="<?php echo $editData['id']; ?>">

                                    <div>
                                        <label
                                            class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Nombre
                                            Comercial</label>
                                        <input type="text" name="name" value="<?php echo $editData['name']; ?>" required
                                            placeholder="Ej: Via Cargo" class="form-input">
                                    </div>

                                    <div>
                                        <label
                                            class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Contacto
                                            Principal</label>
                                        <input type="text" name="contact_person"
                                            value="<?php echo $editData['contact_person']; ?>"
                                            placeholder="Nombre del contacto" class="form-input">
                                    </div>

                                    <div>
                                        <label
                                            class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Teléfono</label>
                                        <input type="tel" name="phone" value="<?php echo $editData['phone']; ?>"
                                            placeholder="+54 11 ..." class="form-input">
                                    </div>

                                    <div>
                                        <label
                                            class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Email</label>
                                        <input type="email" name="email" value="<?php echo $editData['email']; ?>"
                                            placeholder="email@transporte.com" class="form-input">
                                    </div>

                                    <div class="flex items-center gap-3 pt-2">
                                        <input type="checkbox" name="is_active" id="is_active" <?php echo $editData['is_active'] ? 'checked' : ''; ?>
                                            class="rounded text-primary focus:ring-primary bg-slate-100 dark:bg-[#101822] border-slate-200 dark:border-[#233348]">
                                        <label for="is_active"
                                            class="text-sm font-medium text-slate-600 dark:text-slate-400">Empresa
                                            Habilitada</label>
                                    </div>

                                    <div class="pt-4 flex gap-2">
                                        <?php if ($editData['id']): ?>
                                            <a href="config_transports.php"
                                                class="flex-1 bg-slate-100 dark:bg-white/5 border border-slate-200 dark:border-[#233348] text-slate-500 font-bold py-3 rounded-xl text-center text-[10px] uppercase tracking-widest hover:bg-slate-200 transition-all flex items-center justify-center">CANCELAR</a>
                                        <?php endif; ?>
                                        <button type="submit"
                                            class="flex-[2] bg-primary hover:bg-blue-600 text-white font-bold py-3 rounded-xl text-xs uppercase tracking-widest shadow-lg shadow-primary/20 active:scale-95 transition-all flex items-center justify-center gap-2">
                                            <span class="material-symbols-outlined text-sm">save</span>
                                            <?php echo $editData['id'] ? 'ACTUALIZAR' : 'REGISTRAR'; ?>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Table Section -->
                        <div class="lg:col-span-8">
                            <div
                                class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl overflow-hidden shadow-xl dark:shadow-none transition-colors text-sm">
                                <table class="w-full text-left">
                                    <thead
                                        class="bg-slate-50 dark:bg-[#101822]/50 border-b border-slate-200 dark:border-[#233348]">
                                        <tr class="text-slate-500 text-[10px] font-bold uppercase tracking-widest">
                                            <th class="px-6 py-4">Transportista</th>
                                            <th class="px-6 py-4">Contacto</th>
                                            <th class="px-6 py-4">Estado</th>
                                            <th class="px-6 py-4 text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-[#233348]">
                                        <?php foreach ($transports as $t): ?>
                                            <tr
                                                class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors group">
                                                <td class="px-6 py-5">
                                                    <div class="font-bold dark:text-white text-slate-800">
                                                        <?php echo $t['name']; ?></div>
                                                    <div class="text-xs text-slate-500"><?php echo $t['email']; ?></div>
                                                </td>
                                                <td class="px-6 py-5">
                                                    <div class="dark:text-slate-300"><?php echo $t['contact_person']; ?>
                                                    </div>
                                                    <div class="text-xs text-slate-500 font-mono"><?php echo $t['phone']; ?>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-5">
                                                    <span
                                                        class="text-[10px] font-bold uppercase py-1 px-2 rounded-full <?php echo $t['is_active'] ? 'bg-green-500/10 text-green-500' : 'bg-red-500/10 text-red-500'; ?>">
                                                        <?php echo $t['is_active'] ? 'HABILITADO' : 'INACTIVO'; ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-5">
                                                    <div class="flex items-center justify-center">
                                                        <a href="config_transports.php?edit=<?php echo $t['id']; ?>"
                                                            class="p-2 rounded-lg hover:bg-primary/10 text-slate-400 hover:text-primary transition-all"
                                                            title="Editar">
                                                            <span class="material-symbols-outlined text-[18px]">edit</span>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($transports)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-20 text-slate-500 italic">No hay
                                                    transportistas registrados.</td>
                                            </tr>
                                        <?php endif; ?>
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