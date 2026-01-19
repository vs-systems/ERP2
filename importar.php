<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/catalogo/Catalog.php';

use Vsys\Modules\Catalogo\Catalog;

$message = '';
$messageType = 'success';
$catalog = new Catalog();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $type = $_POST['import_type'] ?? 'product';
    $providerId = $_POST['provider_id'] ?? null;

    $targetDir = __DIR__ . "/data/uploads/";
    if (!file_exists($targetDir))
        mkdir($targetDir, 0777, true);

    $extension = pathinfo($_FILES["csv_file"]["name"], PATHINFO_EXTENSION);
    $targetFile = $targetDir . "import_" . time() . "." . $extension;

    if (move_uploaded_file($_FILES["csv_file"]["tmp_name"], $targetFile)) {
        try {
            $count = 0;
            if ($type === 'product') {
                $count = $catalog->importProductsFromCsv($targetFile, $providerId);
            } elseif ($type === 'client' || $type === 'supplier') {
                $count = $catalog->importEntitiesFromCsv($targetFile, $type);
            }

            if ($count !== false) {
                $message = "¡Éxito! Se han procesado $count registros correctamente.";
            } else {
                throw new Exception("Error al procesar el archivo CSV.");
            }
        } catch (\Exception $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = 'error';
        }
    }
}

$providers = $catalog->getProviders();
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carga Masiva - VS System</title>
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

        .drop-zone {
            @apply border-2 border-dashed border-slate-200 dark:border-[#233348] rounded-2xl p-12 flex flex-col items-center justify-center transition-all cursor-pointer bg-slate-50/50 dark:bg-white/[0.02] hover:border-primary/50 hover:bg-primary/5;
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
                        <span class="material-symbols-outlined text-2xl">upload_file</span>
                    </div>
                    <h2 class="dark:text-white text-slate-800 font-bold text-lg uppercase tracking-tight">Carga Masiva
                        de Datos</h2>
                </div>
            </header>

            <!-- Content Area -->
            <div class="flex-1 overflow-y-auto p-6 space-y-6">
                <div class="max-w-[1000px] mx-auto space-y-8">

                    <div class="space-y-1">
                        <h1 class="text-2xl font-bold dark:text-white text-slate-800 tracking-tight">Importador
                            Centralizado</h1>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Actualice su catálogo de productos o base
                            de entidades mediante archivos CSV.</p>
                    </div>

                    <?php if ($message): ?>
                        <div
                            class="flex items-center gap-3 p-4 rounded-2xl border <?php echo $messageType === 'success' ? 'bg-green-500/10 border-green-500/20 text-green-500' : 'bg-red-500/10 border-red-500/20 text-red-500'; ?> animate-in fade-in slide-in-from-top-4 duration-300">
                            <span
                                class="material-symbols-outlined"><?php echo $messageType === 'success' ? 'check_circle' : 'error'; ?></span>
                            <span class="text-sm font-bold uppercase tracking-widest"><?php echo $message; ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div
                                class="bg-white dark:bg-[#16202e] p-6 rounded-2xl border border-slate-200 dark:border-[#233348] shadow-sm">
                                <label
                                    class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-3 ml-1">¿Qué
                                    desea importar?</label>
                                <select name="import_type" id="import_type" onchange="toggleProvider()"
                                    class="w-full bg-slate-50 dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-xl text-sm dark:text-white text-slate-800 focus:ring-primary focus:border-primary transition-colors">
                                    <option value="product">Catálogo de Productos</option>
                                    <option value="client">Base de Clientes</option>
                                    <option value="supplier">Base de Proveedores</option>
                                </select>
                            </div>

                            <div id="provider-selector"
                                class="bg-white dark:bg-[#16202e] p-6 rounded-2xl border border-slate-200 dark:border-[#233348] shadow-sm">
                                <label
                                    class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-3 ml-1">Proveedor
                                    (Solo Productos)</label>
                                <select name="provider_id"
                                    class="w-full bg-slate-50 dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-xl text-sm dark:text-white text-slate-800 focus:ring-primary focus:border-primary transition-colors">
                                    <option value="">-- Sin proveedor (Costo Base) --</option>
                                    <?php foreach ($providers as $p): ?>
                                        <option value="<?php echo $p['id']; ?>"><?php echo $p['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="drop-zone" onclick="document.getElementById('file-input').click()">
                            <div
                                class="bg-primary/10 p-4 rounded-full text-primary mb-4 transform group-hover:scale-110 transition-transform">
                                <span class="material-symbols-outlined text-4xl">cloud_upload</span>
                            </div>
                            <h3 class="text-lg font-bold mb-1" id="file-name">Seleccionar archivo CSV</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Haga clic o arrastre un archivo aquí
                            </p>
                            <input type="file" name="csv_file" id="file-input" accept=".csv" required class="hidden"
                                onchange="updateFileName()">
                        </div>

                        <div id="product-hint" class="p-6 bg-blue-500/5 border border-blue-500/10 rounded-2xl">
                            <div class="flex items-center gap-2 mb-2 text-blue-500">
                                <span class="material-symbols-outlined text-sm">info</span>
                                <span class="text-xs font-bold uppercase tracking-widest">Formato Requerido
                                    (Productos)</span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-mono leading-relaxed">SKU;
                                DESCRIPCION; MARCA; COSTO; IVA %; CATEGORIA; SUBCATEGORIA; PROVEEDOR</p>
                        </div>

                        <div id="entity-hint"
                            class="p-6 bg-purple-500/5 border border-purple-500/10 rounded-2xl hidden">
                            <div class="flex items-center gap-2 mb-2 text-purple-500">
                                <span class="material-symbols-outlined text-sm">info</span>
                                <span class="text-xs font-bold uppercase tracking-widest">Formato Requerido
                                    (Entidades)</span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-mono leading-relaxed">RAZON
                                SOCIAL; NOMBRE FANTASIA; CUIT; DNI; EMAIL; TELEFONO; CELULAR; CONTACTO; DIRECCION;
                                ENTREGA</p>
                        </div>

                        <button type="submit"
                            class="w-full bg-primary hover:bg-blue-600 text-white font-bold py-4 rounded-2xl text-xs uppercase tracking-widest shadow-xl shadow-primary/20 active:scale-[0.98] transition-all flex items-center justify-center gap-3">
                            <span class="material-symbols-outlined">play_circle</span> INICIAR PROCESAMIENTO
                        </button>
                    </form>

                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleProvider() {
            const type = document.getElementById('import_type').value;
            document.getElementById('provider-selector').style.opacity = (type === 'product' ? '1' : '0.5');
            document.getElementById('provider-selector').style.pointerEvents = (type === 'product' ? 'auto' : 'none');

            document.getElementById('product-hint').classList.toggle('hidden', type !== 'product');
            document.getElementById('entity-hint').classList.toggle('hidden', type === 'product');
        }
        function updateFileName() {
            const input = document.getElementById('file-input');
            const display = document.getElementById('file-name');
            if (input.files.length > 0) {
                display.innerText = input.files[0].name;
                display.parentElement.classList.add('border-primary/50', 'bg-primary/5');
            }
        }
    </script>
</body>

</html>