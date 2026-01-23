<?php
/**
 * VS System ERP - AJAX Update Status (Confirmed / Paid)
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? null;
$type = $input['type'] ?? ''; // 'quotation' or 'purchase'
$field = $input['field'] ?? ''; // 'is_confirmed' or 'payment_status'
$value = $input['value'] ?? null;

if (!$id || !$type || !$field) {
    echo json_encode(['success' => false, 'error' => 'Paró¡metros incompletos']);
    exit;
}

try {
    $db = Vsys\Lib\Database::getInstance();
    $table = ($type === 'quotation') ? 'quotations' : 'purchases';

    $sql = "UPDATE $table SET $field = :val WHERE id = :id";
    $stmt = $db->prepare($sql);
    $res = $stmt->execute(['val' => $value, 'id' => $id]);

    // If marking as paid, also ensure it's confirmed
    if ($field === 'payment_status' && $value === 'Pagado') {
        $db->prepare("UPDATE $table SET is_confirmed = 1 WHERE id = ?")->execute([$id]);

        // --- CRM AUTOMATION ---
        if ($type === 'quotation') {
            // 1. Fetch quote details
            $stmtQ = $db->prepare("SELECT quote_number, client_id FROM quotations WHERE id = ?");
            $stmtQ->execute([$id]);
            $quote = $stmtQ->fetch();

            if ($quote) {
                // 2. Find and Update CRM Lead to 'Ganado'
                // Search by name (linked in CRM.php logInteraction) or other link
                $stmtC = $db->prepare("SELECT name FROM entities WHERE id = ?");
                $stmtC->execute([$quote['client_id']]);
                $clientName = $stmtC->fetchColumn();

                if ($clientName) {
                    $db->prepare("UPDATE crm_leads SET status = 'Ganado', updated_at = NOW() WHERE name = ?")
                        ->execute([$clientName]);

                    // Log interaction in CRM
                    $userId = $_SESSION['user_id'] ?? 0;
                    $db->prepare("INSERT INTO crm_interactions (entity_id, entity_type, user_id, type, description, interaction_date) 
                                 SELECT id, 'lead', ?, 'Venta', ?, NOW() FROM crm_leads WHERE name = ? LIMIT 1")
                        ->execute([$userId, "Pedido #{$quote['quote_number']} marcado como cobrado.", $clientName]);
                }

                // --- LOGISTICS AUTOMATION ---
                // Initialize in 'En reserva' when paid/confirmed
                $db->prepare("INSERT INTO logistics_process (quote_number, current_phase) 
                             VALUES (?, 'En reserva') 
                             ON DUPLICATE KEY UPDATE updated_at = NOW()")
                    ->execute([$quote['quote_number']]);
            }
        }
    }

    // Sync status and is_confirmed for quotations
    if ($type === 'quotation') {
        if ($field === 'status') {
            $isConfirmed = ($value === 'Aceptado' || $value === 'Pedido') ? 1 : 0;
            $db->prepare("UPDATE quotations SET is_confirmed = ? WHERE id = ?")->execute([$isConfirmed, $id]);
        } elseif ($field === 'is_confirmed') {
            $status = ($value == 1) ? 'Aceptado' : 'Pendiente';
            $db->prepare("UPDATE quotations SET status = ? WHERE id = ?")->execute([$status, $id]);
        }
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}





