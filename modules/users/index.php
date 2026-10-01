<?php
require_once __DIR__ . '/../../config/database.php';

$error = '';
$success = '';

// Handle Tambah Karyawan Baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $nama    = trim($_POST['nama']);
    $email   = trim($_POST['email']);
    $jabatan = trim($_POST['jabatan']);
    $role    = $_POST['role'];
    $password = password_hash('123456', PASSWORD_BCRYPT); // Password default 123456

    if (!empty($nama) && !empty($email) && !empty($jabatan)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO users (nama, email, password, role, jabatan) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nama, $email, $password, $role, $jabatan]);
            $success = "Karyawan $nama berhasil ditambahkan!";
        } catch (\PDOException $e) {
            $error = "Email sudah terdaftar di sistem!";
        }
    } else {
        $error = "Mohon isi seluruh kolom wajib!";
    }
}

// Handle Hapus Karyawan
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    if ($delete_id != 1) { // Jangan hapus admin utama
        $stmtDel = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmtDel->execute([$delete_id]);
        header("Location: index.php");
        exit;
    }
}

// Fetch Semua Karyawan
$users = $pdo->query("
    SELECT u.*, 
           COUNT(t.id) AS total_assigned_tasks,
           SUM(CASE WHEN t.status = 'Done' THEN 1 ELSE 0 END) AS completed_tasks
    FROM users u
    LEFT JOIN tasks t ON u.id = t.user_id
    GROUP BY u.id
    ORDER BY u.role ASC, u.nama ASC
")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<main class="flex-1 p-8 overflow-y-auto bg-slate-50">
    <div class="flex flex-col md:flex-row justify-between md:items-center gap-4 mb-8">
        <div>
            <div class="flex items-center gap-2 text-emerald-700 font-semibold text-xs tracking-wider uppercase mb-1">
                <i class="fa-solid fa-circle text-[8px]"></i> Resource Management
            </div>
            <h2 class="text-2xl font-bold text-slate-800">Manajemen Tim & Karyawan</h2>
            <p class="text-slate-500 text-sm">Kelola daftar tim, jobdesk/jabatan, dan alokasi personel PT. CTC</p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm mb-6 flex items-center justify-between">
            <span><i class="fa-solid fa-triangle-exclamation mr-2"></i> <?= $error ?></span>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm mb-6 flex items-center justify-between">
            <span><i class="fa-solid fa-circle-check mr-2"></i> <?= $success ?></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Form Tambah Karyawan Baru -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm h-fit">
            <h3 class="font-bold text-slate-800 text-base mb-4 flex items-center gap-2">
                <i class="fa-solid fa-user-plus text-emerald-600"></i> Tambah Anggota Tim
            </h3>
            <form action="" method="POST" class="space-y-4">
                <input type="hidden" name="add_user" value="1">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Nama Lengkap *</label>
                    <input type="text" name="nama" required placeholder="Contoh: Budi Pratama" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Email Karyawan *</label>
                    <input type="email" name="email" required placeholder="budi@ctc.co.id" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Jabatan / Jobdesk *</label>
                    <input type="text" name="jabatan" required placeholder="Contoh: Frontend Developer / UI Designer" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Role Hak Akses</label>
                    <select name="role" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-sm bg-white focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        <option value="karyawan" selected>Karyawan / Technical Staff</option>
                        <option value="admin">Project Manager / Admin</option>
                    </select>
                </div>
                <p class="text-[11px] text-slate-400">Password standar akun baru adalah: <span class="font-mono text-slate-600">123456</span></p>
                <button type="submit" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-sm rounded-xl transition shadow-md shadow-emerald-600/20">
                    + Simpan Karyawan
                </button>
            </form>
        </div>

        <!-- Tabel Daftar Karyawan -->
        <div class="lg:col-span-2 bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
            <h3 class="font-bold text-slate-800 text-base mb-4">Daftar Anggota Tim PT. CTC</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="text-xs uppercase bg-slate-100 text-slate-500 font-bold border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3">Nama & Jabatan</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Role</th>
                            <th class="px-4 py-3">Beban Tugas</th>
                            <th class="px-4 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($users as $u): ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-800 font-bold flex items-center justify-center text-xs">
                                            <?= strtoupper(substr($u['nama'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-slate-800 leading-tight"><?= htmlspecialchars($u['nama']) ?></p>
                                            <span class="text-xs text-slate-400"><?= htmlspecialchars($u['jabatan']) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 text-xs text-slate-500"><?= htmlspecialchars($u['email']) ?></td>
                                <td class="px-4 py-3.5">
                                    <span class="px-2.5 py-0.5 text-[10px] font-bold uppercase rounded-md <?= $u['role'] === 'admin' ? 'bg-purple-100 text-purple-700' : 'bg-slate-100 text-slate-700' ?>">
                                        <?= $u['role'] ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-xs">
                                    <span class="font-semibold text-slate-700"><?= $u['completed_tasks'] ?> / <?= $u['total_assigned_tasks'] ?></span> Tugas Selesai
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <?php if ($u['id'] != 1): ?>
                                        <a href="index.php?delete=<?= $u['id'] ?>" onclick="return confirm('Yakin ingin menghapus karyawan ini?')" class="text-red-500 hover:text-red-700 text-xs p-1">
                                            <i class="fa-solid fa-trash-can"></i> Hapus
                                        </a>
                                    <?php else: ?>
                                        <span class="text-[10px] text-slate-300 italic">Primary Admin</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>