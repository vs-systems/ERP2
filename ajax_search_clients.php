<?php
/**
 * VS System ERP - AJAX Search Clients
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/clientes/Client.php';

use Vsys\Modules\Clientes\Client;

if (session_status() === PHP_SESSION_NONE)
    session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Content-Type: application/json');

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$clientMod = new Client();
$results = $clientMod->searchClients($query, 'all');

// Transform and add 'origin' field
$finalResults = [];
foreach ($results as $r) {
    $finalResults[] = [
        'id' => $r['id'],
        'name' => $r['name'],
        'type' => $r['type'] ?? 'client',
        'tax_id' => $r['tax_id'] ?? '',
        'address' => $r['address'] ?? '',
        'is_retention_agent' => $r['is_retention_agent'] ?? 0,
        'preferred_payment_method' => $r['preferred_payment_method'] ?? '',
        'origin' => 'entity'
    ];
}

// Search Leads too
$db = Vsys\Lib\Database::getInstance();
$q = "%" . strtolower($query) . "%";
$leads = $db->prepare("SELECT id, name, tax_id, address FROM crm_leads WHERE (LOWER(name) LIKE ? OR LOWER(tax_id) LIKE ?) LIMIT 10");
$leads->execute([$q, $q]);
foreach ($leads->fetchAll() as $l) {
    $finalResults[] = [
        'id' => $l['id'],
        'name' => $l['name'],
        'type' => 'Lead',
        'tax_id' => $l['tax_id'] ?? '',
        'address' => $l['address'] ?? '',
        'is_retention_agent' => 0,
        'preferred_payment_method' => '',
        'origin' => 'lead'
    ];
}

echo json_encode($finalResults);





