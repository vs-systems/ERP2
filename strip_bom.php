<?php
/**
 * Comprehensive BOM Stripper
 * Recursively checks all PHP files in src/ and root
 */

function stripBom($directory)
{
    if (!is_dir($directory))
        return;

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
    $phpFiles = new RegexIterator($iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

    echo "<h3>Processing directory: $directory</h3><ul>";
    foreach ($phpFiles as $file) {
        $path = $file[0];
        $data = file_get_contents($path);
        $bom = pack("CCC", 0xef, 0xbb, 0xbf);

        if (strncmp($data, $bom, 3) === 0) {
            $clean = substr($data, 3);
            file_put_contents($path, $clean);
            echo "<li>" . str_replace(__DIR__, '', $path) . ": <b style='color:green'>BOM REMOVED</b></li>";
        }
    }
    echo "</ul>";
}

// Strip from root files
$rootFiles = ['db_migrate_multi_tier_pricing.php', 'strip_bom.php', 'config_entities.php', 'index.php', 'login.php', 'catalogo.php'];
echo "<h3>Processing root files</h3><ul>";
foreach ($rootFiles as $file) {
    if (!file_exists($file))
        continue;
    $data = file_get_contents($file);
    $bom = pack("CCC", 0xef, 0xbb, 0xbf);
    if (strncmp($data, $bom, 3) === 0) {
        $clean = substr($data, 3);
        file_put_contents($file, $clean);
        echo "<li>$file: <b style='color:green'>BOM REMOVED</b></li>";
    }
}
echo "</ul>";

// Strip from src recursively
stripBom(__DIR__ . '/src');

echo "<p><b>Cleanup complete!</b> Please refresh your dashboard.</p>";
echo "<p><a href='index.php'>Go to Dashboard</a></p>";