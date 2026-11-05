<?php
$dari = $_GET['dari'] ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');

$rows = q("SELECT b.*, p.nama, p.nama_tim, l.nama lapangan FROM booking b
           JOIN pelanggan p ON p.id = b.pelanggan_id JOIN lapangan l ON l.id = b.lapangan_id
           WHERE b.status <> 'batal' AND b.tanggal BETWEEN ? AND ? ORDER BY b.tanggal, b.jam_mulai", [$dari, $sampai])->fetchAll();

if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="laporan-futsal-' . $dari . '-sd-' . $sampai . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Kode', 'Tanggal', 'Jam', 'Lapangan', 'Pemesan', 'Tim', 'Jam Siang', 'Jam Malam', 'Total', 'Dibayar', 'Status'], ';', '"', '');
    foreach ($rows as $r) {
        fputcsv($out, [$r['kode'], $r['tanggal'], jam($r['jam_mulai']) . '-' . jam($r['jam_selesai']), $r['lapangan'], $r['nama'], $r['nama_tim'],
            $r['jam_siang'], $r['jam_malam'], $r['total'], $r['dibayar'], $r['status']], ';', '"', '');
    }
    exit;
}

$omzet = array_sum(array_column($rows, 'total'));
$diterima = array_sum(array_column($rows, 'dibayar'));
$totalJam = array_sum(array_map(fn ($r) => $r['jam_selesai'] - $r['jam_mulai'], $rows));
$batal = (int) q("SELECT COUNT(*) FROM booking WHERE status = 'batal' AND tanggal BETWEEN ? AND ?", [$dari, $sampai])->fetchColumn();

$perLapangan = q("SELECT l.nama, l.jenis, COUNT(b.id) n, SUM(b.jam_selesai - b.jam_mulai) jam, SUM(b.jam_malam) malam, SUM(b.total) t
                  FROM booking b JOIN lapangan l ON l.id = b.lapangan_id
                  WHERE b.status <> 'batal' AND b.tanggal BETWEEN ? AND ? GROUP BY l.id ORDER BY t DESC", [$dari, $sampai])->fetchAll();
$hari = q("SELECT DAYOFWEEK(tanggal) d, COUNT(*) n, SUM(total) t FROM booking WHERE status <> 'batal' AND tanggal BETWEEN ? AND ?
           GROUP BY d ORDER BY d", [$dari, $sampai])->fetchAll();
$namaHari = [1 => 'Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$topTim = q("SELECT COALESCE(p.nama_tim, p.nama) tim, COUNT(*) n, SUM(b.total) t FROM booking b JOIN pelanggan p ON p.id = b.pelanggan_id
             WHERE b.status <> 'batal' AND b.tanggal BETWEEN ? AND ? GROUP BY p.id ORDER BY n DESC LIMIT 5", [$dari, $sampai])->fetchAll();

$title = 'Laporan';
$actions = '<a class="btn btn-light" href="' . url('laporan', ['dari' => $dari, 'sampai' => $sampai, 'export' => 'csv']) . '">Ekspor CSV</a>'
    . '<button onclick="window.print()" class="btn btn-primary">' . icon('printer', 16) . ' Cetak</button>';
require __DIR__ . '/../includes/header.php';
?>
<form class="card no-print" method="get" style="margin-bottom:20px">
    <div class="card-body filters" style="align-items:end">
        <input type="hidden" name="page" value="laporan">
        <div><label>Dari</label><input type="date" name="dari" value="<?= e($dari) ?>"></div>
        <div><label>Sampai</label><input type="date" name="sampai" value="<?= e($sampai) ?>"></div>
        <button class="btn btn-primary">Tampilkan</button>
    </div>
</form>

<div class="stats">
    <div class="card stat"><div class="stat-icon green"><?= icon('wallet', 22) ?></div><div><small>Omzet</small><strong><?= rupiah($omzet) ?></strong></div></div>
    <div class="card stat"><div class="stat-icon blue"><?= icon('calendar', 22) ?></div><div><small>Booking</small><strong><?= count($rows) ?></strong> <span class="muted"><?= $batal ?> batal</span></div></div>
    <div class="card stat"><div class="stat-icon amber"><?= icon('clock', 22) ?></div><div><small>Total Jam Main</small><strong><?= $totalJam ?> jam</strong></div></div>
    <div class="card stat"><div class="stat-icon red"><?= icon('alert', 22) ?></div><div><small>Belum Diterima</small><strong><?= rupiah($omzet - $diterima) ?></strong></div></div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-head"><h2>Pendapatan per Lapangan</h2><span class="muted"><?= tgl($dari) ?> – <?= tgl($sampai) ?></span></div>
        <div class="table-wrap"><table>
            <thead><tr><th>Lapangan</th><th>Booking</th><th>Jam Main</th><th>Jam Malam</th><th class="text-right">Omzet</th></tr></thead>
            <tbody>
            <?php foreach ($perLapangan as $l): ?>
                <tr><td><strong><?= e($l['nama']) ?></strong><small><?= e($l['jenis']) ?></small></td><td><?= $l['n'] ?></td><td><?= $l['jam'] ?> jam</td>
                    <td><?= $l['malam'] ?> jam</td><td class="text-right"><strong><?= rupiah($l['t']) ?></strong></td></tr>
            <?php endforeach; ?>
            <?php if (!$perLapangan): ?><tr><td colspan="5" class="empty">Tidak ada data.</td></tr><?php endif; ?>
            </tbody>
        </table></div>
    </div>
    <div class="card">
        <div class="card-head"><h2>Tim Paling Sering Main</h2></div>
        <div class="card-body summary">
            <?php foreach ($topTim as $i => $t): ?>
                <div><span><?= $i + 1 ?>. <?= e($t['tim']) ?></span><strong><?= $t['n'] ?>x</strong></div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="card mt">
    <div class="card-head"><h2>Ramai per Hari</h2></div>
    <div class="table-wrap"><table>
        <thead><tr><th>Hari</th><th>Booking</th><th class="text-right">Omzet</th></tr></thead>
        <tbody>
        <?php foreach ($hari as $h): ?>
            <tr><td><?= $namaHari[$h['d']] ?></td><td><?= $h['n'] ?></td><td class="text-right"><?= rupiah($h['t']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
