<?php
/**
 * VS System ERP - AJAX Delete Quotation
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once 'auth_check.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? null;

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'ID no proporcionado']);
    exit;
}

try {
    $db = Vsys\Lib\Database::getInstance();
    $db->beginTransaction();

    // 1. Delete items (filtered by quotation_id and company_id indirectly via the header check, or directly if items have company_id)
    $stmtItems = $db->prepare("DELETE FROM quotation_items WHERE quotation_id = ? AND company_id = ?");
    $stmtItems->execute([$id, $_SESSION['company_id']]);

    // 2. Delete header
    $stmtHeader = $db->prepare("DELETE FROM quotations WHERE id = ? AND company_id = ?");
    $stmtHeader->execute([$id, $_SESSION['company_id']]);

    $db->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    if ($db->inTransaction())
        $db->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}





