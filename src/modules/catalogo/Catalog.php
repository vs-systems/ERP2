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



    public function getCategoriesWithSubcategories()
    {
        // Get all distinct category + subcategory pairs
        $stmt = $this->db->query("SELECT DISTINCT category, subcategory FROM products WHERE category != '' ORDER BY category, subcategory");
        $rows = $stmt->fetchAll();

        $tree = [];
        foreach ($rows as $row) {
            $cat = $row['category'];
            $sub = $row['subcategory'];
            if (!isset($tree[$cat])) {
                $tree[$cat] = [];
            }
            if ($sub && !in_array($sub, $tree[$cat])) {
                $tree[$cat][] = $sub;
            }
        }
        return $tree;
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
        // 1. Insert or Update main product
        $sql = "INSERT INTO products (sku, barcode, image_url, provider_code, description, category, subcategory, unit_cost_usd, unit_price_usd, iva_rate, brand, has_serial_number, stock_current) 
                VALUES (:sku, :barcode, :image_url, :provider_code, :description, :category, :subcategory, :unit_cost_usd, :unit_price_usd, :iva_rate, :brand, :has_serial_number, :stock_current)
                ON DUPLICATE KEY UPDATE 
                barcode = VALUES(barcode),
                image_url = IF(VALUES(image_url) IS NOT NULL AND VALUES(image_url) != '', VALUES(image_url), image_url),
                description = VALUES(description),
                category = VALUES(category),
                subcategory = VALUES(subcategory),
                brand = VALUES(brand),
                iva_rate = VALUES(iva_rate),
                has_serial_number = VALUES(has_serial_number)";

        $stmt = $this->db->prepare($sql);
        $res = $stmt->execute([
            ':sku' => $data['sku'],
            ':barcode' => $data['barcode'] ?? null,
            ':image_url' => $data['image_url'] ?? null,
            ':provider_code' => $data['provider_code'] ?? null,
            ':description' => $data['description'],
            ':category' => $data['category'] ?? '',
            ':subcategory' => $data['subcategory'] ?? '',
            ':unit_cost_usd' => $data['unit_cost_usd'],
            ':unit_price_usd' => $data['unit_price_usd'] ?? ($data['unit_cost_usd'] * 1.4),
            ':iva_rate' => $data['iva_rate'] ?? 21.00,
            ':brand' => $data['brand'] ?? '',
            ':has_serial_number' => $data['has_serial_number'] ?? 0,
            ':stock_current' => $data['stock_current'] ?? 0
        ]);

        if (!$res)
            return false;

        // 2. Get the product ID
        $stmtId = $this->db->prepare("SELECT id FROM products WHERE sku = ?");
        $stmtId->execute([$data['sku']]);
        $productId = $stmtId->fetchColumn();

        // 3. Insert or Update supplier price
        if ($productId && isset($data['supplier_id']) && $data['supplier_id']) {
            $sqlSup = "INSERT INTO supplier_prices (product_id, entity_id, cost_usd) 
                       VALUES (:p_id, :e_id, :cost)
                       ON DUPLICATE KEY UPDATE cost_usd = VALUES(cost_usd)";
            $stmtSup = $this->db->prepare($sqlSup);
            $stmtSup->execute([
                ':p_id' => $productId,
                ':e_id' => $data['supplier_id'],
                ':cost' => $data['unit_cost_usd']
            ]);

            // 4. Update the main product's unit_cost_usd to be the minimum of all suppliers
            $sqlMin = "UPDATE products p 
                       SET unit_cost_usd = (SELECT MIN(cost_usd) FROM supplier_prices WHERE product_id = p.id)
                       WHERE p.id = ?";
            $this->db->prepare($sqlMin)->execute([$productId]);
        }

        return $res;
    }

    public function getCategories()
    {
        $stmt = $this->db->prepare("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function importProductsFromCsv($filePath, $defaultProviderId = null)
    {
        $handle = fopen($filePath, "r");
        if (!$handle)
            return false;

        // Try to detect delimiter (semicolon or comma)
        $firstLine = fgets($handle);
        $delimiter = (strpos($firstLine, ';') !== false) ? ';' : ',';
        rewind($handle);

        // Skip header
        fgetcsv($handle, 1000, $delimiter);

        $imported = 0;
        $suppliers = [];

        while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
            if (count($data) < 4)
                continue; // Basic check (SKU, Desc, Brand, Cost)

            $sku = trim($data[0]);
            $description = trim($data[1]);
            $brand = trim($data[2]);
            $cost = floatval(str_replace(',', '.', $data[3]));
            $iva = isset($data[4]) ? floatval(str_replace(',', '.', $data[4])) : 21.00;
            $catName = $data[5] ?? '';
            $subcatName = $data[6] ?? '';
            $providerName = trim($data[7] ?? '');

            $supplierId = $defaultProviderId;
            if ($providerName) {
                if (!isset($suppliers[$providerName])) {
                    $stmt = $this->db->prepare("SELECT id FROM entities WHERE name = ? AND (type = 'provider' OR type = 'supplier')");
                    $stmt->execute([$providerName]);
                    $id = $stmt->fetchColumn();
                    if (!$id) {
                        $this->db->prepare("INSERT INTO entities (type, name, is_enabled) VALUES ('provider', ?, 1)")->execute([$providerName]);
                        $id = $this->db->lastInsertId();
                    }
                    $suppliers[$providerName] = $id;
                }
                $supplierId = $suppliers[$providerName];
            }

            $this->addProduct([
                'sku' => $sku,
                'description' => $description,
                'brand' => $brand,
                'unit_cost_usd' => $cost,
                'iva_rate' => $iva,
                'category' => $catName,
                'subcategory' => $subcatName,
                'supplier_id' => $supplierId
            ]);

            $imported++;
        }
        fclose($handle);
        return $imported;
    }
}
