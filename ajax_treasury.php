<?php
/**
 * AJAX Handler - Treasury
 */
ob_start();
header('Content-Type: application/json');
ini_set('display_errors', 0);

require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/modules/treasury/Treasury.php';

use Vsys\Modules\Treasury\Treasury;

try {
    session_start();
    $action = $_POST['action'] ?? '';
    $treasury = new Treasury();
    $response = ['success' => false, 'error' => 'Acción no válida'];

    switch ($action) {
        case 'add_movement':
            $data = [
                'type' => $_POST['type'],
                'category' => $_POST['category'],
                'amount' => $_POST['amount'],
                'payment_method' => $_POST['payment_method'],
                'notes' => $_POST['notes'] ?? ''
            ];
            $id = $treasury->addMovement($data);
            $response = ['success' => true, 'movement_id' => $id];
            break;
    }

} catch (Exception $e) {
    error_log("AJAX Treasury Error: " . $e->getMessage());
    $response = ['success' => false, 'error' => $e->getMessage()];
}

ob_end_clean();
echo json_encode($response);
exit;
