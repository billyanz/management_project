<?php
/**
 * File: login.php
 */

require_once 'includes/security.php';
require_once 'config/database.php'; //

if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true) {
    header("Location: index.php");
    exit;
}

// Cek Rate Limiting Login (Max 5x salah, dikunci 5 menit)
check_rate_limit('login', 5, 300);

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Cek Honeypot Trap
    check_honeypot('website_honeypot');

    // 2. Verifikasi Token CSRF
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("Validasi keamanan gagal (CSRF Mismatch).");
    }

    // 3. Sanitasi Input
    $username_email = trim($_POST['username_email'] ?? '');
    $password       = $_POST['password'] ?? '';

    if (empty($username_email) || empty($password)) {
        $error_msg = "Username/Email dan Password wajib diisi!";
    } else {
        // 4. Prepared Statement (Anti SQL Injection)
        $stmt = $conn->prepare("SELECT id, username, email, password, role FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->bind_param("ss", $username_email, $username_email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            // 5. Verifikasi Hash Password
            if (password_verify($password, $user['password'])) {
                
                // Login Berhasil -> Reset Counter Percobaan
                reset_rate_limit('login');

                // Prevent Session Fixation
                session_regenerate_id(true);

                $_SESSION['is_logged_in'] = true;
                $_SESSION['user_id']      = $user['id'];
                $_SESSION['username']     = $user['username'];
                $_SESSION['user_role']    = $user['role'];
                $_SESSION['user_agent']   = $_SERVER['HTTP_USER_AGENT'] ?? '';

                header("Location: index.php");
                exit;
            }
        }

        // Login Gagal -> Catat Percobaan
        register_failed_attempt('login', 5, 300);
        $error_msg = "Kombinasi Username/Email dan Password salah!";
        $stmt->close();
    }
}

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Manajemen Projek</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background-color: #f4f6f9; margin: 0; padding: 0; }
        .login-box { max-width: 380px; margin: 80px auto; padding: 30px; background: #fff; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 600; color: #333; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; }
        .btn-submit { width: 100%; padding: 12px; background: #007bff; color: #fff; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; }
        .btn-submit:hover { background: #0056b3; }
        .alert-error { background: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 15px; border-radius: 5px; font-size: 14px; }
        .hp-field { display: none !important; visibility: hidden !important; }
        .reg-link { text-align: center; margin-top: 15px; font-size: 14px; }
        .reg-link a { color: #007bff; text-decoration: none; }
    </style>
</head>
<body>

<div class="login-box">
    <h2 style="margin-top:0; text-align:center;">Login Sistem</h2>

    <?php if (!empty($error_msg)): ?>
        <div class="alert-error"><?= sanitize_out($error_msg); ?></div>
    <?php endif; ?>

    <form action="login.php" method="POST" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token; ?>">

        <!-- Honeypot Trap -->
        <div class="hp-field">
            <input type="text" name="website_honeypot" id="website_honeypot" tabindex="-1" autocomplete="off">
        </div>

        <div class="form-group">
            <label for="username_email">Username / Email</label>
            <input type="text" id="username_email" name="username_email" required autofocus>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>

        <button type="submit" class="btn-submit">Masuk</button>
    </form>

    <div class="reg-link">
        Belum punya akun? <a href="register.php">Daftar sekarang</a>
    </div>
</div>

</body>
</html>