<?php
// debug_fix.php - Diagnóstico y Limpieza de Caché
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Herramienta de Diagnóstico y Limpieza</h1>";

// 1. Intentar limpiar OPcache
echo "<h2>1. Limpieza de Caché (OPcache)</h2>";
if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        echo "<p style='color:green'>✅ Opcache reseteado correctamente.</p>";
    } else {
        echo "<p style='color:orange'>⚠️ No se pudo resetear Opcache (quizás no está activo o restringido).</p>";
    }
} else {
    echo "<p style='color:gray'>ℹ️ Opcache no detectado o función no disponible.</p>";
}

// 2. Verificar archivo CRM.php
echo "<h2>2. Verificación de Código: CRM.php</h2>";
$crmFile = __DIR__ . '/src/modules/crm/CRM.php';

if (file_exists($crmFile)) {
    echo "<p>Archivo encontrado: $crmFile</p>";
    echo "<p>Última modificación: " . date("Y-m-d H:i:s", filemtime($crmFile)) . "</p>";

    // Leer contenido
    $content = file_get_contents($crmFile);

    // Buscar la función
    if (strpos($content, 'function getFunnelStats') !== false) {
        echo "<p style='color:green'>✅ La función <strong>getFunnelStats</strong> ESTÁ en el archivo físico.</p>";
    } else {
        echo "<p style='color:red'>❌ La función <strong>getFunnelStats</strong> NO está en el archivo físico. (Fallo de Git)</p>";
    }

    // Verificar clase cargada en memoria
    require_once __DIR__ . '/src/config/config.php';
    require_once $crmFile;

    if (class_exists('Vsys\Modules\CRM\CRM')) {
        $methods = get_class_methods('Vsys\Modules\CRM\CRM');
        if (in_array('getFunnelStats', $methods)) {
            echo "<p style='color:green'>✅ La clase en memoria TIENE el método getFunnelStats.</p>";
        } else {
            echo "<p style='color:red'>❌ La clase en memoria NO tiene el método (Caché vieja persistente).</p>";
            echo "<pre>Métodos disponibles: " . print_r($methods, true) . "</pre>";
        }
    }

} else {
    echo "<p style='color:red'>❌ Archivo CRM.php NO encontrado.</p>";
}

// 3. Verificar OperationAnalysis.php
echo "<h2>3. Verificación de Código: OperationAnalysis.php</h2>";
$analysisFile = __DIR__ . '/src/modules/analysis/OperationAnalysis.php';

if (file_exists($analysisFile)) {
    $content = file_get_contents($analysisFile);
    if (strpos($content, '$result[\'margin_percent\']') !== false) {
        echo "<p style='color:green'>✅ OperationAnalysis parece actualizado (usa estructura plana).</p>";
    } else {
        echo "<p style='color:red'>❌ OperationAnalysis parece ser la versión VIEJA.</p>";
    }
}

echo "<hr><a href='index.php' class='btn'>Volver al Dashboard</a>";
?>