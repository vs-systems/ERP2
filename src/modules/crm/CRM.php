<?php
/**
 * VS System ERP - CRM Module Logic
 */

namespace Vsys\Modules\CRM;

require_once __DIR__ . '/../../lib/Database.php';

use Vsys\Lib\Database;

class CRM
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get Leads count by status
     */
    public function getLeadsStats()
    {
        try {
            $sql = "SELECT status, COUNT(*) as total FROM crm_leads GROUP BY status";
            return $this->db->query($sql)->fetchAll(\PDO::FETCH_KEY_PAIR);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get specific Leads by status for the pipeline
     */
    public function getLeadsByStatus($status)
    {
        try {
            $sql = "SELECT * FROM crm_leads WHERE status = :status ORDER BY updated_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':status' => $status]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Save/Create a new lead
     */
    public function saveLead($data)
    {
        if (isset($data['id']) && $data['id'] > 0) {
            $sql = "UPDATE crm_leads SET 
                    name = :name, contact_person = :contact, email = :email, 
                    phone = :phone, status = :status, notes = :notes 
                    WHERE id = :id";
            $params = [
                ':name' => $data['name'],
                ':contact' => $data['contact_person'] ?? '',
                ':email' => $data['email'] ?? '',
                ':phone' => $data['phone'] ?? '',
                ':status' => $data['status'] ?? 'Nuevo',
                ':notes' => $data['notes'] ?? '',
                ':id' => $data['id']
            ];
        } else {
            $sql = "INSERT INTO crm_leads (name, contact_person, email, phone, status, notes) 
                    VALUES (:name, :contact, :email, :phone, :status, :notes)";
            $params = [
                ':name' => $data['name'],
                ':contact' => $data['contact_person'] ?? '',
                ':email' => $data['email'] ?? '',
                ':phone' => $data['phone'] ?? '',
                ':status' => $data['status'] ?? 'Nuevo',
                ':notes' => $data['notes'] ?? ''
            ];
        }

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Log a new interaction and optionally sync with Lead Pipeline
     */
    public function logInteraction($entityId, $type, $description, $userId, $entityType = 'entity')
    {
        // 1. If it's an 'entity' (Client), check if we should create/link a Lead
        if ($entityType === 'entity') {
            $stmt = $this->db->prepare("SELECT name, contact_person, email, phone FROM entities WHERE id = ?");
            $stmt->execute([$entityId]);
            $ent = $stmt->fetch();

            if ($ent) {
                // Check if lead already exists based on name
                $stmtLead = $this->db->prepare("SELECT id FROM crm_leads WHERE name = ? LIMIT 1");
                $stmtLead->execute([$ent['name']]);
                $leadId = $stmtLead->fetchColumn();

                if (!$leadId) {
                    // Create Lead
                    $this->saveLead([
                        'name' => $ent['name'],
                        'contact_person' => $ent['contact_person'],
                        'email' => $ent['email'],
                        'phone' => $ent['phone'],
                        'status' => ($type === 'Presupuesto' || $type === 'Envío Presupuesto') ? 'Presupuestado' : 'Contactado',
                        'notes' => 'Auto-generado desde interacción con Cliente'
                    ]);
                } else {
                    // Update existing lead status
                    $newStatus = ($type === 'Presupuesto' || $type === 'Envío Presupuesto') ? 'Presupuestado' : 'Contactado';
                    $this->db->prepare("UPDATE crm_leads SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$newStatus, $leadId]);
                }
            }
        } elseif ($entityType === 'lead') {
            // Update existing lead status
            $newStatus = ($type === 'Presupuesto' || $type === 'Envío Presupuesto') ? 'Presupuestado' : 'Contactado';
            $this->db->prepare("UPDATE crm_leads SET status = ?, updated_at = NOW() WHERE id = ? AND status IN ('Nuevo', 'Contactado')")->execute([$newStatus, $entityId]);
        }

        $sql = "INSERT INTO crm_interactions (entity_id, entity_type, user_id, type, description, interaction_date) 
                VALUES (:eid, :etype, :uid, :type, :desc, NOW())";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':eid' => $entityId,
            ':etype' => $entityType,
            ':uid' => $userId,
            ':type' => $type,
            ':desc' => $description
        ]);
    }

    /**
     * Get recent interactions
     */
    public function getRecentInteractions($limit = 10)
    {
        try {
            $sql = "SELECT i.*, 
                           CASE 
                            WHEN i.entity_type = 'entity' THEN (SELECT name FROM entities WHERE id = i.entity_id LIMIT 1)
                            WHEN i.entity_type = 'lead' THEN (SELECT name FROM crm_leads WHERE id = i.entity_id LIMIT 1)
                           END as entity_name,
                           u.full_name as user_name 
                    FROM crm_interactions i 
                    LEFT JOIN users u ON i.user_id = u.id 
                    ORDER BY i.interaction_date DESC LIMIT :limit";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', (int) $limit, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Move lead to next/prev stage
     */
    public function moveLead($id, $direction)
    {
        $statuses = ['Nuevo', 'Contactado', 'Presupuestado', 'Ganado', 'Perdido'];

        $stmt = $this->db->prepare("SELECT status FROM crm_leads WHERE id = ?");
        $stmt->execute([$id]);
        $current = $stmt->fetchColumn();

        if (!$current)
            return false;

        $idx = array_search($current, $statuses);
        if ($direction === 'next' && $idx < count($statuses) - 1)
            $idx++;
        elseif ($direction === 'prev' && $idx > 0)
            $idx--;
        else
            return true; // No movement possible but ok

        $stmt = $this->db->prepare("UPDATE crm_leads SET status = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$statuses[$idx], $id]);
    }
}
