<?php
// debug_fix.php - Diagnó³stico y Limpieza de Cachó©
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Herramienta de Diagnó³stico y Limpieza</h1>";

// 1. Intentar limpiar OPcache
echo "<h2>1. Limpieza de Cachó© (OPcache)</h2>";
if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        echo "<p style='color:green'>âœ… Opcache reseteado correctamente.</p>";
    } else {
        echo "<p style='color:orange'>âš ï¸ No se pudo resetear Opcache (quizó¡s no estó¡ activo o restringido).</p>";
    }
} else {
    echo "<p style='color:gray'>â„¹ï¸ Opcache no detectado o funció³n no disponible.</p>";
}

// 2. Verificar archivo CRM.php
echo "<h2>2. Verificació³n de Có³digo: CRM.php</h2>";
$crmFile = __DIR__ . '/src/modules/crm/CRM.php';

if (file_exists($crmFile)) {
    echo "<p>Archivo encontrado: $crmFile</p>";
    echo "<p>óšltima modificació³n: " . date("Y-m-d H:i:s", filemtime($crmFile)) . "</p>";

    // Leer contenido
    $content = file_get_contents($crmFile);

    // Buscar la funció³n
    if (strpos($content, 'function getFunnelStats') !== false) {
        echo "<p style='color:green'>âœ… La funció³n <strong>getFunnelStats</strong> ESTó en el archivo fó­sico.</p>";
    } else {
        echo "<p style='color:red'>âŒ La funció³n <strong>getFunnelStats</strong> NO estó¡ en el archivo fó­sico. (Fallo de Git)</p>";
    }

    // Verificar clase cargada en memoria
    require_once __DIR__ . '/src/config/config.php';
    require_once $crmFile;

    if (class_exists('Vsys\Modules\CRM\CRM')) {
        $methods = get_class_methods('Vsys\Modules\CRM\CRM');
        if (in_array('getFunnelStats', $methods)) {
            echo "<p style='color:green'>âœ… La clase en memoria TIENE el mó©todo getFunnelStats.</p>";
        } else {
            echo "<p style='color:red'>âŒ La clase en memoria NO tiene el mó©todo (Cachó© vieja persistente).</p>";
            echo "<pre>Mó©todos disponibles: " . print_r($methods, true) . "</pre>";
        }
    }

} else {
    echo "<p style='color:red'>âŒ Archivo CRM.php NO encontrado.</p>";
}

// 3. Verificar OperationAnalysis.php
echo "<h2>3. Verificació³n de Có³digo: OperationAnalysis.php</h2>";
$analysisFile = __DIR__ . '/src/modules/analysis/OperationAnalysis.php';

if (file_exists($analysisFile)) {
    $content = file_get_contents($analysisFile);
    if (strpos($content, '$result[\'margin_percent\']') !== false) {
        echo "<p style='color:green'>âœ… OperationAnalysis parece actualizado (usa estructura plana).</p>";
    } else {
        echo "<p style='color:red'>âŒ OperationAnalysis parece ser la versió³n VIEJA.</p>";
    }
}

echo "<hr><a href='index.php' class='btn'>Volver al Dashboard</a>";
?>




