<?php
require_once 'auth_check.php';
/**
 * Importador Centralizado - VS System ERP
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/catalogo/Catalog.php';

use Vsys\Modules\Catalogo\Catalog;

$message = '';
$status = '';
$catalog = new Catalog();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $type = $_POST['import_type'] ?? 'product';
    $providerId = $_POST['provider_id'] ?? null;

    $targetDir = __DIR__ . "/../data/uploads/";
    if (!file_exists($targetDir))
        mkdir($targetDir, 0777, true);
    $targetFile = $targetDir . time() . "_" . basename($_FILES["csv_file"]["name"]);

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
                $status = "success";
            } else {
                $message = "Error al procesar el archivo CSV.";
                $status = "error";
            }
        } catch (\Exception $e) {
            $message = "Error: " . $e->getMessage();
            $status = "error";
        }
    }
}

$providers = $catalog->getProviders();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Importador Centralizado - VS System</title>
    <link rel="stylesheet" href="css/style_premium.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .import-box {
            border: 2px dashed var(--accent-violet);
            padding: 30px;
            text-align: center;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.05);
            cursor: pointer;
            margin: 15px 0;
        }

        .format-hint {
            font-size: 0.85rem;
            color: #94a3b8;
            background: #0f172a;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #818cf8;
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
            <a href="productos.php" class="nav-link"><i class="fas fa-box-open"></i> PRODUCTOS</a>
            <a href="presupuestos.php" class="nav-link"><i class="fas fa-history"></i> PRESUPUESTOS</a>
            <a href="clientes.php" class="nav-link"><i class="fas fa-users"></i> CLIENTES</a>
            <a href="proveedores.php" class="nav-link"><i class="fas fa-truck-loading"></i> PROVEEDORES</a>
            <a href="compras.php" class="nav-link"><i class="fas fa-cart-arrow-down"></i> COMPRAS</a>
            <a href="importar.php" class="nav-link active"><i class="fas fa-upload"></i> IMPORTAR</a>
            <a href="crm.php" class="nav-link"><i class="fas fa-handshake"></i> CRM</a>
            <a href="cotizador.php" class="nav-link"><i class="fas fa-file-invoice-dollar"></i> COTIZADOR</a>
        </nav>

        <main class="content">
            <div class="card">
                <h2><i class="fas fa-file-import"></i> Importar Datos al Sistema</h2>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $status; ?>"><?php echo $message; ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 1.5rem;">
                        <div class="form-group">
                            <label>¿Qu&eacute; desea importar?</label>
                            <select name="import_type" id="import_type" onchange="toggleProvider()"
                                style="width: 100%; padding: 10px; background: #1e293b; color: white; border: 1px solid #334155; border-radius: 6px;">
                                <option value="product">Cat&aacute;logo de Productos</option>
                                <option value="client">Base de Clientes</option>
                                <option value="supplier">Base de Proveedores</option>
                            </select>
                        </div>
                        <div class="form-group" id="provider-selector">
                            <label>Proveedor (Solo para productos)</label>
                            <select name="provider_id"
                                style="width: 100%; padding: 10px; background: #1e293b; color: white; border: 1px solid #334155; border-radius: 6px;">
                                <option value="">-- Sin proveedor (Costo Base) --</option>
                                <?php foreach ($providers as $p): ?>
                                    <option value="<?php echo $p['id']; ?>"><?php echo $p['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="import-box" onclick="document.getElementById('file-input').click()">
                        <i class="fas fa-upload" style="font-size: 2rem; margin-bottom: 10px;"></i>
                        <h4 id="file-name">Seleccione un archivo CSV</h4>
                        <input type="file" name="csv_file" id="file-input" accept=".csv" required style="display: none;"
                            onchange="updateFileName()">
                    </div>

                    <button type="submit" class="btn-primary" style="width: 100%; padding: 15px;">
                        <i class="fas fa-play"></i> INICIAR PROCESAMIENTO
                    </button>
                </form>

                <div id="product-hint" class="format-hint">
                    <strong>Formato Productos:</strong> SKU; DESCRIPCION; MARCA; COSTO; VENTA; IVA %; CATEGORIA
                </div>
                <div id="entity-hint" class="format-hint" style="display: none;">
                    <strong>Formato Clientes/Proveedores:</strong> RAZON SOCIAL; NOMBRE FANTASIA; CUIT; DNI; EMAIL;
                    TELEFONO; CELULAR; CONTACTO; DIRECCION; ENTREGA
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleProvider() {
            const type = document.getElementById('import_type').value;
            document.getElementById('provider-selector').style.display = (type === 'product' ? 'block' : 'none');
            document.getElementById('product-hint').style.display = (type === 'product' ? 'block' : 'none');
            document.getElementById('entity-hint').style.display = (type !== 'product' ? 'block' : 'none');
        }
        function updateFileName() {
            const input = document.getElementById('file-input');
            const display = document.getElementById('file-name');
            if (input.files.length > 0) display.innerText = input.files[0].name;
        }
    </script>
</body>

</html>
