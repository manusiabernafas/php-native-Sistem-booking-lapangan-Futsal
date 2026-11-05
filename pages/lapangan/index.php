<?php
$title = 'Lapangan';
$actions = user()['role'] === 'admin' ? '<a href="' . url('lapangan.form') . '" class="btn btn-primary">' . icon('plus', 16) . ' Tambah Lapangan</a>' : '';

$rows = q("SELECT l.*, COUNT(b.id) jml, COALESCE(SUM(b.total),0) omzet FROM lapangan l
           LEFT JOIN booking b ON b.lapangan_id = l.id AND b.status <> 'batal' AND b.tanggal >= CURDATE() - INTERVAL 30 DAY
           GROUP BY l.id ORDER BY l.nama")->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>
<div class="card">
    <div class="table-wrap"><table>
        <thead><tr><th>Lapangan</th><th>Jenis & Ukuran</th><th>Tarif Siang</th><th>Tarif Malam</th><th>30 Hari Terakhir</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $l): ?>
            <tr>
                <td><strong><?= e($l['nama']) ?></strong><small><?= e($l['keterangan']) ?></small></td>
                <td><?= e($l['jenis']) ?><br><small><?= e($l['ukuran']) ?></small></td>
                <td><?= rupiah($l['harga_siang']) ?><small>/jam</small></td>
                <td><?= rupiah($l['harga_malam']) ?><small>/jam</small></td>
                <td><strong><?= (int) $l['jml'] ?> booking</strong><small><?= rupiah($l['omzet']) ?></small></td>
                <td><?= status_badge($l['status']) ?></td>
                <td class="actions"><?php if (user()['role'] === 'admin'): ?><a class="icon-btn" title="Ubah" href="<?= url('lapangan.form', ['id' => $l['id']]) ?>"><?= icon('edit', 16) ?></a><?php endif; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
