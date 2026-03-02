<?php
/**
 * VS System ERP - Mass Image Update from Sanyi Lights
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/catalogo/Catalog.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(600); // Increase limit for scraping

$db = Vsys\Lib\Database::getInstance();
$catalog = new Vsys\Modules\Catalogo\Catalog();
$products = $catalog->getAllProducts();

echo "<h2>Sincronización de Imágenes - Sanyi Lights</h2>";
echo "Escaneando productos...<br><br>";

$updated = 0;
$skipped = 0;
$notFound = 0;

foreach ($products as $p) {
    if (!empty($p['image_url']) && strpos($p['image_url'], 'supabase.co') !== false) {
        $skipped++;
        continue;
    }

    $sku = $p['sku'];
    $brand = strtoupper($p['brand']);

    // Solo procesar si la marca es SANYI (o similar)
    if (strpos($brand, 'SANYI') === false) {
        continue;
    }

    // Intentar obtener la página del producto en Sanyi
    // El patrón de URL es https://sanyilights.com.ar/producto/{sku}
    $productUrl = "https://sanyilights.com.ar/producto/" . urlencode($sku);

    $context = stream_context_create([
        "http" => [
            "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36\r\n"
        ]
    ]);

    $content = @file_get_contents($productUrl, false, $context);

    if ($content) {
        // Buscar imágenes en el contenido
        // El patrón detectado por el subagent es: https://yxldaywdnpzpfeftctpr.supabase.co/storage/v1/object/public/product-images/products/{SKU}/{timestamp}_{index}.{ext}
        preg_match_all('/https:\/\/yxldaywdnpzpfeftctpr\.supabase\.co\/storage\/v1\/object\/public\/product-images\/products\/' . preg_quote($sku, '/') . '\/[^"]+/', $content, $matches);

        if (!empty($matches[0])) {
            $imageUrl = $matches[0][0]; // Tomamos la primera imagen encontrada

            $db->prepare("UPDATE products SET image_url = ? WHERE id = ?")
                ->execute([$imageUrl, $p['id']]);

            echo "<span style='color:green;'>Actualizado: $sku</span><br>";
            $updated++;
        } else {
            echo "<span style='color:orange;'>Imágenes no encontradas para: $sku</span><br>";
            $notFound++;
        }
    } else {
        echo "<span style='color:red;'>Producto no encontrado en Sanyi: $sku</span><br>";
        $notFound++;
    }

    // Delay para no saturar al servidor
    usleep(500000); // 0.5s
}

echo "<br><b>Resultado:</b><br>";
echo "Actualizados: $updated <br>";
echo "Ya tenían imagen Sanyi (Omitidos): $skipped <br>";
echo "No encontrados / Sin imagen: $notFound <br>";

echo "<br><a href='productos.php'>Volver a Productos</a>";
