<?php
/**
 * VS System ERP - Seller Dashboard Logic
 */

namespace Vsys\Modules\Dashboard;

use Vsys\Lib\Database;
use PDO;

class SellerDashboard
{
    private $db;
    private $seller_id;
    private $company_id;

    public function __construct($userId, $companyId = null)
    {
        $this->db = Database::getInstance();
        $this->seller_id = $userId;
        $this->company_id = $companyId ?: ($_SESSION['company_id'] ?? null);
    }

    public function getEfficiencyStats()
    {
        // Check if company_id exists in quotations
        $cols = $this->db->query("DESCRIBE quotations")->fetchAll(\PDO::FETCH_COLUMN);
        $hasCid = in_array('company_id', $cols);

        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN authorized_dispatch = 1 THEN 1 ELSE 0 END) as converted
                FROM quotations 
                WHERE seller_id = :sid";

        $params = ['sid' => $this->seller_id];
        if ($hasCid) {
            $sql .= " AND company_id = :cid";
            $params['cid'] = $this->company_id;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    public function getRecentQuotes()
    {
        $cols = $this->db->query("DESCRIBE quotations")->fetchAll(\PDO::FETCH_COLUMN);
        $hasCid = in_array('company_id', $cols);

        $sql = "SELECT q.*, e.name as client_name 
                FROM quotations q 
                JOIN entities e ON q.client_id = e.id 
                WHERE q.seller_id = :sid";

        $params = ['sid' => $this->seller_id];
        if ($hasCid) {
            $sql .= " AND q.company_id = :cid";
            $params['cid'] = $this->company_id;
        }

        $sql .= " ORDER BY q.created_at DESC LIMIT 5";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getClientShipments()
    {
        $cols = $this->db->query("DESCRIBE quotations")->fetchAll(\PDO::FETCH_COLUMN);
        $hasCid = in_array('company_id', $cols);

        $sql = "SELECT l.*, e.name as client_name 
                FROM logistics_process l 
                JOIN quotations q ON l.quote_number = q.quote_number 
                JOIN entities e ON q.client_id = e.id 
                WHERE q.seller_id = :sid";

        $params = ['sid' => $this->seller_id];
        if ($hasCid) {
            $sql .= " AND q.company_id = :cid";
            $params['cid'] = $this->company_id;
        }

        $sql .= " ORDER BY l.updated_at DESC LIMIT 5";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
