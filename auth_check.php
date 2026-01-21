<?php
ob_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
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
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
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
            if (!headers_sent()) {
                header('Location: login.php');
                exit;
            } else {
                echo "<script>window.location.href='login.php';</script>";
                exit;
            }
        }
    }
}

// Ensure company_id is set if the user is logged in
if ($userAuth->isLoggedIn() && (!isset($_SESSION['company_id']) || empty($_SESSION['company_id']))) {
    $db = Vsys\Lib\Database::getInstance();
    $stmt = $db->prepare("SELECT company_id FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cid = $stmt->fetchColumn();
    $_SESSION['company_id'] = $cid ?: 1;
}