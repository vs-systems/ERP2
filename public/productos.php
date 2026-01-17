<?php
/**
 * VS System ERP - Gestión de Productos
 */
require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/lib/Database.php';
require_once __DIR__ . '/../src/modules/catalogo/Catalog.php';

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

$products = $catalog->getAllProducts();
$suppliers = $catalog->getProviders();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestión de Productos - VS System</title>
    <link rel="stylesheet" href="css/style_premium.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .btn-edit {
            background: #ca8a04;
            color: white;
            padding: 5px 10px;
            border-radius: 6px;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <header
        style="background: #020617; border-bottom: 2px solid var(--accent-violet); display: flex; justify-content: space-between; align-items: center; padding: 0 20px;">
        <div style="display: flex; align-items: center; gap: 20px;">
            <img src="logo_display.php?v=1" alt="VS System" class="logo-large" style="height: 50px; width: auto;">
            <div
                style="color: #fff; font-family: 'Inter', sans-serif; font-weight: 700; font-size: 1.4rem; letter-spacing: 1px; text-shadow: 0 0 10px rgba(139, 92, 246, 0.4);">
                Vecino Seguro <span
                    style="background: linear-gradient(90deg, #8b5cf6, #d946ef); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Sistemas</span>
                by Javier Gozzi - 2026
            </div>
        </div>
        <div class="header-right" style="color: #cbd5e1;">
            <span class="user-badge"><i class="fas fa-user-circle"></i> Admin</span>
        </div>
    </header>

    <div class="dashboard-container">
        <nav class="sidebar">
            <a href="index.php" class="nav-link"><i class="fas fa-home"></i> DASHBOARD</a>
            <a href="analisis.php" class="nav-link"><i class="fas fa-chart-line"></i> AN&Aacute;LISIS OP.</a>
            <a href="productos.php" class="nav-link active"><i class="fas fa-box-open"></i> PRODUCTOS</a>
            <a href="presupuestos.php" class="nav-link"><i class="fas fa-history"></i> PRESUPUESTOS</a>
            <a href="clientes.php" class="nav-link"><i class="fas fa-users"></i> CLIENTES</a>
            <a href="proveedores.php" class="nav-link"><i class="fas fa-truck-loading"></i> PROVEEDORES</a>
            <a href="compras.php" class="nav-link"><i class="fas fa-cart-arrow-down"></i> COMPRAS</a>
            <a href="importar.php" class="nav-link"><i class="fas fa-upload"></i> IMPORTAR</a>
            <a href="crm.php" class="nav-link"><i class="fas fa-handshake"></i> CRM</a>
            <a href="cotizador.php" class="nav-link"><i class="fas fa-file-invoice-dollar"></i> COTIZADOR</a>
            <a href="catalogo.php" class="nav-link" target="_blank" style="color: #25d366; font-weight: 700;"><i
                    class="fas fa-external-link-alt"></i> VER CAT&Aacute;LOGO</a>
        </nav>

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
                            <label>URL de Imagen</label>
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
                            <input type="number" step="0.01" name="unit_cost_usd" id="unit_cost_usd" required
                                placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label>Precio Venta USD</label>
                            <input type="number" step="0.01" name="unit_price_usd" id="unit_price_usd" required
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
                                    <option value="<?php echo $s['id']; ?>"><?php echo $s['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn-primary"><i class="fas fa-save"></i> GUARDAR PRODUCTO</button>
                </form>
            </div>

            <div class="card" style="margin-top: 2rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3><i class="fas fa-list"></i> Cat&aacute;logo de Productos</h3>
                    <div style="position: relative;">
                        <input type="text" id="tableSearch" placeholder="Buscar por SKU o Descripci&oacute;n..."
                            style="padding: 8px 12px; border-radius: 20px; border: 1px solid var(--accent-violet); background: rgba(255,255,255,0.05); color: white; width: 300px;">
                        <i class="fas fa-search" style="position: absolute; right: 15px; top: 12px; opacity: 0.5;"></i>
                    </div>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Descripci&oacute;n / Marca</th>
                                <th>Cat. / Subcat.</th>
                                <th style="text-align: right;">Costo USD</th>
                                <th style="text-align: right;">Precio USD</th>
                                <th style="text-align: center;">IVA</th>
                                <th style="text-align: center;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $p): ?>
                                <tr class="product-row" data-sku="<?php echo strtolower($p['sku']); ?>"
                                    data-desc="<?php echo strtolower($p['description']); ?>">
                                    <td><strong>
                                            <?php echo $p['sku']; ?>
                                        </strong></td>
                                    <td>
                                        <?php echo $p['description']; ?><br>
                                        <small style="color: var(--accent-blue);">
                                            <?php echo $p['brand']; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php echo $p['category'] ?? '-'; ?><br>
                                        <small style="opacity: 0.7;">
                                            <?php echo $p['subcategory'] ?? '-'; ?>
                                        </small>
                                    </td>
                                    <td style="text-align: right;">$
                                        <?php echo number_format($p['unit_cost_usd'], 2); ?>
                                    </td>
                                    <td style="text-align: right; color: var(--accent-violet); font-weight: 700;">$
                                        <?php echo number_format($p['unit_price_usd'], 2); ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <?php echo $p['iva_rate']; ?>%
                                    </td>
                                    <td style="text-align: center;">
                                        <button class="btn-edit" onclick='editProduct(<?php echo json_encode($p); ?>)'>
                                            <i class="fas fa-edit"></i>
                                        </button>
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
        function editProduct(p) {
            document.getElementById('sku').value = p.sku;
            document.getElementById('description').value = p.description;
            document.getElementById('brand').value = p.brand;
            document.getElementById('image_url').value = p.image_url || '';
            document.getElementById('category').value = p.category || '';
            document.getElementById('subcategory').value = p.subcategory || '';
            document.getElementById('unit_cost_usd').value = p.unit_cost_usd;
            document.getElementById('unit_price_usd').value = p.unit_price_usd;
            document.getElementById('iva_rate').value = p.iva_rate;
            document.getElementById('supplier_id').value = p.supplier_id || '';

            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Live Search Filter
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