<?php
require_once __DIR__ . '/../../config/database.php';

// Fungsi Konversi Bulan ke Angka Romawi
function bulanRomawi($bulan) {
    $map = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'];
    return $map[(int)$bulan] ?? 'I';
}

// Generate Nomor Surat Otomatis Berurutan
$currentYear = date('Y');
$currentMonthRomawi = bulanRomawi(date('n'));

// Hitung jumlah kontrak tahun ini untuk mendapatkan urutan berikutnya
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM contracts WHERE YEAR(created_at) = ?");
$stmtCount->execute([$currentYear]);
$nextNo = $stmtCount->fetchColumn() + 1;

$autoNomorKontrak = sprintf("%03d", $nextNo) . "/CTC-SPK/" . $currentMonthRomawi . "/" . $currentYear;

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id      = $_POST['project_id'];
    $nomor_kontrak   = trim($_POST['nomor_kontrak']);
    $durasi_bulan    = (int)$_POST['durasi_bulan'];
    $tanggal_mulai   = $_POST['tanggal_mulai'];
    $tanggal_selesai = $_POST['tanggal_selesai'];
    $nilai_kontrak   = str_replace('.', '', $_POST['nilai_kontrak']);

    if (!empty($project_id) && !empty($nomor_kontrak) && $durasi_bulan > 0) {
        $stmtInsert = $pdo->prepare("
            INSERT INTO contracts (project_id, nomor_kontrak, durasi_bulan, tanggal_mulai, tanggal_selesai, nilai_kontrak) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmtInsert->execute([$project_id, $nomor_kontrak, $durasi_bulan, $tanggal_mulai, $tanggal_selesai, $nilai_kontrak]);
        $contract_id = $pdo->lastInsertId();

        header("Location: print_pdf.php?id=" . $contract_id);
        exit;
    } else {
        $error = "Mohon lengkapi seluruh data kontrak!";
    }
}

// Fetch Proyek yang belum dibuatkan kontrak atau semua proyek
$projects = $pdo->query("SELECT id, nama_projek, klien, budget, tanggal_mulai, deadline FROM projects ORDER BY nama_projek ASC")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<main class="flex-1 p-8 overflow-y-auto bg-slate-50">
    <div class="max-w-3xl mx-auto">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <a href="index.php" class="text-xs text-emerald-600 font-semibold hover:underline mb-1 inline-block"><i class="fa-solid fa-arrow-left mr-1"></i> Kembali ke Arsip Kontrak</a>
                <h2 class="text-2xl font-bold text-slate-800">Generator Kontrak Resmi PT. CTC</h2>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm mb-6">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-5">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Pilih Proyek Acuan *</label>
                <select name="project_id" id="project_select" onchange="autoFillProject()" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 font-medium">
                    <option value="">-- Pilih Proyek Terdaftar --</option>
                    <?php foreach ($projects as $p): ?>
                        <option value="<?= $p['id'] ?>" data-budget="<?= $p['budget'] ?>" data-start="<?= $p['tanggal_mulai'] ?>" data-end="<?= $p['deadline'] ?>" data-klien="<?= htmlspecialchars($p['klien']) ?>">
                            <?= htmlspecialchars($p['nama_projek']) ?> (Klien: <?= htmlspecialchars($p['klien']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Nomor Kontrak (Auto-Generated)</label>
                    <input type="text" name="nomor_kontrak" readonly required value="<?= $autoNomorKontrak ?>" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm bg-slate-50 font-mono font-bold text-emerald-800 focus:outline-none cursor-not-allowed">
                    <span class="text-[10px] text-slate-400 mt-1 block">Format otomatis berurutan sesuai nomor agenda perusahaan.</span>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Nilai Kontrak (Rp) *</label>
                    <input type="number" name="nilai_kontrak" id="nilai_kontrak" required placeholder="100000000" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 font-bold text-emerald-700">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Durasi Pengerjaan (Bulan) *</label>
                    <input type="number" name="durasi_bulan" id="durasi_bulan" required value="3" min="1" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Tanggal Mulai *</label>
                    <input type="date" name="tanggal_mulai" id="tanggal_mulai" required value="<?= date('Y-m-d') ?>" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Tanggal Selesai *</label>
                    <input type="date" name="tanggal_selesai" id="tanggal_selesai" required value="<?= date('Y-m-d', strtotime('+3 months')) ?>" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-xs text-emerald-800">
                <i class="fa-solid fa-shield-halved mr-1 text-emerald-600"></i>
                Dokumen kontrak ini akan di-generate menggunakan template standar legal korporat enterprise lengkap dengan pasal SLA, garansi, NDA, termin pembayaran, serta bidang meterai.
            </div>

            <div class="pt-4 border-t border-slate-100 flex justify-end gap-3">
                <a href="index.php" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-medium text-sm hover:bg-slate-50">Batal</a>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-sm shadow-md shadow-emerald-600/20">
                    Terbitkan & Cetak Kontrak PDF <i class="fa-solid fa-arrow-right ml-1"></i>
                </button>
            </div>
        </form>
    </div>
</main>

<script>
function autoFillProject() {
    const select = document.getElementById('project_select');
    const selectedOption = select.options[select.selectedIndex];
    
    if (selectedOption.value !== '') {
        document.getElementById('nilai_kontrak').value = selectedOption.getAttribute('data-budget');
        document.getElementById('tanggal_mulai').value = selectedOption.getAttribute('data-start');
        document.getElementById('tanggal_selesai').value = selectedOption.getAttribute('data-end');
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>