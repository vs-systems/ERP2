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
     * Get all products with calculated Web Price in ARS.
     */
    public function getProductsForWeb()
    {
        $rate = $this->getExchangeRate();

        // Get Web Margin
        $lists = $this->priceListModule->getAll();
        $webMargin = 40; // Default
        foreach ($lists as $l) {
            if ($l['name'] === 'Web') {
                $webMargin = (float) $l['margin_percent'];
                break;
            }
        }

        // Get Products
        $stmt = $this->db->prepare("SELECT * FROM products WHERE stock_current > 0 OR stock_current IS NULL ORDER BY brand, description"); // Maybe filter enabled?
        // Assuming all products in DB are active. Added basic stock check optional.
        // Actually, user didn't specify stock check. Let's just return all.
        $stmt = $this->db->prepare("SELECT * FROM products ORDER BY brand, description");
        $stmt->execute();
        $products = $stmt->fetchAll();

        $webProducts = [];
        foreach ($products as $p) {
            $cost = (float) $p['unit_cost_usd'];
            $iva = (float) $p['iva_rate'];

            // Calc Web Price USD: Cost + Margin
            $priceUsd = $cost * (1 + ($webMargin / 100));

            // Add IVA
            $priceUsdWithIva = $priceUsd * (1 + ($iva / 100));

            // Convert to ARS
            $priceArs = $priceUsdWithIva * $rate;

            // Only add if price is valid
            if ($priceArs > 0) {
                // Rounding
                $p['price_final_ars'] = round($priceArs, 0); // Round to integer for cleaner look? Or 2 decimals? Standard retail is 2 decimals or rounded. ARS is usually rounded.
                $p['price_final_formatted'] = number_format($p['price_final_ars'], 0, ',', '.'); // $ 1.000
                $p['image_url'] = !empty($p['image_url']) ? $p['image_url'] : 'https://placehold.co/300x300?text=No+Image';

                $webProducts[] = $p;
            }
        }

        return [
            'rate' => $rate,
            'products' => $webProducts
        ];
    }
}
