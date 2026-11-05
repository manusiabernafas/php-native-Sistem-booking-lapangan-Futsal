<?php
$tanggal = $_GET['tanggal'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) $tanggal = date('Y-m-d');

$set = setting();
$lapangan = q('SELECT * FROM lapangan ORDER BY nama')->fetchAll();
$rows = q("SELECT b.*, p.nama, p.nama_tim FROM booking b JOIN pelanggan p ON p.id = b.pelanggan_id
           WHERE b.tanggal = ? AND b.status <> 'batal'", [$tanggal])->fetchAll();

// Peta [lapangan_id][jam] => booking (hanya di jam mulai), dan sel yang "tertutup" rowspan
$peta = [];
$tertutup = [];
foreach ($rows as $b) {
    $peta[$b['lapangan_id']][$b['jam_mulai']] = $b;
    for ($h = $b['jam_mulai'] + 1; $h < $b['jam_selesai']; $h++) $tertutup[$b['lapangan_id']][$h] = true;
}
$terisi = array_sum(array_map(fn ($b) => $b['jam_selesai'] - $b['jam_mulai'], $rows));
$aktif = count(array_filter($lapangan, fn ($l) => $l['status'] === 'aktif'));
$kapasitas = max(1, $aktif * ($set['jam_tutup'] - $set['jam_buka']));
$lalu = $tanggal < date('Y-m-d');

$hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'][date('w', strtotime($tanggal))];
$title = 'Jadwal Lapangan';
$actions = '<a href="' . url('booking.baru', ['tanggal' => $tanggal]) . '" class="btn btn-primary">' . icon('plus', 16) . ' Booking Baru</a>';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="card-head">
        <div>
            <h2><?= $hari ?>, <?= tgl($tanggal) ?></h2>
            <small class="muted"><?= count($rows) ?> booking · <?= $terisi ?> dari <?= $kapasitas ?> jam terisi (<?= round($terisi / $kapasitas * 100) ?>% okupansi)</small>
        </div>
        <form class="date-nav" method="get">
            <input type="hidden" name="page" value="jadwal">
            <a class="btn btn-light" href="<?= url('jadwal', ['tanggal' => date('Y-m-d', strtotime("$tanggal -1 day"))]) ?>">&lsaquo;</a>
            <input type="date" name="tanggal" value="<?= e($tanggal) ?>" onchange="this.form.submit()">
            <a class="btn btn-light" href="<?= url('jadwal', ['tanggal' => date('Y-m-d', strtotime("$tanggal +1 day"))]) ?>">&rsaquo;</a>
            <a class="btn btn-light" href="<?= url('jadwal') ?>">Hari ini</a>
        </form>
    </div>
    <div class="card-body">
        <div class="legend" style="margin-bottom:14px">
            <span style="--c:var(--primary)">Lunas</span>
            <span style="--c:#b45309">DP</span>
            <span style="--c:#b91c1c">Belum bayar</span>
            <span style="--c:#4b5563">Selesai main</span>
            <span style="--c:#fbfbf6;outline:1px solid var(--border)">Kosong (tarif malam)</span>
        </div>
        <div class="schedule">
            <table>
                <thead><tr><th>Jam</th>
                    <?php foreach ($lapangan as $l): ?>
                        <th><?= e($l['nama']) ?><br><small class="muted" style="text-transform:none;letter-spacing:0;font-weight:500"><?= e($l['jenis']) ?></small></th>
                    <?php endforeach; ?>
                </tr></thead>
                <tbody>
                <?php for ($h = (int) $set['jam_buka']; $h < (int) $set['jam_tutup']; $h++): ?>
                    <tr>
                        <td class="time"><?= jam($h) ?></td>
                        <?php foreach ($lapangan as $l):
                            if (!empty($tertutup[$l['id']][$h])) continue;
                            $b = $peta[$l['id']][$h] ?? null;
                            if ($b):
                                $durasi = $b['jam_selesai'] - $b['jam_mulai'];
                                $kelas = $b['status'] === 'selesai' ? 'selesai' : ($b['dibayar'] >= $b['total'] ? '' : ($b['dibayar'] > 0 ? 'dp' : 'belum')); ?>
                                <td rowspan="<?= $durasi ?>" class="slot-booked <?= $kelas ?>">
                                    <a href="<?= url('booking', ['cari' => $b['kode']]) ?>">
                                        <strong><?= e($b['nama_tim'] ?: $b['nama']) ?></strong>
                                        <small><?= jam($b['jam_mulai']) ?>–<?= jam($b['jam_selesai']) ?> · <?= e($b['kode']) ?></small>
                                    </a>
                                </td>
                            <?php elseif ($l['status'] !== 'aktif'): ?>
                                <td class="slot-off">Perbaikan</td>
                            <?php elseif ($lalu): ?>
                                <td class="slot-off"></td>
                            <?php else: ?>
                                <td class="slot-free <?= $h >= $set['jam_malam'] ? 'night' : '' ?>">
                                    <a href="<?= url('booking.baru', ['lapangan' => $l['id'], 'tanggal' => $tanggal, 'mulai' => $h]) ?>">+ Booking</a>
                                </td>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tr>
                <?php endfor; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
