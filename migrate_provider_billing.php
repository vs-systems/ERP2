<?php
/**
 * Migration Script - Provider Current Account Table
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

try {
    $db = Vsys\Lib\Database::getInstance();

    echo "<h1>Agregando Tabla de Cuenta Corriente de Proveedores...</h1>";

    $db->exec("CREATE TABLE IF NOT EXISTS provider_movements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL,
        provider_id INT NOT NULL,
        date DATETIME DEFAULT CURRENT_TIMESTAMP,
        type ENUM('Compra', 'Pago', 'Nota de Crédito', 'Nota de Débito', 'Saldo Inicial') NOT NULL,
        reference_id INT DEFAULT NULL,
        reference_text VARCHAR(50) DEFAULT NULL,
        debit DECIMAL(15,2) DEFAULT 0 COMMENT 'Haber para nosotros (Les debemos)',
        credit DECIMAL(15,2) DEFAULT 0 COMMENT 'Debe para nosotros (Pagamos)',
        balance DECIMAL(15,2) DEFAULT 0 COMMENT 'Saldo acumulado',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (provider_id),
        INDEX (date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    echo "<p style='color:green;'>Tabla 'provider_movements' creada correctamente.</p>";

} catch (Exception $e) {
    echo "<h2><span style='color:red;'>Error:</span></h2><pre>" . $e->getMessage() . "</pre>";
}
