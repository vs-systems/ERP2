<?php
/**
 * VS System ERP - NAS Auto-Deploy Webhook
 * This script allows your NAS to automatically pull changes from GitHub.
 */

// 1. Configuration
$repo_dir = __DIR__; // Use current directory automatically
$branch = 'main';

// 2. Execution logic
echo "<div style='font-family: sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px;'>";
echo "<h2>VS System - NAS Auto-Deploy</h2>";
echo "<p><b>Ruta detectada:</b> <code>$repo_dir</code></p>";

// Check if we are in a git repo
if (!is_dir("$repo_dir/.git")) {
    echo "<div style='background: #fee; color: #b91c1c; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<b>Error:</b> Esta carpeta no tiene los metadatos de Git (falta la carpeta .git).<br><br>";
    echo "Para solucionar esto, debes conectarte por SSH a tu NAS y ejecutar:<br>";
    echo "<code>cd " . str_replace('\\', '/', $repo_dir) . " && git init && git remote add origin https://github.com/vs-systems/ERP2.git && git fetch --all && git reset --hard origin/main</code>";
    echo "</div>";
    die("</div>");
}

// Execute git pull
$output = shell_exec("cd $repo_dir && git pull origin $branch 2>&1");

echo "<pre style='background: #f4f4f4; padding: 10px; overflow: auto;'>$output</pre>";

if (strpos($output, 'Updating') !== false || strpos($output, 'Already up to date') !== false) {
    echo "<p style='color: #15803d; font-weight: bold;'>✅ Sincronización completa!</p>";
} else {
    echo "<p style='color: #b91c1c; font-weight: bold;'>❌ Falló la sincronización. Verifica si Git está instalado en tu NAS.</p>";
}
echo "</div>";
?>