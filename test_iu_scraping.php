<?php
$sku = "NVR1104HS-W-S2";
$url = "https://www.iuargsa.com/productos.php?q=" . urlencode($sku) . "&b=s";
$html = file_get_contents($url);
if ($html === false) {
    echo "Error: Could not fetch URL";
} else {
    echo "Success: Fetched " . strlen($html) . " bytes\n";
    // Check if image pattern exists
    if (preg_match('/src="admin\/productos\/([a-z0-9]+)\.jpg"/', $html, $matches)) {
        echo "Found image: " . $matches[1] . ".jpg\n";
    } else {
        echo "Image pattern not found in HTML\n";
    }
}





