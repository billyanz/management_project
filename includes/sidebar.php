<?php
$baseUrl = "http://localhost/website%20manajemen%20projek";
$currentScript = $_SERVER['SCRIPT_NAME'];
?>
<aside class="w-64 bg-emerald-950 border-r border-emerald-900 p-5 flex flex-col justify-between hidden md:flex min-h-screen shrink-0 text-white shadow-xl">
    <div>
        <!-- Logo & Company Name -->
        <div class="flex items-center gap-3 mb-8 px-2 pb-5 border-b border-emerald-800/60">
            <div class="bg-emerald-500 p-2.5 rounded-xl text-emerald-950 font-bold text-xl flex items-center justify-center shadow-lg shadow-emerald-500/20">
                <i class="fa-solid fa-building-user"></i>
            </div>
            <div>
                <h1 class="font-bold text-base text-white tracking-wide">PT. CTC</h1>
                <span class="text-xs text-emerald-300 font-light">Cipta Teknologi Cendekia</span>
            </div>
        </div>

        <!-- Navigasi -->
        <nav class="space-y-1.5">
            <a href="<?= $baseUrl ?>/index.php" class="flex items-center gap-3 px-4 py-3 rounded-xl transition font-medium text-sm <?= strpos($currentScript, 'index.php') !== false && strpos($currentScript, 'modules') === false ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30' : 'text-emerald-100 hover:bg-emerald-900/60 hover:text-white' ?>">
                <i class="fa-solid fa-gauge w-5 text-emerald-300"></i> Dashboard
            </a>
            <a href="<?= $baseUrl ?>/modules/projects/index.php" class="flex items-center gap-3 px-4 py-3 rounded-xl transition font-medium text-sm <?= strpos($currentScript, '/projects/') !== false ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30' : 'text-emerald-100 hover:bg-emerald-900/60 hover:text-white' ?>">
                <i class="fa-solid fa-folder-open w-5 text-emerald-300"></i> Proyek & Timeline
            </a>
            <a href="<?= $baseUrl ?>/modules/tasks/index.php" class="flex items-center gap-3 px-4 py-3 rounded-xl transition font-medium text-sm <?= strpos($currentScript, '/tasks/') !== false ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30' : 'text-emerald-100 hover:bg-emerald-900/60 hover:text-white' ?>">
                <i class="fa-solid fa-list-check w-5 text-emerald-300"></i> Workload & Priority
            </a>
            <a href="<?= $baseUrl ?>/modules/users/index.php" class="flex items-center gap-3 px-4 py-3 rounded-xl transition font-medium text-sm <?= strpos($currentScript, '/users/') !== false ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30' : 'text-emerald-100 hover:bg-emerald-900/60 hover:text-white' ?>">
                <i class="fa-solid fa-users-gear w-5 text-emerald-300"></i> Manajemen Tim
            </a>
            <a href="<?= $baseUrl ?>/modules/expenses/index.php" class="flex items-center gap-3 px-4 py-3 rounded-xl transition font-medium text-sm <?= strpos($currentScript, '/expenses/') !== false ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30' : 'text-emerald-100 hover:bg-emerald-900/60 hover:text-white' ?>">
                <i class="fa-solid fa-wallet w-5 text-emerald-300"></i> Tracking Biaya
            </a>
            <a href="<?= $baseUrl ?>/modules/invoices/index.php" class="flex items-center gap-3 px-4 py-3 rounded-xl transition font-medium text-sm <?= strpos($currentScript, '/invoices/') !== false ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30' : 'text-emerald-100 hover:bg-emerald-900/60 hover:text-white' ?>">
                <i class="fa-solid fa-file-invoice-dollar w-5 text-emerald-300"></i> Invoice & Billing
            </a>
            <a href="<?= $baseUrl ?>/modules/reports/index.php" class="flex items-center gap-3 px-4 py-3 rounded-xl transition font-medium text-sm <?= strpos($currentScript, '/reports/') !== false ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30' : 'text-emerald-100 hover:bg-emerald-900/60 hover:text-white' ?>">
                <i class="fa-solid fa-chart-pie w-5 text-emerald-300"></i> Laporan Keuangan
            </a>
            <a href="<?= $baseUrl ?>/modules/proposals/index.php" class="flex items-center gap-3 px-4 py-3 rounded-xl transition font-medium text-sm <?= strpos($currentScript, '/proposals/') !== false ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30' : 'text-emerald-100 hover:bg-emerald-900/60 hover:text-white' ?>">
                <i class="fa-solid fa-file-signature w-5 text-emerald-300"></i> Surat Penawaran
            </a>
            <a href="<?= $baseUrl ?>/modules/contracts/index.php" class="flex items-center gap-3 px-4 py-3 rounded-xl transition font-medium text-sm <?= strpos($currentScript, '/contracts/') !== false ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30' : 'text-emerald-100 hover:bg-emerald-900/60 hover:text-white' ?>">
                <i class="fa-solid fa-file-contract w-5 text-emerald-300"></i> Cetak Kontrak PDF
            </a>
        </nav>
    </div>

    <!-- Profil & Logout -->
    <div class="pt-4 border-t border-emerald-800/60">
        <div class="flex items-center gap-3 px-2 mb-3">
            <div class="w-9 h-9 rounded-xl bg-emerald-500 text-emerald-950 flex items-center justify-center font-bold text-sm shadow-md">
                <?= strtoupper(substr($_SESSION['nama'] ?? 'A', 0, 1)) ?>
            </div>
            <div class="overflow-hidden">
                <p class="text-sm font-semibold text-white truncate"><?= htmlspecialchars($_SESSION['nama'] ?? 'User') ?></p>
                <p class="text-xs text-emerald-300 capitalize"><?= htmlspecialchars($_SESSION['role'] ?? 'Role') ?></p>
            </div>
        </div>
        <a href="<?= $baseUrl ?>/logout.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-red-300 hover:bg-red-500/20 hover:text-red-100 transition text-sm font-medium">
            <i class="fa-solid fa-right-from-bracket"></i> Keluar
        </a>
    </div>
</aside>