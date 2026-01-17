<?php
// restore_files.php - Restauración de Archivos Críticos v4 (Price Lists & Import Fix)
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Restaurador de Archivos Críticos v4 (Price List Update)</h1>";

/**
 * Helper to write file safely
 */
function writeFile($path, $content)
{
    echo "<p>Escribiendo: $path ... ";
    $dir = dirname($path);
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "<span style='color:orange'> (Directorio creado) </span>";
        } else {
            echo "<span style='color:red'> [ERROR: No se pudo crear directorio] </span>";
            return false;
        }
    }

    // Force delete if exists
    if (file_exists($path)) {
        unlink($path);
    }

    if (file_put_contents($path, $content) !== false) {
        echo "<span style='color:green'> [OK] </span> (" . strlen($content) . " bytes)</p>";
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($path, true);
        }
        return true;
    } else {
        echo "<span style='color:red'> [ERROR DE ESCRITURA] </span></p>";
        return false;
    }
}

// ---------------------------------------------------------
// 0. SQL Migration: price_lists table
// ---------------------------------------------------------
require_once __DIR__ . '/src/lib/Database.php';
use Vsys\Lib\Database;

try {
    $db = Database::getInstance();
    echo "<h3>Migración de Base de Datos</h3>";

    // 1. Create Table
    $sql = "CREATE TABLE IF NOT EXISTS price_lists (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE,
        margin_percent DECIMAL(5,2) DEFAULT 0.00,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $db->query($sql);
    echo "<p>Tabla <code>price_lists</code> verificada/creada.</p>";

    // 2. Insert Defaults
    $defaults = [
        ['name' => 'Gremio', 'margin' => 30.00],
        ['name' => 'Web', 'margin' => 40.00],
        ['name' => 'MercadoLibre', 'margin' => 50.00]
    ];
    $stmt = $db->prepare("INSERT IGNORE INTO price_lists (name, margin_percent) VALUES (:name, :margin)");
    foreach ($defaults as $def) {
        $stmt->execute([':name' => $def['name'], ':margin' => $def['margin']]);
    }
    echo "<p>Listas de precios por defecto insertadas.</p>";

    // 3. Ensure products table has correct columns (subcategory, brand, supplier_id)
    // We try to add them; if they exist, it might fail or we can use generic ALTER IGNORE logic or check via DESCRIBE.
    // Simple approach: Try adding one by one and catch exception if duplicate column.
    $alters = [
        "ALTER TABLE products ADD COLUMN subcategory VARCHAR(100) NULL AFTER category",
        "ALTER TABLE products ADD COLUMN brand VARCHAR(100) NULL AFTER description",
        "ALTER TABLE products ADD COLUMN supplier_id INT NULL AFTER subcategory"
    ];

    foreach ($alters as $alter) {
        try {
            $db->query($alter);
            echo "<p>Columna agregada: $alter</p>";
        } catch (Exception $e) {
            // Likely column exists
        }
    }

} catch (Exception $e) {
    echo "<p style='color:red'>Error SQL: " . $e->getMessage() . "</p>";
}

// ---------------------------------------------------------
// 1. src/modules/config/PriceList.php
// ---------------------------------------------------------
$contentPriceList = <<<'PHP'
<?php
/**
 * VS System ERP - Price List Module
 */

namespace Vsys\Modules\Config;

require_once __DIR__ . '/../../lib/Database.php';

use Vsys\Lib\Database;

class PriceList
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all price lists
     */
    public function getAll()
    {
        $stmt = $this->db->query("SELECT * FROM price_lists ORDER BY id ASC");
        return $stmt->fetchAll();
    }

    /**
     * Update margin for a specific list
     */
    public function updateMargin($id, $percent)
    {
        $stmt = $this->db->prepare("UPDATE price_lists SET margin_percent = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([(float)$percent, $id]);
    }

    /**
     * Calculate price based on cost and target list
     */
    public function calculatePrice($cost, $listId)
    {
        $stmt = $this->db->prepare("SELECT margin_percent FROM price_lists WHERE id = ?");
        $stmt->execute([$listId]);
        $margin = $stmt->fetchColumn();

        if ($margin === false) return $cost;

        return $cost * (1 + ($margin / 100));
    }
}
PHP;
writeFile(__DIR__ . '/src/modules/config/PriceList.php', $contentPriceList);

// ---------------------------------------------------------
// 2. config_precios.php
// ---------------------------------------------------------
$contentConfigUi = <<<'PHP'
<?php
require_once 'auth_check.php';
/**
 * Configuración de Precios y Márgenes - VS System ERP
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/config/PriceList.php';

use Vsys\Modules\Config\PriceList;

$priceListModule = new PriceList();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['update_margins'])) {
            foreach ($_POST['margins'] as $id => $margin) {
                $priceListModule->updateMargin($id, $margin);
            }
            $message = "Márgenes actualizados correctamente.";
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
    }
}

$lists = $priceListModule->getAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configuración de Precios - VS System</title>
    <link rel="stylesheet" href="css/style_premium.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header style="background: #020617; border-bottom: 2px solid var(--accent-violet); display: flex; justify-content: space-between; align-items: center; padding: 0 20px;">
        <div style="display: flex; align-items: center; gap: 20px;">
            <img src="logo_display.php?v=1" alt="VS System" class="logo-large" style="height: 50px; width: auto;">
            <div style="color: #fff; font-family: 'Inter', sans-serif; font-weight: 700; font-size: 1.4rem;">
                Configuración <span style="color: var(--accent-violet);">Precios</span>
            </div>
        </div>
        <div class="header-right" style="color: #cbd5e1;">
            <span class="user-badge"><i class="fas fa-user-circle"></i> Admin</span>
        </div>
    </header>

    <div class="dashboard-container">
        <nav class="sidebar">
            <a href="index.php" class="nav-link"><i class="fas fa-home"></i> DASHBOARD</a>
            <a href="productos.php" class="nav-link"><i class="fas fa-box-open"></i> PRODUCTOS</a>
            <a href="importar.php" class="nav-link"><i class="fas fa-upload"></i> IMPORTAR</a>
            <a href="config_precios.php" class="nav-link active"><i class="fas fa-tags"></i> LISTAS DE PRECIOS</a>
        </nav>

        <main class="content">
            <div class="card">
                <h2><i class="fas fa-percentage"></i> Gestión de Márgenes por Lista</h2>
                <p style="color: #94a3b8; margin-bottom: 2rem;">Defina el porcentaje de ganancia sobre el costo (USD) para cada lista de precios.</p>

                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Lista de Precios</th>
                                    <th>Margen (%)</th>
                                    <th>Ejemplo (Costo $100)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lists as $list): ?>
                                    <tr>
                                        <td><strong><?php echo $list['name']; ?></strong></td>
                                        <td>
                                            <input type="number" step="0.01" name="margins[<?php echo $list['id']; ?>]" 
                                                   value="<?php echo $list['margin_percent']; ?>" 
                                                   class="form-control" style="width: 100px; display: inline-block;"> %
                                        </td>
                                        <td style="color: #10b981;">
                                            $ <?php echo number_format(100 * (1 + $list['margin_percent']/100), 2); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div style="margin-top: 2rem; text-align: right;">
                        <button type="submit" name="update_margins" class="btn-primary">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
PHP;
writeFile(__DIR__ . '/config_precios.php', $contentConfigUi);

// ---------------------------------------------------------
// 3. src/modules/catalogo/Catalog.php
// ---------------------------------------------------------
// Simplified update - REPLACES entire file content logic here to ensure it sticks
$contentCatalog = <<<'PHP'
<?php
/**
 * VS System ERP - Catalog Module
 */

namespace Vsys\Modules\Catalogo;

use Vsys\Lib\Database;

class Catalog
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAllProducts()
    {
        $stmt = $this->db->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.description ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getProviders()
    {
        $stmt = $this->db->prepare("SELECT id, name FROM entities WHERE type = 'provider' OR type = 'supplier' ORDER BY name ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function searchProducts($query)
    {
        $sql = "SELECT * FROM products WHERE 
                sku LIKE ? OR 
                barcode LIKE ? OR 
                provider_code LIKE ? OR 
                description LIKE ? 
                LIMIT 20";
        $searchTerm = "%$query%";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        return $stmt->fetchAll();
    }

    public function addProduct($data)
    {
        $sql = "INSERT INTO products (sku, barcode, image_url, provider_code, description, category, subcategory, supplier_id, unit_cost_usd, unit_price_usd, iva_rate, brand, has_serial_number, stock_current) 
                VALUES (:sku, :barcode, :image_url, :provider_code, :description, :category, :subcategory, :supplier_id, :unit_cost_usd, :unit_price_usd, :iva_rate, :brand, :has_serial_number, :stock_current)
                ON DUPLICATE KEY UPDATE 
                barcode = VALUES(barcode),
                image_url = VALUES(image_url),
                description = VALUES(description),
                category = VALUES(category),
                subcategory = VALUES(subcategory),
                supplier_id = VALUES(supplier_id),
                brand = VALUES(brand),
                unit_cost_usd = VALUES(unit_cost_usd),
                unit_price_usd = VALUES(unit_price_usd),
                iva_rate = VALUES(iva_rate),
                has_serial_number = VALUES(has_serial_number)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':sku' => $data['sku'],
            ':barcode' => $data['barcode'] ?? null,
            ':image_url' => $data['image_url'] ?? null,
            ':provider_code' => $data['provider_code'] ?? null,
            ':description' => $data['description'],
            ':category' => $data['category'] ?? '',
            ':subcategory' => $data['subcategory'] ?? '',
            ':supplier_id' => $data['supplier_id'] ?? null,
            ':unit_cost_usd' => $data['unit_cost_usd'],
            ':unit_price_usd' => $data['unit_price_usd'],
            ':iva_rate' => $data['iva_rate'],
            ':brand' => $data['brand'] ?? '',
            ':has_serial_number' => $data['has_serial_number'] ?? 0,
            ':stock_current' => $data['stock_current'] ?? 0
        ]);
    }

    public function getCategories()
    {
        $stmt = $this->db->prepare("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function importProductsFromCsv($filePath, $providerId = null)
    {
        $handle = fopen($filePath, "r");
        if (!$handle)
            return false;

        // Skip header
        fgetcsv($handle, 1000, ";");

        $imported = 0;
        $categories = [];
        $subcategories = [];
        $suppliers = [];

        while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
            if (count($data) < 8)
                continue;

            $sku = trim($data[0]);
            $description = trim($data[1]);
            $brand = trim($data[2]);
            $cost = floatval(str_replace(',', '.', $data[3]));
            $iva = floatval(str_replace(',', '.', $data[4]));
            $catName = trim($data[5]);
            $subcatName = trim($data[6]);
            $providerName = trim($data[7]);

            // 1. Resolve Category
            if (!isset($categories[$catName])) {
                $stmt = $this->db->prepare("SELECT id FROM categories WHERE name = ?");
                $stmt->execute([$catName]);
                $id = $stmt->fetchColumn();
                if (!$id && $catName) {
                    $this->db->prepare("INSERT INTO categories (name) VALUES (?)")->execute([$catName]);
                    $id = $this->db->lastInsertId();
                }
                $categories[$catName] = $id;
            }
            $catId = $categories[$catName] ?? null;

            // 3. Resolve Supplier/Provider
            $supplierId = null;
            if ($providerName) {
                if (!isset($suppliers[$providerName])) {
                    $stmt = $this->db->prepare("SELECT id FROM entities WHERE name = ? AND (type = 'provider' OR type = 'supplier')");
                    $stmt->execute([$providerName]);
                    $id = $stmt->fetchColumn();
                    if (!$id) {
                         $sqlProv = "INSERT INTO entities (type, name, is_enabled) VALUES ('provider', ?, 1)";
                         $this->db->prepare($sqlProv)->execute([$providerName]);
                         $id = $this->db->lastInsertId();
                    }
                    $suppliers[$providerName] = $id;
                }
                $supplierId = $suppliers[$providerName];
            }

            // 4. Update/Insert Product
            $this->addProduct([
                'sku' => $sku,
                'barcode' => null,
                'provider_code' => null,
                'description' => $description,
                'category' => $catName, 
                'category_id' => $catId,
                'subcategory' => $subcatName,
                'unit_cost_usd' => $cost,
                'unit_price_usd' => $cost * 1.4, 
                'iva_rate' => $iva,
                'brand' => $brand,
                'supplier_id' => $supplierId,
                'has_serial_number' => 0,
                'stock_current' => 0
            ]);

            $imported++;
        }
        fclose($handle);
        return $imported;
    }

    public function importEntitiesFromCsv($filePath, $type = 'client')
    {
        $handle = fopen($filePath, "r");
        if (!$handle)
            return false;

        fgetcsv($handle, 1000, ";");

        $imported = 0;
        while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
            if (count($data) < 1)
                continue;

            $sql = "INSERT INTO entities (
                        type, tax_id, document_number, name, fantasy_name, 
                        contact_person, email, phone, mobile, address, 
                        delivery_address, is_enabled
                    ) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
                    ON DUPLICATE KEY UPDATE 
                    document_number = VALUES(document_number),
                    name = VALUES(name),
                    fantasy_name = VALUES(fantasy_name),
                    contact_person = VALUES(contact_person),
                    email = VALUES(email),
                    phone = VALUES(phone),
                    mobile = VALUES(mobile),
                    address = VALUES(address),
                    delivery_address = VALUES(delivery_address)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $type,
                $data[2] ?? null, 
                $data[3] ?? null, 
                $data[0] ?? 'SIN NOMBRE',
                $data[1] ?? '',
                $data[7] ?? '',
                $data[4] ?? '',
                $data[5] ?? '',
                $data[6] ?? '',
                $data[8] ?? '',
                $data[9] ?? ''
            ]);
            $imported++;
        }
        fclose($handle);
        return $imported;
    }
}
PHP;
writeFile(__DIR__ . '/src/modules/catalogo/Catalog.php', $contentCatalog);

echo "<hr><p>¡Actualización Completa! Listas de precios creadas, Módulo actualizado y UI desplegada.</p>";
echo "<p><a href='config_precios.php' class='btn'>Ir a Configuración de Precios</a></p>";
?>