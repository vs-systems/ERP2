<?php
/**
 * GIT SYNC HELPER v2 - VS System ERP
 * Trying multiple execution methods.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

function run_git($cmd)
{
    $full_cmd = "git $cmd 2>&1";
    echo "<h3>Sincronizando: $full_cmd</h3>";

    $methods = ['shell_exec', 'exec', 'system', 'passthru', 'proc_open'];
    $success = false;

    foreach ($methods as $method) {
        if (function_exists($method)) {
            echo "<em>Intentando con método: $method</em><br>";
            try {
                if ($method === 'shell_exec') {
                    $out = shell_exec($full_cmd);
                    echo "<pre>$out</pre>";
                    $success = true;
                } elseif ($method === 'exec') {
                    $out = [];
                    exec($full_cmd, $out);
                    echo "<pre>" . implode("\n", $out) . "</pre>";
                    $success = true;
                } elseif ($method === 'system') {
                    echo "<pre>";
                    system($full_cmd);
                    echo "</pre>";
                    $success = true;
                } elseif ($method === 'passthru') {
                    echo "<pre>";
                    passthru($full_cmd);
                    echo "</pre>";
                    $success = true;
                }

                if ($success) {
                    echo "<span style='color:green;'>Comando ejecutado con éxito vía $method</span><br>";
                    break;
                }
            } catch (Error $e) {
                echo "<span style='color:orange;'>Error con $method: " . $e->getMessage() . "</span><br>";
            }
        } else {
            echo "<em>Método deshabilitado: $method</em><br>";
        }
    }

    if (!$success) {
        echo "<b style='color:red;'>No se pudo ejecutar ningún comando. Todas las funciones de ejecución están deshabilitadas.</b><br>";
    }
}

echo "<h2>Git Recovery Tools</h2>";

if (isset($_GET['action'])) {
    if ($_GET['action'] == 'fix') {
        run_git('reset --hard HEAD');
        run_git('pull origin main');
    }
}

echo "<ul>
    <li><a href='?action=fix'>INTENTAR REPARACIÓN (Reset & Pull)</a></li>
</ul>";
