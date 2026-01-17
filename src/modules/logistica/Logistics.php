<?php
namespace Vsys\Modules\Logistica;

use Vsys\Lib\Database;

class Logistics
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get orders ready for preparation or in logistics process
     */
    public function getOrdersForPreparation()
    {
        // Join with logistics_process to get current phase
        $sql = "SELECT q.*, e.name as client_name, lp.current_phase 
                FROM quotations q
                LEFT JOIN entities e ON q.client_id = e.id
                LEFT JOIN logistics_process lp ON q.quote_number = lp.quote_number
                WHERE q.payment_status = 'Pagado' OR q.authorized_dispatch = 1 OR lp.id IS NOT NULL
                ORDER BY q.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Update order phase
     */
    public function updateOrderPhase($quoteNumber, $newPhase)
    {
        $stmt = $this->db->prepare("INSERT INTO logistics_process (quote_number, current_phase) 
                                   VALUES (?, ?) 
                                   ON DUPLICATE KEY UPDATE current_phase = ?, updated_at = NOW()");
        return $stmt->execute([$quoteNumber, $newPhase, $newPhase]);
    }

    /**
     * Log freight cost analysis data
     */
    public function logFreightCost($data)
    {
        $sql = "INSERT INTO logistics_freight_costs 
                (quote_number, dispatch_date, client_id, packages_qty, freight_cost, transport_id) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['quote_number'],
            $data['dispatch_date'] ?? date('Y-m-d'),
            $data['client_id'],
            $data['packages_qty'],
            $data['freight_cost'],
            $data['transport_id']
        ]);
    }

    /**
     * Get master list of transport companies with new fields
     */
    public function getTransports($onlyActive = true)
    {
        $sql = "SELECT * FROM transports";
        if ($onlyActive)
            $sql .= " WHERE is_active = TRUE";
        $sql .= " ORDER BY name";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Save/Update Transport (including new fields)
     */
    public function saveTransport($data)
    {
        if (isset($data['id']) && $data['id']) {
            $stmt = $this->db->prepare("UPDATE transports SET 
                name = ?, contact_person = ?, phone = ?, email = ?, 
                address = ?, cuit = ?, can_pickup = ?, is_active = ? 
                WHERE id = ?");
            return $stmt->execute([
                $data['name'],
                $data['contact_person'],
                $data['phone'],
                $data['email'],
                $data['address'] ?? '',
                $data['cuit'] ?? '',
                $data['can_pickup'] ?? 0,
                $data['is_active'],
                $data['id']
            ]);
        } else {
            $stmt = $this->db->prepare("INSERT INTO transports 
                (name, contact_person, phone, email, address, cuit, can_pickup) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            return $stmt->execute([
                $data['name'],
                $data['contact_person'],
                $data['phone'],
                $data['email'],
                $data['address'] ?? '',
                $data['cuit'] ?? '',
                $data['can_pickup'] ?? 0
            ]);
        }
    }

    /**
     * Create Remito (Dispatch Note)
     */
    public function createRemito($quoteNumber, $transportId)
    {
        $remitoNum = 'REM-' . strtoupper(substr(uniqid(), -6));
        $stmt = $this->db->prepare("INSERT INTO logistics_remitos (quote_number, transport_id, remito_number, status) VALUES (?, ?, ?, 'Pending')");
        if ($stmt->execute([$quoteNumber, $transportId, $remitoNum])) {
            // Also advance phase to 'En su transporte'
            $this->updateOrderPhase($quoteNumber, 'En su transporte');
            return $remitoNum;
        }
        return false;
    }

    /**
     * Get Shipping Stats for Dashboard (current month)
     */
    public function getShippingStats()
    {
        $stats = [
            'pending' => 0,
            'prepared' => 0,
            'dispatched' => 0
        ];

        try {
            // Pending: En reserva or En preparación
            $res = $this->db->query("SELECT COUNT(*) FROM logistics_process WHERE current_phase IN ('En reserva', 'En preparación') AND MONTH(updated_at) = MONTH(CURRENT_DATE)")->fetchColumn();
            $stats['pending'] = $res ?: 0;

            // Prepared: Disponible
            $res = $this->db->query("SELECT COUNT(*) FROM logistics_process WHERE current_phase = 'Disponible' AND MONTH(updated_at) = MONTH(CURRENT_DATE)")->fetchColumn();
            $stats['prepared'] = $res ?: 0;

            // Dispatched: En su transporte or Entregado
            $res = $this->db->query("SELECT COUNT(*) FROM logistics_process WHERE current_phase IN ('En su transporte', 'Entregado') AND MONTH(updated_at) = MONTH(CURRENT_DATE)")->fetchColumn();
            $stats['dispatched'] = $res ?: 0;

            return $stats;
        } catch (\Exception $e) {
            return $stats;
        }
    }

    public function attachDocument($entityId, $entityType, $docType, $filePath, $notes = '')
    {
        $stmt = $this->db->prepare("INSERT INTO operation_documents (entity_id, entity_type, doc_type, file_path, notes) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$entityId, $entityType, $docType, $filePath, $notes]);
    }

    public function getDocuments($entityId, $entityType)
    {
        $stmt = $this->db->prepare("SELECT * FROM operation_documents WHERE entity_id = ? AND entity_type = ? ORDER BY uploaded_at DESC");
        $stmt->execute([$entityId, $entityType]);
        return $stmt->fetchAll();
    }
}
