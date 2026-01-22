<?php
/**
 * VS System ERP - AJAX Product Search
 */

require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/catalogo/Catalog.php';

use Vsys\Modules\Catalogo\Catalog;

header('Content-Type: application/json');

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$catalog = new Catalog();
$priceList = new \Vsys\Modules\Config\PriceList();
$results = $catalog->searchProducts($query);

// Inject calculated prices for each profile
foreach ($results as &$r) {
    $cost = (float) $r['unit_cost_usd'];
    $r['prices'] = [
        'Gremio' => round($priceList->calculatePrice($cost, 1), 2), // Assuming ID 1 is Gremio
        'Web' => round($priceList->calculatePrice($cost, 2), 2),    // Assuming ID 2 is Web
        'Mostrador' => round($priceList->calculatePrice($cost, 3), 2) // Assuming ID 3 is Mostrador
    ];

    // Fallback if IDs are different: find by name
    $r['prices_by_name'] = [
        'Gremio' => round($priceList->getPriceByListName($cost, 0, 'Gremio', 1.0, false), 2),
        'Web' => round($priceList->getPriceByListName($cost, 0, 'Web', 1.0, false), 2),
        'Mostrador' => round($priceList->getPriceByListName($cost, 0, 'Mostrador', 1.0, false), 2)
    ];
}

echo json_encode($results);
?>