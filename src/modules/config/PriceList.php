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
        return $stmt->execute([(float) $percent, $id]);
    }

    /**
     * Calculate price based on cost and target list
     */
    public function calculatePrice($cost, $listId)
    {
        $stmt = $this->db->prepare("SELECT margin_percent FROM price_lists WHERE id = ?");
        $stmt->execute([$listId]);
        $margin = $stmt->fetchColumn();

        if ($margin === false)
            return $cost;

        return $cost * (1 + ($margin / 100));
    }

    /**
     * Get price by list name (Gremio, Web, Mostrador)
     */
    public function getPriceByListName($costUsd, $ivaPercent, $listName, $exchangeRate = 1.0, $includeIva = true)
    {
        $stmt = $this->db->prepare("SELECT margin_percent FROM price_lists WHERE name = ?");
        $stmt->execute([$listName]);
        $margin = $stmt->fetchColumn();

        if ($margin === false)
            $margin = 0;

        $priceUsd = $costUsd * (1 + ($margin / 100));

        if ($includeIva) {
            $priceUsd *= (1 + ($ivaPercent / 100));
        }

        return $priceUsd * $exchangeRate;
    }
}


