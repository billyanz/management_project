<?php
// 1. Eksekusi pengecekan autentikasi & session DULUAN sebelum ada output HTML
require_once __DIR__ . '/includes/auth_check.php';

// 2. Jika koneksi database diperlukan di dashboard utama
if (file_exists(__DIR__ . '/config/database.php')) {
    require_once __DIR__ . '/config/database.php';
}

// 3. Baru panggil komponen Tampilan/UI
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Dashboard</h1>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <h5>Selamat Datang, <b><?= htmlspecialchars($_SESSION['username'] ?? 'User'); ?></b>!</h5>
                            <p>Sistem Manajemen Projek siap digunakan.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php 
include __DIR__ . '/includes/footer.php'; 
?>