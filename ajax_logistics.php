<?php
header('Content-Type: application/json');
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/logistica/Logistics.php';

use Vsys\Modules\Logistica\Logistics;
$logistics = new Logistics();

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'create_remito':
            $quoteNumber = $_POST['quote_number'] ?? '';
            $transportId = $_POST['transport_id'] ?? '';
            if (!$quoteNumber || !$transportId)
                throw new Exception("Faltan datos requeridos.");

            $remitoNum = $logistics->createRemito($quoteNumber, $transportId);
            if ($remitoNum) {
                echo json_encode(['success' => true, 'remito_number' => $remitoNum, 'message' => "Remito $remitoNum generado con éxito."]);
            } else {
                throw new Exception("Error al generar el remito.");
            }
            break;

        default:
            throw new Exception("Acción no válida.");
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
PHP;
