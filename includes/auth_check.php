<?php
/**
 * File: includes/auth_check.php
 */

require_once __DIR__ . '/security.php';

// Jika belum login, lempar ke login.php yang ada di root projek
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    // PENTING: Gunakan path relative biar fleksibel di folder manapun
    $root_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
    
    // Atau cara paling simpel & aman tanpa hardcode nama folder:
    header("Location: " . $root_url . "/management_project/login.php");
    exit;
}

// Cek User Agent (Anti Session Hijacking)
$current_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
if (!isset($_SESSION['user_agent'])) {
    $_SESSION['user_agent'] = $current_agent;
} elseif ($_SESSION['user_agent'] !== $current_agent) {
    session_unset();
    session_destroy();
    header("Location: login.php?error=session_invalid");
    exit;
}