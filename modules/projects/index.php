<?php
require_once __DIR__ . '/../../config/database.php';

// Handle Hapus Proyek
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $stmtDel = $pdo->prepare("DELETE FROM projects WHERE id = ?");
    $stmtDel->execute([$delete_id]);
    header("Location: index.php");
    exit;
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

// Ambil semua proyek & hitung progress % dari tasks
$stmt = $pdo->query("
    SELECT p.*, 
           COALESCE(SUM(e.jumlah_biaya), 0) AS total_expense,
           COUNT(t.id) AS total_tasks,
           SUM(CASE WHEN t.status = 'Done' THEN 1 ELSE 0 END) AS completed_tasks
    FROM projects p
    LEFT JOIN expenses e ON p.id = e.project_id
    LEFT JOIN tasks t ON p.id = t.project_id
    GROUP BY p.id
    ORDER BY p.created_at DESC
");
$projects = $stmt->fetchAll();
?>

<main class="flex-1 p-8 overflow-y-auto bg-slate-50">
    <div class="flex flex-col md:flex-row justify-between md:items-center gap-4 mb-8">
        <div>
            <div class="flex items-center gap-2 text-emerald-700 font-semibold text-xs tracking-wider uppercase mb-1">
                <i class="fa-solid fa-circle text-[8px]"></i> Modul 1
            </div>
            <h2 class="text-2xl font-bold text-slate-800">Manajemen Proyek & Timeline</h2>
            <p class="text-slate-500 text-sm">Kelola daftar seluruh proyek PT. CTC beserta progress dan budget-nya</p>
        </div>
        <a href="create.php" class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-5 py-2.5 rounded-xl flex items-center justify-center gap-2 transition shadow-md shadow-emerald-600/20 text-sm">
            <i class="fa-solid fa-plus"></i> Tambah Proyek Baru
        </a>
    </div>

    <!-- Project Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($projects)): ?>
            <div class="col-span-full bg-white border border-slate-200 rounded-2xl p-12 text-center text-slate-400">
                <i class="fa-solid fa-folder-open text-4xl mb-3 text-slate-300"></i>
                <p class="font-medium text-slate-600">Belum ada data proyek terdaftar.</p>
                <p class="text-sm text-slate-400 mt-1">Klik tombol "Tambah Proyek Baru" di atas untuk memulai.</p>
            </div>
        <?php else: ?>
            <?php foreach ($projects as $p): 
                $progressPercent = $p['total_tasks'] > 0 ? round(($p['completed_tasks'] / $p['total_tasks']) * 100) : 0;
            ?>
                <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm hover:shadow-md transition flex flex-col justify-between">
                    <div>
                        <div class="flex justify-between items-start mb-3">
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

                            <div class="flex items-center gap-2">
                                <a href="edit.php?id=<?= $p['id'] ?>" title="Edit Proyek" class="text-slate-400 hover:text-emerald-600 p-1">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <a href="index.php?delete=<?= $p['id'] ?>" onclick="return confirm('Yakin menghapus proyek ini beserta seluruh tugasnya?')" title="Hapus Proyek" class="text-slate-400 hover:text-red-500 p-1">
                                    <i class="fa-solid fa-trash-can"></i>
                                </a>
                            </div>
                        </div>

                        <h3 class="font-bold text-slate-800 text-lg mb-1 truncate"><?= htmlspecialchars($p['nama_projek']) ?></h3>
                        <p class="text-xs text-slate-400 mb-4 flex items-center gap-1.5">
                            <i class="fa-solid fa-building text-slate-300"></i> Klien: <span class="font-medium text-slate-600"><?= htmlspecialchars($p['klien']) ?></span>
                        </p>

                        <!-- Progress Bar -->
                        <div class="mb-4">
                            <div class="flex justify-between text-xs font-semibold mb-1.5">
                                <span class="text-slate-500">Progress Pengerjaan</span>
                                <span class="text-emerald-600"><?= $progressPercent ?>%</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2">
                                <div class="bg-emerald-500 h-2 rounded-full transition-all duration-300" style="width: <?= $progressPercent ?>%"></div>
                            </div>
                        </div>

                        <!-- Info Financial -->
                        <div class="bg-slate-50 rounded-xl p-3 grid grid-cols-2 gap-2 text-xs mb-4 border border-slate-100">
                            <div>
                                <span class="text-slate-400 block mb-0.5">Budget Proyek</span>
                                <span class="font-bold text-slate-700">Rp <?= number_format($p['budget'], 0, ',', '.') ?></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block mb-0.5">Pengeluaran</span>
                                <span class="font-bold text-amber-600">Rp <?= number_format($p['total_expense'], 0, ',', '.') ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Card -->
                    <div class="pt-3 border-t border-slate-100 flex justify-between items-center text-xs text-slate-400">
                        <span><i class="fa-regular fa-calendar text-slate-400 mr-1"></i> <?= date('d M Y', strtotime($p['deadline'])) ?></span>
                        <a href="detail.php?id=<?= $p['id'] ?>" class="font-semibold text-emerald-600 hover:text-emerald-700">
                            Detail & Tasks <i class="fa-solid fa-arrow-right text-[10px] ml-0.5"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>