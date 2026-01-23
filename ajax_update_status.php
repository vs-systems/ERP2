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
    }

    // Sync status and is_confirmed for quotations
    if ($type === 'quotation') {
        if ($field === 'status') {
            $isConfirmed = ($value === 'Aceptado') ? 1 : 0;
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





