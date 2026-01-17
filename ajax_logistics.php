<?php
/**
 * AJAX Handler - Logistics Actions
 */
header('Content-Type: application/json');
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/modules/logistica/Logistics.php';

use Vsys\Modules\Logistica\Logistics;

$logistics = new Logistics();
$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'update_phase':
            $success = $logistics->updateOrderPhase($_POST['quote_number'], $_POST['phase']);
            echo json_encode(['success' => $success]);
            break;

        case 'despachar':
            // 1. Log freight cost
            $logistics->logFreightCost([
                'quote_number' => $_POST['quote_number'],
                'dispatch_date' => date('Y-m-d'),
                'client_id' => 0, // Should fetch from quote
                'packages_qty' => $_POST['packages_qty'],
                'freight_cost' => $_POST['freight_cost'],
                'transport_id' => $_POST['transport_id']
            ]);

            // 2. Advance phase
            $success = $logistics->updateOrderPhase($_POST['quote_number'], 'En su transporte');
            echo json_encode(['success' => $success]);
            break;

        case 'create_remito':
            $remito = $logistics->createRemito($_POST['quote_number'], $_POST['transport_id']);
            echo json_encode(['success' => (bool) $remito, 'remito_number' => $remito]);
            break;

        case 'upload_guide':
            $quoteNumber = $_POST['quote_number'];
            if (!empty($_FILES['guide_photo']['name'])) {
                $uploadDir = __DIR__ . '/uploads/guides/';
                if (!is_dir($uploadDir))
                    mkdir($uploadDir, 0777, true);

                $fileName = $quoteNumber . '_' . time() . '_' . $_FILES['guide_photo']['name'];
                $dest = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['guide_photo']['tmp_name'], $dest)) {
                    // Log the document
                    $logistics->attachDocument($quoteNumber, 'quotation', 'Shipping Guide', 'uploads/guides/' . $fileName, 'Guía de transporte subida.');
                    // Update phase to Entregado
                    $logistics->updateOrderPhase($quoteNumber, 'Entregado');
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Error al guardar el archivo.']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'No se recibió ningún archivo.']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Acción no válida']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
