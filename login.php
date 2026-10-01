<?php
/**
 * File: login.php
 * UI Login Modern Matching Emerald Theme PT. CTC
 */

require_once __DIR__ . '/includes/security.php';

if (file_exists(__DIR__ . '/config/database.php')) {
    require_once __DIR__ . '/config/database.php';
} elseif (file_exists(__DIR__ . '/config/database.php.example')) {
    require_once __DIR__ . '/config/database.php.example';
}

if (!isset($conn) || $conn === null) {
    $host = "localhost"; $user = "root"; $pass = ""; $db = "db_manajemen_projek";
    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) { die("Koneksi gagal: " . $conn->connect_error); }
}

if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true) {
    header("Location: index.php");
    exit;
}

check_rate_limit('login', 5, 300);

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_honeypot('website_honeypot');

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("Validasi keamanan gagal (CSRF Mismatch).");
    }

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error_msg = "Email dan Password wajib diisi!";
    } else {
        $stmt = $conn->prepare("SELECT id, nama, email, password, role FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                reset_rate_limit('login');
                session_regenerate_id(true);

                $_SESSION['is_logged_in'] = true;
                $_SESSION['user_id']      = $user['id'];
                $_SESSION['username']     = $user['nama'];
                $_SESSION['nama']         = $user['nama'];
                $_SESSION['user_role']    = $user['role'];
                $_SESSION['role']         = $user['role'];
                $_SESSION['user_agent']   = $_SERVER['HTTP_USER_AGENT'] ?? '';

                header("Location: index.php");
                exit;
            }
        }

        register_failed_attempt('login', 5, 300);
        $error_msg = "Kombinasi Email dan Password salah!";
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
    <title>Login System - PT. Cipta Teknologi Cendekia</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl overflow-hidden border border-slate-100">
        <!-- Header Brand (Emerald Theme Matching Sidebar) -->
        <div class="bg-emerald-950 p-8 text-center relative overflow-hidden">
            <div class="absolute -right-10 -bottom-10 w-32 h-32 bg-emerald-800/30 rounded-full blur-2xl"></div>
            
            <div class="inline-flex items-center justify-center bg-emerald-500 w-16 h-16 rounded-2xl text-emerald-950 text-2xl font-bold mb-4 shadow-lg shadow-emerald-500/30">
                <i class="fa-solid fa-building-user"></i>
            </div>
            <h1 class="text-2xl font-bold text-white tracking-wide">PT. CTC</h1>
            <p class="text-emerald-300 text-xs font-light tracking-wider mt-1">Cipta Teknologi Cendekia</p>
        </div>

        <!-- Form Login Area -->
        <div class="p-8">
            <h2 class="text-xl font-bold text-slate-800 mb-1">Selamat Datang!</h2>
            <p class="text-xs text-slate-500 mb-6">Silakan masuk menggunakan akun internal Anda.</p>

            <?php if (!empty($error_msg)): ?>
                <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-xl text-xs mb-5 flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation text-rose-500 text-sm"></i>
                    <span><?= sanitize_out($error_msg); ?></span>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" autocomplete="off" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token; ?>">

                <!-- Honeypot -->
                <div style="display:none !important;">
                    <input type="text" name="website_honeypot" tabindex="-1" autocomplete="off">
                </div>

                <!-- Input Email -->
                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-700 mb-2">EMAIL *</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-sm">
                            <i class="fa-solid fa-envelope"></i>
                        </div>
                        <input type="email" id="email" name="email" required autofocus
                            placeholder="nama@projek.com"
                            class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-emerald-500 focus:bg-white transition text-slate-800">
                    </div>
                </div>

                <!-- Input Password -->
                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-700 mb-2">PASSWORD *</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-sm">
                            <i class="fa-solid fa-lock"></i>
                        </div>
                        <input type="password" id="password" name="password" required
                            placeholder="••••••••"
                            class="w-full pl-10 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-emerald-500 focus:bg-white transition text-slate-800">
                        <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600">
                            <i class="fa-solid fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" 
                    class="w-full mt-2 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl text-sm shadow-lg shadow-emerald-600/25 transition duration-200 flex items-center justify-center gap-2">
                    <span>Masuk Ke Sistem</span>
                    <i class="fa-solid fa-arrow-right text-xs"></i>
                </button>
            </form>

            <div class="mt-8 text-center border-t border-slate-100 pt-4">
                <p class="text-[11px] text-slate-400">&copy; 2026 PT. Cipta Teknologi Cendekia. Internal System Only.</p>
            </div>
        </div>
    </div>

    <script>
    function togglePassword() {
        const pass = document.getElementById("password");
        const icon = document.getElementById("eyeIcon");
        if (pass.type === "password") {
            pass.type = "text";
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
        } else {
            pass.type = "password";
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
        }
    }
    </script>
</body>
</html>