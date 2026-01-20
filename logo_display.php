<?php
/**
 * VS System ERP - Logo Proxy
 */
require_once __DIR__ . '/src/config/config.php';

// Fallback local paths if constants not defined (relative to this file)
if (!defined('LOGO_URL_LARGE'))
    define('LOGO_URL_LARGE', 'src/img/VSLogo.png');
if (!defined('LOGO_URL_SMALL'))
    define('LOGO_URL_SMALL', 'src/img/logo_short.png');

$type = isset($_GET['type']) && $_GET['type'] == 'small' ? 'small' : 'large';
$file = ($type == 'large') ? LOGO_URL_LARGE : LOGO_URL_SMALL;

// Resolve absolute path
$fullPath = realpath(__DIR__ . '/' . $file);

if ($fullPath && file_exists($fullPath)) {
    $mime = mime_content_type($fullPath);
    header("Content-Type: $mime");
    header("Content-Length: " . filesize($fullPath));
    readfile($fullPath);
    exit;
} else {
    // Fallback if local file not found
    header("Location: http://www.vecinoseguro.com.ar/Logos/VSLogo.png");
    exit;
}
?>