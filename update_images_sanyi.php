<?php
/**
 * VS System ERP - Mass Image Update from Sanyi Lights
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(1200); // 20 minutes

$db = Vsys\Lib\Database::getInstance();

echo "<h2>Sincronización de Imágenes - Sanyi Lights</h2>";
echo "Filtrando productos de marca SANYI...<br>";
flush();

// Obtener solo productos SANYI
$stmt = $db->prepare("SELECT id, sku, brand, image_url FROM products WHERE UPPER(brand) LIKE '%SANYI%'");
$stmt->execute();
$products = $stmt->fetchAll();

echo "Encontrados " . count($products) . " productos para procesar.<br><br>";
flush();

$updated = 0;
$skipped = 0;
$notFound = 0;

foreach ($products as $p) {
    $sku = trim($p['sku']);

    // Omitir si ya tiene imagen de supabase (ya sincronizado)
    if (!empty($p['image_url']) && strpos($p['image_url'], 'supabase.co') !== false) {
        echo "Omitido (Ya sincronizado): $sku<br>";
        $skipped++;
        flush();
        continue;
    }

    echo "Procesando $sku... ";
    flush();

    // Intentar obtener la página del producto
    $productUrl = "https://sanyilights.com.ar/producto/" . urlencode($sku);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $productUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $content = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200 && $content) {
        // Regex para buscar el patrón de Supabase
        // https://yxldaywdnpzpfeftctpr.supabase.co/storage/v1/object/public/product-images/products/SKU/timestamp_index.ext
        $pattern = '/https:\/\/yxldaywdnpzpfeftctpr\.supabase\.co\/storage\/v1\/object\/public\/product-images\/products\/' . preg_quote($sku, '/') . '\/[^"\'\s>]+/';

        if (preg_match($pattern, $content, $match)) {
            $imageUrl = $match[0];

            $db->prepare("UPDATE products SET image_url = ? WHERE id = ?")
                ->execute([$imageUrl, $p['id']]);

            echo "<span style='color:green;'>¡ÉXITO!</span><br>";
            $updated++;
        } else {
            echo "<span style='color:orange;'>Imagen no encontrada en la página (Posible SKU diferente en web)</span><br>";
            $notFound++;
        }
    } else {
        echo "<span style='color:red;'>Error HTTP $httpCode / Página no encontrada</span><br>";
        $notFound++;
    }

    echo str_repeat(" ", 1024); // Relleno para forzar el flush en algunos navegadores
    flush();

    // Delay para no saturar
    usleep(300000); // 0.3s
}

echo "<br><b>Resultado Final:</b><br>";
echo "Actualizados: $updated <br>";
echo "Omitidos: $skipped <br>";
echo "No encontrados: $notFound <br>";

echo "<br><a href='configuration.php'>Volver a Configuración</a>";
