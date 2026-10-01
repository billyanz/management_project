<?php
/**
 * File: includes/security.php
 * Core Security Helpers & Session Management
 */

if (session_status() === PHP_SESSION_NONE) {
    // Keamanan Cookie Session
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    
    // Ubah jadi 1 jika server produksi lo sudah HTTPS/SSL
    // ini_set('session.cookie_secure', 1);

    session_start();
}

/**
 * Generasi & Validasi Anti CSRF Token
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Protection Against Malicious Bots (Honeypot Trap)
 */
function check_honeypot($field_name = 'website_honeypot') {
    if (!empty($_POST[$field_name])) {
        http_response_code(403);
        die("Aktivitas Bot Terdeteksi.");
    }
}

/**
 * Rate Limiting / Anti Brute Force
 */
function check_rate_limit($action = 'login', $max_attempts = 5, $lockout_time = 300) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $key_attempts = "rate_limit_{$action}_{$ip}_attempts";
    $key_timeout  = "rate_limit_{$action}_{$ip}_timeout";

    if (isset($_SESSION[$key_timeout])) {
        $remaining_time = $_SESSION[$key_timeout] - time();
        if ($remaining_time > 0) {
            $minutes = ceil($remaining_time / 60);
            die("<div style='color:#721c24; background:#f8d7da; border:1px solid #f5c6cb; padding:20px; font-family:sans-serif; text-align:center; margin:50px auto; max-width:500px; border-radius:8px;'>
                    <h3>Akses Terkunci Sementara</h3>
                    <p>Terlalu banyak percobaan gagal. Demi keamanan, coba lagi dalam <b>{$minutes} menit</b>.</p>
                 </div>");
        } else {
            unset($_SESSION[$key_attempts]);
            unset($_SESSION[$key_timeout]);
        }
    }
}

function register_failed_attempt($action = 'login', $max_attempts = 5, $lockout_time = 300) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $key_attempts = "rate_limit_{$action}_{$ip}_attempts";
    $key_timeout  = "rate_limit_{$action}_{$ip}_timeout";

    if (!isset($_SESSION[$key_attempts])) {
        $_SESSION[$key_attempts] = 0;
    }

    $_SESSION[$key_attempts]++;

    if ($_SESSION[$key_attempts] >= $max_attempts) {
        $_SESSION[$key_timeout] = time() + $lockout_time;
    }
}

function reset_rate_limit($action = 'login') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    unset($_SESSION["rate_limit_{$action}_{$ip}_attempts"]);
    unset($_SESSION["rate_limit_{$action}_{$ip}_timeout"]);
}

/**
 * Sanitasi Tampilan Output Anti-XSS
 */
function sanitize_out($data) {
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}