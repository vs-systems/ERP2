<?php
// debug_config_test.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Debug Configuration Logic</h1>";

echo "<h2>RAW \$_GET</h2>";
var_dump($_GET);

echo "<h2>Logic Test</h2>";
$section = (isset($_GET['section']) && $_GET['section'] !== '') ? $_GET['section'] : 'main';
echo "Calculated \$section: [";
var_dump($section);
echo "]<br>";

echo "<h2>Comparison</h2>";
if ($section === 'main') {
    echo "MATCHES 'main'<br>";
} else {
    echo "DOES NOT MATCH 'main' (Why?)<br>";
    echo "String length: " . strlen($section) . "<br>";
    echo "Hex dump: " . bin2hex($section) . "<br>";
}

echo "<h2>File Info</h2>";
echo "Current File: " . __FILE__ . "<br>";
?>