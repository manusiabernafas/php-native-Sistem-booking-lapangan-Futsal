<?php
require_admin();
$id = (int) ($_GET['id'] ?? 0);
$l = $id ? q('SELECT * FROM lapangan WHERE id = ?', [$id])->fetch() : null;
if ($id && !$l) redirect('lapangan');

$data = $l ?: ['nama' => '', 'jenis' => 'Sintetis', 'ukuran' => '', 'harga_siang' => '', 'harga_malam' => '', 'status' => 'aktif', 'keterangan' => ''];
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['nama', 'jenis', 'ukuran', 'harga_siang', 'harga_malam', 'status', 'keterangan'] as $f) $data[$f] = trim($_POST[$f] ?? '');
    if ($data['nama'] === '' || (int) $data['harga_siang'] <= 0 || (int) $data['harga_malam'] <= 0) {
        $error = 'Nama dan kedua tarif wajib diisi.';
    } else {
        $vals = [$data['nama'], $data['jenis'], $data['ukuran'], (int) $data['harga_siang'], (int) $data['harga_malam'], $data['status'], $data['keterangan']];
        if ($id) {
            q('UPDATE lapangan SET nama=?, jenis=?, ukuran=?, harga_siang=?, harga_malam=?, status=?, keterangan=? WHERE id=?', [...$vals, $id]);
        } else {
            q('INSERT INTO lapangan (nama, jenis, ukuran, harga_siang, harga_malam, status, keterangan) VALUES (?,?,?,?,?,?,?)', $vals);
        }
        flash('Data lapangan disimpan.');
        redirect('lapangan');
    }
}

$title = $id ? 'Ubah Lapangan' : 'Tambah Lapangan';
require __DIR__ . '/../../includes/header.php';
?>
<form method="post" class="card" style="max-width:760px">
    <?= csrf_field() ?>
    <div class="card-body">
        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
        <div class="form-grid">
            <div><label>Nama Lapangan</label><input type="text" name="nama" value="<?= e($data['nama']) ?>" required></div>
            <div><label>Jenis</label><select name="jenis"><?php foreach (['Vinyl', 'Sintetis', 'Interlock', 'Parket'] as $o): ?><option <?= $data['jenis'] === $o ? 'selected' : '' ?>><?= $o ?></option><?php endforeach; ?></select></div>
            <div><label>Tarif Siang / Jam (Rp)</label><input type="number" name="harga_siang" value="<?= e($data['harga_siang']) ?>" min="0" step="5000" required></div>
            <div><label>Tarif Malam / Jam (Rp)</label><input type="number" name="harga_malam" value="<?= e($data['harga_malam']) ?>" min="0" step="5000" required></div>
            <div><label>Ukuran</label><input type="text" name="ukuran" value="<?= e($data['ukuran']) ?>" placeholder="25 x 15 m"></div>
            <div><label>Status</label><select name="status"><option value="aktif" <?= $data['status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option><option value="perbaikan" <?= $data['status'] === 'perbaikan' ? 'selected' : '' ?>>Perbaikan (tidak bisa dibooking)</option></select></div>
            <div class="full"><label>Keterangan</label><input type="text" name="keterangan" value="<?= e($data['keterangan']) ?>"></div>
        </div>
        <div class="form-actions"><button class="btn btn-primary">Simpan</button><a href="<?= url('lapangan') ?>" class="btn btn-light">Batal</a></div>
    </div>
</form>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
