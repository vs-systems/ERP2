<?php
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

try {
    $db = Vsys\Lib\Database::getInstance();

    // 1. Add tax_category to entities
    $db->exec("ALTER TABLE entities ADD COLUMN IF NOT EXISTS tax_category ENUM('Responsable Inscripto', 'Monotributo', 'Exento', 'No Aplica') DEFAULT 'No Aplica' AFTER contact_person;");
    echo "âœ… Columna 'tax_category' agregada a 'entities'.<br>";

    // 2. Create purchases table
    $db->exec("CREATE TABLE IF NOT EXISTS purchases (
        id INT AUTO_INCREMENT PRIMARY KEY,
        purchase_number VARCHAR(50) UNIQUE NOT NULL,
        entity_id INT NOT NULL,
        purchase_date DATE NOT NULL,
        exchange_rate_usd DECIMAL(10,2) NOT NULL,
        total_usd DECIMAL(15,2) NOT NULL,
        total_ars DECIMAL(15,2) NOT NULL,
        status ENUM('Pendiente', 'Pagado', 'Cancelado') DEFAULT 'Pendiente',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (entity_id) REFERENCES entities(id)
    );");
    echo "âœ… Tabla 'purchases' creada.<br>";

    // 3. Create purchase_items table
    $db->exec("CREATE TABLE IF NOT EXISTS purchase_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        purchase_id INT NOT NULL,
        product_id INT,
        sku VARCHAR(100),
        description TEXT,
        qty INT NOT NULL,
        unit_price_usd DECIMAL(15,2) NOT NULL,
        total_usd DECIMAL(15,2) NOT NULL,
        FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE
    );");
    echo "âœ… Tabla 'purchase_items' creada.<br>";

    echo "<h3>Migració³n completada con ó©xito.</h3>";
} catch (Exception $e) {
    echo "âŒ ERROR: " . $e->getMessage();
}
?>





