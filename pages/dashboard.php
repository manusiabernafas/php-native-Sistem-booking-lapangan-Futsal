<?php
$title = 'Dashboard';
$actions = '<a href="' . url('booking.baru') . '" class="btn btn-primary">' . icon('plus', 16) . ' Booking Baru</a>';
$set = setting();
$hariIni = date('Y-m-d');

$bookingHariIni = q("SELECT b.*, p.nama, p.nama_tim, l.nama lapangan FROM booking b
                     JOIN pelanggan p ON p.id = b.pelanggan_id JOIN lapangan l ON l.id = b.lapangan_id
                     WHERE b.tanggal = ? AND b.status <> 'batal' ORDER BY b.jam_mulai, l.nama", [$hariIni])->fetchAll();
$jamTerisi = array_sum(array_map(fn ($b) => $b['jam_selesai'] - $b['jam_mulai'], $bookingHariIni));
$lapAktif = (int) q("SELECT COUNT(*) FROM lapangan WHERE status = 'aktif'")->fetchColumn();
$okupansi = round($jamTerisi / max(1, $lapAktif * ($set['jam_tutup'] - $set['jam_buka'])) * 100);
$omzetBulan = (int) q("SELECT COALESCE(SUM(total),0) FROM booking WHERE status <> 'batal' AND DATE_FORMAT(tanggal,'%Y-%m') = DATE_FORMAT(CURDATE(),'%Y-%m') AND tanggal <= CURDATE()")->fetchColumn();
$piutang = (int) q("SELECT COALESCE(SUM(total - dibayar),0) FROM booking WHERE status = 'dipesan'")->fetchColumn();

// Pendapatan 14 hari terakhir
$grafik = [];
for ($i = 13; $i >= 0; $i--) $grafik[date('Y-m-d', strtotime("-$i day"))] = 0;
foreach (q("SELECT tanggal, SUM(total) t FROM booking WHERE status <> 'batal' AND tanggal BETWEEN ? AND ? GROUP BY tanggal",
    [array_key_first($grafik), $hariIni])->fetchAll() as $r) $grafik[$r['tanggal']] = (int) $r['t'];
$max = max(1, max($grafik));

// Jam paling ramai (30 hari)
$jamRamai = q("SELECT jam_mulai, COUNT(*) n FROM booking WHERE status <> 'batal' AND tanggal >= CURDATE() - INTERVAL 30 DAY
               GROUP BY jam_mulai ORDER BY n DESC LIMIT 5")->fetchAll();
$maxJam = max(1, $jamRamai[0]['n'] ?? 1);

require __DIR__ . '/../includes/header.php';
?>
<div class="stats">
    <div class="card stat"><div class="stat-icon green"><?= icon('calendar', 22) ?></div><div><small>Booking Hari Ini</small><strong><?= count($bookingHariIni) ?></strong></div></div>
    <div class="card stat"><div class="stat-icon blue"><?= icon('clock', 22) ?></div><div><small>Okupansi Hari Ini</small><strong><?= $okupansi ?>%</strong> <span class="muted"><?= $jamTerisi ?> jam</span></div></div>
    <div class="card stat"><div class="stat-icon amber"><?= icon('wallet', 22) ?></div><div><small>Omzet Bulan Ini</small><strong><?= rupiah($omzetBulan) ?></strong></div></div>
    <div class="card stat"><div class="stat-icon red"><?= icon('alert', 22) ?></div><div><small>Belum Dibayar</small><strong><?= rupiah($piutang) ?></strong></div></div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-head"><h2>Omzet 14 Hari Terakhir</h2></div>
        <div class="card-body">
            <div class="bars">
                <?php foreach ($grafik as $d => $v): ?>
                    <div class="bar-col" title="<?= tgl($d) ?>: <?= rupiah($v) ?>">
                        <small><?= $v ? round($v / 1000) . 'rb' : '0' ?></small>
                        <div class="bar" style="height: <?= round($v / $max * 100) ?>%"></div>
                        <small><?= date('d/m', strtotime($d)) ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-head"><h2>Jam Paling Ramai</h2><small class="muted">30 hari</small></div>
        <div class="card-body summary">
            <?php foreach ($jamRamai as $j): ?>
                <div style="align-items:center;gap:12px">
                    <span style="width:52px;font-weight:600"><?= jam($j['jam_mulai']) ?></span>
                    <span style="flex:1;height:10px;background:var(--gray-soft);border-radius:6px;overflow:hidden"><span style="display:block;height:100%;width:<?= round($j['n'] / $maxJam * 100) ?>%;background:var(--primary)"></span></span>
                    <span class="muted" style="width:96px;text-align:right;white-space:nowrap"><?= $j['n'] ?> booking</span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="card mt">
    <div class="card-head"><h2>Jadwal Hari Ini</h2><a href="<?= url('jadwal') ?>">Lihat grid jadwal</a></div>
    <div class="table-wrap"><table>
        <thead><tr><th>Jam</th><th>Lapangan</th><th>Tim / Pemesan</th><th>Pembayaran</th><th>Kode</th></tr></thead>
        <tbody>
        <?php foreach ($bookingHariIni as $b): $sisa = $b['total'] - $b['dibayar']; ?>
            <tr>
                <td><strong><?= jam($b['jam_mulai']) ?>–<?= jam($b['jam_selesai']) ?></strong></td>
                <td><?= e($b['lapangan']) ?></td>
                <td><strong><?= e($b['nama_tim'] ?: $b['nama']) ?></strong><small><?= e($b['nama']) ?></small></td>
                <td><?= status_badge($sisa <= 0 ? 'lunas' : ($b['dibayar'] > 0 ? 'dp' : 'belum')) ?> <?php if ($sisa > 0): ?><small>sisa <?= rupiah($sisa) ?></small><?php endif; ?></td>
                <td><a href="<?= url('booking.struk', ['id' => $b['id']]) ?>"><?= e($b['kode']) ?></a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$bookingHariIni): ?><tr><td colspan="5" class="empty">Belum ada booking hari ini.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
