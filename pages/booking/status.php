<?php
$id = (int) ($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$id || !in_array($status, ['selesai', 'batal'], true)) redirect('booking');

$b = q("SELECT * FROM booking WHERE id = ? AND status = 'dipesan'", [$id])->fetch();
if ($b) {
    if ($status === 'selesai' && $b['dibayar'] < $b['total']) {
        flash("{$b['kode']} belum lunas. Selesaikan pembayaran dulu.", 'error');
    } else {
        q('UPDATE booking SET status = ? WHERE id = ?', [$status, $id]);
        flash("Booking {$b['kode']} " . ($status === 'batal' ? 'dibatalkan, slot kembali kosong.' : 'ditandai selesai.'));
    }
}
redirect('booking');
