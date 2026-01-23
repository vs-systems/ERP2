<?php
/**
 * Migration Script - Billing & Current Accounts Tables
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

try {
    $db = Vsys\Lib\Database::getInstance();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h1>Iniciando Migración de Tablas de Facturación...</h1>";

    // 1. Invoices Table
    echo "<li>Creando tabla 'invoices'... ";
    $db->exec("CREATE TABLE IF NOT EXISTS invoices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL,
        client_id INT NOT NULL,
        quote_id INT DEFAULT NULL,
        invoice_number VARCHAR(20) NOT NULL,
        invoice_type ENUM('A', 'B', 'C', 'M', 'P', 'X') DEFAULT 'A',
        date DATE NOT NULL,
        due_date DATE DEFAULT NULL,
        status ENUM('Pendiente', 'Pagado', 'Anulado', 'Parcial') DEFAULT 'Pendiente',
        total_net DECIMAL(15,2) DEFAULT 0,
        total_iva DECIMAL(15,2) DEFAULT 0,
        total_amount DECIMAL(15,2) DEFAULT 0,
        currency VARCHAR(5) DEFAULT 'ARS',
        exchange_rate DECIMAL(15,4) DEFAULT 1,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (client_id),
        INDEX (invoice_number)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "<span style='color:green;'>OK</span></li>";

    // 2. Invoice Items Table
    echo "<li>Creando tabla 'invoice_items'... ";
    $db->exec("CREATE TABLE IF NOT EXISTS invoice_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        invoice_id INT NOT NULL,
        product_id INT DEFAULT NULL,
        description VARCHAR(255) NOT NULL,
        quantity DECIMAL(15,2) DEFAULT 1,
        unit_price DECIMAL(15,2) DEFAULT 0,
        iva_rate DECIMAL(5,2) DEFAULT 21,
        subtotal DECIMAL(15,2) DEFAULT 0,
        FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "<span style='color:green;'>OK</span></li>";

    // 3. Client Movements (Current Account)
    echo "<li>Creando tabla 'client_movements'... ";
    $db->exec("CREATE TABLE IF NOT EXISTS client_movements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL,
        client_id INT NOT NULL,
        date DATETIME DEFAULT CURRENT_TIMESTAMP,
        type ENUM('Factura', 'Recibo', 'Nota de Crédito', 'Nota de Débito', 'Saldo Inicial') NOT NULL,
        reference_id INT DEFAULT NULL COMMENT 'ID de la factura o recibo relacionado',
        reference_text VARCHAR(50) DEFAULT NULL COMMENT 'Número legible del comprobante',
        debit DECIMAL(15,2) DEFAULT 0 COMMENT 'Debe (Aumenta saldo)',
        credit DECIMAL(15,2) DEFAULT 0 COMMENT 'Haber (Disminuye saldo)',
        balance DECIMAL(15,2) DEFAULT 0 COMMENT 'Saldo acumulado',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (client_id),
        INDEX (date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "<span style='color:green;'>OK</span></li>";

    // 4. Receipts Table
    echo "<li>Creando tabla 'receipts'... ";
    $db->exec("CREATE TABLE IF NOT EXISTS receipts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL,
        client_id INT NOT NULL,
        amount DECIMAL(15,2) NOT NULL,
        date DATE NOT NULL,
        payment_method ENUM('Efectivo', 'Transferencia', 'Cheque', 'Tarjeta', 'Otro') DEFAULT 'Transferencia',
        reference_number VARCHAR(50) DEFAULT NULL,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (client_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "<span style='color:green;'>OK</span></li>";

    echo "<h2>¡Migración completada con éxito!</h2>";
    echo "<p><a href='facturacion.php'>Ir al Módulo de Facturación</a></p>";

} catch (Exception $e) {
    echo "<h2><span style='color:red;'>Error en la migración:</span></h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
