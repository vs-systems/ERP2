<?php
namespace Vsys\Modules\Catalogo;

use Vsys\Lib\Database;
use Vsys\Modules\Config\PriceList;

class PublicCatalog
{
    private $db;
    private $priceListModule;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->priceListModule = new PriceList();
    }

    /**
     * Get the current USD to ARS exchange rate from DB (or fallback).
     */
    public function getExchangeRate()
    {
        // Fetch latest rate from exchange_rates table
        $stmt = $this->db->prepare("SELECT rate FROM exchange_rates ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        $rate = $stmt->fetchColumn();

        // Fallback if no rate found (e.g. 1000 to avoid crash, but should be sync'd)
        return $rate ? (float) $rate : 1200.00;
    }

    /**
     * Get all products with calculated Price in ARS based on profile (Gremio or Web).
     */
    public function getProductsForUser($isLoggedIn = false)
    {
        $rate = $this->getExchangeRate();
        $targetListName = $isLoggedIn ? 'Gremio' : 'WEB Final ARS';

        // Get Margin
        $lists = $this->priceListModule->getAll();
        $margin = 0;
        foreach ($lists as $l) {
            if (stripos($l['name'], $targetListName) !== false) {
                $margin = (float) $l['margin_percent'];
                break;
            }
        }

        // Get Products
        $stmt = $this->db->prepare("SELECT * FROM products ORDER BY brand, description");
        $stmt->execute();
        $products = $stmt->fetchAll();

        $processedProducts = [];
        foreach ($products as $p) {
            $cost = (float) $p['unit_cost_usd'];
            $iva = (float) $p['iva_rate'] ?: 21; // Default 21 if not set

            // Calculate Price USD: Cost + Margin
            $priceUsd = $cost * (1 + ($margin / 100));

            // Add IVA
            $priceUsdWithIva = $priceUsd * (1 + ($iva / 100));

            // Convert to ARS
            $priceArs = $priceUsdWithIva * $rate;

            if ($priceArs > 0) {
                $p['price_final_usd'] = $priceUsd; // Price without IVA
                $p['price_final_ars_total'] = round($priceArs, 0); // Final ARS with IVA
                $p['price_final_formatted'] = number_format($p['price_final_ars_total'], 0, ',', '.');
                $p['image_url'] = !empty($p['image_url']) ? $p['image_url'] : 'https://placehold.co/300x300?text=No+Image';

                $processedProducts[] = $p;
            }
        }

        return [
            'rate' => $rate,
            'products' => $processedProducts,
            'target_list' => $targetListName
        ];
    }
}

