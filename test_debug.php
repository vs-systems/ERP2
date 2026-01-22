<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Debug Start</h1>";

echo "<h2>Checking Includes</h2>";
$files = [
    'src/config/config.php',
    'src/lib/Database.php',
    'src/modules/clientes/Client.php',
    'auth_check.php'
];

foreach ($files as $f) {
    if (file_exists(__DIR__ . '/' . $f)) {
        echo "OK: $f found<br>";
        try {
            require_once __DIR__ . '/' . $f;
            echo "OK: $f included<br>";
        } catch (Throwable $e) {
            echo "ERROR including $f: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "ERROR: $f NOT FOUND<br>";
    }
}

echo "<h2>Checking Database</h2>";
try {
    $db = Vsys\Lib\Database::getInstance();
    echo "Database: Connected<br>";
} catch (Throwable $e) {
    echo "Database Error: " . $e->getMessage() . "<br>";
}

echo "<h2>Checking Client Module</h2>";
try {
    $client = new Vsys\Modules\Clientes\Client();
    echo "Client Module: Instantiated<br>";
} catch (Throwable $e) {
    echo "Client Module Error: " . $e->getMessage() . "<br>";
}

echo "<h1>Debug End</h1>";
