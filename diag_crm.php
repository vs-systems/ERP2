<?php
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

try {
    $db = Vsys\Lib\Database::getInstance();

    $tables = ['entities', 'crm_leads', 'crm_interactions', 'users'];

    echo "<h2>Database Diagnostics</h2>";

    foreach ($tables as $table) {
        echo "<h3>Table: $table</h3>";
        try {
            $cols = $db->query("DESCRIBE $table")->fetchAll();
            echo "<ul>";
            foreach ($cols as $col) {
                echo "<li>{$col['Field']} ({$col['Type']})</li>";
            }
            echo "</ul>";
        } catch (Exception $e) {
            echo "<p style='color:red'>Table $table error: " . $e->getMessage() . "</p>";
        }
    }

} catch (Exception $e) {
    echo "Global error: " . $e->getMessage();
}
