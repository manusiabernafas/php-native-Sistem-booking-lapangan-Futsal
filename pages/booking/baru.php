<?php
$set = setting();
$lapanganList = q("SELECT * FROM lapangan WHERE status = 'aktif' ORDER BY nama")->fetchAll();
$pelangganList = q('SELECT id, nama, telepon, nama_tim FROM pelanggan ORDER BY nama')->fetchAll();

$data = [
    'lapangan_id' => $_GET['lapangan'] ?? ($lapanganList[0]['id'] ?? ''),
    'tanggal' => $_GET['tanggal'] ?? date('Y-m-d'),
    'jam_mulai' => (int) ($_GET['mulai'] ?? max((int) $set['jam_buka'], (int) date('G') + 1)),
    'durasi' => 2,
    'pelanggan_id' => '', 'nama_baru' => '', 'telepon_baru' => '', 'tim_baru' => '',
    'dibayar' => '', 'catatan' => '',
];
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($data as $k => $_) $data[$k] = trim((string) ($_POST[$k] ?? ''));
    $mulai = (int) $data['jam_mulai'];
    $selesai = $mulai + (int) $data['durasi'];

    try {
        db()->beginTransaction();
        $lap = q("SELECT * FROM lapangan WHERE id = ? AND status = 'aktif' FOR UPDATE", [(int) $data['lapangan_id']])->fetch();

        if (!$lap) throw new RuntimeException('Pilih lapangan yang aktif.');
        if ($data['tanggal'] < date('Y-m-d')) throw new RuntimeException('Tanggal booking tidak boleh di masa lalu.');
        if ($mulai < $set['jam_buka'] || $selesai > $set['jam_tutup'] || $selesai <= $mulai) {
            throw new RuntimeException('Jam main harus di antara ' . jam($set['jam_buka']) . ' dan ' . jam($set['jam_tutup']) . '.');
        }
        if ($b = slot_bentrok($lap['id'], $data['tanggal'], $mulai, $selesai)) {
            throw new RuntimeException("Jadwal bentrok dengan {$b['kode']} ({$b['nama']}, " . jam($b['jam_mulai']) . '–' . jam($b['jam_selesai']) . ').');
        }

        // Pelanggan lama atau daftar baru langsung dari form
        $pelangganId = (int) $data['pelanggan_id'];
        if (!$pelangganId) {
            if ($data['nama_baru'] === '' || $data['telepon_baru'] === '') throw new RuntimeException('Pilih pelanggan atau isi nama & telepon pelanggan baru.');
            $ada = q('SELECT id FROM pelanggan WHERE telepon = ?', [$data['telepon_baru']])->fetchColumn();
            if ($ada) {
                $pelangganId = (int) $ada;
            } else {
                q('INSERT INTO pelanggan (nama, telepon, nama_tim) VALUES (?,?,?)', [$data['nama_baru'], $data['telepon_baru'], $data['tim_baru'] ?: null]);
                $pelangganId = (int) db()->lastInsertId();
            }
        }

        $harga = hitung_harga($lap, $mulai, $selesai);
        $dibayar = min(max(0, (int) $data['dibayar']), $harga['total']);

        q('INSERT INTO booking (kode, lapangan_id, pelanggan_id, user_id, tanggal, jam_mulai, jam_selesai, jam_siang, jam_malam, total, dibayar, catatan)
           VALUES (?,?,?,?,?,?,?,?,?,?,?,?)', [
            'TMP-' . bin2hex(random_bytes(4)), $lap['id'], $pelangganId, user()['id'], $data['tanggal'], $mulai, $selesai,
            $harga['siang'], $harga['malam'], $harga['total'], $dibayar, $data['catatan'] ?: null,
        ]);
        $id = (int) db()->lastInsertId();
        $kode = 'BK-' . str_pad((string) $id, 5, '0', STR_PAD_LEFT);
        q('UPDATE booking SET kode = ? WHERE id = ?', [$kode, $id]);
        db()->commit();

        flash("Booking $kode tersimpan.");
        redirect('booking.struk', ['id' => $id]);
    } catch (RuntimeException $ex) {
        db()->rollBack();
        $error = $ex->getMessage();
    }
}

$title = 'Booking Baru';
require __DIR__ . '/../../includes/header.php';
?>
<form method="post" id="form-booking" data-malam="<?= (int) $set['jam_malam'] ?>">
    <?= csrf_field() ?>
    <div class="grid-2">
        <div class="card">
            <div class="card-head"><h2>Jadwal Main</h2></div>
            <div class="card-body">
                <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
                <div class="form-grid">
                    <div class="full"><label>Lapangan</label>
                        <select name="lapangan_id" required>
                            <?php foreach ($lapanganList as $l): ?>
                                <option value="<?= $l['id'] ?>" data-siang="<?= $l['harga_siang'] ?>" data-malam="<?= $l['harga_malam'] ?>" <?= $data['lapangan_id'] == $l['id'] ? 'selected' : '' ?>>
                                    <?= e($l['nama']) ?> · <?= e($l['jenis']) ?> · <?= rupiah($l['harga_siang']) ?> / <?= rupiah($l['harga_malam']) ?> per jam
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div><label>Tanggal</label><input type="date" name="tanggal" min="<?= date('Y-m-d') ?>" value="<?= e($data['tanggal']) ?>" required></div>
                    <div class="form-grid" style="gap:12px">
                        <div><label>Jam Mulai</label>
                            <select name="jam_mulai">
                                <?php for ($h = $set['jam_buka']; $h < $set['jam_tutup']; $h++): ?>
                                    <option value="<?= $h ?>" <?= (int) $data['jam_mulai'] === $h ? 'selected' : '' ?>><?= jam($h) ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div><label>Durasi</label>
                            <select name="durasi">
                                <?php for ($d = 1; $d <= 5; $d++): ?><option value="<?= $d ?>" <?= (int) $data['durasi'] === $d ? 'selected' : '' ?>><?= $d ?> jam</option><?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <h3 style="font-size:14px;margin:26px 0 12px">Pelanggan</h3>
                <div class="form-grid">
                    <div class="full">
                        <select name="pelanggan_id">
                            <option value="">Pelanggan baru (isi di bawah)</option>
                            <?php foreach ($pelangganList as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= $data['pelanggan_id'] == $p['id'] ? 'selected' : '' ?>><?= e($p['nama']) ?><?= $p['nama_tim'] ? ' · ' . e($p['nama_tim']) : '' ?> · <?= e($p['telepon']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div><label>Nama (pelanggan baru)</label><input type="text" name="nama_baru" value="<?= e($data['nama_baru']) ?>"></div>
                    <div><label>No. WhatsApp</label><input type="text" name="telepon_baru" value="<?= e($data['telepon_baru']) ?>"></div>
                    <div class="full"><label>Nama Tim (opsional)</label><input type="text" name="tim_baru" value="<?= e($data['tim_baru']) ?>"></div>
                    <div class="full"><label>Catatan</label><input type="text" name="catatan" value="<?= e($data['catatan']) ?>" placeholder="Sewa rompi, bola, dll."></div>
                </div>
            </div>
        </div>
        <div class="card" style="align-self:start">
            <div class="card-head"><h2>Rincian Harga</h2></div>
            <div class="card-body">
                <div class="summary">
                    <div><span class="muted">Jam main</span><strong id="r-jam">-</strong></div>
                    <div><span class="muted">Tarif siang</span><span id="r-siang">-</span></div>
                    <div><span class="muted">Tarif malam (≥ <?= jam($set['jam_malam']) ?>)</span><span id="r-malam">-</span></div>
                    <div class="total"><span>Total</span><span id="r-total">Rp 0</span></div>
                    <div><span class="muted">Min. DP <?= (int) $set['min_dp_persen'] ?>%</span><span id="r-dp">Rp 0</span></div>
                </div>
                <div class="mt"><label>Dibayar Sekarang</label><input type="number" name="dibayar" min="0" step="1000" value="<?= e($data['dibayar']) ?>" placeholder="0"></div>
                <button class="btn btn-primary btn-block mt"><?= icon('check', 16) ?> Simpan Booking</button>
            </div>
        </div>
    </div>
</form>
<script>
(function () {
    var f = document.getElementById('form-booking'), malam = +f.dataset.malam, dp = <?= (int) $set['min_dp_persen'] ?>;
    var rp = function (n) { return 'Rp ' + n.toLocaleString('id-ID'); };
    var pad = function (h) { return (h < 10 ? '0' : '') + h + '.00'; };
    function hitung() {
        var o = f.lapangan_id.options[f.lapangan_id.selectedIndex]; if (!o) return;
        var m = +f.jam_mulai.value, d = +f.durasi.value, s = 0, n = 0;
        for (var h = m; h < m + d; h++) h >= malam ? n++ : s++;
        var tot = s * o.dataset.siang + n * o.dataset.malam;
        document.getElementById('r-jam').textContent = pad(m) + '–' + pad(m + d) + ' (' + d + ' jam)';
        document.getElementById('r-siang').textContent = s + ' x ' + rp(+o.dataset.siang);
        document.getElementById('r-malam').textContent = n + ' x ' + rp(+o.dataset.malam);
        document.getElementById('r-total').textContent = rp(tot);
        document.getElementById('r-dp').textContent = rp(Math.ceil(tot * dp / 100));
    }
    ['lapangan_id', 'jam_mulai', 'durasi'].forEach(function (n) { f[n].addEventListener('change', hitung); });
    hitung();
})();
</script>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
