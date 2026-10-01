<?php

require_once __DIR__ . '/../../includes/auth_check.php';

if (file_exists(__DIR__ . '/../../config/database.php')) {
    require_once __DIR__ . '/../../config/database.php';
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

// Filter Parameter
$filter_status = $_GET['status'] ?? 'All';
$filter_user   = $_GET['user_id'] ?? 'All';

// Build Query Tasks
$query = "
    SELECT t.*, 
           p.nama_projek, 
           p.klien, 
           u.nama AS nama_karyawan,
           u.jabatan
    FROM tasks t
    JOIN projects p ON t.project_id = p.id
    JOIN users u ON t.user_id = u.id
    WHERE 1=1
";

$params = [];

if ($filter_status !== 'All') {
    $query .= " AND t.status = ?";
    $params[] = $filter_status;
}

if ($filter_user !== 'All') {
    $query .= " AND t.user_id = ?";
    $params[] = $filter_user;
}

$query .= " ORDER BY CASE t.prioritas 
                WHEN 'Urgent' THEN 1 
                WHEN 'High' THEN 2 
                WHEN 'Medium' THEN 3 
                ELSE 4 
            END ASC, t.deadline ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

// Fetch Data Karyawan & Stats
$users = $pdo->query("SELECT * FROM users ORDER BY nama ASC")->fetchAll();
$totalTasks = $pdo->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
$urgentTasks = $pdo->query("SELECT COUNT(*) FROM tasks WHERE prioritas = 'Urgent' AND status != 'Done'")->fetchColumn();
$completedTasks = $pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'Done'")->fetchColumn();
$inProgressTasks = $pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'In Progress'")->fetchColumn();
?>

<main class="flex-1 p-8 overflow-y-auto bg-slate-50">
    <!-- Top Header Bar -->
    <div class="flex flex-col md:flex-row justify-between md:items-center gap-4 mb-8">
        <div>
            <div class="flex items-center gap-2 text-emerald-700 font-semibold text-xs tracking-wider uppercase mb-1">
                <i class="fa-solid fa-circle text-[8px]"></i> Modul 2
            </div>
            <h2 class="text-2xl font-bold text-slate-800">Workload & Priority Hub</h2>
            <p class="text-slate-500 text-sm">Monitoring tugas prioritas harian karyawan PT. CTC & update status pengerjaan</p>
        </div>
    </div>

    <!-- Stats Bar -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white border border-slate-200 p-4 rounded-2xl shadow-sm">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-semibold text-slate-400 uppercase">Total Tugas</span>
                <i class="fa-solid fa-list-check text-slate-400"></i>
            </div>
            <p class="text-xl font-bold text-slate-800"><?= number_format($totalTasks) ?></p>
        </div>
        <div class="bg-white border border-slate-200 p-4 rounded-2xl shadow-sm">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-semibold text-red-500 uppercase">Prioritas Urgent</span>
                <i class="fa-solid fa-triangle-exclamation text-red-500"></i>
            </div>
            <p class="text-xl font-bold text-red-600"><?= number_format($urgentTasks) ?></p>
        </div>
        <div class="bg-white border border-slate-200 p-4 rounded-2xl shadow-sm">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-semibold text-blue-500 uppercase">Sedang Dikerjakan</span>
                <i class="fa-solid fa-spinner text-blue-500"></i>
            </div>
            <p class="text-xl font-bold text-blue-600"><?= number_format($inProgressTasks) ?></p>
        </div>
        <div class="bg-white border border-slate-200 p-4 rounded-2xl shadow-sm">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-semibold text-emerald-600 uppercase">Selesai (Done)</span>
                <i class="fa-solid fa-circle-check text-emerald-600"></i>
            </div>
            <p class="text-xl font-bold text-emerald-600"><?= number_format($completedTasks) ?></p>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="bg-white border border-slate-200 rounded-2xl p-5 mb-6 shadow-sm flex flex-col md:flex-row justify-between items-center gap-4">
        <form method="GET" class="flex flex-wrap items-center gap-3 w-full">
            <div class="flex items-center gap-2">
                <label class="text-xs font-bold text-slate-500 uppercase">Status:</label>
                <select name="status" onchange="this.form.submit()" class="px-3 py-1.5 rounded-xl border border-slate-200 text-xs bg-white font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <option value="All" <?= $filter_status === 'All' ? 'selected' : '' ?>>Semua Status</option>
                    <option value="To Do" <?= $filter_status === 'To Do' ? 'selected' : '' ?>>To Do</option>
                    <option value="In Progress" <?= $filter_status === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="Done" <?= $filter_status === 'Done' ? 'selected' : '' ?>>Done</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <label class="text-xs font-bold text-slate-500 uppercase">Karyawan:</label>
                <select name="user_id" onchange="this.form.submit()" class="px-3 py-1.5 rounded-xl border border-slate-200 text-xs bg-white font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <option value="All" <?= $filter_user === 'All' ? 'selected' : '' ?>>Semua Karyawan</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= $filter_user == $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($filter_status !== 'All' || $filter_user !== 'All'): ?>
                <a href="index.php" class="text-xs text-red-500 hover:underline font-semibold ml-auto"><i class="fa-solid fa-xmark mr-1"></i> Reset Filter</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tasks Grid / Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($tasks)): ?>
            <div class="col-span-full bg-white border border-slate-200 rounded-2xl p-12 text-center text-slate-400">
                <i class="fa-solid fa-clipboard-check text-4xl mb-3 text-slate-300"></i>
                <p class="font-medium text-slate-600">Tidak ada tugas ditemukan.</p>
                <p class="text-sm text-slate-400 mt-1">Alokasikan tugas baru melalui menu Proyek & Timeline.</p>
            </div>
        <?php else: ?>
            <?php foreach ($tasks as $t): ?>
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm hover:shadow-md transition flex flex-col justify-between">
                    <div>
                        <!-- Header Task Card -->
                        <div class="flex justify-between items-start mb-3">
                            <span class="px-2.5 py-1 text-[10px] font-bold uppercase rounded-lg tracking-wide
                                <?php
                                    switch($t['prioritas']) {
                                        case 'Urgent': echo 'bg-red-50 text-red-600 border border-red-200'; break;
                                        case 'High': echo 'bg-amber-50 text-amber-600 border border-amber-200'; break;
                                        case 'Medium': echo 'bg-blue-50 text-blue-600 border border-blue-200'; break;
                                        default: echo 'bg-slate-100 text-slate-600 border border-slate-200';
                                    }
                                ?>">
                                <i class="fa-solid fa-flag mr-1"></i> <?= $t['prioritas'] ?>
                            </span>

                            <span class="text-xs font-semibold text-slate-400">
                                <i class="fa-regular fa-calendar-xmark text-slate-400 mr-1"></i> <?= date('d M Y', strtotime($t['deadline'])) ?>
                            </span>
                        </div>

                        <!-- Task Name & Project Info -->
                        <h4 class="font-bold text-slate-800 text-base mb-1 line-clamp-2"><?= htmlspecialchars($t['nama_tugas']) ?></h4>
                        <p class="text-xs text-emerald-700 font-medium mb-4 flex items-center gap-1">
                            <i class="fa-solid fa-folder text-emerald-600"></i> <?= htmlspecialchars($t['nama_projek']) ?>
                        </p>

                        <!-- Karyawan Info -->
                        <div class="flex items-center gap-2.5 bg-slate-50 p-2.5 rounded-xl border border-slate-100 mb-4">
                            <div class="w-7 h-7 rounded-lg bg-emerald-600 text-white flex items-center justify-center font-bold text-xs shadow-sm">
                                <?= strtoupper(substr($t['nama_karyawan'], 0, 1)) ?>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-800 leading-none"><?= htmlspecialchars($t['nama_karyawan']) ?></p>
                                <span class="text-[10px] text-slate-400"><?= htmlspecialchars($t['jabatan']) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Actions Form: Change Status -->
                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400">Update Status:</span>
                        <form action="update.php" method="POST" class="inline-block">
                            <input type="hidden" name="task_id" value="<?= $t['id'] ?>">
                            <select name="status" onchange="this.form.submit()" class="px-3 py-1 rounded-lg border border-slate-200 text-xs font-semibold bg-white focus:ring-2 focus:ring-emerald-500 cursor-pointer
                                <?php
                                    switch($t['status']) {
                                        case 'Done': echo 'text-emerald-700 bg-emerald-50 border-emerald-300'; break;
                                        case 'In Progress': echo 'text-blue-700 bg-blue-50 border-blue-300'; break;
                                        default: echo 'text-slate-600 bg-slate-50';
                                    }
                                ?>">
                                <option value="To Do" <?= $t['status'] === 'To Do' ? 'selected' : '' ?>>To Do</option>
                                <option value="In Progress" <?= $t['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                <option value="Done" <?= $t['status'] === 'Done' ? 'selected' : '' ?>>Done</option>
                            </select>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>