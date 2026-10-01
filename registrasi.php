<?php
/**
 * File: register.php
 */

require_once 'includes/security.php';
require_once 'config/database.php'; //

// Cek Rate Limit Registrasi (Max 3x per 10 menit)
check_rate_limit('register', 3, 600);

$error_msg   = '';
$success_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Cek Honeypot Trap
    check_honeypot('website_honeypot');

    // 2. Verifikasi Token CSRF
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("Validasi keamanan gagal (CSRF Mismatch).");
    }

    // 3. Sanitasi & Validasi Input
    $username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS));
    $email    = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (!$username || !$email) {
        $error_msg = "Format Username atau Email tidak valid!";
    } elseif (strlen($password) < 8) {
        $error_msg = "Password minimal harus 8 karakter!";
    } elseif ($password !== $confirm) {
        $error_msg = "Konfirmasi password tidak cocok!";
    } else {
        // 4. Prepared Statement Cek Duplikasi (Anti SQL Injection)
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            register_failed_attempt('register', 3, 600);
            $error_msg = "Username atau Email sudah terdaftar!";
        } else {
            // 5. Hash Password BCRYPT
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $role_default    = 'user';

            $insert_stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $insert_stmt->bind_param("ssss", $username, $email, $hashed_password, $role_default);

            if ($insert_stmt->execute()) {
                reset_rate_limit('register');
                $success_msg = "Registrasi akun berhasil! Silakan <a href='login.php'>Login di sini</a>.";
            } else {
                $error_msg = "Gagal mendaftarkan akun. Terjadi kesalahan sistem.";
            }
            $insert_stmt->close();
        }
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
    <title>Registrasi - Manajemen Projek</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background-color: #f4f6f9; margin: 0; padding: 0; }
        .reg-box { max-width: 420px; margin: 60px auto; padding: 30px; background: #fff; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 600; color: #333; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; }
        .btn-submit { width: 100%; padding: 12px; background: #28a745; color: #fff; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; }
        .btn-submit:hover { background: #218838; }
        .alert-error { background: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 15px; border-radius: 5px; font-size: 14px; }
        .alert-success { background: #d4edda; color: #155724; padding: 10px; margin-bottom: 15px; border-radius: 5px; font-size: 14px; }
        .hp-field { display: none !important; visibility: hidden !important; }
        .login-link { text-align: center; margin-top: 15px; font-size: 14px; }
        .login-link a { color: #007bff; text-decoration: none; }
    </style>
</head>
<body>

<div class="reg-box">
    <h2 style="margin-top:0; text-align:center;">Daftar Akun Baru</h2>

    <?php if (!empty($error_msg)): ?>
        <div class="alert-error"><?= sanitize_out($error_msg); ?></div>
    <?php endif; ?>

    <?php if (!empty($success_msg)): ?>
        <div class="alert-success"><?= $success_msg; ?></div>
    <?php endif; ?>

    <form action="register.php" method="POST" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token; ?>">

        <!-- Honeypot Trap (Disembunyikan dari manusia) -->
        <div class="hp-field">
            <input type="text" name="website_honeypot" id="website_honeypot" tabindex="-1" autocomplete="off">
        </div>

        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>
        </div>

        <div class="form-group">
            <label for="password">Password (Min. 8 Karakter)</label>
            <input type="password" id="password" name="password" required>
        </div>

        <div class="form-group">
            <label for="confirm_password">Konfirmasi Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>

        <button type="submit" class="btn-submit">Daftar Sekarang</button>
    </form>

    <div class="login-link">
        Sudah punya akun? <a href="login.php">Login di sini</a>
    </div>
</div>

</body>
</html>