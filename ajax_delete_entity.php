<?php
/**
 * VS System ERP - AJAX Delete Entity (Client/Supplier)
 * Mandatory: Admin Role
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once 'auth_check.php';

header('Content-Type: application/json');

// Check Admin permission
if ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Permiso denegado. Solo administradores pueden eliminar.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? null;

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'ID no proporcionado']);
    exit;
}

try {
    $db = Vsys\Lib\Database::getInstance();

    // Check if there are related records (optional but recommended for UX)
    // For now, we perform a standard delete filtered by company_id for security
    $stmt = $db->prepare("DELETE FROM entities WHERE id = ? AND company_id = ?");
    $stmt->execute([$id, $_SESSION['company_id']]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No se encontró el registro o no pertenece a su empresa.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error de base de datos: ' . $e->getMessage()]);
}
