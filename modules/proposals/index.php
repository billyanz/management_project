<?php
require_once 'includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

// Fetch Seluruh Proyek Terdaftar
$projects = $pdo->query("
    SELECT p.*, 
           COUNT(c.id) as total_contracts
    FROM projects p
    LEFT JOIN contracts c ON p.id = c.project_id
    GROUP BY p.id
    ORDER BY p.created_at DESC
")->fetchAll();
?>

<main class="flex-1 p-8 overflow-y-auto bg-slate-50">
    <!-- Header Bar -->
    <div class="flex flex-col md:flex-row justify-between md:items-center gap-4 mb-8">
        <div>
            <div class="flex items-center gap-2 text-emerald-700 font-semibold text-xs tracking-wider uppercase mb-1">
                <i class="fa-solid fa-circle text-[8px]"></i> Modul Proposal Penawaran
            </div>
            <h2 class="text-2xl font-bold text-slate-800">Surat Penawaran & Commercial Proposal</h2>
            <p class="text-slate-500 text-sm">Otomatisasi pencetakan Surat Penawaran Proyek resmi PT. Cipta Teknologi Cendekia</p>
        </div>
    </div>

    <!-- Project Proposal Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($projects)): ?>
            <div class="col-span-full bg-white border border-slate-200 rounded-2xl p-12 text-center text-slate-400">
                <i class="fa-solid fa-file-signature text-4xl mb-3 text-slate-300"></i>
                <p class="font-medium text-slate-600">Belum ada proyek terdaftar.</p>
                <p class="text-sm text-slate-400 mt-1">Tambahkan proyek baru terlebih dahulu di menu Proyek & Timeline.</p>
            </div>
        <?php else: ?>
            <?php foreach ($projects as $p): ?>
                <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm hover:shadow-md transition flex flex-col justify-between">
                    <div>
                        <div class="flex justify-between items-center mb-3">
                            <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-200">
                                Automatic Proposal
                            </span>
                            <span class="text-xs text-slate-400 font-medium">
                                <i class="fa-regular fa-calendar mr-1"></i> <?= date('d M Y', strtotime($p['tanggal_mulai'])) ?>
                            </span>
                        </div>

                        <h3 class="font-bold text-slate-800 text-base mb-1 truncate"><?= htmlspecialchars($p['nama_projek']) ?></h3>
                        <p class="text-xs text-slate-500 mb-4 flex items-center gap-1.5">
                            <i class="fa-solid fa-building text-slate-400"></i> Klien Target: <span class="font-semibold text-slate-700"><?= htmlspecialchars($p['klien']) ?></span>
                        </p>

                        <div class="bg-slate-50 rounded-xl p-3 text-xs mb-4 border border-slate-100 grid grid-cols-2 gap-2">
                            <div>
                                <span class="text-slate-400 block mb-0.5">Nilai Penawaran</span>
                                <span class="font-bold text-emerald-700">Rp <?= number_format($p['budget'], 0, ',', '.') ?></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block mb-0.5">Status Proyek</span>
                                <span class="font-semibold text-slate-700"><?= $p['status'] ?></span>
                            </div>
                        </div>
                    </div>

                    <a href="print_proposal.php?project_id=<?= $p['id'] ?>" target="_blank" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs rounded-xl transition text-center flex items-center justify-center gap-2 shadow-md shadow-emerald-600/20">
                        <i class="fa-solid fa-print"></i> Generate & Cetak Penawaran PDF
                    </a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>