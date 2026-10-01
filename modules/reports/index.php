<?php

require_once __DIR__ . '/../../includes/auth_check.php';

if (file_exists(__DIR__ . '/../../config/database.php')) {
    require_once __DIR__ . '/../../config/database.php';
}

// Handle Export Excel (Diproses sebelum output HTML)
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $project_filter = $_GET['project_id'] ?? 'All';
    $filename = "Laporan_Keuangan_PT_CTC_" . date('Y-m-d_H-i') . ".xls";

    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=$filename");
    header("Pragma: no-cache");
    header("Expires: 0");

    if ($project_filter !== 'All') {
        $stmtP = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
        $stmtP->execute([$project_filter]);
        $proj = $stmtP->fetch();

        $stmtE = $pdo->prepare("SELECT * FROM expenses WHERE project_id = ? ORDER BY tanggal_pengeluaran ASC");
        $stmtE->execute([$project_filter]);
        $expensesList = $stmtE->fetchAll();

        $kategoriGroup = [];
        foreach ($expensesList as $ex) {
            $cat = $ex['kategori'];
            if (!isset($kategoriGroup[$cat])) {
                $kategoriGroup[$cat] = 0;
            }
            $kategoriGroup[$cat] += $ex['jumlah_biaya'];
        }
        ?>
        <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
            <style>
                body { font-family: 'Calibri', Arial, sans-serif; font-size: 11pt; }
                .title-header { font-size: 16pt; font-weight: bold; color: #064e3b; }
                .bg-header { background-color: #047857; color: #ffffff; font-weight: bold; text-align: center; }
                .bg-sub-header { background-color: #065f46; color: #ffffff; font-weight: bold; }
                .border-all { border: 1px solid #cbd5e1; }
                .num-currency { mso-number-format:"Rp\ \#\,\#\#0"; text-align: right; }
                .num-percent { mso-number-format:"0\.0%"; text-align: center; }
            </style>
        </head>
        <body>
            <table>
                <tr><td colspan="6" class="title-header">PT. CIPTA TEKNOLOGI CENDEKIA</td></tr>
                <tr><td colspan="6" style="font-weight: bold; font-size: 12pt; color: #0f172a;">LAPORAN DETAIL FINANCIAL & PROFITABILITAS PROYEK</td></tr>
                <tr><td colspan="6" style="font-size: 9pt; color: #64748b;">Tanggal Ekspor: <?= date('d F Y H:i:s') ?> WIB</td></tr>
            </table>
            <br>
            <table class="border-all" cellpadding="5">
                <tr class="bg-header"><td colspan="6" align="center">I. RINGKASAN EKSEKUTIF PROYEK</td></tr>
                <tr>
                    <td width="180"><b>Nama Proyek:</b></td><td colspan="2"><?= htmlspecialchars($proj['nama_projek']) ?></td>
                    <td width="180"><b>Klien:</b></td><td colspan="2"><?= htmlspecialchars($proj['klien']) ?></td>
                </tr>
                <tr class="bg-sub-header"><td colspan="2" align="center">METRIK KEUANGAN</td><td colspan="4" align="center">NILAI NOMINAL & RATIO</td></tr>
                <tr><td colspan="2">Nilai Kontrak Proyek</td><td colspan="4" class="num-currency"><?= $proj['budget'] ?></td></tr>
                <tr><td colspan="2">Total Realisasi Biaya</td><td colspan="4" class="num-currency">=F24</td></tr>
                <tr><td colspan="2"><b>Net Profit Bersih</b></td><td colspan="4" class="num-currency">=C10-C11</td></tr>
                <tr><td colspan="2">Margin Profitabilitas (%)</td><td colspan="4" class="num-percent">=C12/C10</td></tr>
            </table>
            <br>
            <table class="border-all" cellpadding="5">
                <tr class="bg-header"><td colspan="6" align="center">II. RINCIAN LEDGER TRANSAKSI PENGELUARAN</td></tr>
                <tr class="bg-sub-header">
                    <th>No</th><th>Tanggal</th><th>Ref ID</th><th>Kategori</th><th>Keterangan</th><th>Nominal (Rp)</th>
                </tr>
                <?php $no = 1; $expStartRow = 18; foreach ($expensesList as $ex): ?>
                    <tr>
                        <td align="center"><?= $no ?></td>
                        <td align="center"><?= date('d/m/Y', strtotime($ex['tanggal_pengeluaran'])) ?></td>
                        <td align="center">EXP-<?= sprintf("%04d", $ex['id']) ?></td>
                        <td><?= htmlspecialchars($ex['kategori']) ?></td>
                        <td><?= htmlspecialchars($ex['keterangan'] ?: '-') ?></td>
                        <td class="num-currency"><?= $ex['jumlah_biaya'] ?></td>
                    </tr>
                <?php $no++; endforeach; $lastExpRow = $expStartRow + count($expensesList) - 1; ?>
                <tr style="font-weight: bold; background-color: #cbd5e1;">
                    <td colspan="5" align="right">TOTAL AKUMULASI BIAYA:</td>
                    <td class="num-currency">=SUM(F<?= $expStartRow ?>:F<?= $lastExpRow ?>)</td>
                </tr>
            </table>
        </body>
        </html>
        <?php
    } else {
        $stmtAll = $pdo->query("
            SELECT p.*, COALESCE(SUM(e.jumlah_biaya), 0) AS total_expense
            FROM projects p
            LEFT JOIN expenses e ON p.id = e.project_id
            GROUP BY p.id
            ORDER BY p.created_at DESC
        ");
        $allReports = $stmtAll->fetchAll();
        $totalRows = count($allReports);
        $startRow = 7;
        $endRow = $startRow + $totalRows - 1;
        ?>
        <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
            <style>
                body { font-family: 'Calibri', Arial, sans-serif; font-size: 11pt; }
                .title-header { font-size: 16pt; font-weight: bold; color: #064e3b; }
                .bg-header { background-color: #047857; color: #ffffff; font-weight: bold; text-align: center; }
                .bg-sub-header { background-color: #065f46; color: #ffffff; font-weight: bold; }
                .border-all { border: 1px solid #cbd5e1; }
                .num-currency { mso-number-format:"Rp\ \#\,\#\#0"; text-align: right; }
                .num-percent { mso-number-format:"0\.0%"; text-align: center; }
            </style>
        </head>
        <body>
            <table>
                <tr><td colspan="7" class="title-header">PT. CIPTA TEKNOLOGI CENDEKIA</td></tr>
                <tr><td colspan="7" style="font-weight: bold; font-size: 12pt; color: #0f172a;">LAPORAN KONSOLIDASI KEUANGAN SELURUH PROYEK</td></tr>
            </table>
            <br>
            <table class="border-all" cellpadding="5">
                <tr class="bg-header"><td colspan="7" align="center">RINGKASAN PORTOFOLIO KEUANGAN</td></tr>
                <tr class="bg-sub-header">
                    <th>No</th><th>Nama Proyek</th><th>Klien</th><th>Budget / Revenue (Rp)</th><th>Realisasi Biaya (Rp)</th><th>Net Profit (Rp)</th><th>Margin (%)</th>
                </tr>
                <?php $no = 1; foreach ($allReports as $r): $currentRow = $startRow + $no - 1; ?>
                    <tr>
                        <td align="center"><?= $no ?></td>
                        <td><b><?= htmlspecialchars($r['nama_projek']) ?></b></td>
                        <td><?= htmlspecialchars($r['klien']) ?></td>
                        <td class="num-currency"><?= $r['budget'] ?></td>
                        <td class="num-currency"><?= $r['total_expense'] ?></td>
                        <td class="num-currency">=D<?= $currentRow ?>-E<?= $currentRow ?></td>
                        <td class="num-percent">=F<?= $currentRow ?>/D<?= $currentRow ?></td>
                    </tr>
                <?php $no++; endforeach; ?>
                <tr style="font-weight: bold; background-color: #e2e8f0;">
                    <td colspan="3" align="right">TOTAL KONSOLIDASI PORTOFOLIO:</td>
                    <td class="num-currency">=SUM(D<?= $startRow ?>:D<?= $endRow ?>)</td>
                    <td class="num-currency">=SUM(E<?= $startRow ?>:E<?= $endRow ?>)</td>
                    <td class="num-currency">=SUM(F<?= $startRow ?>:F<?= $endRow ?>)</td>
                    <td class="num-percent">=AVERAGE(G<?= $startRow ?>:G<?= $endRow ?>)</td>
                </tr>
            </table>
        </body>
        </html>
        <?php
    }
    exit;
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

$selected_project = $_GET['project_id'] ?? 'All';
$projectsList = $pdo->query("SELECT id, nama_projek, klien FROM projects ORDER BY nama_projek ASC")->fetchAll();

if ($selected_project !== 'All') {
    $stmt = $pdo->prepare("
        SELECT p.*, COALESCE(SUM(e.jumlah_biaya), 0) AS total_expense, (p.budget - COALESCE(SUM(e.jumlah_biaya), 0)) AS net_profit
        FROM projects p
        LEFT JOIN expenses e ON p.id = e.project_id
        WHERE p.id = ? GROUP BY p.id
    ");
    $stmt->execute([$selected_project]);
    $reports = $stmt->fetchAll();

    $stmtExpDetail = $pdo->prepare("SELECT * FROM expenses WHERE project_id = ? ORDER BY tanggal_pengeluaran DESC");
    $stmtExpDetail->execute([$selected_project]);
    $expensesDetails = $stmtExpDetail->fetchAll();
} else {
    $stmt = $pdo->query("
        SELECT p.*, COALESCE(SUM(e.jumlah_biaya), 0) AS total_expense, (p.budget - COALESCE(SUM(e.jumlah_biaya), 0)) AS net_profit
        FROM projects p
        LEFT JOIN expenses e ON p.id = e.project_id
        GROUP BY p.id ORDER BY p.created_at DESC
    ");
    $reports = $stmt->fetchAll();
    $expensesDetails = [];
}

$grandBudget  = array_sum(array_column($reports, 'budget'));
$grandExpense = array_sum(array_column($reports, 'total_expense'));
$grandProfit  = $grandBudget - $grandExpense;
$grandMargin  = $grandBudget > 0 ? round(($grandProfit / $grandBudget) * 100, 1) : 0;
?>

<!-- Style Khusus Mode Cetak (Print & PDF) -->
<style>
@media print {
    /* Sembunyikan sidebar, header navigasi, dan elemen non-cetak */
    aside, header, nav, .no-print, button, a.bg-emerald-700 {
        display: none !important;
        visibility: hidden !important;
        width: 0 !important;
        height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    body {
        background-color: #ffffff !important;
        color: #000000 !important;
        margin: 0 !important;
        padding: 0 !important;
        font-family: Arial, Helvetica, sans-serif !important;
    }

    main {
        padding: 0 !important;
        margin: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        background: transparent !important;
    }

    /* Tampilkan Kop Surat Resmi hanya saat diprint */
    .print-header {
        display: block !important;
    }

    /* Ubah Stat Cards menjadi tabel ringkasan horizontal formal */
    .screen-stats {
        display: none !important;
    }
    .print-summary-table {
        display: table !important;
        width: 100% !important;
        border-collapse: collapse !important;
        margin-bottom: 20px !important;
    }
    .print-summary-table th, .print-summary-table td {
        border: 1px solid #000 !important;
        padding: 8px !important;
        text-align: center !important;
        font-size: 11px !important;
    }
    .print-summary-table th {
        background-color: #f2f2f2 !important;
        font-weight: bold !important;
    }

    /* Format Tabel Laporan */
    .report-table-container {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
    }

    table {
        width: 100% !important;
        border-collapse: collapse !important;
        font-size: 11px !important;
    }

    th, td {
        border: 1px solid #000000 !important;
        padding: 6px 8px !important;
    }

    th {
        background-color: #f2f2f2 !important;
        color: #000000 !important;
        text-transform: uppercase !important;
        font-weight: bold !important;
    }

    /* Blok Pengesahan Tanda Tangan Cetak */
    .print-signatures {
        display: flex !important;
        justify-content: space-between !important;
        margin-top: 40px !important;
        page-break-inside: avoid !important;
    }
    .signature-box {
        text-align: center !important;
        width: 30% !important;
        font-size: 11px !important;
    }
}

.print-header, .print-summary-table, .print-signatures {
    display: none;
}
</style>

<main class="flex-1 p-8 overflow-y-auto bg-slate-50">
    
    <!-- KOP SURAT FORMAL PT. CTC (Hanya Muncul Saat Diprint/PDF) -->
    <div class="print-header mb-6 pb-4 border-b-2 border-slate-800">
        <div class="flex justify-between items-center mb-2">
            <div>
                <h1 class="text-xl font-bold text-slate-900 uppercase tracking-wide">PT. CIPTA TEKNOLOGI CENDEKIA</h1>
                <p class="text-xs text-slate-600">Enterprise IT Solution, Software Development & Financial Control</p>
                <p class="text-[10px] text-slate-500">Gedung CTC Tower Lt. 8, Jl. Jend. Sudirman, Jakarta | Website: www.ctc.co.id</p>
            </div>
            <div class="text-right text-xs text-slate-600">
                <p><strong>DOKUMEN KEUANGAN RESMI</strong></p>
                <p>Tanggal Cetak: <?= date('d F Y') ?></p>
            </div>
        </div>
        <div class="text-center mt-4">
            <h2 class="text-sm font-bold uppercase underline">
                <?= $selected_project === 'All' ? 'LAPORAN KONSOLIDASI KEUANGAN SELURUH PROYEK' : 'LAPORAN DETAIL KEUANGAN PROYEK' ?>
            </h2>
        </div>
    </div>

    <!-- Top Header Bar (Halaman Web) -->
    <div class="flex flex-col md:flex-row justify-between md:items-center gap-4 mb-8 no-print">
        <div>
            <div class="flex items-center gap-2 text-emerald-700 font-semibold text-xs tracking-wider uppercase mb-1">
                <i class="fa-solid fa-circle text-[8px]"></i> Modul 4
            </div>
            <h2 class="text-2xl font-bold text-slate-800">Laporan Keuangan & Profitabilitas</h2>
            <p class="text-slate-500 text-sm">Analisis detail pendapatan, pengeluaran operasional, dan margin profit PT. CTC</p>
        </div>
        
        <div class="flex flex-wrap items-center gap-2">
            <a href="index.php?project_id=<?= $selected_project ?>&export=excel" class="bg-emerald-700 hover:bg-emerald-800 text-white font-medium px-4 py-2.5 rounded-xl flex items-center gap-2 transition text-sm shadow-md">
                <i class="fa-solid fa-file-excel text-emerald-300"></i> Export Excel (.xls)
            </a>
            <button onclick="window.print()" class="bg-slate-800 hover:bg-slate-900 text-white font-medium px-4 py-2.5 rounded-xl flex items-center gap-2 transition text-sm shadow-md">
                <i class="fa-solid fa-print"></i> Cetak PDF / Print
            </button>
        </div>
    </div>

    <!-- Filter Scope Laporan -->
    <div class="bg-white border border-slate-200 rounded-2xl p-4 mb-6 shadow-sm no-print flex items-center justify-between">
        <form method="GET" class="flex items-center gap-3 w-full">
            <label class="text-xs font-bold text-slate-500 uppercase flex items-center gap-2">
                <i class="fa-solid fa-filter text-emerald-600"></i> Scope Laporan:
            </label>
            <select name="project_id" onchange="this.form.submit()" class="px-4 py-2 rounded-xl border border-slate-200 text-sm font-semibold bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                <option value="All" <?= $selected_project === 'All' ? 'selected' : '' ?>>📊 Seluruh Proyek (Konsolidasi Portfolio)</option>
                <?php foreach ($projectsList as $pl): ?>
                    <option value="<?= $pl['id'] ?>" <?= $selected_project == $pl['id'] ? 'selected' : '' ?>>
                        📂 <?= htmlspecialchars($pl['nama_projek']) ?> (<?= htmlspecialchars($pl['klien']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <!-- TAMPILAN STATS DI LAYAR MONITOR WEB -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8 screen-stats">
        <div class="bg-white border border-slate-200 p-5 rounded-2xl shadow-sm">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-1">Gross Revenue</span>
            <h3 class="text-2xl font-bold text-slate-800">Rp <?= number_format($grandBudget, 0, ',', '.') ?></h3>
            <span class="text-xs text-slate-400">Total nilai kontrak</span>
        </div>

        <div class="bg-white border border-slate-200 p-5 rounded-2xl shadow-sm">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-1">Operational Expense</span>
            <h3 class="text-2xl font-bold text-amber-600">Rp <?= number_format($grandExpense, 0, ',', '.') ?></h3>
            <span class="text-xs text-slate-400">Realisasi pengeluaran</span>
        </div>

        <div class="bg-white border border-slate-200 p-5 rounded-2xl shadow-sm">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-1">Net Profit</span>
            <h3 class="text-2xl font-bold text-emerald-600">Rp <?= number_format($grandProfit, 0, ',', '.') ?></h3>
            <span class="text-xs text-emerald-600 font-medium"><i class="fa-solid fa-arrow-up"></i> Keuntungan Bersih</span>
        </div>

        <div class="bg-white border border-slate-200 p-5 rounded-2xl shadow-sm">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-1">Profit Margin</span>
            <h3 class="text-2xl font-bold text-emerald-700"><?= $grandMargin ?>%</h3>
            <span class="text-xs text-slate-400">Efisiensi keuangan</span>
        </div>
    </div>

    <!-- TAMPILAN TABEL SUMMARY RINGKAS KHUSUS DOKUMEN CETAK / PRINT -->
    <table class="print-summary-table">
        <thead>
            <tr>
                <th>GROSS REVENUE</th>
                <th>OPERATIONAL EXPENSE</th>
                <th>NET PROFIT BERSIH</th>
                <th>PROFIT MARGIN (%)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Rp <?= number_format($grandBudget, 0, ',', '.') ?></strong></td>
                <td><strong>Rp <?= number_format($grandExpense, 0, ',', '.') ?></strong></td>
                <td><strong>Rp <?= number_format($grandProfit, 0, ',', '.') ?></strong></td>
                <td><strong><?= $grandMargin ?>%</strong></td>
            </tr>
        </tbody>
    </table>

    <!-- TABEL UTAMA LAPORAN PROYEK -->
    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm mb-8 report-table-container">
        <h3 class="text-base font-bold text-slate-800 mb-5 no-print">
            <?= $selected_project === 'All' ? 'Laporan Profitabilitas Seluruh Proyek' : 'Ringkasan Keuangan Proyek' ?>
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="text-xs uppercase bg-slate-100 text-slate-500 font-bold border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">Nama Proyek</th>
                        <th class="px-4 py-3">Klien</th>
                        <th class="px-4 py-3 text-right">Budget (Revenue)</th>
                        <th class="px-4 py-3 text-right">Pengeluaran</th>
                        <th class="px-4 py-3 text-right">Profit Bersih</th>
                        <th class="px-4 py-3 text-center">Margin %</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($reports as $r): 
                        $margin = $r['budget'] > 0 ? round(($r['net_profit'] / $r['budget']) * 100, 1) : 0;
                    ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-4 py-4 font-semibold text-slate-800"><?= htmlspecialchars($r['nama_projek']) ?></td>
                            <td class="px-4 py-4 text-slate-500"><?= htmlspecialchars($r['klien']) ?></td>
                            <td class="px-4 py-4 text-right font-medium text-slate-800">Rp <?= number_format($r['budget'], 0, ',', '.') ?></td>
                            <td class="px-4 py-4 text-right font-medium text-amber-600">Rp <?= number_format($r['total_expense'], 0, ',', '.') ?></td>
                            <td class="px-4 py-4 text-right font-bold <?= $r['net_profit'] >= 0 ? 'text-emerald-600' : 'text-red-600' ?>">
                                Rp <?= number_format($r['net_profit'], 0, ',', '.') ?>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold <?= $margin >= 50 ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' ?>">
                                    <?= $margin ?>%
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- RINCIAN EXPENSE SPESIFIK PROYEK -->
    <?php if ($selected_project !== 'All'): ?>
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm report-table-container">
            <h3 class="text-base font-bold text-slate-800 mb-4">Item Pengeluaran Operasional Proyek</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="text-xs uppercase bg-slate-100 text-slate-500 font-bold border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">Kategori</th>
                            <th class="px-4 py-3">Keterangan</th>
                            <th class="px-4 py-3 text-right">Biaya (Rp)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($expensesDetails)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-6 text-slate-400">Belum ada catatan biaya operasional pada proyek ini.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($expensesDetails as $ex): ?>
                                <tr>
                                    <td class="px-4 py-3 text-xs text-slate-500"><?= date('d M Y', strtotime($ex['tanggal_pengeluaran'])) ?></td>
                                    <td class="px-4 py-3 text-xs font-semibold text-slate-700"><?= htmlspecialchars($ex['kategori']) ?></td>
                                    <td class="px-4 py-3 text-xs text-slate-600"><?= htmlspecialchars($ex['keterangan'] ?: '-') ?></td>
                                    <td class="px-4 py-3 text-right font-bold text-slate-800">Rp <?= number_format($ex['jumlah_biaya'], 0, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- BLOK PENGESAHAN TANDA TANGAN (Hanya Muncul Saat Diprint/PDF) -->
    <div class="print-signatures">
        <div class="signature-box">
            <p><strong>Dibuat Oleh:</strong></p>
            <p style="margin-top: 50px;"><u>( Finance Staff )</u></p>
            <p>PT. CTC Finance Dept</p>
        </div>
        <div class="signature-box">
            <p><strong>Diperiksa Oleh:</strong></p>
            <p style="margin-top: 50px;"><u>( Lead PM )</u></p>
            <p>Project Manager</p>
        </div>
        <div class="signature-box">
            <p><strong>Disetujui Oleh:</strong></p>
            <p style="margin-top: 50px;"><u>( Executive VP )</u></p>
            <p>VP Operations</p>
        </div>
    </div>

</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>