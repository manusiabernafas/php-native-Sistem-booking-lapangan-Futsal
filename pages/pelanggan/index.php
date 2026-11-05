<?php
$title = 'Pelanggan & Tim';
$actions = '<a href="' . url('pelanggan.form') . '" class="btn btn-primary">' . icon('plus', 16) . ' Tambah Pelanggan</a>';

$cari = trim($_GET['cari'] ?? '');
$params = [];
$sql = "SELECT p.*, COUNT(b.id) jml, COALESCE(SUM(b.total),0) belanja, MAX(b.tanggal) terakhir
        FROM pelanggan p LEFT JOIN booking b ON b.pelanggan_id = p.id AND b.status <> 'batal'";
if ($cari !== '') {
    $sql .= ' WHERE p.nama LIKE ? OR p.nama_tim LIKE ? OR p.telepon LIKE ?';
    $params = ["%$cari%", "%$cari%", "%$cari%"];
}
$rows = q($sql . ' GROUP BY p.id ORDER BY jml DESC', $params)->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>
<div class="card">
    <div class="card-head">
        <h2><?= count($rows) ?> pelanggan</h2>
        <form class="filters" method="get">
            <input type="hidden" name="page" value="pelanggan">
            <input type="search" name="cari" placeholder="Cari nama, tim, WA..." value="<?= e($cari) ?>">
            <button class="btn btn-light"><?= icon('search', 16) ?></button>
        </form>
    </div>
    <div class="table-wrap"><table>
        <thead><tr><th>Nama</th><th>Tim</th><th>WhatsApp</th><th>Total Booking</th><th>Terakhir Main</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $p): ?>
            <tr>
                <td><strong><?= e($p['nama']) ?></strong><?php if ($p['member']): ?> <span class="badge badge-green">Member</span><?php endif; ?></td>
                <td><?= e($p['nama_tim'] ?: '-') ?></td>
                <td><?= e($p['telepon']) ?></td>
                <td><strong><?= (int) $p['jml'] ?>x</strong><small><?= rupiah($p['belanja']) ?></small></td>
                <td><?= tgl($p['terakhir']) ?></td>
                <td class="actions">
                    <a class="icon-btn" title="Riwayat booking" href="<?= url('booking', ['cari' => $p['telepon']]) ?>"><?= icon('eye', 16) ?></a>
                    <a class="icon-btn" title="Ubah" href="<?= url('pelanggan.form', ['id' => $p['id']]) ?>"><?= icon('edit', 16) ?></a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
