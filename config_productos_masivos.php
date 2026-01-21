<?php
require_once 'auth_check.php';
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
/**
 * VS System ERP - Massive Product Modifications
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/catalogo/Catalog.php';
require_once __DIR__ . '/src/lib/Logger.php';

use Vsys\Modules\Catalogo\Catalog;
use Vsys\Lib\Logger;

$catalog = new Catalog();
$message = '';
$status = '';

// Handle Massive Percentage Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_update'])) {
    $percent = floatval($_POST['percent'] ?? 0);
    $category = $_POST['category'] ?? '';
    $brand = $_POST['brand'] ?? '';

    if ($percent != 0) {
        $db = Vsys\Lib\Database::getInstance();
        $sql = "UPDATE products SET unit_cost_usd = unit_cost_usd * (1 + (:percent / 100)) WHERE company_id = :cid";
        $params = [':percent' => $percent, ':cid' => $_SESSION['company_id']];

        if (!empty($category)) {
            $sql .= " AND category = :cat";
            $params[':cat'] = $category;
        }
        if (!empty($brand)) {
            $sql .= " AND brand = :brand";
            $params[':brand'] = $brand;
        }

        $stmt = $db->prepare($sql);
        if ($stmt->execute($params)) {
            $count = $stmt->rowCount();
            $message = "Se han actualizado $count productos exitosamente.";
            $status = "success";
            Logger::event('PRODUCT_MASS_UPDATE', 'catalog', null, [
                'percent' => $percent,
                'cat' => $category,
                'brand' => $brand,
                'count' => $count
            ]);
        } else {
            $message = "Error al ejecutar la actualización masiva.";
            $status = "error";
        }
    }
}

// Handle CSV Import for Massive Modification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_bulk'])) {
    $targetDir = __DIR__ . "/data/uploads/";
    if (!file_exists($targetDir))
        mkdir($targetDir, 0777, true);
    $target = $targetDir . "bulk_" . time() . ".csv";

    if (move_uploaded_file($_FILES["csv_bulk"]["tmp_name"], $target)) {
        $handle = fopen($target, "r");
        fgetcsv($handle, 1000, ";"); // Skip header
        $count = 0;
        $db = Vsys\Lib\Database::getInstance();
        $cid = $_SESSION['company_id'] ?? 1;

        // Try to detect separator
        $line = fgets($handle);
        $sep = (strpos($line, ';') !== false) ? ';' : ',';
        rewind($handle);

        fgetcsv($handle, 1000, $sep); // Skip header row

        while (($data = fgetcsv($handle, 1000, $sep)) !== FALSE) {
            if (count($data) < 3)
                continue;
            $sku = trim($data[0]);
            $cost = floatval(str_replace(',', '.', $data[1]));
            $stock = intval($data[2]);
            if (empty($sku))
                continue;
            $stmt = $db->prepare("UPDATE products SET unit_cost_usd = ?,
    stock_current = ? WHERE sku = ? AND company_id = ?");
            $stmt->execute([$cost, $stock, $sku, $cid]);
            $count += $stmt->rowCount();
        }
        fclose($handle);
        $message = "Se han actualizado $count productos mediante CSV.";
        $status = "success";
        Logger::event('PRODUCT_CSV_MASS_UPDATE', 'catalog', null, ['count' => $count]);
    }
}

$categories = $catalog->getCategories();
// Get brands
$db = Vsys\Lib\Database::getInstance();
$brands = $db->query("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand != '' AND company_id = "
    . $_SESSION['company_id'])->fetchAll(PDO::FETCH_COLUMN);

?>
    <!DOCTYPE html>
    <html class="dark" lang="es">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Modificación Masiva - VS System</title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
            rel="stylesheet" />
        <link
            href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
            rel="stylesheet" />
        <script src="js/theme_handler.js"></script>
        <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
        <script>
            tailwind.config = {
                darkMode: "class",
                theme: { extend: { colors: { "primary": "#136dec" } } }
            }
        </script>
    </head>

    <body class="bg-white dark:bg-[#101822] text-slate-800 dark:text-white antialiased overflow-hidden">
        <div class="flex h-screen w-full">
            <?php include 'sidebar.php'; ?>
            <main class="flex-1 flex flex-col h-full overflow-hidden">
                <header
                    class="h-16 flex items-center justify-between px-6 border-b border-slate-200 dark:border-[#233348] bg-white dark:bg-[#101822]/95 backdrop-blur z-10 sticky top-0">
                    <div class="flex items-center gap-3">
                        <div class="bg-primary/20 p-2 rounded-lg text-primary">
                            <span class="material-symbols-outlined text-2xl">auto_fix_high</span>
                        </div>
                        <h2 class="dark:text-white text-slate-800 font-bold text-lg uppercase tracking-tight">Acciones
                            Masivas</h2>
                    </div>
                    <a href="productos.php"
                        class="text-sm font-bold text-slate-500 hover:text-primary transition-colors flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">arrow_back</span> VOLVER PRODUCTOS
                    </a>
                </header>

                <div class="flex-1 overflow-y-auto p-6 space-y-6">
                    <div class="max-w-4xl mx-auto space-y-8">

                        <?php if ($message): ?>
                            <div
                                class="p-4 rounded-xl flex items-center gap-3 <?php echo $status === 'success' ? 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/20' : 'bg-red-500/10 text-red-500 border border-red-500/20'; ?>">
                                <span class="material-symbols-outlined font-black tracking-widest text-xs">
                                        <?php echo $status === 'success' ? 'check_circle' : 'error'; ?>
                                </span>
                                <span class="font-bold text-xs uppercase tracking-widest">
                                 <?php echo $message; ?>
                                </span>
                            </div>
                      <?php endif; ?>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Percentage Update -->
                            <div
                                class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] p-8 rounded-3xl shadow-sm">
                                <h3
                                    class="text-xs font-black uppercase tracking-widest text-primary mb-6 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-lg">percent</span> Actualizar por Margen
                                </h3>
                                <form method="POST" class="space-y-4">
                                    <input type="hidden" name="bulk_update" value="1">
                                    <div class="space-y-2">
                                        <label
                                            class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1">Porcentaje
                                            (+ ó -)</label>
                                        <input type="number" step="0.01" name="percent" required placeholder="Ej: 15.5"
                                            class="w-full bg-slate-50 dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-xl px-4 py-3 text-sm focus:ring-primary">
                                    </div>
                                    <div class="space-y-2">
                                        <label
                                            class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1">Filtrar
                                            por Categoría</label>
                                        <select name="category"
                                            class="w-full bg-slate-50 dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-xl px-4 py-3 text-sm focus:ring-primary text-white">
                                            <option value="">Todas las Categorías</option>
                                       <?php foreach ($categories as $c): ?>
                                                <option value="<?php echo $c; ?>">
                                                 <?php echo $c; ?>
                                                </option>
                                          <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="space-y-2">
                                        <label
                                            class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1">Filtrar
                                            por Marca</label>
                                        <select name="brand"
                                            class="w-full bg-slate-50 dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-xl px-4 py-3 text-sm focus:ring-primary text-white">
                                            <option value="">Todas las Marcas</option>
                                       <?php foreach ($brands as $b): ?>
                                                <option value="<?php echo $b; ?>">
                                                 <?php echo $b; ?>
                                                </option>
                                          <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <button type="submit"
                                        onclick="return confirm('¿Está seguro de actualizar todos los productos filtrados? Esta acción no se puede deshacer.')"
                                        class="w-full bg-primary hover:bg-blue-600 text-white py-3 rounded-xl font-black text-xs uppercase tracking-widest transition-all">
                                        APLICAR CAMBIOS
                                    </button>
                                </form>
                            </div>

                            <!-- CSV Bulk Update -->
                            <div
                                class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] p-8 rounded-3xl shadow-sm">
                                <h3
                                    class="text-xs font-black uppercase tracking-widest text-emerald-500 mb-6 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-lg">csv</span> Actualización por CSV
                                </h3>
                                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                                    <p
                                        class="text-[11px] text-slate-500 leading-relaxed bg-[#101822] p-4 rounded-xl border border-white/5 font-mono">
                                        Formato: <br><strong>SKU; COSTO; STOCK</strong><br><br>
                                        Use punto (.) para decimales.
                                    </p>
                                    <div class="space-y-2">
                                        <label
                                            class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1">Archivo
                                            CSV</label>
                                        <input type="file" name="csv_bulk" accept=".csv" required
                                            class="w-full bg-slate-50 dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-xl px-4 py-3 text-sm text-slate-400">
                                    </div>
                                    <button type="submit"
                                        class="w-full bg-emerald-500 hover:bg-emerald-600 text-white py-3 rounded-xl font-black text-xs uppercase tracking-widest transition-all mt-4">
                                        SUBIR Y ACTUALIZAR
                                    </button>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>
            </main>
        </div>
    </body>

    </html>