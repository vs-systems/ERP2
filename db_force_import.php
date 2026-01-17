<?php
// db_force_import.php
// Script de emergencia para importar la base de datos desde el archivo SQL en el servidor.

ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(300); // 5 minutos max

require_once __DIR__ . '/src/config/config.php';

echo "<h1>Importador de Base de Datos de Emergencia</h1>";

$dumpFile = __DIR__ . '/vsys_migration_dump_20260116_214027.sql';

if (!file_exists($dumpFile)) {
    die("<h3 style='color:red'>Error: No encuentro el archivo de respaldo: $dumpFile</h3><p>Asegurate de que el archivo .sql esté en la carpeta raíz.</p>");
}

echo "<p>Archivo encontrado: " . basename($dumpFile) . " (" . round(filesize($dumpFile) / 1024, 2) . " KB)</p>";

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color:green'>Conexión a BD exitosa.</p>";
} catch (PDOException $e) {
    die("<h3 style='color:red'>Error de conexión a BD: " . $e->getMessage() . "</h3>");
}

// Leer archivo
$sql = file_get_contents($dumpFile);

// Separar por sentencias (aproximación simple)
// Nota: Este dump generado por mi script custom usa ";\n" o ";\r\n" al final de las queries.
// Vamos a intentar ejecutarlo todo junto si es pequeño, o partirlo.
// Dado que es 2MB, PDO podría manejarlo en un exec o necesitar split.
// MySQL puede manejar múltiples queries si se configura, pero PDO disable emulates by default sometimes.

echo "<p>Iniciando importación... esto puede tardar unos segundos...</p>";

try {
    // Opción 1: Ejecutar todo el bloque
    // $pdo->exec($sql); 

    // Opción 2: Split manual para mejor control y reporte
    // Asumimos que cada sentencia termina en ";\n" o ";\r\n" que es como lo generé.
    $link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$link) {
        die('Error conectando con mysqli: ' . mysqli_connect_error());
    }

    // Multi query es más robusto para dumps
    if (mysqli_multi_query($link, $sql)) {
        do {
            // Guardar resultados si los hay
            if ($result = mysqli_store_result($link)) {
                mysqli_free_result($result);
            }
        } while (mysqli_next_result($link));
        echo "<h2 style='color:green'>¡IMPORTACIÓN COMPLETADA CON ÉXITO!</h2>";
        echo "<p>Las tablas deberían estar creadas. Probá entrar al Dashboard ahora.</p>";
        echo "<a href='index.php' style='background:blue; color:white; padding:10px; text-decoration:none; border-radius:5px;'>Ir al Dashboard</a>";
    } else {
        echo "<h3 style='color:red'>Error durante la importación: " . mysqli_error($link) . "</h3>";
    }
    mysqli_close($link);

} catch (Exception $e) {
    echo "<h3 style='color:red'>Excepción: " . $e->getMessage() . "</h3>";
}
?>