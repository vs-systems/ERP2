<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/crm/CRM.php';

use Vsys\Modules\CRM\CRM;

header('Content-Type: application/json');

$crm = new CRM();
$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'move_lead_manual':
            $id = $_POST['id'] ?? null;
            $status = $_POST['status'] ?? '';
            if ($id && $status) {
                $crm->moveLeadToStatus($id, $status);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
            }
            break;

        case 'delete_lead':
            $id = $_POST['id'] ?? null;
            if ($id) {
                $crm->deleteLead($id);
                echo json_encode(['success' => true]);
            }
            break;

        case 'reset_crm':
            if ($_SESSION['role'] === 'Admin' || $_SESSION['role'] === 'Sistemas') {
                $crm->resetCRM();
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'No autorizado']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Acción no válida']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
