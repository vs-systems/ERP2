<?php
/**
 * VS System ERP - Core Configuration
 */

// Error reporting (Disable in production)
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);

// Database configuration
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'gozziar_vs_system_erp');
define('DB_USER', getenv('DB_USER') ?: 'gozziar_javiergdm');
define('DB_PASS', getenv('DB_PASS') ?: 'Andrea1910');
define('DB_CHARSET', 'utf8mb4');

// API Tokens
define('BCRA_TOKEN', 'eyJhbGciOiJIUzUxMiIsInR5cCI6IkpXVCJ9.eyJleHAiOjE3OTg3MjIwMTksInR5cGUiOiJleHRlcm5hbCIsInVzZXIiOiJqYXZpZXJAdmVjaW5vc2VndXJvLmNvbS5hciJ9.5gGamU2tbfkH1EJusB7a39P4sod-7XAJvcPljaIlDgEapFfGdk95fyhRARGcvy1xSux3jRXFStQnS1kKTxQEBQ');

// System Settings
define('APP_NAME', 'VS System ERP');
define('LOGO_URL_LARGE', 'src/img/VSLogo.png');
define('LOGO_URL_SMALL', 'src/img/logo_short.png');
define('CURRENCY_DEFAULT', 'USD');
define('CURRENCY_SECONDARY', 'ARS');

// Contact Info
define('COMPANY_PHONE', '+5491122334455');
define('COMPANY_WHATSAPP', '5491122334455');

// Paths
define('BASE_PATH', dirname(__DIR__, 2));
define('MODULES_PATH', BASE_PATH . '/src/modules');
define('LIB_PATH', BASE_PATH . '/src/lib');

// Timezone
date_default_timezone_set('America/Argentina/Buenos_Aires');
?>