<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/catalogo/Catalog.php';
require_once __DIR__ . '/src/modules/config/PriceList.php';

use Vsys\Lib\Database;
use Vsys\Modules\Catalogo\Catalog;
use Vsys\Modules\Config\PriceList;

$db = Database::getInstance();
$catalog = new Catalog();
$priceListModule = new PriceList();

$tab = $_GET['tab'] ?? 'general';
$message = '';
$status = '';

// Check admin role for sensitive tabs/actions
$isAdmin = ($_SESSION['role'] === 'Admin');

// --- ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_margins' && $isAdmin) {
        foreach ($_POST['margins'] as $id => $margin) {
            $priceListModule->updateMargin($id, $margin);
        }
        $message = "Márgenes actualizados correctamente.";
        $status = "success";
    }

    if ($action === 'import_v2' && $isAdmin && isset($_FILES['csv_file'])) {
        $targetDir = __DIR__ . "/src/data/uploads/";
        if (!file_exists($targetDir))
            mkdir($targetDir, 0777, true);
        $targetFile = $targetDir . time() . "_" . basename($_FILES["csv_file"]["name"]);

        if (move_uploaded_file($_FILES["csv_file"]["tmp_name"], $targetFile)) {
            $count = $catalog->importProductsFromCsv($targetFile);
            $message = "¡Éxito! Se han procesado $count registros.";
            $status = "success";
        }
    }
}

// --- DATA FETCHING ---
$priceLists = $priceListModule->getAll();

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

        .tab-active {
            color: white;
            border-bottom: 2px solid #136dec;
        }

        .tab-inactive {
            color: #64748b;
        }

        .tab-inactive:hover {
            color: #94a3b8;
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
                    <button onclick="toggleVsysSidebar()" class="lg:hidden dark:text-white text-slate-800 p-1 mr-2">
                        <span class="material-symbols-outlined">menu</span>
                    </button>
                    <div class="bg-primary/20 p-2 rounded-lg text-primary">
                        <span class="material-symbols-outlined text-2xl">settings</span>
                    </div>
                    <h2 class="dark:text-white text-slate-800 font-bold text-lg uppercase tracking-tight">Centro de
                        Configuración</h2>
                </div>
            </header>

            <!-- Tab Navigation -->
            <div class="px-6 border-b border-slate-200 dark:border-[#233348] bg-slate-50 dark:bg-[#101822]/50">
                <div class="flex gap-8">
                    <a href="?tab=general"
                        class="py-4 text-sm font-bold uppercase tracking-wider transition-all <?php echo $tab === 'general' ? 'tab-active' : 'tab-inactive'; ?>">General</a>
                    <a href="?tab=productos"
                        class="py-4 text-sm font-bold uppercase tracking-wider transition-all <?php echo $tab === 'productos' ? 'tab-active' : 'tab-inactive'; ?>">Catálogo</a>
                    <a href="?tab=entidades"
                        class="py-4 text-sm font-bold uppercase tracking-wider transition-all <?php echo $tab === 'entidades' ? 'tab-active' : 'tab-inactive'; ?>">Entidades</a>
                    <a href="?tab=importar"
                        class="py-4 text-sm font-bold uppercase tracking-wider transition-all <?php echo $tab === 'importar' ? 'tab-active' : 'tab-inactive'; ?>">Importar</a>
                    <a href="?tab=precios"
                        class="py-4 text-sm font-bold uppercase tracking-wider transition-all <?php echo $tab === 'precios' ? 'tab-active' : 'tab-inactive'; ?>">Precios
                        & Márgenes</a>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-6">
                <div class="max-w-7xl mx-auto space-y-6">

                    <?php if ($message): ?>
                        <div
                            class="bg-green-500/10 border border-green-500/20 text-green-500 p-4 rounded-xl flex items-center gap-3">
                            <span class="material-symbols-outlined">check_circle</span>
                            <span class="text-sm font-bold uppercase truncate"><?php echo $message; ?></span>
                        </div>
                    <?php endif; ?>

                    <?php switch ($tab):
                        case 'general': ?>
                            <!-- General Settings -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div
                                    class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] p-6 rounded-2xl">
                                    <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
                                        <span class="material-symbols-outlined text-primary">palette</span> Apariencia Visual
                                    </h3>
                                    <form method="POST" action="configuration.php" class="space-y-4">
                                        <input type="hidden" name="action" value="save_visuals">
                                        <div>
                                            <label
                                                class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Tema
                                                Predeterminado</label>
                                            <select name="default_theme"
                                                class="w-full bg-slate-100 dark:bg-[#101822] border-none rounded-xl text-sm">
                                                <option value="auto">Auto (Sigue Sistema)</option>
                                                <option value="dark" selected>Siempre Oscuro</option>
                                                <option value="light">Siempre Claro</option>
                                            </select>
                                        </div>
                                        <button
                                            class="w-full bg-primary text-white font-bold py-3 rounded-xl shadow-lg shadow-primary/20">GUARDAR
                                            PREFERENCIAS</button>
                                    </form>
                                </div>
                            </div>
                            <?php break;

                        case 'precios': ?>
                            <!-- Price & Margins -->
                            <div
                                class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] p-8 rounded-2xl max-w-2xl">
                                <h3 class="text-xl font-bold mb-6 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary">sell</span> Gestión de Markups
                                </h3>
                                <p class="text-slate-500 text-sm mb-8">Defina los porcentajes de ganancia sobre el costo bruto
                                    (USD) para cada lista de precios oficial.</p>

                                <form method="POST" class="space-y-6">
                                    <input type="hidden" name="action" value="save_margins">
                                    <div class="space-y-4">
                                        <?php foreach ($priceLists as $list): ?>
                                            <div
                                                class="flex items-center justify-between p-4 bg-slate-50 dark:bg-white/5 rounded-xl border border-slate-100 dark:border-white/5">
                                                <div class="flex flex-col">
                                                    <span
                                                        class="font-bold text-lg text-slate-800 dark:text-white"><?php echo $list['name']; ?></span>
                                                    <span
                                                        class="text-[10px] text-slate-500 uppercase font-bold tracking-widest">Margen
                                                        de Ganancia</span>
                                                </div>
                                                <div class="flex items-center gap-3">
                                                    <div class="relative">
                                                        <input type="number" step="0.01" name="margins[<?php echo $list['id']; ?>]"
                                                            value="<?php echo $list['margin_percent']; ?>"
                                                            class="w-24 bg-white dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-lg text-right font-bold pr-8">
                                                        <span
                                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 font-bold">%</span>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <button
                                        class="w-full bg-primary hover:bg-blue-600 text-white font-bold py-4 rounded-2xl shadow-xl shadow-primary/20 transition-all flex items-center justify-center gap-2">
                                        <span class="material-symbols-outlined">save</span> ACTUALIZAR TODOS LOS PRECIOS
                                    </button>
                                </form>
                            </div>
                            <?php break;

                        case 'importar': ?>
                            <!-- Import Module -->
                            <div
                                class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] p-8 rounded-2xl max-w-4xl">
                                <h3 class="text-xl font-bold mb-2 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary">cloud_upload</span> Importador
                                    Multi-Proveedor
                                </h3>
                                <p class="text-slate-500 text-sm mb-8">Carga masiva de productos. Si el SKU ya existe con otro
                                    proveedor, se añadirá el nuevo precio y se mantendrá el más barato como referencia.</p>

                                <form method="POST" enctype="multipart/form-data" class="space-y-6">
                                    <input type="hidden" name="action" value="import_v2">

                                    <div class="border-2 border-dashed border-primary/30 rounded-2xl p-12 text-center hover:border-primary/50 transition-all cursor-pointer group bg-primary/5"
                                        onclick="document.getElementById('csv_file').click()">
                                        <span
                                            class="material-symbols-outlined text-5xl text-primary/50 group-hover:scale-110 transition-transform mb-4">file_upload</span>
                                        <p class="text-lg font-bold text-slate-700 dark:text-slate-300" id="file_status">Suelta
                                            tu archivo CSV aquí o haz clic para buscar</p>
                                        <p class="text-xs text-slate-500 mt-2">Formato: SKU; DESCRIPCION; MARCA; COSTO; IVA;
                                            CATEGORIA; SUBCATEGORIA; PROVEEDOR</p>
                                        <input type="file" name="csv_file" id="csv_file" class="hidden" accept=".csv"
                                            onchange="document.getElementById('file_status').innerText = this.files[0].name.toUpperCase()">
                                    </div>

                                    <button
                                        class="w-full bg-primary text-white font-bold py-4 rounded-2xl shadow-xl shadow-primary/20 transition-all flex items-center justify-center gap-3">
                                        <span class="material-symbols-outlined">play_arrow</span> INICIAR PROCESAMIENTO
                                    </button>
                                </form>
                            </div>
                            <?php break;

                        case 'entidades': ?>
                            <!-- Entities (Clients/Suppliers) -->
                            <div class="space-y-6">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-xl font-bold flex items-center gap-2">
                                        <span class="material-symbols-outlined text-primary">group</span> Directorio de
                                        Entidades
                                    </h3>
                                    <div class="flex gap-2">
                                        <a href="config_entities.php?type=client"
                                            class="bg-primary/10 text-primary px-4 py-2 rounded-lg text-xs font-bold hover:bg-primary hover:text-white transition-all">+
                                            NUEVO CLIENTE</a>
                                        <a href="config_entities.php?type=supplier"
                                            class="bg-amber-500/10 text-amber-500 px-4 py-2 rounded-lg text-xs font-bold hover:bg-amber-500 hover:text-white transition-all">+
                                            NUEVO PROVEEDOR</a>
                                    </div>
                                </div>
                                <div
                                    class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl overflow-hidden">
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
                                            $entities = $db->query("SELECT * FROM entities ORDER BY name ASC LIMIT 20")->fetchAll();
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
                                                            <?php echo $e['name']; ?></div>
                                                    </td>
                                                    <td class="px-6 py-4 text-xs"><?php echo $e['tax_id']; ?></td>
                                                    <td class="px-6 py-4">
                                                        <div class="text-xs font-medium"><?php echo $e['email']; ?></div>
                                                    </td>
                                                    <td class="px-6 py-4 text-right">
                                                        <a href="config_entities.php?type=<?php echo $e['type']; ?>&edit=<?php echo $e['id']; ?>"
                                                            class="text-primary hover:underline font-bold text-xs uppercase">Editar</a>
                                                        <?php if ($isAdmin): ?>
                                                            <button
                                                                class="ml-4 text-red-500 hover:underline font-bold text-xs uppercase"
                                                                onclick="confirm('¿Eliminar?')">Eliminar</button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <?php break;

                        case 'productos': ?>
                            <!-- Products Catalog ABM -->
                            <div class="space-y-6">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-xl font-bold flex items-center gap-2">
                                        <span class="material-symbols-outlined text-primary">inventory_2</span> Catálogo Maestro
                                    </h3>
                                    <a href="config_productos_add.php"
                                        class="bg-primary text-white px-6 py-2.5 rounded-xl text-xs font-bold shadow-lg shadow-primary/20 hover:scale-[1.02] transition-all">+
                                        AÑADIR PRODUCTO</a>
                                </div>
                                <div
                                    class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl overflow-hidden">
                                    <table class="w-full text-left">
                                        <thead class="bg-slate-50 dark:bg-white/5">
                                            <tr
                                                class="text-[10px] font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100 dark:border-white/5">
                                                <th class="px-6 py-4">SKU / Marca</th>
                                                <th class="px-6 py-4">Descripción</th>
                                                <th class="px-6 py-4">Costo USD</th>
                                                <th class="px-6 py-4">Stock</th>
                                                <th class="px-6 py-4 text-right">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                                            <?php
                                            $products = $db->query("SELECT * FROM products ORDER BY id DESC LIMIT 20")->fetchAll();
                                            foreach ($products as $p): ?>
                                                <tr class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors">
                                                    <td class="px-6 py-4">
                                                        <div class="font-bold text-slate-800 dark:text-white">
                                                            <?php echo $p['sku']; ?></div>
                                                        <div class="text-[9px] uppercase font-bold text-primary">
                                                            <?php echo $p['brand']; ?></div>
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <div class="text-xs font-medium line-clamp-1">
                                                            <?php echo $p['description']; ?></div>
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <span class="font-bold text-emerald-500">USD
                                                            <?php echo $p['unit_cost_usd']; ?></span>
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <span class="font-bold"><?php echo $p['stock_current']; ?></span>
                                                    </td>
                                                    <td class="px-6 py-4 text-right">
                                                        <a href="config_productos_add.php?id=<?php echo $p['id']; ?>"
                                                            class="text-primary hover:underline font-bold text-xs uppercase">Editar</a>
                                                        <?php if ($isAdmin): ?>
                                                            <button
                                                                class="ml-4 text-red-500 hover:underline font-bold text-xs uppercase"
                                                                onclick="confirm('¿Eliminar?')">Eliminar</button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <?php break;
                    endswitch; ?>

                </div>
            </div>
        </main>
    </div>
</body>

</html>