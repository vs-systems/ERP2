<?php
/**
 * VS System ERP - Core Configuration (Production)
 */

// Error reporting (Enable for debugging)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'u499089589_vsys');
define('DB_USER', 'u499089589_admin');
define('DB_PASS', 'v5yS_2024_P@ss!#');
define('DB_CHARSET', 'utf8mb4');

// API Tokens
define('BCRA_TOKEN', 'eyJhbGciOiJIUzUxMiIsInR5cCI6IkpXVCJ9.eyJleHAiOjE3OTg3MjIwMTksInR5cGUiOiJleHRlcm5hbCIsInVzZXIiOiJqYXZpZXJAdmVjaW5vc2VndXJvLmNvbS5hciJ9.5gGamU2tbfkH1EJusB7a39P4sod-7XAJvcPljaIlDgEapFfGdk95fyhRARGcvy1xSux3jRXFStQnS1kKTxQEBQ');

// System Settings
define('APP_NAME', 'VS System ERP');
define('LOGO_URL_LARGE', 'src/img/VSLogo_v2.jpg');
define('LOGO_URL_SMALL', 'src/img/VSLogo_v2.jpg');
define('CURRENCY_DEFAULT', 'USD');
define('CURRENCY_SECONDARY', 'ARS');

// Contact Info
define('COMPANY_PHONE', '+54 9 11 3889 1414');
define('COMPANY_WHATSAPP', '5491138891414');

// Paths
define('BASE_PATH', dirname(__DIR__, 2));
define('MODULES_PATH', BASE_PATH . '/src/modules');
define('LIB_PATH', BASE_PATH . '/src/lib');

// SMTP Configuration (Gmail)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'vecinoseguro0@gmail.com');
define('SMTP_PASS', 'Picabea260205@');
define('SMTP_PORT', 465);
define('SMTP_SECURE', 'ssl');

// Timezone
date_default_timezone_set('America/Argentina/Buenos_Aires');
?>