<?php
/**
 * VS System ERP - AJAX Product Search
 */

require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/catalogo/Catalog.php';

use Vsys\Modules\Catalogo\Catalog;

if (session_status() === PHP_SESSION_NONE)
    session_start();

header('Content-Type: application/json');

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$cid = $_SESSION['company_id'] ?? 1; // Fallback to 1 for safety

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$catalog = new Catalog($cid);
$results = $catalog->searchProducts($query);

echo json_encode($results);
?>