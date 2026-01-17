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
     * Get orders ready for preparation (Paid or Authorized)
     */
    public function getOrdersForPreparation()
    {
        $sql = "SELECT q.*, e.name as client_name 
                FROM quotations q
                LEFT JOIN entities e ON q.client_id = e.id
                WHERE q.payment_status = 'Paid' OR q.authorized_dispatch = 1
                ORDER BY q.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get master list of transport companies
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
     * Save/Update Transport
     */
    public function saveTransport($data)
    {
        if (isset($data['id']) && $data['id']) {
            $stmt = $this->db->prepare("UPDATE transports SET name = ?, contact_person = ?, phone = ?, email = ?, is_active = ? WHERE id = ?");
            return $stmt->execute([$data['name'], $data['contact_person'], $data['phone'], $data['email'], $data['is_active'], $data['id']]);
        } else {
            $stmt = $this->db->prepare("INSERT INTO transports (name, contact_person, phone, email) VALUES (?, ?, ?, ?)");
            return $stmt->execute([$data['name'], $data['contact_person'], $data['phone'], $data['email']]);
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
            return $remitoNum;
        }
        return false;
    }

    /**
     * Attach document to operation
     */
    public function attachDocument($entityId, $entityType, $docType, $filePath, $notes = '')
    {
        $stmt = $this->db->prepare("INSERT INTO operation_documents (entity_id, entity_type, doc_type, file_path, notes) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$entityId, $entityType, $docType, $filePath, $notes]);
    }

    /**
     * Get documents for an entity
     */
    public function getDocuments($entityId, $entityType)
    {
        $stmt = $this->db->prepare("SELECT * FROM operation_documents WHERE entity_id = ? AND entity_type = ? ORDER BY uploaded_at DESC");
        $stmt->execute([$entityId, $entityType]);
        return $stmt->fetchAll();
    }
}
