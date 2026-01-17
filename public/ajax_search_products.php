<?php
/**
 * VS System ERP - AJAX Product Search
 */

require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/lib/Database.php';
require_once __DIR__ . '/../src/modules/catalogo/Catalog.php';

use Vsys\Modules\Catalogo\Catalog;

header('Content-Type: application/json');

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$catalog = new Catalog();
$results = $catalog->searchProducts($query);

echo json_encode($results);
?>