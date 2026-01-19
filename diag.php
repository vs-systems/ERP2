<?php
$files = [
    'src/lib/Database.php',
    'db_migrate_multi_tier_pricing.php',
    'src/config/config.php'
];

echo "<h3>BOM Check</h3><ul>";
foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    if (!file_exists($path)) {
        echo "<li>$file: File not found</li>";
        continue;
    }
    $data = file_get_contents($path);
    $bom = pack("CCC", 0xef, 0xbb, 0xbf);
    if (strncmp($data, $bom, 3) === 0) {
        echo "<li>$file: <b style='color:red'>BOM DETECTED!</b></li>";
    } else {
        echo "<li>$file: <b style='color:green'>No BOM</b></li>";
    }

    // Check for leading whitespace
    if (preg_match('/^\s+<\?php/', $data)) {
        echo "<li>$file: <b style='color:orange'>Leading whitespace detected!</b></li>";
    }
}
echo "</ul>";

echo "<h3>Namespace Test</h3>";
try {
    include 'test_ns_diag.php';
} catch (Throwable $e) {
    echo "Namespace inclusion failed: " . $e->getMessage();
}
