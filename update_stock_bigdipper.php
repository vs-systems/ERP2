<?php
/**
 * VS System ERP - Scraper Automático de Stock y Costos (Big Dipper)
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/catalogo/Catalog.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuración de Big Dipper
define('BD_USER', 'javier@gozzi.ar');
define('BD_PASS', 'Milla6397@@');
define('BD_LOGIN_URL', 'https://www2.bigdipper.com.ar/api/AccountApi/Login');
define('BD_LIST_URL', 'https://www2.bigdipper.com.ar/api/Products/List');

$db = Vsys\Lib\Database::getInstance();
$catalog = new Vsys\Modules\Catalogo\Catalog();
$products = $catalog->getAllProducts();

$logFile = __DIR__ . '/bigdipper_scraper.log';

function log_message($msg)
{
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $msg\n", FILE_APPEND);
    echo $msg . "<br>";
}

log_message("=== Iniciando Scraper de Big Dipper ===");

// 1. Obtener Token
$loginData = ['User' => BD_USER, 'Password' => BD_PASS];
$ch = curl_init(BD_LOGIN_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$loginResponse = curl_exec($ch);
$loginInfo = json_decode($loginResponse, true);
curl_close($ch);

if (!isset($loginInfo['Token'])) {
    log_message("ERROR: No se pudo obtener el token de Big Dipper. Respuesta: " . $loginResponse);
    exit;
}

$token = $loginInfo['Token'];
log_message("Login exitoso. Token obtenido.");

$updatedCount = 0;
$errorCount = 0;

// 2. Procesar Productos
foreach ($products as $p) {
    // Solo procesamos productos que tengan SKU (podríamos filtrar por marca si tuviéramos esa columna)
    if (empty($p['sku']))
        continue;

    $sku = $p['sku'];

    // Consultar Producto en Big Dipper
    $searchParams = [
        "Description" => $sku,
        "Page" => 0,
        "PageSize" => 1
    ];

    $ch = curl_init(BD_LIST_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($searchParams));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token
    ]);
    $res = curl_exec($ch);
    $data = json_decode($res, true);
    curl_close($ch);

    if (isset($data['Products']) && count($data['Products']) > 0) {
        $bdProduct = null;
        // Buscamos coincidencia exacta de SKU
        foreach ($data['Products'] as $prod) {
            if (trim($prod['Code']) === trim($sku)) {
                $bdProduct = $prod;
                break;
            }
        }

        if ($bdProduct) {
            $stock = (int) $bdProduct['Stock'];
            $price = (float) $bdProduct['Price'];

            // Actualizar en DB
            $sql = "UPDATE products SET stock_current = ?, unit_cost_usd = ? WHERE id = ?";
            $db->prepare($sql)->execute([$stock, $price, $p['id']]);

            log_message("Actualizado SKU $sku: Stock $stock, Precio USD $price");
            $updatedCount++;
        } else {
            log_message("SKU $sku no encontrado de forma exacta en resultados de búsqueda.");
            $errorCount++;
        }
    } else {
        log_message("SKU $sku no encontrado en Big Dipper.");
        $errorCount++;
    }
}

log_message("=== Scraper Finalizado ===");
log_message("Resumen: $updatedCount actualizados, $errorCount no encontrados.");
?>