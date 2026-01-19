<?php
/**
 * VS System ERP - AJAX Save Quotation
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/cotizador/Cotizador.php';

use Vsys\Modules\Cotizador\Cotizador;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['items'])) {
    echo json_encode(['success' => false, 'error' => 'No data received']);
    exit;
}

$cot = new Cotizador();

// Prepare data for save
$isRetention = $input['is_retention'] ?? false;
$isBank = $input['is_bank'] ?? false;

$data = [
    'quote_number' => $input['quote_number'],
    'client_id' => $input['client_id'] ?? 1,
    'user_id' => 1,
    'payment_method' => $input['payment_method'],
    'with_iva' => $input['with_iva'] ? 1 : 0,
    'exchange_rate_usd' => $input['exchange_rate_usd'],
    'subtotal_usd' => $input['subtotal_usd'],
    'subtotal_ars' => $input['subtotal_usd'] * $input['exchange_rate_usd'],
    'total_usd' => $input['total_usd'],
    'total_ars' => $input['total_ars'],
    'valid_until' => date('Y-m-d', strtotime('+2 days')),
    'observations' => $input['observations'] ?? '',
    'items' => []
];

foreach ($input['items'] as $item) {
    // Apply adjustments to unit price before saving to items table
    $adjustedPrice = $item['price'];
    if ($isRetention)
        $adjustedPrice *= 1.07;
    if ($isBank)
        $adjustedPrice *= 1.03;

    $data['items'][] = [
        'product_id' => $item['id'],
        'quantity' => $item['qty'],
        'unit_price_usd' => $adjustedPrice,
        'subtotal_usd' => $adjustedPrice * $item['qty'],
        'iva_rate' => $item['iva']
    ];
}

try {
    $id = $cot->saveQuotation($data);

    if ($id) {
        echo json_encode(['success' => true, 'id' => $id]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al guardar en la base de datos (Execute falló³)']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Excepció³n: ' . $e->getMessage()]);
}
?>





