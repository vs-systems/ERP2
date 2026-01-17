<?php
/**
 * AJAX Handler - CRM Actions
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/modules/crm/CRM.php';

use Vsys\Modules\CRM\CRM;

$data = json_decode(file_get_contents('php://input'), true);
$crm = new CRM();

if (!$data || empty($data['action'])) {
    echo json_encode(['success' => false, 'error' => 'No action specified']);
    exit;
}

try {
    switch ($data['action']) {
        case 'save_lead':
            $success = $crm->saveLead($data);
            echo json_encode(['success' => $success]);
            break;

        case 'log_interaction':
            $success = $crm->logInteraction(
                $data['entity_id'],
                $data['user_id'] ?? 1,
                $data['type'],
                $data['description'],
                $data['entity_type'] ?? 'entity'
            );
            echo json_encode(['success' => $success]);
            break;

        case 'move_lead':
            $success = $crm->moveLead($data['id'], $data['direction']);
            echo json_encode(['success' => $success]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
