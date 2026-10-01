<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Fetch statistik
$totalProyek = $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
$totalBudget = $pdo->query("SELECT SUM(budget) FROM projects")->fetchColumn() ?: 0;
$totalPengeluaran = $pdo->query("SELECT SUM(jumlah_biaya) FROM expenses")->fetchColumn() ?: 0;
$totalProfit = $totalBudget - $totalPengeluaran;
$marginProfit = $totalBudget > 0 ? round(($totalProfit / $totalBudget) * 100, 1) : 0;

$stmtProjects = $pdo->query("
    SELECT p.*, COALESCE(SUM(e.jumlah_biaya), 0) AS total_expense
    FROM projects p
    LEFT JOIN expenses e ON p.id = e.project_id
    GROUP BY p.id
    ORDER BY p.created_at DESC
    LIMIT 5
");
$recentProjects = $stmtProjects->fetchAll();
?>

<main class="flex-1 p-8 overflow-y-auto bg-slate-50">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between md:items-center gap-4 mb-8">
        <div>
            <div class="flex items-center gap-2 text-emerald-700 font-semibold text-xs tracking-wider uppercase mb-1">
                <i class="fa-solid fa-circle text-[8px]"></i> System Overview
            </div>
            <h2 class="text-2xl font-bold text-slate-800">PT. Cipta Teknologi Cendekia</h2>
            <p class="text-slate-500 text-sm">Monitoring real-time progres, budget, dan profitabilitas proyek</p>
        </div>
        <a href="modules/projects/create.php" class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-5 py-2.5 rounded-xl flex items-center justify-center gap-2 transition shadow-md shadow-emerald-600/20 text-sm">
            <i class="fa-solid fa-plus"></i> Tambah Proyek Baru
        </a>
    </div>

    <!-- Stat Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <div class="bg-white border border-slate-200 p-5 rounded-2xl shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between mb-3">
                <span class="text-slate-500 text-xs font-semibold uppercase tracking-wider">Total Proyek</span>
                <div class="p-2 bg-emerald-50 text-emerald-600 rounded-xl"><i class="fa-solid fa-folder-closed"></i></div>
            </div>
            <h3 class="text-2xl font-bold text-slate-800"><?= number_format($totalProyek) ?></h3>
            <span class="text-xs text-slate-400">Proyek terdaftar aktif</span>
        </div>

        <div class="bg-white border border-slate-200 p-5 rounded-2xl shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between mb-3">
                <span class="text-slate-500 text-xs font-semibold uppercase tracking-wider">Total Budget</span>
                <div class="p-2 bg-emerald-50 text-emerald-600 rounded-xl"><i class="fa-solid fa-vault"></i></div>
            </div>
            <h3 class="text-2xl font-bold text-slate-800">Rp <?= number_format($totalBudget, 0, ',', '.') ?></h3>
            <span class="text-xs text-emerald-600 font-medium"><i class="fa-solid fa-arrow-up"></i> Estimasi Nilai Kontrak</span>
        </div>

        <div class="bg-white border border-slate-200 p-5 rounded-2xl shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between mb-3">
                <span class="text-slate-500 text-xs font-semibold uppercase tracking-wider">Realisasi Biaya</span>
                <div class="p-2 bg-amber-50 text-amber-600 rounded-xl"><i class="fa-solid fa-receipt"></i></div>
            </div>
            <h3 class="text-2xl font-bold text-slate-800">Rp <?= number_format($totalPengeluaran, 0, ',', '.') ?></h3>
            <span class="text-xs text-slate-400">Pengeluaran operasional</span>
        </div>

        <div class="bg-white border border-slate-200 p-5 rounded-2xl shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between mb-3">
                <span class="text-slate-500 text-xs font-semibold uppercase tracking-wider">Margin Profit</span>
                <div class="p-2 bg-emerald-50 text-emerald-700 rounded-xl"><i class="fa-solid fa-chart-line"></i></div>
            </div>
            <h3 class="text-2xl font-bold text-slate-800"><?= $marginProfit ?>%</h3>
            <span class="text-xs text-emerald-600 font-medium">Rp <?= number_format($totalProfit, 0, ',', '.') ?> keuntungannya</span>
        </div>
    </div>

    <!-- Tabel Proyek Terbaru -->
    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-bold text-slate-800">Proyek Terbaru</h3>
            <a href="modules/projects/index.php" class="text-sm font-semibold text-emerald-600 hover:text-emerald-700">Lihat Semua <i class="fa-solid fa-arrow-right ml-1"></i></a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="text-xs uppercase bg-slate-100 text-slate-500 font-bold border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">Nama Proyek</th>
                        <th class="px-4 py-3">Klien</th>
                        <th class="px-4 py-3">Budget</th>
                        <th class="px-4 py-3">Pengeluaran</th>
                        <th class="px-4 py-3">Deadline</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($recentProjects)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-8 text-slate-400 font-medium">Belum ada proyek. Silakan tambahkan proyek baru melalui tombol di atas.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentProjects as $p): ?>
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="px-4 py-4 font-semibold text-slate-800"><?= htmlspecialchars($p['nama_projek']) ?></td>
                                <td class="px-4 py-4 text-slate-500"><?= htmlspecialchars($p['klien']) ?></td>
                                <td class="px-4 py-4 font-medium">Rp <?= number_format($p['budget'], 0, ',', '.') ?></td>
                                <td class="px-4 py-4 text-amber-600 font-medium">Rp <?= number_format($p['total_expense'], 0, ',', '.') ?></td>
                                <td class="px-4 py-4 text-slate-500"><?= date('d M Y', strtotime($p['deadline'])) ?></td>
                                <td class="px-4 py-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold
                                        <?php
                                            switch($p['status']) {
                                                case 'In Progress': echo 'bg-blue-50 text-blue-700 border border-blue-200'; break;
                                                case 'Completed': echo 'bg-emerald-50 text-emerald-700 border border-emerald-200'; break;
                                                case 'On Hold': echo 'bg-amber-50 text-amber-700 border border-amber-200'; break;
                                                default: echo 'bg-slate-100 text-slate-600 border border-slate-200';
                                            }
                                        ?>">
                                        <?= $p['status'] ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>