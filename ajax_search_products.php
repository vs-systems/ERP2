<?php
/**
 * VS System ERP - AJAX Product Search
 */

require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/catalogo/Catalog.php';
require_once __DIR__ . '/src/modules/config/PriceList.php';

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

// Pre-fetch all margins to avoid N+1 queries
$allMargins = [];
foreach ($priceList->getAll() as $list) {
    $allMargins[$list['name']] = (float) $list['margin_percent'];
}

// Inject calculated prices for each profile
foreach ($results as &$r) {
    $cost = (float) $r['unit_cost_usd'];

    // Use pre-fetched margins for speed
    $r['prices'] = [
        'Gremio' => round($cost * (1 + (($allMargins['Gremio'] ?? 25) / 100)), 2),
        'Web' => round($cost * (1 + (($allMargins['Web'] ?? 40) / 100)), 2),
        'Mostrador' => round($cost * (1 + (($allMargins['Mostrador'] ?? 55) / 100)), 2)
    ];

    $r['prices_by_name'] = $r['prices']; // Consolidate
}

echo json_encode($results);
?>