<?php
// restore_files.php - Restauración de Archivos Críticos v7 (Centralized Sidebar)
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

echo "<h1>Restaurador de Archivos Críticos v7 (Sidebar Centralizado)</h1>";

function writeFile($path, $content)
{
    echo "<p>Escribiendo: $path ... ";
    $dir = dirname($path);
    if (!is_dir($dir))
        mkdir($dir, 0755, true);
    if (file_exists($path))
        unlink($path);
    if (file_put_contents($path, $content) !== false) {
        echo "<span style='color:green'> [OK] </span></p>";
        if (function_exists('opcache_invalidate'))
            opcache_invalidate($path, true);
        return true;
    } else {
        echo "<span style='color:red'> [ERROR] </span></p>";
        return false;
    }
}

// ---------------------------------------------------------
// 1. Create Smart Sidebar
// ---------------------------------------------------------
$sidebarContent = <<<'PHP'
<?php
$cur = basename($_SERVER['PHP_SELF']);
?>
<nav class="sidebar">
    <a href="index.php" class="nav-link <?php echo $cur == 'index.php' ? 'active' : ''; ?>"><i class="fas fa-home"></i> DASHBOARD</a>
    <a href="analisis.php" class="nav-link <?php echo $cur == 'analisis.php' ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> AN&Aacute;LISIS OP.</a>
    <a href="productos.php" class="nav-link <?php echo $cur == 'productos.php' ? 'active' : ''; ?>"><i class="fas fa-box-open"></i> PRODUCTOS</a>
    <a href="presupuestos.php" class="nav-link <?php echo $cur == 'presupuestos.php' ? 'active' : ''; ?>"><i class="fas fa-history"></i> PRESUPUESTOS</a>
    <a href="clientes.php" class="nav-link <?php echo $cur == 'clientes.php' ? 'active' : ''; ?>"><i class="fas fa-users"></i> CLIENTES</a>
    <a href="proveedores.php" class="nav-link <?php echo $cur == 'proveedores.php' ? 'active' : ''; ?>"><i class="fas fa-truck-loading"></i> PROVEEDORES</a>
    <a href="compras.php" class="nav-link <?php echo $cur == 'compras.php' ? 'active' : ''; ?>"><i class="fas fa-cart-arrow-down"></i> COMPRAS</a>
    <a href="crm.php" class="nav-link <?php echo $cur == 'crm.php' ? 'active' : ''; ?>"><i class="fas fa-handshake"></i> CRM</a>
    <a href="cotizador.php" class="nav-link <?php echo $cur == 'cotizador.php' ? 'active' : ''; ?>"><i class="fas fa-file-invoice-dollar"></i> COTIZADOR</a>
    <a href="configuration.php" class="nav-link <?php echo ($cur == 'configuration.php' || $cur == 'config_precios.php' || $cur == 'config_productos_add.php' || $cur == 'importar.php') ? 'active' : ''; ?>"><i class="fas fa-cogs"></i> CONFIGURACIÓN</a>
    <a href="catalogo.php" class="nav-link" target="_blank" style="color: #25d366; font-weight: 700;"><i class="fas fa-external-link-alt"></i> VER CAT&Aacute;LOGO</a>
</nav>
PHP;
writeFile(__DIR__ . '/src/includes/sidebar.php', $sidebarContent);


// ---------------------------------------------------------
// 2. Rewrite Config & Product Files with Include
// ---------------------------------------------------------
// We use a helper to wrap content with standard header/include sidebar/footer(optional)
// But since we have full content strings, we just inject the include line.

// MODULE: configuration.php
$contentConfiguration = <<<'PHP'
<?php
require_once 'auth_check.php';
/**
 * Centro de Configuración - VS System ERP
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configuración - VS System</title>
    <link rel="stylesheet" href="css/style_premium.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .config-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        .config-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: white;
            display: block;
        }
        .config-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--accent-violet);
            box-shadow: 0 10px 30px -10px rgba(139, 92, 246, 0.3);
        }
        .config-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--accent-violet);
        }
    </style>
</head>
<body>
    <header style="background: #020617; border-bottom: 2px solid var(--accent-violet); display: flex; justify-content: space-between; align-items: center; padding: 0 20px;">
        <div style="display: flex; align-items: center; gap: 20px;">
            <img src="logo_display.php?v=1" alt="VS System" class="logo-large" style="height: 50px; width: auto;">
            <div style="color: #fff; font-family: 'Inter', sans-serif; font-weight: 700; font-size: 1.4rem;">
                Centro de <span style="color: var(--accent-violet);">Configuración</span>
            </div>
        </div>
        <div class="header-right" style="color: #cbd5e1;">
            <span class="user-badge"><i class="fas fa-user-circle"></i> Admin</span>
        </div>
    </header>

    <div class="dashboard-container">
        <?php include 'src/includes/sidebar.php'; ?>

        <main class="content">
            <div class="card">
                <h2><i class="fas fa-sliders-h"></i> Panel de Control del Sistema</h2>
                <p style="color: #94a3b8;">Administre los datos maestros, precios y parámetros del sistema desde aquí.</p>

                <div class="config-grid">
                    <a href="config_precios.php" class="config-card">
                        <i class="fas fa-tags config-icon"></i>
                        <h3>Listas de Precios</h3>
                        <p style="font-size: 0.9rem; color: #cbd5e1; margin-top: 10px;">
                            Defina márgenes de ganancia.
                        </p>
                    </a>
                    <a href="config_productos_add.php" class="config-card">
                        <i class="fas fa-plus-circle config-icon" style="color: #10b981;"></i>
                        <h3>Carga Manual</h3>
                        <p style="font-size: 0.9rem; color: #cbd5e1; margin-top: 10px;">
                            Añada nuevos productos.
                        </p>
                    </a>
                    <a href="importar.php" class="config-card">
                        <i class="fas fa-file-csv config-icon" style="color: #f59e0b;"></i>
                        <h3>Importar Datos</h3>
                        <p style="font-size: 0.9rem; color: #cbd5e1; margin-top: 10px;">
                            Carga masiva desde CSV.
                        </p>
                    </a>
                     <a href="update_images_bigdipper.php" class="config-card">
                        <i class="fas fa-images config-icon" style="color: #3b82f6;"></i>
                        <h3>Imágenes</h3>
                        <p style="font-size: 0.9rem; color: #cbd5e1; margin-top: 10px;">
                            Actualizar vínculos con BigDipper.
                        </p>
                    </a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
PHP;
writeFile(__DIR__ . '/configuration.php', $contentConfiguration);


// MODULE: config_productos_add.php
$contentConfigAdd = <<<'PHP'
<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/catalogo/Catalog.php';

use Vsys\Modules\Catalogo\Catalog;

$catalog = new Catalog();
$message = '';
$status = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    $data = [
        'sku' => $_POST['sku'],
        'barcode' => $_POST['barcode'] ?? null,
        'provider_code' => $_POST['provider_code'] ?? null,
        'description' => $_POST['description'],
        'category' => $_POST['category'] ?? '',
        'subcategory' => $_POST['subcategory'] ?? '',
        'unit_cost_usd' => $_POST['unit_cost_usd'],
        'unit_price_usd' => $_POST['unit_price_usd'], 
        'iva_rate' => $_POST['iva_rate'],
        'brand' => $_POST['brand'] ?? '',
        'image_url' => $_POST['image_url'] ?? null,
        'has_serial_number' => isset($_POST['has_serial_number']) ? 1 : 0,
        'stock_current' => $_POST['stock_current'] ?? 0,
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

$editingProduct = null;
if (isset($_GET['sku'])) {
    $sku = $_GET['sku'];
    $db = Vsys\Lib\Database::getInstance();
    $stmt = $db->prepare("SELECT * FROM products WHERE sku = ?");
    $stmt->execute([$sku]);
    $editingProduct = $stmt->fetch();
}

$suppliers = $catalog->getProviders();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Carga de Productos - VS System</title>
    <link rel="stylesheet" href="css/style_premium.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <header style="background: #020617; border-bottom: 2px solid var(--accent-violet); display: flex; justify-content: space-between; align-items: center; padding: 0 20px;">
        <div style="display: flex; align-items: center; gap: 20px;">
            <img src="logo_display.php?v=1" alt="VS System" class="logo-large" style="height: 50px; width: auto;">
            <div style="color: #fff; font-family: 'Inter', sans-serif; font-weight: 700; font-size: 1.4rem;">
                Configuración <span style="color: var(--accent-violet);">Productos</span>
            </div>
        </div>
        <div class="header-right" style="color: #cbd5e1;">
            <span class="user-badge"><i class="fas fa-user-circle"></i> Admin</span>
        </div>
    </header>

    <div class="dashboard-container">
        <?php include 'src/includes/sidebar.php'; ?>

        <main class="content">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $status; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <h3><i class="fas fa-plus-circle"></i> Nuevo / Editar Producto</h3>
                <form method="POST" id="product-form">
                    <input type="hidden" name="save_product" value="1">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>SKU (C&oacute;digo)</label>
                            <input type="text" name="sku" id="sku" required placeholder="C&Oacute;DIGO">
                        </div>
                        <div class="form-group">
                            <label>Descripción del Producto</label>
                            <input type="text" name="description" id="form-description" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-barcode"></i> Código EAN / Barra (Opcional)</label>
                            <input type="text" name="barcode" id="form-barcode"
                                placeholder="Escanear o ingresar código...">
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group"
                            style="display: flex; align-items: center; gap: 10px; padding-top: 25px;">
                            <input type="checkbox" name="has_serial_number" id="form-has-serial">
                            <label for="form-has-serial" style="cursor: pointer;">Requiere Número de Serie
                                (Trazabilidad)</label>
                        </div>
                        <div class="form-group">
                            <label>Marca</label>
                            <input type="text" name="brand" id="brand" placeholder="MARCA">
                        </div>
                        <div class="form-group">
                            <label>URL de Imagen (BigDipper Auto: Dejar vacío si existe)</label>
                            <input type="text" name="image_url" id="image_url" placeholder="https://ejemplo.com/foto.jpg">
                        </div>
                        <div class="form-group">
                            <label>Categor&iacute;a</label>
                            <input type="text" name="category" id="category" placeholder="CATEGOR&Iacute;A">
                        </div>
                        <div class="form-group">
                            <label>Subcategor&iacute;a</label>
                            <input type="text" name="subcategory" id="subcategory" placeholder="SUBCATEGOR&Iacute;A">
                        </div>
                        <div class="form-group">
                            <label>Costo USD</label>
                            <input type="number" step="0.01" name="unit_cost_usd" id="unit_cost_usd" required placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label>Precio Base USD (Opc)</label>
                            <input type="number" step="0.01" name="unit_price_usd" id="unit_price_usd" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label>IVA %</label>
                            <select name="iva_rate" id="iva_rate">
                                <option value="21">21%</option>
                                <option value="10.5">10.5%</option>
                                <option value="0">Exento</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Proveedor Favorito</label>
                            <select name="supplier_id" id="supplier_id">
                                <option value="">Seleccione proveedor...</option>
                                <?php foreach ($suppliers as $s): ?>
                                    <option value="<?php echo $s['id']; ?>" <?php echo ($editingProduct && $editingProduct['supplier_id'] == $s['id']) ? 'selected' : ''; ?>>
                                        <?php echo $s['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                     <script>
                        <?php if ($editingProduct): ?>
                        document.addEventListener("DOMContentLoaded", function() {
                            document.getElementById('sku').value = "<?php echo $editingProduct['sku']; ?>";
                            document.getElementById('form-description').value = "<?php echo addslashes($editingProduct['description']); ?>";
                            document.getElementById('form-barcode').value = "<?php echo $editingProduct['barcode']; ?>";
                            document.getElementById('brand').value = "<?php echo addslashes($editingProduct['brand']); ?>";
                            document.getElementById('image_url').value = "<?php echo $editingProduct['image_url']; ?>";
                            document.getElementById('category').value = "<?php echo addslashes($editingProduct['category']); ?>";
                            document.getElementById('subcategory').value = "<?php echo addslashes($editingProduct['subcategory']); ?>";
                            document.getElementById('unit_cost_usd').value = "<?php echo $editingProduct['unit_cost_usd']; ?>";
                            document.getElementById('unit_price_usd').value = "<?php echo $editingProduct['unit_price_usd']; ?>";
                            document.getElementById('iva_rate').value = "<?php echo $editingProduct['iva_rate']; ?>";
                            if (<?php echo $editingProduct['has_serial_number']; ?> == 1) {
                                document.getElementById('form-has-serial').checked = true;
                            }
                        });
                        <?php endif; ?>
                    </script>
                    <button type="submit" class="btn-primary"><i class="fas fa-save"></i> GUARDAR</button>
                    <a href="configuration.php" class="btn" style="background:#475569; margin-left:10px;">Cancelar</a>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
PHP;
writeFile(__DIR__ . '/config_productos_add.php', $contentConfigAdd);


// MODULE: productos.php
$contentProducts = <<<'PHP'
<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/catalogo/Catalog.php';
require_once __DIR__ . '/src/modules/config/PriceList.php';

use Vsys\Modules\Catalogo\Catalog;
use Vsys\Modules\Config\PriceList;

$catalog = new Catalog();
$priceListModule = new PriceList();

$products = $catalog->getAllProducts();
$lists = $priceListModule->getAll();

$listsByName = [];
foreach ($lists as $l) {
    $listsByName[$l['name']] = $l['margin_percent'];
}

$gremioMargin = $listsByName['Gremio'] ?? 30;
$webMargin = $listsByName['Web'] ?? 40;
$mlMargin = $listsByName['MercadoLibre'] ?? 50;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Productos - VS System</title>
    <link rel="stylesheet" href="css/style_premium.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header style="background: #020617; border-bottom: 2px solid var(--accent-violet); display: flex; justify-content: space-between; align-items: center; padding: 0 20px;">
        <div style="display: flex; align-items: center; gap: 20px;">
            <img src="logo_display.php?v=1" alt="VS System" class="logo-large" style="height: 50px; width: auto;">
            <div style="color: #fff; font-family: 'Inter', sans-serif; font-weight: 700; font-size: 1.4rem;">
                Listado de <span style="color: var(--accent-violet);">Productos</span>
            </div>
        </div>
        <div class="header-right" style="color: #cbd5e1;">
            <span class="user-badge"><i class="fas fa-user-circle"></i> Admin</span>
        </div>
    </header>

    <div class="dashboard-container">
        <?php include 'src/includes/sidebar.php'; ?>

        <main class="content">
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3><i class="fas fa-list"></i> Cat&aacute;logo General</h3>
                    <div style="position: relative;">
                        <input type="text" id="tableSearch" placeholder="Buscar por SKU o Desc..."
                            style="padding: 8px 12px; border-radius: 20px; border: 1px solid var(--accent-violet); background: rgba(255,255,255,0.05); color: white; width: 300px;">
                        <i class="fas fa-search" style="position: absolute; right: 15px; top: 12px; opacity: 0.5;"></i>
                    </div>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr style="font-size: 0.85rem; text-transform: uppercase; color: #94a3b8;">
                                <th>SKU</th>
                                <th>Descripci&oacute;n / Marca</th>
                                <th>Rubro</th>
                                <th style="text-align: right; color: var(--accent-violet);">Costo USD</th>
                                <th style="text-align: right;">Gremio (+<?php echo (int)$gremioMargin; ?>%)</th>
                                <th style="text-align: right;">Web (+<?php echo (int)$webMargin; ?>%)</th>
                                <th style="text-align: right;">ML (+<?php echo (int)$mlMargin; ?>%)</th>
                                <th style="text-align: center;">IVA</th>
                                <th style="text-align: center;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $p): 
                                $cost = $p['unit_cost_usd'];
                                $priceGremio = $cost * (1 + ($gremioMargin / 100));
                                $priceWeb = $cost * (1 + ($webMargin / 100));
                                $priceML = $cost * (1 + ($mlMargin / 100));
                            ?>
                                <tr class="product-row" data-sku="<?php echo strtolower($p['sku']); ?>"
                                    data-desc="<?php echo strtolower($p['description']); ?>">
                                    <td style="font-weight: bold; color: #fff;"><?php echo $p['sku']; ?></td>
                                    <td>
                                        <div style="font-weight: 500;"><?php echo $p['description']; ?></div>
                                        <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;">
                                            <i class="fas fa-tag"></i> <?php echo $p['brand']; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div><?php echo $p['category'] ?? '-'; ?></div>
                                        <div style="font-size: 0.8rem; color: #64748b;">
                                            <?php echo $p['subcategory'] ?? ''; ?>
                                        </div>
                                    </td>
                                    <td style="text-align: right; color: var(--accent-violet); font-weight: 700; background: rgba(139, 92, 246, 0.05);">
                                        $ <?php echo number_format($cost, 2); ?>
                                    </td>
                                    <td style="text-align: right; color: #cbd5e1;">$ <?php echo number_format($priceGremio, 2); ?></td>
                                    <td style="text-align: right; color: #cbd5e1;">$ <?php echo number_format($priceWeb, 2); ?></td>
                                    <td style="text-align: right; color: #cbd5e1;">$ <?php echo number_format($priceML, 2); ?></td>
                                    <td style="text-align: center;">
                                        <span class="badge badge-info"><?php echo $p['iva_rate']; ?>%</span>
                                    </td>
                                    <td style="text-align: center;">
                                        <a href="config_productos_add.php?sku=<?php echo urlencode($p['sku']); ?>" class="btn-edit" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <script>
        document.getElementById('tableSearch').addEventListener('input', function (e) {
            const q = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.product-row');
            rows.forEach(row => {
                const sku = row.getAttribute('data-sku');
                const desc = row.getAttribute('data-desc');
                if (sku.includes(q) || desc.includes(q)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
PHP;
writeFile(__DIR__ . '/productos.php', $contentProducts);

// ---------------------------------------------------------
// 3. Regex Patch for Remaining Files
// ---------------------------------------------------------
echo "<h3>Parcheado de Navegación en Archivos Legados</h3>";

$filesToPatch = [
    'index.php',
    'analisis.php',
    'presupuestos.php',
    'clientes.php',
    'proveedores.php',
    'compras.php',
    'crm.php',
    'cotizador.php',
    'importar.php', // Also patching importar.php to use include, since we didn't rewrite it fully above (only config/products) - Wait, we should rewrite it or patch it. Let's patch it.
    'config_precios.php'
];

foreach ($filesToPatch as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        $content = file_get_contents(__DIR__ . '/' . $file);

        // Regex to find <nav class="sidebar">...</nav>
        // We use dotall s modifier
        $pattern = '/<nav class="sidebar">.*?<\/nav>/s';
        $replacement = '<?php include \'src/includes/sidebar.php\'; ?>';

        $newContent = preg_replace($pattern, $replacement, $content);

        if ($newContent !== null && $newContent !== $content) {
            if (file_put_contents(__DIR__ . '/' . $file, $newContent)) {
                echo "<p>Parcheado: $file ... <span style='color:green'>[OK]</span></p>";
            } else {
                echo "<p>Parcheado: $file ... <span style='color:red'>[ERROR WRITE]</span></p>";
            }
        } else {
            echo "<p>Saltado (Sin cambios o no encontrado): $file</p>";
        }
    } else {
        echo "<p>Saltado (No existe): $file</p>";
    }
}

echo "<hr><p>¡Actualización v7 Completa! Navegación Centralizada.</p>";
?>