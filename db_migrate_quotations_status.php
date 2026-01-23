<?php
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

try {
    $db = Vsys\Lib\Database::getInstance();
    $db->exec("ALTER TABLE quotations ADD COLUMN IF NOT EXISTS status ENUM('Pendiente', 'Aceptado', 'Perdido') DEFAULT 'Pendiente' AFTER quote_number;");

    // Sync existing data
    $db->exec("UPDATE quotations SET status = 'Aceptado' WHERE is_confirmed = 1");

    echo "âœ… Columna 'status' agregada a 'quotations'.";
} catch (Exception $e) {
    echo "â Œ ERROR: " . $e->getMessage();
}
?>