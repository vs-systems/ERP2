<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Starting syntax check...<br>";

// Define custom error handler to catch fatal errors
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null && $error['type'] === E_ERROR) {
        echo "FATAL ERROR: " . $error['message'] . " in " . $error['file'] . ":" . $error['line'];
    }
});

try {
    $path = __DIR__ . '/src/modules/purchases/Purchases.php';
    echo "Including: $path<br>";

    if (!file_exists($path)) {
        throw new Exception("File not found: $path");
    }

    require_once $path;
    echo "Included. namespace: Vsys\Modules\Purchases<br>";

    if (!class_exists('Vsys\Modules\Purchases\Purchases')) {
        throw new Exception("Class Vsys\Modules\Purchases\Purchases not found");
    }

    echo "Class exists.<br>";

    // Check Database dependency
    $dbPath = __DIR__ . '/src/lib/Database.php';
    require_once $dbPath;
    echo "Database included.<br>";

    $p = new Vsys\Modules\Purchases\Purchases();
    echo "Purchases instantiated OK.<br>";

} catch (Throwable $t) {
    echo "Exception: " . $t->getMessage() . " in " . $t->getFile() . ":" . $t->getLine();
}
?>