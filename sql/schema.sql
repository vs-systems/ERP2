-- VS System ERP - Complete Database Schema
-- Version: 1.0.0
-- Created: 2026-01-14

-- 1. CONFIGURATION & USERS
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    role ENUM('admin', 'vendedor', 'logistica') DEFAULT 'vendedor',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. CURRENCY & EXCHANGE RATES
CREATE TABLE IF NOT EXISTS exchange_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    currency_from VARCHAR(5) DEFAULT 'USD',
    currency_to VARCHAR(5) DEFAULT 'ARS',
    rate DECIMAL(15, 4) NOT NULL,
    source VARCHAR(50) DEFAULT 'BCRA',
    fetched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. PRODUCTS & CATALOG
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(50) NOT NULL UNIQUE,
    barcode VARCHAR(100) UNIQUE,
    provider_code VARCHAR(100),
    description VARCHAR(255) NOT NULL,
    category_id INT,
    unit_cost_usd DECIMAL(15, 2) DEFAULT 0.00,
    unit_price_usd DECIMAL(15, 2) DEFAULT 0.00,
    iva_rate DECIMAL(5, 2) DEFAULT 21.00,
    brand VARCHAR(100),
    has_serial_number TINYINT(1) DEFAULT 0,
    stock_current INT DEFAULT 0,
    stock_minimum INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. SERIAL NUMBERS TRAKCKING
CREATE TABLE IF NOT EXISTS product_serials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    serial_number VARCHAR(100) NOT NULL UNIQUE,
    status ENUM('available', 'sold', 'returned', 'defective') DEFAULT 'available',
    purchase_order_id INT NULL,
    sale_quote_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. CLIENTS & SUPPLIERS
CREATE TABLE IF NOT EXISTS entities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('client', 'provider', 'supplier') NOT NULL,
    tax_id VARCHAR(20) UNIQUE, -- CUIT/CUIL
    document_number VARCHAR(20), -- DNI
    name VARCHAR(200) NOT NULL, -- Razon Social
    fantasy_name VARCHAR(200), -- Nombre de Fantasía
    contact_person VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(50),
    mobile VARCHAR(50),
    address TEXT, -- Domicilio
    delivery_address TEXT, -- Lugar de Entrega
    default_voucher_type ENUM('Factura', 'Remito', 'Ninguno') DEFAULT 'Factura',
    is_enabled TINYINT(1) DEFAULT 1,
    is_retention_agent TINYINT(1) DEFAULT 0, -- 7% logic
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5b. MULTI-SUPPLIER PRICING
CREATE TABLE IF NOT EXISTS supplier_prices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    supplier_id INT NOT NULL,
    cost_usd DECIMAL(15, 2) NOT NULL,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES entities(id) ON DELETE CASCADE,
    UNIQUE KEY (product_id, supplier_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. QUOTATIONS (COTIZADOR)
CREATE TABLE IF NOT EXISTS quotations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quote_number VARCHAR(100) NOT NULL UNIQUE, -- VS-COT-YYYY-MM-DD-0001
    version INT DEFAULT 1,
    parent_quote_id INT NULL, -- For versioning link
    client_id INT NOT NULL,
    user_id INT NOT NULL, -- Seller
    status ENUM('draft', 'sent', 'accepted', 'rejected', 'expired', 'ordered') DEFAULT 'draft',
    payment_method ENUM('cash', 'bank') DEFAULT 'cash', -- Bank adds 3%
    with_iva TINYINT(1) DEFAULT 1, -- VAT toggle
    exchange_rate_usd DECIMAL(15, 4) NOT NULL,
    subtotal_usd DECIMAL(15, 2) NOT NULL,
    total_usd DECIMAL(15, 2) NOT NULL,
    total_ars DECIMAL(15, 2) NOT NULL,
    notes TEXT,
    valid_until DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES entities(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS quotation_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quotation_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price_usd DECIMAL(15, 2) NOT NULL,
    subtotal_usd DECIMAL(15, 2) NOT NULL,
    iva_rate DECIMAL(5, 2) NOT NULL,
    FOREIGN KEY (quotation_id) REFERENCES quotations(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. PRICE ANALYSIS (ANALIZADOR DE PRECIOS)
CREATE TABLE IF NOT EXISTS price_analysis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    analysis_number VARCHAR(100) NOT NULL UNIQUE, -- VS-ANA-YYYY-MM-DD-0001
    quotation_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quotation_id) REFERENCES quotations(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS price_analysis_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    analysis_id INT NOT NULL,
    product_id INT NOT NULL,
    vs_price_usd DECIMAL(15, 2) NOT NULL,
    competitor_price_usd DECIMAL(15, 2) NOT NULL,
    difference_percentage DECIMAL(5, 2) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    FOREIGN KEY (analysis_id) REFERENCES price_analysis(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. PURCHASES (COMPRAS)
CREATE TABLE IF NOT EXISTS purchase_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider_id INT NOT NULL,
    status ENUM('pending', 'received', 'cancelled') DEFAULT 'pending',
    total_usd DECIMAL(15, 2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (provider_id) REFERENCES entities(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default Admin User (Password: admin123)
INSERT IGNORE INTO users (username, password_hash, full_name, email, role) 
VALUES ('admin', '$2y$10$GfI.n3m8WJtT2q7mR9y6uO8pB5tFzJq7x5v7r9mF3oY6o1Wq.hS.i', 'VS System Admin', 'admin@vecinoseguro.com.ar', 'admin');
