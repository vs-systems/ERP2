<?php
require_once 'auth_check.php';
/**
 * VS System ERP - Add/Edit Product (Premium Redesign)
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/catalogo/Catalog.php';

use Vsys\Modules\Catalogo\Catalog;

$catalog = new Catalog();
$message = '';
$status = '';

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    $data = [
        'sku' => $_POST['sku'],
        'barcode' => $_POST['barcode'] ?? null,
        'provider_code' => $_POST['provider_code'] ?? null,
        'description' => $_POST['description'],
        'category' => $_POST['category'] ?? '',
        'subcategory' => $_POST['subcategory'] ?? '',
        'unit_cost_usd' => floatval($_POST['unit_cost_usd']),
        'unit_price_usd' => floatval($_POST['unit_price_usd'] ?: 0),
        'iva_rate' => floatval($_POST['iva_rate']),
        'brand' => $_POST['brand'] ?? '',
        'image_url' => $_POST['image_url'] ?? null,
        'has_serial_number' => isset($_POST['has_serial_number']) ? 1 : 0,
        'stock_current' => intval($_POST['stock_current'] ?? 0),
        'stock_min' => intval($_POST['stock_min'] ?? 0),
        'stock_transit' => intval($_POST['stock_transit'] ?? 0),
        'stock_incoming' => intval($_POST['stock_incoming'] ?? 0),
        'incoming_date' => !empty($_POST['incoming_date']) ? $_POST['incoming_date'] : null,
        'supplier_id' => !empty($_POST['supplier_id']) ? $_POST['supplier_id'] : null
    ];

    if ($catalog->addProduct($data)) {
        $message = "Producto guardado correctamente.";
        $status = "success";
    } else {
        $message = "Error al guardar el producto.";
        $status = "error";
    }
}

$p = null;
if (isset($_GET['sku'])) {
    $sku = $_GET['sku'];
    $db = Vsys\Lib\Database::getInstance();
    $stmt = $db->prepare("SELECT * FROM products WHERE sku = ? AND company_id = ?");
    $stmt->execute([$sku, $_SESSION['company_id']]);
    $p = $stmt->fetch();
}

$suppliers = $catalog->getProviders();
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $p ? 'Editar' : 'Nuevo'; ?> Producto - VS System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet" />
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

        .glass-card {
            background: rgba(22, 32, 46, 0.8);
            backdrop-filter: blur(8px);
        }
    </style>
</head>

<body class="bg-white dark:bg-[#101822] text-slate-800 dark:text-white antialiased overflow-hidden">
    <div class="flex h-screen w-full">
        <?php include 'sidebar.php'; ?>

        <main class="flex-1 flex flex-col h-full overflow-hidden relative">
            <header
                class="h-16 flex items-center justify-between px-6 border-b border-slate-200 dark:border-[#233348] bg-white dark:bg-[#101822]/95 backdrop-blur z-10 sticky top-0">
                <div class="flex items-center gap-3">
                    <div class="bg-primary/20 p-2 rounded-lg text-primary">
                        <span
                            class="material-symbols-outlined text-2xl"><?php echo $p ? 'edit_square' : 'add_box'; ?></span>
                    </div>
                    <h2 class="dark:text-white text-slate-800 font-bold text-lg uppercase tracking-tight">
                        <?php echo $p ? 'Editar' : 'Cargar'; ?> Producto
                    </h2>
                </div>
                <a href="productos.php"
                    class="text-sm font-bold text-slate-500 hover:text-primary transition-colors flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">arrow_back</span> VOLVER AL LISTADO
                </a>
            </header>

            <div class="flex-1 overflow-y-auto p-6">
                <div class="max-w-5xl mx-auto">
                    <?php if ($message): ?>
                        <div
                            class="mb-6 p-4 rounded-xl flex items-center gap-3 <?php echo $status === 'success' ? 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/20' : 'bg-red-500/10 text-red-500 border border-red-500/20'; ?>">
                            <span
                                class="material-symbols-outlined"><?php echo $status === 'success' ? 'check_circle' : 'error'; ?></span>
                            <span class="font-bold text-sm uppercase tracking-wide"><?php echo $message; ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-8">
                        <input type="hidden" name="save_product" value="1">

                        <!-- Essential Info -->
                        <div
                            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] p-8 rounded-3xl shadow-sm space-y-6">
                            <h3 class="text-xs font-black uppercase tracking-widest text-[#136dec] mb-4">Información
                                Principal</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label
                                        class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1">SKU
                                        / Código</label>
                                    <input type="text" name="sku" value="<?php echo $p['sku'] ?? ''; ?>" required <?php echo $p ? 'readonly' : ''; ?>
                                        class="w-full bg-slate-50 dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-xl px-4 py-3 text-sm focus:ring-primary <?php echo $p ? 'opacity-60 cursor-not-allowed' : ''; ?>">
                                </div>
                                <div class="space-y-2">
                                    <label
                                        class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1">Descripción</label>
                                    <input type="text" name="description" value="<?php echo $p['description'] ?? ''; ?>"
                                        required
                                        class="w-full bg-slate-50 dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-xl px-4 py-3 text-sm focus:ring-primary">
                                </div>
                                <div class="space-y-2">
                                    <label
                                        class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1">Marca</label>
                                    <input type="text" name="brand" value="<?php echo $p['brand'] ?? ''; ?>"
                                        class="w-full bg-slate-50 dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-xl px-4 py-3 text-sm focus:ring-primary">
                                </div>
                                <div class="space-y-2">
                                    <label
                                        class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1">Categoría</label>
                                    <input type="text" name="category" value="<?php echo $p['category'] ?? ''; ?>"
                                        class="w-full bg-slate-50 dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-xl px-4 py-3 text-sm focus:ring-primary">
                                </div>
                            </div>
                        </div>

                        <!-- Pricing and Taxes -->
                        <div
                            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] p-8 rounded-3xl shadow-sm space-y-6">
                            <h3 class="text-xs font-black uppercase tracking-widest text-emerald-500 mb-4">Costos y
                                Precios</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="space-y-2">
                                    <label
                                        class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1">Costo
                                        Unit. (USD)</label>
                                    <div class="relative">
                                        <span
                                            class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold">$</span>
                                        <input type="number" step="0.0001" name="unit_cost_usd"
                                            value="<?php echo $p['unit_cost_usd'] ?? ''; ?>" required
                                            class="w-full bg-slate-50 dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-xl pl-8 pr-4 py-3 text-sm focus:ring-emerald-500">
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <label
                                        class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1">IVA
                                        Rate %</label>
                                    <select name="iva_rate"
                                        class="w-full bg-slate-50 dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-xl px-4 py-3 text-sm focus:ring-emerald-500">
                                        <option value="21" <?php echo ($p['iva_rate'] ?? 21) == 21 ? 'selected' : ''; ?>>
                                            21.0%</option>
                                        <option value="10.5" <?php echo ($p['iva_rate'] ?? 0) == 10.5 ? 'selected' : ''; ?>>10.5%</option>
                                        <option value="0" <?php echo ($p['iva_rate'] ?? 0) == 0 ? 'selected' : ''; ?>>
                                            Exento</option>
                                    </select>
                                </div>
                                <div class="space-y-2">
                                    <label
                                        class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1">Proveedor
                                        Primario</label>
                                    <select name="supplier_id"
                                        class="w-full bg-slate-50 dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-xl px-4 py-3 text-sm focus:ring-emerald-500">
                                        <option value="">Ninguno</option>
                                        <?php foreach ($suppliers as $s): ?>
                                            <option value="<?php echo $s['id']; ?>" <?php echo ($p['supplier_id'] ?? '') == $s['id'] ? 'selected' : ''; ?>>
                                                <?php echo $s['name']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Stock Management (New Section) -->
                        <div
                            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] p-8 rounded-3xl shadow-sm space-y-6">
                            <h3 class="text-xs font-black uppercase tracking-widest text-amber-500 mb-4">Control de
                                Inventario</h3>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                                <div class="space-y-2">
                                    <label
                                        class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1">Stock
                                        Actual</label>
                                    <input type="number" name="stock_current"
                                        value="<?php echo $p['stock_current'] ?? 0; ?>"
                                        class="w-full bg-slate-50 dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-xl px-4 py-3 text-sm focus:ring-amber-500">
                                </div>
                                <div class="space-y-2">
                                    <label
                                        class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1">Stock
                                        Mínimo</label>
                                    <input type="number" name="stock_min" value="<?php echo $p['stock_min'] ?? 0; ?>"
                                        class="w-full bg-slate-50 dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-xl px-4 py-3 text-sm focus:ring-amber-500">
                                </div>
                                <div class="space-y-2">
                                    <label
                                        class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1">En
                                        Tránsito</label>
                                    <input type="number" name="stock_transit"
                                        value="<?php echo $p['stock_transit'] ?? 0; ?>"
                                        class="w-full bg-slate-50 dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-xl px-4 py-3 text-sm focus:ring-amber-500">
                                </div>
                                <div class="space-y-2">
                                    <label
                                        class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1">Por
                                        Ingresar</label>
                                    <input type="number" name="stock_incoming"
                                        value="<?php echo $p['stock_incoming'] ?? 0; ?>"
                                        class="w-full bg-slate-50 dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-xl px-4 py-3 text-sm focus:ring-amber-500">
                                </div>
                                <div class="md:col-span-2 space-y-2">
                                    <label
                                        class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1">Fecha
                                        Est. Ingreso</label>
                                    <input type="date" name="incoming_date"
                                        value="<?php echo $p['incoming_date'] ?? ''; ?>"
                                        class="w-full bg-slate-50 dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-xl px-4 py-3 text-sm focus:ring-amber-500">
                                </div>
                                <div class="md:col-span-2 flex items-center gap-3 pt-6 px-1">
                                    <input type="checkbox" name="has_serial_number" id="has_sn" <?php echo ($p['has_serial_number'] ?? 0) ? 'checked' : ''; ?>
                                        class="rounded border-slate-300 text-primary focus:ring-primary size-5">
                                    <label for="has_sn"
                                        class="text-xs font-bold text-slate-400 uppercase tracking-wide cursor-pointer">Seguimiento
                                        por N° de Serie</label>
                                </div>
                            </div>
                        </div>

                        <div class="flex gap-4">
                            <button type="submit"
                                class="flex-1 bg-primary hover:bg-blue-600 text-white py-4 rounded-3xl font-black text-xs uppercase tracking-widest shadow-xl shadow-primary/20 transition-all flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined">save</span>
                                <?php echo $p ? 'ACTUALIZAR PRODUCTO' : 'REGISTRAR PRODUCTO'; ?>
                            </button>
                            <a href="productos.php"
                                class="bg-slate-500/10 hover:bg-slate-500/20 text-slate-500 py-4 px-8 rounded-3xl font-bold text-xs uppercase tracking-widest transition-all">
                                CANCELAR
                            </a>
                        </div>
                    </form>
                </div>
                <div class="h-10"></div>
            </div>
        </main>
    </div>
</body>

</html>