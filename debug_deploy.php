<?php
echo "<h1>NAS Deploy Debug</h1>";
echo "<b>Current Directory:</b> " . getcwd() . "<br>";
echo "<b>User:</b> " . shell_exec('whoami') . "<br>";
echo "<b>Git Version:</b> " . shell_exec('git --version') . "<br>";

echo "<h3>Directory Contents:</h3>";
echo "<pre>" . shell_exec('ls -la') . "</pre>";

echo "<h3>Git Status:</h3>";
echo "<pre>" . shell_exec('git status 2>&1') . "</pre>";

echo "<h3>Attempting Repair:</h3>";
// If .git is missing but we want to re-init
if (!file_exists('.git')) {
    echo "NO .GIT FOUND. Attempting init...<br>";
    // CAUTION: This might overwrite local changes on the server if we do reset --hard
    // But since it's already broken, we need to fix it.
}

echo "<h3>Running Pull from origin main:</h3>";
$output = shell_exec("git pull origin main 2>&1");
echo "<pre>$output</pre>";
?>