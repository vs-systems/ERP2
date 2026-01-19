<?php
require_once 'auth_check.php';
/**
 * VS System ERP - Add Product Manually
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
        'unit_cost_usd' => $_POST['unit_cost_usd'],
        'unit_price_usd' => $_POST['unit_price_usd'], // Can be 0 if we rely on margins, but legacy support keeps it.
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
    // Manual search query since Catalog doesn't have getBySku public method easily accessible in one line (it has search).
    // Let's rely on DB directly or add method. Simplest: DB query here.
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
    <header
        style="background: #020617; border-bottom: 2px solid var(--accent-violet); display: flex; justify-content: space-between; align-items: center; padding: 0 20px;">
        <div style="display: flex; align-items: center; gap: 20px;">
            <img src="logo_display.php?v=2" alt="VS System" class="logo-large"class="logo-large">
            <div style="color: #fff; font-family: 'Inter', sans-serif; font-weight: 700; font-size: 1.4rem;">
                Configuració³n <span style="color: var(--accent-violet);">Productos</span>
            </div>
        </div>
        <div class="header-right" style="color: #cbd5e1;">
            <span class="user-badge"><i class="fas fa-user-circle"></i> Admin</span>
        </div>
    </header>

    <div class="dashboard-container">
        <?php include 'sidebar.php'; ?>

        <main class="content">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $status; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <h3><i class="fas fa-plus-circle"></i> Nueva Carga / Editar Producto</h3>
                <form method="POST" id="product-form">
                    <input type="hidden" name="save_product" value="1">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>SKU (C&oacute;digo)</label>
                            <input type="text" name="sku" id="sku" required placeholder="C&Oacute;DIGO">
                        </div>
                        <div class="form-group">
                            <label>Descripció³n del Producto</label>
                            <input type="text" name="description" id="form-description" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-barcode"></i> Có³digo EAN / Barra (Opcional)</label>
                            <input type="text" name="barcode" id="form-barcode"
                                placeholder="Escanear o ingresar có³digo...">
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group"
                            style="display: flex; align-items: center; gap: 10px; padding-top: 25px;">
                            <input type="checkbox" name="has_serial_number" id="form-has-serial">
                            <label for="form-has-serial" style="cursor: pointer;">Requiere Nóºmero de Serie
                                (Trazabilidad)</label>
                        </div>
                        <div class="form-group">
                            <label>Marca</label>
                            <input type="text" name="brand" id="brand" placeholder="MARCA">
                        </div>
                        <div class="form-group">
                            <label>URL de Imagen</label>
                            <input type="text" name="image_url" id="image_url"
                                placeholder="https://ejemplo.com/foto.jpg">
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
                            <input type="number" step="0.01" name="unit_cost_usd" id="unit_cost_usd" required
                                placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label>Precio Venta Base USD (Opcional)</label>
                            <input type="number" step="0.01" name="unit_price_usd" id="unit_price_usd"
                                placeholder="0.00">
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
                                    <option value="<?php echo $s['id']; ?>">
                                        <?php echo $s['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn-primary"><i class="fas fa-save"></i> GUARDAR PRODUCTO</button>
                    <a href="configuration.php" class="btn" style="background:#475569; margin-left: 10px;">Cancelar</a>
                </form>
            </div>
        </main>
    </div>
</body>

</html>




