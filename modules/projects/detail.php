<?php
require_once __DIR__ . '/../../config/database.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php");
    exit;
}

// Fetch detail proyek
$stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->execute([$id]);
$project = $stmt->fetch();

if (!$project) {
    header("Location: index.php");
    exit;
}

// Tambah Task Baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_task'])) {
    $nama_tugas = trim($_POST['nama_tugas']);
    $user_id    = $_POST['user_id'];
    $prioritas  = $_POST['prioritas'];
    $deadline   = $_POST['deadline'];

    if (!empty($nama_tugas)) {
        $stmtTask = $pdo->prepare("INSERT INTO tasks (project_id, user_id, nama_tugas, prioritas, status, deadline) VALUES (?, ?, ?, ?, 'To Do', ?)");
        $stmtTask->execute([$id, $user_id, $nama_tugas, $prioritas, $deadline]);
        header("Location: detail.php?id=" . $id);
        exit;
    }
}

// Fetch keryawan
$users = $pdo->query("SELECT * FROM users ORDER BY nama ASC")->fetchAll();

// Fetch list task proyek
$stmtTasks = $pdo->prepare("
    SELECT t.*, u.nama AS nama_karyawan 
    FROM tasks t
    JOIN users u ON t.user_id = u.id
    WHERE t.project_id = ?
    ORDER BY t.deadline ASC
");
$stmtTasks->execute([$id]);
$tasks = $stmtTasks->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<main class="flex-1 p-8 overflow-y-auto bg-slate-50">
    <div class="mb-6">
        <a href="index.php" class="text-xs text-emerald-600 font-semibold hover:underline mb-1 inline-block"><i class="fa-solid fa-arrow-left mr-1"></i> Kembali ke Daftar Proyek</a>
        <div class="flex justify-between items-center">
            <h2 class="text-2xl font-bold text-slate-800"><?= htmlspecialchars($project['nama_projek']) ?></h2>
            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                <?= $project['status'] ?>
            </span>
        </div>
        <p class="text-slate-500 text-sm">Klien: <span class="font-medium text-slate-700"><?= htmlspecialchars($project['klien']) ?></span> | Budget: <span class="font-medium text-slate-700">Rp <?= number_format($project['budget'], 0, ',', '.') ?></span></p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Form Tambah Tugas Baru -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm h-fit">
            <h3 class="font-bold text-slate-800 text-base mb-4 flex items-center gap-2">
                <i class="fa-solid fa-list-check text-emerald-600"></i> Penugasan Tim
            </h3>
            <form action="" method="POST" class="space-y-4">
                <input type="hidden" name="add_task" value="1">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Nama Tugas / Modul</label>
                    <input type="text" name="nama_tugas" required placeholder="Contoh: Slicing UI Frontend" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Penanggung Jawab (Karyawan)</label>
                    <select name="user_id" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-sm bg-white focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nama']) ?> (<?= $u['jabatan'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Tingkat Prioritas</label>
                    <select name="prioritas" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-sm bg-white focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        <option value="Low">Low</option>
                        <option value="Medium" selected>Medium</option>
                        <option value="High">High</option>
                        <option value="Urgent">Urgent</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Deadline Tugas</label>
                    <input type="date" name="deadline" required value="<?= date('Y-m-d') ?>" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>
                <button type="submit" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-sm rounded-xl transition shadow-md shadow-emerald-600/20">
                    + Alokasikan Tugas
                </button>
            </form>
        </div>

        <!-- Tabel Daftar Task Proyek -->
        <div class="lg:col-span-2 bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
            <h3 class="font-bold text-slate-800 text-base mb-4">Daftar Tugas & Workload Tim</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="text-xs uppercase bg-slate-100 text-slate-500 font-bold border-b border-slate-200">
                        <tr>
                            <th class="px-3 py-3">Tugas</th>
                            <th class="px-3 py-3">PIC</th>
                            <th class="px-3 py-3">Prioritas</th>
                            <th class="px-3 py-3">Status</th>
                            <th class="px-3 py-3">Deadline</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($tasks)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-6 text-slate-400">Belum ada alokasi tugas pada proyek ini.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tasks as $t): ?>
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-3 py-3.5 font-semibold text-slate-800"><?= htmlspecialchars($t['nama_tugas']) ?></td>
                                    <td class="px-3 py-3.5 text-slate-600"><?= htmlspecialchars($t['nama_karyawan']) ?></td>
                                    <td class="px-3 py-3.5">
                                        <span class="px-2 py-0.5 text-[10px] uppercase font-bold rounded
                                            <?php
                                                switch($t['prioritas']) {
                                                    case 'Urgent': echo 'bg-red-100 text-red-700'; break;
                                                    case 'High': echo 'bg-amber-100 text-amber-700'; break;
                                                    default: echo 'bg-slate-100 text-slate-600';
                                                }
                                            ?>">
                                            <?= $t['prioritas'] ?>
                                        </span>
                                    </td>
                                    <td class="px-3 py-3.5 text-xs font-semibold text-slate-700"><?= $t['status'] ?></td>
                                    <td class="px-3 py-3.5 text-xs text-slate-500"><?= date('d M Y', strtotime($t['deadline'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>


<?php require_once __DIR__ . '/../../includes/footer.php'; ?>