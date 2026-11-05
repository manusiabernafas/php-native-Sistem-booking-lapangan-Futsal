<?php
$title = 'Data Booking';
$actions = '<a href="' . url('booking.baru') . '" class="btn btn-primary">' . icon('plus', 16) . ' Booking Baru</a>';

$cari = trim($_GET['cari'] ?? '');
$status = $_GET['status'] ?? '';
$bayar = $_GET['bayar'] ?? '';
$dari = $_GET['dari'] ?? date('Y-m-d', strtotime('-7 days'));
$sampai = $_GET['sampai'] ?? date('Y-m-d', strtotime('+14 days'));

$where = ['b.tanggal BETWEEN ? AND ?'];
$params = [$dari, $sampai];
if ($cari !== '') {
    $where = ['(b.kode LIKE ? OR p.nama LIKE ? OR p.nama_tim LIKE ? OR p.telepon LIKE ?)'];
    $params = array_fill(0, 4, "%$cari%");
}
if (in_array($status, ['dipesan', 'selesai', 'batal'], true)) {
    $where[] = 'b.status = ?';
    $params[] = $status;
}
if ($bayar === 'belum') $where[] = "b.dibayar < b.total AND b.status <> 'batal'";

$rows = q('SELECT b.*, p.nama, p.telepon, p.nama_tim, l.nama lapangan FROM booking b
           JOIN pelanggan p ON p.id = b.pelanggan_id JOIN lapangan l ON l.id = b.lapangan_id
           WHERE ' . implode(' AND ', $where) . ' ORDER BY b.tanggal DESC, b.jam_mulai DESC LIMIT 300', $params)->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>
<div class="card">
    <div class="card-head">
        <h2><?= count($rows) ?> booking</h2>
        <form class="filters" method="get">
            <input type="hidden" name="page" value="booking">
            <input type="search" name="cari" placeholder="Kode, nama, tim, WA..." value="<?= e($cari) ?>">
            <input type="date" name="dari" value="<?= e($dari) ?>">
            <input type="date" name="sampai" value="<?= e($sampai) ?>">
            <select name="status">
                <option value="">Semua status</option>
                <?php foreach (['dipesan', 'selesai', 'batal'] as $s): ?><option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
            </select>
            <select name="bayar"><option value="">Semua pembayaran</option><option value="belum" <?= $bayar === 'belum' ? 'selected' : '' ?>>Belum lunas</option></select>
            <button class="btn btn-light"><?= icon('search', 16) ?></button>
        </form>
    </div>
    <div class="table-wrap"><table>
        <thead><tr><th>Kode</th><th>Pelanggan</th><th>Lapangan</th><th>Jadwal</th><th>Total</th><th>Pembayaran</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $b):
            $sisa = $b['total'] - $b['dibayar'];
            $bayarStatus = $sisa <= 0 ? 'lunas' : ($b['dibayar'] > 0 ? 'dp' : 'belum'); ?>
            <tr>
                <td><strong><?= e($b['kode']) ?></strong></td>
                <td><strong><?= e($b['nama_tim'] ?: $b['nama']) ?></strong><small><?= e($b['nama']) ?> · <?= e($b['telepon']) ?></small></td>
                <td><?= e($b['lapangan']) ?></td>
                <td><?= tgl($b['tanggal']) ?><br><small><?= jam($b['jam_mulai']) ?>–<?= jam($b['jam_selesai']) ?></small></td>
                <td><strong><?= rupiah($b['total']) ?></strong><?php if ($sisa > 0 && $b['status'] !== 'batal'): ?><small>Sisa <?= rupiah($sisa) ?></small><?php endif; ?></td>
                <td><?= $b['status'] === 'batal' ? '-' : status_badge($bayarStatus) ?></td>
                <td><?= status_badge($b['status']) ?></td>
                <td>
                    <div class="actions">
                        <?php if ($b['status'] === 'dipesan' && $sisa > 0): ?>
                            <a class="btn btn-light btn-sm" href="<?= url('booking.bayar', ['id' => $b['id']]) ?>"><?= icon('wallet', 14) ?> Bayar</a>
                        <?php endif; ?>
                        <?php if ($b['status'] === 'dipesan'): ?>
                            <form method="post" action="<?= url('booking.status') ?>">
                                <?= csrf_field() ?><input type="hidden" name="id" value="<?= $b['id'] ?>"><input type="hidden" name="status" value="selesai">
                                <button class="icon-btn" title="Tandai selesai main"><?= icon('check', 16) ?></button>
                            </form>
                            <form method="post" action="<?= url('booking.status') ?>" data-confirm="Batalkan booking <?= e($b['kode']) ?>?">
                                <?= csrf_field() ?><input type="hidden" name="id" value="<?= $b['id'] ?>"><input type="hidden" name="status" value="batal">
                                <button class="icon-btn danger" title="Batalkan"><?= icon('x', 16) ?></button>
                            </form>
                        <?php endif; ?>
                        <a class="icon-btn" title="Struk" href="<?= url('booking.struk', ['id' => $b['id']]) ?>"><?= icon('printer', 16) ?></a>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="8" class="empty">Tidak ada booking pada filter ini.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
