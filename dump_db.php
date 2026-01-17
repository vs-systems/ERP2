<?php
/**
 * VS System ERP - Database Export Tool
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

echo "<h2>Preparando Exportación para Migración...</h2>";

try {
    $db = Vsys\Lib\Database::getInstance();
    
    // Get all tables
    $tables = [];
    $stmt = $db->query("SHOW TABLES");
    while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }

    $dump = "-- VS System ERP Migration Dump\n";
    $dump .= "-- Generated: " . date("Y-m-d H:i:s") . "\n\n";
    $dump .= "SET FOREIGN_KEY_CHECKS=0;\n";

    foreach ($tables as $table) {
        echo "Exportando tabla: $table...<br>";
        
        // Structure
        $stmt = $db->query("SHOW CREATE TABLE `$table`");
        $row = $stmt->fetch(\PDO::FETCH_NUM);
        $dump .= "\n\n-- Structure for table `$table` --\n";
        $dump .= "DROP TABLE IF EXISTS `$table`;\n";
        $dump .= $row[1] . ";\n\n";

        // Data
        $stmt = $db->query("SELECT * FROM `$table`");
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $keys = array_keys($row);
            $values = array_values($row);
            $values = array_map(function($v) use ($db) {
                if ($v === null) return "NULL";
                return $db->quote($v);
            }, $values);
            
            $dump .= "INSERT INTO `$table` (`" . implode("`, `", $keys) . "`) VALUES (" . implode(", ", $values) . ");\n";
        }
    }

    $dump .= "\nSET FOREIGN_KEY_CHECKS=1;\n";

    $filename = "vsys_migration_dump_" . date("Ymd_His") . ".sql";
    file_put_contents(__DIR__ . "/public/$filename", $dump);

    echo "<br>✅ **LISTO!** Base de datos exportada con éxito.<br>";
    echo "Archivo generado: <a href='$filename'>$filename</a><br>";
    echo "<p>Descarga este archivo para subirlo a WNPower.</p>";

} catch (\Exception $e) {
    echo "<br>❌ ERROR: " . $e->getMessage();
}
