<?php
// Enable full error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Debug de Servidor</h1>";

echo "<h2>1. Verificando Rutas</h2>";
echo "Current Dir: " . __DIR__ . "<br>";
echo "Config Path expected: " . __DIR__ . '/src/config/config.php' . "<br>";

echo "<h2>2. Testeando Configuració³n</h2>";
if (file_exists(__DIR__ . '/src/config/config.php')) {
    echo "Config file found!<br>";
    try {
        require_once __DIR__ . '/src/config/config.php';
        echo "Config loaded successfully.<br>";
        echo "DB_HOST: " . DB_HOST . "<br>";
        echo "DB_USER: " . DB_USER . "<br>";
    } catch (Throwable $e) {
        echo "Error loading config: " . $e->getMessage() . "<br>";
    }
} else {
    echo "<strong style='color:red'>Config file NOT found at " . __DIR__ . '/src/config/config.php' . "</strong><br>";
}

echo "<h2>3. Testeando Conexió³n BD</h2>";
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    echo "<strong style='color:green'>Conexió³n Exitosa a la Base de Datos!</strong>";
} catch (PDOException $e) {
    echo "<strong style='color:red'>Error de Conexió³n: " . $e->getMessage() . "</strong>";
}

echo "<h2>4. Testeando Require de Clases</h2>";
$files = [
    '/src/lib/Database.php',
    '/src/modules/catalogo/Catalog.php'
];

foreach ($files as $f) {
    if (file_exists(__DIR__ . $f)) {
        echo "Found: $f<br>";
    } else {
        echo "<strong style='color:red'>MISSING: $f</strong><br>";
    }
}
?>




