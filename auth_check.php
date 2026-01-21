<?php
/**
 * Authentication check script
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/lib/User.php';

use Vsys\Lib\User;

if (!isset($userAuth)) {
    $userAuth = new User();
}

// Local Network Auto-Login Bypass (Dev only)
$clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
$isLocal = (strpos($clientIp, '192.168.0.') === 0 || $clientIp === '127.0.0.1' || $clientIp === '::1');

if (!$userAuth->isLoggedIn()) {
    if ($isLocal) {
        // Auto-login logic for local development
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'admin';
        $_SESSION['role'] = 'Admin';
        $_SESSION['company_id'] = 1;

        // Refresh object state
        $userAuth = new User();
    } else {
        $currentPage = basename($_SERVER['PHP_SELF']);
        if ($currentPage !== 'login.php') {
            header('Location: login.php');
            exit;
        }
    }
}

// Ensure company_id is set if the user is logged in (legacy support)
if ($userAuth->isLoggedIn() && (!isset($_SESSION['company_id']) || empty($_SESSION['company_id']))) {
    $_SESSION['company_id'] = 1;
}