<?php
/**
 * VS System ERP - Catalog Module
 */

namespace Vsys\Modules\Catalogo;

use Vsys\Lib\Database;
use Vsys\Lib\Logger;

class Catalog
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAllProducts()
    {
        // Relaxed filter: return all products if only one company exists or just to ensure functionality
        $stmt = $this->db->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY (p.stock_current > 0) DESC, p.description ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getProviders()
    {
        $stmt = $this->db->prepare("SELECT id, name FROM entities WHERE (type = 'provider' OR type = 'supplier') ORDER BY name ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function searchProducts($query)
    {
        $sql = "SELECT * FROM products WHERE 
                (sku LIKE ? OR 
                barcode LIKE ? OR 
                provider_code LIKE ? OR 
                description LIKE ?) 
                LIMIT 20";
        $searchTerm = "%$query%";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        return $stmt->fetchAll();
    }

    public function addProduct($data)
    {
        $sql = "INSERT INTO products (sku, barcode, image_url, provider_code, description, category, subcategory, supplier_id, unit_cost_usd, unit_price_usd, price_gremio, price_web, iva_rate, brand, has_serial_number, stock_current, stock_min, stock_transit, stock_incoming, incoming_date) 
                VALUES (:sku, :barcode, :image_url, :provider_code, :description, :category, :subcategory, :supplier_id, :unit_cost_usd, :unit_price_usd, :price_gremio, :price_web, :iva_rate, :brand, :has_serial_number, :stock_current, :stock_min, :stock_transit, :stock_incoming, :incoming_date)
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
                price_gremio = VALUES(price_gremio),
                price_web = VALUES(price_web),
                iva_rate = VALUES(iva_rate),
                has_serial_number = VALUES(has_serial_number),
                stock_current = VALUES(stock_current),
                stock_min = VALUES(stock_min),
                stock_transit = VALUES(stock_transit),
                stock_incoming = VALUES(stock_incoming),
                incoming_date = VALUES(incoming_date)";
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
            ':price_gremio' => $data['price_gremio'] ?? null,
            ':price_web' => $data['price_web'] ?? null,
            ':iva_rate' => $data['iva_rate'],
            ':brand' => $data['brand'] ?? '',
            ':has_serial_number' => $data['has_serial_number'] ?? 0,
            ':stock_current' => $data['stock_current'] ?? 0,
            ':stock_min' => $data['stock_min'] ?? 0,
            ':stock_transit' => $data['stock_transit'] ?? 0,
            ':stock_incoming' => $data['stock_incoming'] ?? 0,
            ':incoming_date' => $data['incoming_date'] ?? null
        ]);

        if ($success) {
            Logger::event($p ? 'PRODUCT_UPDATE' : 'PRODUCT_CREATE', 'product', null, [
                'sku' => $data['sku'],
                'description' => $data['description'],
                'cost' => $data['unit_cost_usd']
            ]);
        }

        return $success;
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

        fgetcsv($handle, 1000, ";");

        $imported = 0;
        $categories = [];
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

            $supplierId = null;
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
                'barcode' => null,
                'provider_code' => null,
                'description' => $description,
                'category' => $catName,
                'category_id' => $catId,
                'subcategory' => $subcatName,
                'unit_cost_usd' => $cost,
                'unit_price_usd' => $cost * 1.4,
                'price_gremio' => $cost * 1.4,
                'price_web' => $cost * 1.6,
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
}
