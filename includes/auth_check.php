<?php
/**
 * File: includes/auth_check.php
 * Middleware Proteksi Halaman Internal/Dashboard
 */

require_once __DIR__ . '/security.php';

// Wajib Login
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header("Location: /login.php");
    exit;
}

// Cek Integritas User-Agent (Anti Session Hijacking)
$current_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
if (!isset($_SESSION['user_agent'])) {
    $_SESSION['user_agent'] = $current_agent;
} elseif ($_SESSION['user_agent'] !== $current_agent) {
    session_unset();
    session_destroy();
    header("Location: /login.php?error=session_invalid");
    exit;
}