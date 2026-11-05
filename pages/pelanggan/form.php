<?php
$id = (int) ($_GET['id'] ?? 0);
$p = $id ? q('SELECT * FROM pelanggan WHERE id = ?', [$id])->fetch() : null;
if ($id && !$p) redirect('pelanggan');

$data = $p ?: ['nama' => '', 'telepon' => '', 'nama_tim' => '', 'member' => 0];
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['nama', 'telepon', 'nama_tim'] as $f) $data[$f] = trim($_POST[$f] ?? '');
    $data['member'] = isset($_POST['member']) ? 1 : 0;
    if ($data['nama'] === '' || !preg_match('/^[0-9+]{9,15}$/', $data['telepon'])) {
        $error = 'Nama wajib diisi dan nomor WhatsApp 9-15 digit.';
    } elseif (q('SELECT id FROM pelanggan WHERE telepon = ? AND id <> ?', [$data['telepon'], $id])->fetch()) {
        $error = 'Nomor WhatsApp sudah terdaftar.';
    } else {
        $vals = [$data['nama'], $data['telepon'], $data['nama_tim'] ?: null, $data['member']];
        if ($id) q('UPDATE pelanggan SET nama=?, telepon=?, nama_tim=?, member=? WHERE id=?', [...$vals, $id]);
        else q('INSERT INTO pelanggan (nama, telepon, nama_tim, member) VALUES (?,?,?,?)', $vals);
        flash('Data pelanggan disimpan.');
        redirect('pelanggan');
    }
}

$title = $id ? 'Ubah Pelanggan' : 'Tambah Pelanggan';
require __DIR__ . '/../../includes/header.php';
?>
<form method="post" class="card" style="max-width:640px">
    <?= csrf_field() ?>
    <div class="card-body">
        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
        <div class="form-grid">
            <div><label>Nama</label><input type="text" name="nama" value="<?= e($data['nama']) ?>" required></div>
            <div><label>No. WhatsApp</label><input type="text" name="telepon" value="<?= e($data['telepon']) ?>" required></div>
            <div class="full"><label>Nama Tim</label><input type="text" name="nama_tim" value="<?= e($data['nama_tim']) ?>"></div>
            <div class="full"><label class="check"><input type="checkbox" name="member" <?= $data['member'] ? 'checked' : '' ?>> Member tetap</label></div>
        </div>
        <div class="form-actions"><button class="btn btn-primary">Simpan</button><a href="<?= url('pelanggan') ?>" class="btn btn-light">Batal</a></div>
    </div>
</form>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
