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

        while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
            if (count($data) < 7)
                continue;

            $sku = trim($data[0]);
            $description = trim($data[1]);
            $brand = trim($data[2]);
            $cost = floatval(str_replace(',', '.', $data[3]));
            $price = floatval(str_replace(',', '.', $data[4]));
            $iva = floatval($data[5]);
            $catName = trim($data[6]);

            if (!isset($categories[$catName])) {
                $stmt = $this->db->prepare("INSERT IGNORE INTO categories (name) VALUES (?)");
                $stmt->execute([$catName]);
                $stmt = $this->db->prepare("SELECT id FROM categories WHERE name = ?");
                $stmt->execute([$catName]);
                $categories[$catName] = $stmt->fetchColumn();
            }
            $catId = $categories[$catName];

            $this->addProduct([
                'sku' => $sku,
                'barcode' => null,
                'provider_code' => null,
                'description' => $description,
                'category_id' => $catId,
                'unit_cost_usd' => $cost,
                'unit_price_usd' => $price,
                'iva_rate' => $iva,
                'brand' => $brand,
                'has_serial_number' => 0,
                'stock_current' => 0
            ]);

            if ($providerId) {
                $stmt = $this->db->prepare("SELECT id FROM products WHERE sku = ?");
                $stmt->execute([$sku]);
                $productId = $stmt->fetchColumn();

                if ($productId) {
                    $sqlPrice = "INSERT INTO supplier_prices (product_id, supplier_id, cost_usd) 
                                 VALUES (:pid, :sid, :cost) 
                                 ON DUPLICATE KEY UPDATE cost_usd = VALUES(cost_usd)";
                    $stmtPrice = $this->db->prepare($sqlPrice);
                    $stmtPrice->execute(['pid' => $productId, 'sid' => $providerId, 'cost' => $cost]);
                }
            }
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
                $data[2] ?? null, // tax_id
                $data[3] ?? null, // doc
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