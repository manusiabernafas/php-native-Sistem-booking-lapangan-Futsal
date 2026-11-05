<?php
require_admin();

$set = setting();
$pesan = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['aksi'] ?? '') === 'usaha') {
        $buka = min(23, max(0, (int) $_POST['jam_buka']));
        $tutup = min(24, max($buka + 1, (int) $_POST['jam_tutup']));
        q('UPDATE pengaturan SET nama_usaha=?, alamat=?, telepon=?, jam_buka=?, jam_tutup=?, jam_malam=?, min_dp_persen=? WHERE id=1', [
            trim($_POST['nama_usaha']), trim($_POST['alamat']), trim($_POST['telepon']),
            $buka, $tutup, min($tutup, max($buka, (int) $_POST['jam_malam'])), min(100, max(0, (int) $_POST['min_dp_persen'])),
        ]);
        flash('Pengaturan usaha disimpan.');
        redirect('pengaturan');
    }
    if (($_POST['aksi'] ?? '') === 'user') {
        $username = trim($_POST['username']);
        if ($username === '' || strlen($_POST['password']) < 6) {
            flash('Username wajib diisi dan password minimal 6 karakter.', 'error');
        } elseif (q('SELECT id FROM users WHERE username = ?', [$username])->fetch()) {
            flash('Username sudah dipakai.', 'error');
        } else {
            q('INSERT INTO users (nama, username, password, role) VALUES (?,?,?,?)', [
                trim($_POST['nama']), $username, password_hash($_POST['password'], PASSWORD_DEFAULT),
                $_POST['role'] === 'admin' ? 'admin' : 'kasir',
            ]);
            flash('Pengguna baru ditambahkan.');
        }
        redirect('pengaturan');
    }
    if (($_POST['aksi'] ?? '') === 'password') {
        if (strlen($_POST['password']) < 6) {
            flash('Password minimal 6 karakter.', 'error');
        } else {
            q('UPDATE users SET password = ? WHERE id = ?', [password_hash($_POST['password'], PASSWORD_DEFAULT), user()['id']]);
            flash('Password kamu sudah diganti.');
        }
        redirect('pengaturan');
    }
}

$users = q('SELECT id, nama, username, role, created_at FROM users ORDER BY id')->fetchAll();
$title = 'Pengaturan';
require __DIR__ . '/../includes/header.php';
?>
<div class="grid-2">
    <form method="post" class="card">
        <?= csrf_field() ?><input type="hidden" name="aksi" value="usaha">
        <div class="card-head"><h2>Profil Usaha & Jam Operasional</h2></div>
        <div class="card-body">
            <div class="form-grid">
                <div class="full"><label>Nama Usaha</label><input type="text" name="nama_usaha" value="<?= e($set['nama_usaha']) ?>" required></div>
                <div class="full"><label>Alamat</label><input type="text" name="alamat" value="<?= e($set['alamat']) ?>"></div>
                <div><label>Telepon</label><input type="text" name="telepon" value="<?= e($set['telepon']) ?>"></div>
                <div><label>Minimal DP (%)</label><input type="number" name="min_dp_persen" value="<?= (int) $set['min_dp_persen'] ?>" min="0" max="100"></div>
                <div><label>Jam Buka</label><input type="number" name="jam_buka" value="<?= (int) $set['jam_buka'] ?>" min="0" max="23"></div>
                <div><label>Jam Tutup</label><input type="number" name="jam_tutup" value="<?= (int) $set['jam_tutup'] ?>" min="1" max="24"></div>
                <div class="full"><label>Tarif Malam Mulai Jam</label>
                    <input type="number" name="jam_malam" value="<?= (int) $set['jam_malam'] ?>" min="0" max="24">
                    <small class="muted">Contoh: 17 berarti jam main mulai 17.00 ke atas memakai tarif malam.</small>
                </div>
            </div>
            <div class="form-actions"><button class="btn btn-primary">Simpan</button></div>
        </div>
    </form>

    <form method="post" class="card" style="align-self:start">
        <?= csrf_field() ?><input type="hidden" name="aksi" value="password">
        <div class="card-head"><h2>Ganti Password Saya</h2></div>
        <div class="card-body">
            <label>Password Baru</label><input type="password" name="password" minlength="6" required>
            <div class="form-actions"><button class="btn btn-primary">Ganti Password</button></div>
        </div>
    </form>
</div>

<div class="card mt">
    <div class="card-head"><h2>Pengguna Sistem</h2></div>
    <div class="table-wrap"><table>
        <thead><tr><th>Nama</th><th>Username</th><th>Role</th><th>Dibuat</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr><td><strong><?= e($u['nama']) ?></strong></td><td><?= e($u['username']) ?></td>
                <td><span class="badge badge-<?= $u['role'] === 'admin' ? 'blue' : 'gray' ?>"><?= e(ucfirst($u['role'])) ?></span></td>
                <td><?= tgl($u['created_at']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <form method="post" class="card-body" style="border-top:1px solid var(--border)">
        <?= csrf_field() ?><input type="hidden" name="aksi" value="user">
        <div class="form-grid" style="grid-template-columns:repeat(4,1fr)">
            <div><label>Nama</label><input type="text" name="nama" required></div>
            <div><label>Username</label><input type="text" name="username" required></div>
            <div><label>Password</label><input type="password" name="password" minlength="6" required></div>
            <div><label>Role</label><select name="role"><option value="kasir">Kasir</option><option value="admin">Admin</option></select></div>
        </div>
        <div class="form-actions"><button class="btn btn-primary"><?= icon('plus', 16) ?> Tambah Pengguna</button></div>
    </form>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
