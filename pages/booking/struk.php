<?php
$id = (int) ($_GET['id'] ?? 0);
$b = q("SELECT b.*, p.nama, p.telepon, p.nama_tim, l.nama lapangan, l.jenis, l.harga_siang, l.harga_malam, u.nama kasir
        FROM booking b JOIN pelanggan p ON p.id = b.pelanggan_id JOIN lapangan l ON l.id = b.lapangan_id
        LEFT JOIN users u ON u.id = b.user_id WHERE b.id = ?", [$id])->fetch();
if (!$b) redirect('booking');

$set = setting();
$sisa = $b['total'] - $b['dibayar'];
// Pesan konfirmasi siap kirim ke WhatsApp pelanggan
$wa = preg_replace('/^0/', '62', preg_replace('/\D/', '', $b['telepon']));
$pesan = "Halo {$b['nama']}, booking futsal kamu di {$set['nama_usaha']} sudah tercatat:\n"
    . "Kode: {$b['kode']}\nLapangan: {$b['lapangan']}\nJadwal: " . tgl($b['tanggal']) . ', ' . jam($b['jam_mulai']) . '-' . jam($b['jam_selesai']) . "\n"
    . 'Total: ' . rupiah($b['total']) . "\n" . ($sisa > 0 ? 'Sisa bayar: ' . rupiah($sisa) : 'Status: LUNAS') . "\nTerima kasih!";

$title = 'Struk ' . $b['kode'];
$actions = '<a href="https://wa.me/' . $wa . '?text=' . rawurlencode($pesan) . '" target="_blank" class="btn btn-light">Kirim ke WhatsApp</a>'
    . '<button onclick="window.print()" class="btn btn-primary">' . icon('printer', 16) . ' Cetak</button>';
require __DIR__ . '/../../includes/header.php';
?>
<div class="card invoice" style="max-width:520px">
    <div style="text-align:center;margin-bottom:22px">
        <h2 style="margin:0"><?= e($set['nama_usaha']) ?></h2>
        <div class="muted"><?= e($set['alamat']) ?><br><?= e($set['telepon']) ?></div>
    </div>
    <div class="summary">
        <div><span class="muted">Kode Booking</span><strong><?= e($b['kode']) ?></strong></div>
        <div><span class="muted">Pemesan</span><span><?= e($b['nama']) ?><?= $b['nama_tim'] ? ' (' . e($b['nama_tim']) . ')' : '' ?></span></div>
        <div><span class="muted">Lapangan</span><span><?= e($b['lapangan']) ?> · <?= e($b['jenis']) ?></span></div>
        <div><span class="muted">Jadwal</span><span><?= tgl($b['tanggal']) ?>, <?= jam($b['jam_mulai']) ?>–<?= jam($b['jam_selesai']) ?></span></div>
        <?php if ($b['jam_siang']): ?><div><span class="muted"><?= $b['jam_siang'] ?> jam × tarif siang</span><span><?= rupiah($b['jam_siang'] * $b['harga_siang']) ?></span></div><?php endif; ?>
        <?php if ($b['jam_malam']): ?><div><span class="muted"><?= $b['jam_malam'] ?> jam × tarif malam</span><span><?= rupiah($b['jam_malam'] * $b['harga_malam']) ?></span></div><?php endif; ?>
        <div class="total"><span>Total</span><span><?= rupiah($b['total']) ?></span></div>
        <div><span class="muted">Dibayar</span><span><?= rupiah($b['dibayar']) ?></span></div>
        <div><strong><?= $sisa > 0 ? 'Sisa' : 'Status' ?></strong><strong><?= $sisa > 0 ? rupiah($sisa) : 'LUNAS' ?></strong></div>
        <div><span class="muted">Status booking</span><?= status_badge($b['status']) ?></div>
    </div>
    <p class="muted" style="text-align:center;margin-top:24px;font-size:13px">Kasir: <?= e($b['kasir'] ?? '-') ?> · Datang 10 menit sebelum jadwal.<br>Pembatalan maksimal H-1, DP tidak dapat dikembalikan.</p>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
