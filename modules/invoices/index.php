<?php
require_once __DIR__ . '/../../config/database.php';

// Auto-Generate Nomor Invoice (e.g. INV/CTC/2026/10/001)
function bulanRomawi($bulan) {
    $map = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'];
    return $map[(int)$bulan] ?? 'I';
}

$currentYear = date('Y');
$currentMonth = date('m');

$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE YEAR(created_at) = ?");
$stmtCount->execute([$currentYear]);
$nextNo = $stmtCount->fetchColumn() + 1;

$autoNomorInvoice = "INV/CTC/" . $currentYear . "/" . $currentMonth . "/" . sprintf("%03d", $nextNo);

$error = '';
$success = '';

// Handle Tambah Invoice Baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_invoice'])) {
    $project_id        = $_POST['project_id'];
    $nomor_invoice     = trim($_POST['nomor_invoice']);
    $termin_ke         = (int)$_POST['termin_ke'];
    $keterangan_termin = trim($_POST['keterangan_termin']);
    $jumlah_tagihan    = str_replace(['.', ','], '', $_POST['jumlah_tagihan']);
    $tanggal_tagihan   = $_POST['tanggal_tagihan'];
    $jatuh_tempo       = $_POST['jatuh_tempo'];
    $status            = $_POST['status'];

    if (!empty($project_id) && !empty($nomor_invoice) && !empty($jumlah_tagihan)) {
        $stmtInsert = $pdo->prepare("
            INSERT INTO invoices (project_id, nomor_invoice, termin_ke, keterangan_termin, jumlah_tagihan, tanggal_tagihan, jatuh_tempo, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtInsert->execute([$project_id, $nomor_invoice, $termin_ke, $keterangan_termin, $jumlah_tagihan, $tanggal_tagihan, $jatuh_tempo, $status]);
        $success = "Invoice penagihan baru berhasil diterbitkan!";
    } else {
        $error = "Mohon lengkapi seluruh kolom wajib!";
    }
}

// Fetch Proyek untuk Dropdown Choice
$projects = $pdo->query("SELECT id, nama_projek, klien, budget FROM projects ORDER BY nama_projek ASC")->fetchAll();

// Fetch Invoices List
$stmtInvoices = $pdo->query("
    SELECT i.*, p.nama_projek, p.klien 
    FROM invoices i
    JOIN projects p ON i.project_id = p.id
    ORDER BY i.created_at DESC
");
$invoices = $stmtInvoices->fetchAll();

// Stats Summary
$totalInvoiced = $pdo->query("SELECT SUM(jumlah_tagihan) FROM invoices")->fetchColumn() ?: 0;
$totalPaid     = $pdo->query("SELECT SUM(jumlah_tagihan) FROM invoices WHERE status = 'Paid'")->fetchColumn() ?: 0;
$totalUnpaid   = $pdo->query("SELECT SUM(jumlah_tagihan) FROM invoices WHERE status = 'Unpaid'")->fetchColumn() ?: 0;

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<main class="flex-1 p-8 overflow-y-auto bg-slate-50">
    <!-- Top Header -->
    <div class="flex flex-col md:flex-row justify-between md:items-center gap-4 mb-8">
        <div>
            <div class="flex items-center gap-2 text-emerald-700 font-semibold text-xs tracking-wider uppercase mb-1">
                <i class="fa-solid fa-circle text-[8px]"></i> Modul Billing System
            </div>
            <h2 class="text-2xl font-bold text-slate-800">Invoice & Penagihan Klien</h2>
            <p class="text-slate-500 text-sm">Kelola tagihan termin, status pembayaran, dan cetak kuitansi resmi PT. CTC</p>
        </div>

        <button onclick="openInvoiceModal()" class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-5 py-2.5 rounded-xl flex items-center justify-center gap-2 transition shadow-md shadow-emerald-600/20 text-sm">
            <i class="fa-solid fa-plus-circle"></i> Terbitkan Invoice Baru
        </button>
    </div>

    <!-- Alert Messages -->
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

    <!-- Stat Summary Bar -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
        <div class="bg-white border border-slate-200 p-5 rounded-2xl shadow-sm">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-1">Total Tagihan Diterbitkan</span>
            <h3 class="text-2xl font-bold text-slate-800">Rp <?= number_format($totalInvoiced, 0, ',', '.') ?></h3>
            <span class="text-xs text-slate-400">Akumulasi seluruh termin</span>
        </div>

        <div class="bg-white border border-slate-200 p-5 rounded-2xl shadow-sm">
            <span class="text-xs font-semibold text-emerald-600 uppercase tracking-wider block mb-1">Total Sudah Dilunasi (Paid)</span>
            <h3 class="text-2xl font-bold text-emerald-600">Rp <?= number_format($totalPaid, 0, ',', '.') ?></h3>
            <span class="text-xs text-emerald-600 font-medium"><i class="fa-solid fa-check-double"></i> Masuk ke kas perusahaan</span>
        </div>

        <div class="bg-white border border-slate-200 p-5 rounded-2xl shadow-sm">
            <span class="text-xs font-semibold text-amber-600 uppercase tracking-wider block mb-1">Piutang / Unpaid</span>
            <h3 class="text-2xl font-bold text-amber-600">Rp <?= number_format($totalUnpaid, 0, ',', '.') ?></h3>
            <span class="text-xs text-amber-600 font-medium"><i class="fa-solid fa-clock"></i> Menunggu pembayaran klien</span>
        </div>
    </div>

    <!-- Tabel Daftar Invoice -->
    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
        <h3 class="font-bold text-slate-800 text-lg mb-5">Daftar Tagihan & Status Pembayaran</h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="text-xs uppercase bg-slate-100 text-slate-500 font-bold border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3.5">No. Invoice</th>
                        <th class="px-4 py-3.5">Proyek & Klien</th>
                        <th class="px-4 py-3.5">Termin</th>
                        <th class="px-4 py-3.5 text-right">Jumlah Tagihan</th>
                        <th class="px-4 py-3.5">Jatuh Tempo</th>
                        <th class="px-4 py-3.5 text-center">Status</th>
                        <th class="px-4 py-3.5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($invoices)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-10 text-slate-400 font-medium">
                                <i class="fa-solid fa-file-invoice-dollar text-3xl mb-2 text-slate-300 block"></i>
                                Belum ada invoice diterbitkan. Klik "Terbitkan Invoice Baru" untuk membuat penagihan.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($invoices as $inv): ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-4 py-4 font-mono font-bold text-xs text-slate-800"><?= htmlspecialchars($inv['nomor_invoice']) ?></td>
                                <td class="px-4 py-4">
                                    <p class="font-bold text-slate-800 text-xs leading-tight"><?= htmlspecialchars($inv['nama_projek']) ?></p>
                                    <span class="text-[11px] text-slate-400"><?= htmlspecialchars($inv['klien']) ?></span>
                                </td>
                                <td class="px-4 py-4 text-xs font-semibold text-slate-700">
                                    Termin <?= $inv['termin_ke'] ?>
                                    <span class="block text-[10px] text-slate-400 font-normal"><?= htmlspecialchars($inv['keterangan_termin']) ?></span>
                                </td>
                                <td class="px-4 py-4 text-right font-bold text-slate-800">
                                    Rp <?= number_format($inv['jumlah_tagihan'], 0, ',', '.') ?>
                                </td>
                                <td class="px-4 py-4 text-xs text-slate-500">
                                    <?= date('d M Y', strtotime($inv['jatuh_tempo'])) ?>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <form action="update_status.php" method="POST" class="inline-block">
                                        <input type="hidden" name="invoice_id" value="<?= $inv['id'] ?>">
                                        <select name="status" onchange="this.form.submit()" class="px-2.5 py-1 rounded-lg text-xs font-bold border cursor-pointer focus:outline-none
                                            <?php
                                                switch($inv['status']) {
                                                    case 'Paid': echo 'bg-emerald-50 text-emerald-700 border-emerald-300'; break;
                                                    case 'Partially Paid': echo 'bg-blue-50 text-blue-700 border-blue-300'; break;
                                                    default: echo 'bg-amber-50 text-amber-700 border-amber-300';
                                                }
                                            ?>">
                                            <option value="Unpaid" <?= $inv['status'] === 'Unpaid' ? 'selected' : '' ?>>Unpaid</option>
                                            <option value="Partially Paid" <?= $inv['status'] === 'Partially Paid' ? 'selected' : '' ?>>Partially Paid</option>
                                            <option value="Paid" <?= $inv['status'] === 'Paid' ? 'selected' : '' ?>>Paid (Lunas)</option>
                                        </select>
                                    </form>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <a href="print_pdf.php?id=<?= $inv['id'] ?>" target="_blank" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold transition inline-flex items-center gap-1">
                                        <i class="fa-solid fa-print"></i> Cetak PDF
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Modal Pop-up Terbit Invoice Baru -->
<div id="invoiceModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white border border-slate-200 rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden transform transition-all">
        <div class="px-6 py-4 bg-emerald-950 text-white flex justify-between items-center">
            <h3 class="font-bold text-base flex items-center gap-2">
                <i class="fa-solid fa-file-invoice text-emerald-400"></i> Terbitkan Invoice Baru
            </h3>
            <button onclick="closeInvoiceModal()" class="text-emerald-300 hover:text-white transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form action="" method="POST" class="p-6 space-y-4">
            <input type="hidden" name="add_invoice" value="1">

            <div>
                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Nomor Invoice Auto</label>
                <input type="text" name="nomor_invoice" readonly value="<?= $autoNomorInvoice ?>" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-sm font-mono font-bold bg-slate-50 text-emerald-800">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Pilih Proyek Acuan *</label>
                <select name="project_id" id="project_select" onchange="autoFillInvoice()" required class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-sm bg-white focus:ring-2 focus:ring-emerald-500 font-medium">
                    <option value="">-- Pilih Proyek Target --</option>
                    <?php foreach ($projects as $p): ?>
                        <option value="<?= $p['id'] ?>" data-budget="<?= $p['budget'] ?>">
                            <?= htmlspecialchars($p['nama_projek']) ?> (Klien: <?= htmlspecialchars($p['klien']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Termin Ke *</label>
                    <select name="termin_ke" id="termin_select" onchange="calcTermin()" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-sm bg-white font-medium">
                        <option value="1">Termin 1 (DP 50%)</option>
                        <option value="2">Termin 2 (Pelunasan 50%)</option>
                        <option value="3">Full Payment (100%)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Keterangan Termin *</label>
                    <input type="text" name="keterangan_termin" id="keterangan_termin" required value="DP 50% Penandatanganan SPK" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Jumlah Tagihan (Rp) *</label>
                <input type="text" name="jumlah_tagihan" id="jumlah_tagihan" required onkeyup="formatRupiah(this)" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-sm font-bold text-slate-800">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Tanggal Tagihan</label>
                    <input type="date" name="tanggal_tagihan" required value="<?= date('Y-m-d') ?>" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Tanggal Jatuh Tempo</label>
                    <input type="date" name="jatuh_tempo" required value="<?= date('Y-m-d', strtotime('+14 days')) ?>" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Status Awal</label>
                <select name="status" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-sm bg-white font-medium">
                    <option value="Unpaid" selected>Unpaid (Belum Dibayar)</option>
                    <option value="Partially Paid">Partially Paid</option>
                    <option value="Paid">Paid (Lunas)</option>
                </select>
            </div>

            <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                <button type="button" onclick="closeInvoiceModal()" class="px-4 py-2 rounded-xl border text-xs font-semibold text-slate-600 hover:bg-slate-50">Batal</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-md shadow-emerald-600/20">Terbitkan Tagihan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openInvoiceModal() { document.getElementById('invoiceModal').classList.remove('hidden'); }
function closeInvoiceModal() { document.getElementById('invoiceModal').classList.add('hidden'); }

let currentBudget = 0;

function autoFillInvoice() {
    const select = document.getElementById('project_select');
    const selectedOption = select.options[select.selectedIndex];
    
    if (selectedOption.value !== '') {
        currentBudget = parseFloat(selectedOption.getAttribute('data-budget')) || 0;
        calcTermin();
    }
}

function calcTermin() {
    const termin = document.getElementById('termin_select').value;
    const ketInput = document.getElementById('keterangan_termin');
    const tagihanInput = document.getElementById('jumlah_tagihan');
    
    let tagihan = 0;
    if (termin === '1') {
        ketInput.value = 'DP 50% Penandatanganan SPK';
        tagihan = currentBudget * 0.5;
    } else if (termin === '2') {
        ketInput.value = 'Pelunasan 50% BAST Selesai';
        tagihan = currentBudget * 0.5;
    } else {
        ketInput.value = 'Pelunasan 100% Pembayaran Penuh';
        tagihan = currentBudget;
    }
    
    tagihanInput.value = new Intl.NumberFormat('id-ID').format(tagihan);
}

function formatRupiah(el) {
    let value = el.value.replace(/[^0-9]/g, '');
    el.value = value ? new Intl.NumberFormat('id-ID').format(value) : '';
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>