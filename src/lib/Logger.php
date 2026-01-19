<?php
/**
 * VS System ERP - Logger Class
 */

namespace Vsys\Lib;

class Logger
{
    private $db;
    private $user_id;
    private $company_id;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->user_id = $_SESSION['user_id'] ?? 0;
        $this->company_id = $_SESSION['company_id'] ?? 0;
    }

    /**
     * Log a system action
     * 
     * @param string $action The action performed (e.g. 'PRICE_UPDATE', 'QUOTE_CREATED')
     * @param string $entityType The type of entity involved (e.g. 'product', 'quotation')
     * @param int|null $entityId The ID of the entity
     * @param string|array|null $details Additional details for the log
     */
    public function log($action, $entityType = null, $entityId = null, $details = null)
    {
        if (is_array($details)) {
            $details = json_encode($details);
        }

        $sql = "INSERT INTO system_logs (user_id, company_id, action, entity_type, entity_id, details, ip_address) 
                VALUES (:uid, :cid, :action, :type, :eid, :details, :ip)";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'uid' => $this->user_id,
                'cid' => $this->company_id,
                'action' => $action,
                'type' => $entityType,
                'eid' => $entityId,
                'details' => $details,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'system'
            ]);
        } catch (\Exception $e) {
            // Silently fail to not break the main flow if logging fail
            error_log("Logging Error: " . $e->getMessage());
        }
    }

    /**
     * Static helper for quick logging
     */
    public static function event($action, $entityType = null, $entityId = null, $details = null)
    {
        $logger = new self();
        $logger->log($action, $entityType, $entityId, $details);
    }
}
