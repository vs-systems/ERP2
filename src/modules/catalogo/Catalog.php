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

        // Transaction for better performance? optional.
        while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
            // New Format: SKU; DESCRIPCION; MARCA; COSTO; IVA %; CATEGORIA; SUBCATEGORIA; PROVEEDOR
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

            // 2. Resolve Subcategory (We assume simple text field in products or specific table? 
            // For now, let's store it as text in 'subcategory' column as current addProduct supports it)
            // If we had a table, we'd do similar logic.

            // 3. Resolve Supplier/Provider
            $supplierId = null;
            if ($providerName) {
                if (!isset($suppliers[$providerName])) {
                    $stmt = $this->db->prepare("SELECT id FROM entities WHERE name = ? AND (type = 'provider' OR type = 'supplier')");
                    $stmt->execute([$providerName]);
                    $id = $stmt->fetchColumn();
                    if (!$id) {
                        // Create basic supplier
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
                'category' => $catName, // Keep storing name if schema uses text
                'category_id' => $catId, // Also store ID if we have the col
                'subcategory' => $subcatName,
                'unit_cost_usd' => $cost,
                'unit_price_usd' => $cost * 1.4, // Default markup 40% if not set? Or 0. (User wants lists)
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