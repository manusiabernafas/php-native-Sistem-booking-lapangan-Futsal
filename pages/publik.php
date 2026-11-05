<?php
// Halaman jadwal publik (tanpa login) — pelanggan bisa cek slot kosong sebelum menghubungi admin
$set = setting();
$tanggal = $_GET['tanggal'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal) || $tanggal < date('Y-m-d')) $tanggal = date('Y-m-d');
$lapangan = q("SELECT * FROM lapangan WHERE status = 'aktif' ORDER BY nama")->fetchAll();
$terpakai = [];
foreach (q("SELECT lapangan_id, jam_mulai, jam_selesai FROM booking WHERE tanggal = ? AND status <> 'batal'", [$tanggal])->fetchAll() as $b) {
    for ($h = $b['jam_mulai']; $h < $b['jam_selesai']; $h++) $terpakai[$b['lapangan_id']][$h] = true;
}
$wa = preg_replace('/^0/', '62', preg_replace('/\D/', '', $set['telepon']));
$hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'][date('w', strtotime($tanggal))];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Lapangan · <?= e($set['nama_usaha']) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="public-top">
    <span class="brand-mark"><?= icon('goal', 20) ?></span>
    <div class="grow"><strong><?= e($set['nama_usaha']) ?></strong><small><?= e($set['alamat']) ?></small></div>
    <a class="btn btn-primary" href="<?= url('login') ?>">Login Admin</a>
</header>
<div class="public-wrap">
    <div class="price-list">
        <?php foreach ($lapangan as $l): ?>
            <div class="card"><strong><?= e($l['nama']) ?> · <?= e($l['jenis']) ?></strong>
                <span class="muted">Siang <?= rupiah($l['harga_siang']) ?> · Malam <?= rupiah($l['harga_malam']) ?> /jam</span></div>
        <?php endforeach; ?>
    </div>
    <div class="card">
        <div class="card-head">
            <h2>Jadwal <?= $hari ?>, <?= tgl($tanggal) ?></h2>
            <form class="date-nav" method="get">
                <input type="hidden" name="page" value="publik">
                <input type="date" name="tanggal" min="<?= date('Y-m-d') ?>" value="<?= e($tanggal) ?>" onchange="this.form.submit()">
            </form>
        </div>
        <div class="card-body schedule">
            <table>
                <thead><tr><th>Jam</th><?php foreach ($lapangan as $l): ?><th><?= e($l['nama']) ?></th><?php endforeach; ?></tr></thead>
                <tbody>
                <?php for ($h = (int) $set['jam_buka']; $h < (int) $set['jam_tutup']; $h++):
                    $lewat = $tanggal === date('Y-m-d') && $h <= (int) date('G'); ?>
                    <tr><td class="time"><?= jam($h) ?></td>
                        <?php foreach ($lapangan as $l): ?>
                            <?php if (!empty($terpakai[$l['id']][$h])): ?>
                                <td class="slot-booked" style="text-align:center">Terisi</td>
                            <?php elseif ($lewat): ?>
                                <td class="slot-off"></td>
                            <?php else:
                                $text = "Halo, saya mau booking {$l['nama']} tanggal " . tgl($tanggal) . ' jam ' . jam($h) . '. Apakah masih kosong?'; ?>
                                <td class="slot-free"><a target="_blank" href="https://wa.me/<?= $wa ?>?text=<?= rawurlencode($text) ?>">Kosong · Booking via WA</a></td>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tr>
                <?php endfor; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
