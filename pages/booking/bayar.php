<?php
$id = (int) ($_GET['id'] ?? 0);
$b = q("SELECT b.*, p.nama, p.nama_tim, l.nama lapangan FROM booking b JOIN pelanggan p ON p.id = b.pelanggan_id
        JOIN lapangan l ON l.id = b.lapangan_id WHERE b.id = ? AND b.status = 'dipesan'", [$id])->fetch();
if (!$b) redirect('booking');
$sisa = $b['total'] - $b['dibayar'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bayar = max(0, (int) ($_POST['bayar'] ?? 0));
    if ($bayar <= 0) {
        flash('Nominal pembayaran harus lebih dari 0.', 'error');
        redirect('booking.bayar', ['id' => $id]);
    }
    q('UPDATE booking SET dibayar = LEAST(total, dibayar + ?) WHERE id = ?', [$bayar, $id]);
    flash('Pembayaran ' . rupiah(min($bayar, $sisa)) . " untuk {$b['kode']} tercatat.");
    redirect('booking.struk', ['id' => $id]);
}

$title = 'Pembayaran ' . $b['kode'];
require __DIR__ . '/../../includes/header.php';
?>
<form method="post" class="card" style="max-width:520px">
    <?= csrf_field() ?>
    <div class="card-head"><h2><?= e($b['nama_tim'] ?: $b['nama']) ?></h2><span class="muted"><?= e($b['lapangan']) ?> · <?= tgl($b['tanggal']) ?> <?= jam($b['jam_mulai']) ?>–<?= jam($b['jam_selesai']) ?></span></div>
    <div class="card-body">
        <div class="summary">
            <div><span class="muted">Total</span><span><?= rupiah($b['total']) ?></span></div>
            <div><span class="muted">Sudah dibayar</span><span><?= rupiah($b['dibayar']) ?></span></div>
            <div class="total"><span>Sisa</span><span><?= rupiah($sisa) ?></span></div>
        </div>
        <div class="mt"><label>Bayar Sekarang</label><input type="number" name="bayar" min="1000" step="1000" value="<?= $sisa ?>" required></div>
        <div class="form-actions"><button class="btn btn-primary"><?= icon('wallet', 16) ?> Simpan Pembayaran</button><a class="btn btn-light" href="<?= url('booking') ?>">Batal</a></div>
    </div>
</form>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
