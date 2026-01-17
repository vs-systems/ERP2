<?php
session_start();

// List of public pages that don't require login
$public_pages = [
    'login.php',
    'catalogo.php',
    'ajax_log_catalog_click.php',
    'ajax_search_catalog.php' // Assumed name, verify if strictly needed
];

$current_page = basename($_SERVER['PHP_SELF']);

// If page is not public and user is not logged in, redirect to login
if (!in_array($current_page, $public_pages)) {
    if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
        header('Location: login.php');
        exit;
    }
}
?>