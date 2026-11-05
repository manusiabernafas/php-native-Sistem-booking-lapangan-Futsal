<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $c = require __DIR__ . '/../config/database.php';
        $dsn = "mysql:host={$c['host']};port={$c['port']};dbname={$c['database']};charset=utf8mb4";
        try {
            $pdo = new PDO($dsn, $c['username'], $c['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            die('<h3>Koneksi database gagal.</h3><p>Cek config/database.php dan pastikan database <b>'
                . htmlspecialchars($c['database']) . '</b> sudah di-import.</p>');
        }
    }
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

function e($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function rupiah($n): string
{
    return 'Rp ' . number_format((int) $n, 0, ',', '.');
}

function tgl($date): string
{
    if (!$date) return '-';
    $bulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $t = strtotime($date);
    return date('j', $t) . ' ' . $bulan[(int) date('n', $t)] . ' ' . date('Y', $t);
}

function url(string $page = 'dashboard', array $params = []): string
{
    return 'index.php?' . http_build_query(['page' => $page] + $params);
}

function redirect(string $page, array $params = []): void
{
    header('Location: ' . url($page, $params));
    exit;
}

function flash(?string $msg = null, string $type = 'success')
{
    if ($msg !== null) {
        $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
        return null;
    }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!user()) redirect('login');
}

function require_admin(): void
{
    if ((user()['role'] ?? '') !== 'admin') {
        flash('Halaman ini khusus admin.', 'error');
        redirect('dashboard');
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . csrf_token() . '">';
}

function verify_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !hash_equals(csrf_token(), $_POST['_csrf'] ?? '')) {
        http_response_code(419);
        die('Sesi formulir kedaluwarsa, silakan muat ulang halaman.');
    }
}

function setting(): array
{
    static $s = null;
    return $s ??= q('SELECT * FROM pengaturan WHERE id = 1')->fetch() ?: [];
}

function icon(string $name, int $size = 18): string
{
    // Ikon garis (Lucide, MIT) disematkan langsung agar aplikasi bisa jalan offline
    $paths = [
        'home' => '<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/>',
        'car' => '<path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><path d="M9 17h6"/><circle cx="17" cy="17" r="2"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'key' => '<circle cx="7.5" cy="15.5" r="5.5"/><path d="m21 2-9.6 9.6"/><path d="m15.5 7.5 3 3L22 7l-3-3"/>',
        'chart' => '<path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/>',
        'settings' => '<path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/>',
        'plus' => '<path d="M5 12h14"/><path d="M12 5v14"/>',
        'edit' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
        'trash' => '<path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>',
        'printer' => '<polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/>',
        'undo' => '<path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"/>',
        'search' => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
        'wallet' => '<path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/>',
        'alert' => '<circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/>',
        'wrench' => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>',
        'eye' => '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>',
        'calendar' => '<rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/>',
        'clock' => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'grid' => '<rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/>',
        'goal' => '<path d="M12 13V2l8 4-8 4"/><path d="M20.56 10.22a9 9 0 1 1-12.1-7.47"/><path d="M8 10a4 4 0 1 0 8 0"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'x' => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
        'external' => '<path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>',
        'menu' => '<line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/>',
    ];
    $p = $paths[$name] ?? '';
    return '<svg class="ic" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $p . '</svg>';
}

function status_badge(string $s): string
{
    $map = [
        'aktif' => 'green', 'perbaikan' => 'amber',
        'dipesan' => 'blue', 'main' => 'amber', 'selesai' => 'green', 'batal' => 'gray',
        'lunas' => 'green', 'dp' => 'amber', 'belum' => 'red',
    ];
    $label = ['dp' => 'DP', 'belum' => 'Belum Bayar', 'main' => 'Sedang Main'][$s] ?? ucfirst($s);
    return '<span class="badge badge-' . ($map[$s] ?? 'gray') . '">' . e($label) . '</span>';
}

function jam(int $h): string
{
    return str_pad((string) $h, 2, '0', STR_PAD_LEFT) . '.00';
}

/**
 * Hitung harga sewa per jam: jam mulai >= batas_malam memakai tarif malam.
 * Contoh: main 16.00-19.00 dengan batas 17 => 1 jam tarif siang + 2 jam tarif malam.
 */
function hitung_harga(array $lapangan, int $mulai, int $selesai): array
{
    $batas = (int) (setting()['jam_malam'] ?? 17);
    $siang = $malam = 0;
    for ($h = $mulai; $h < $selesai; $h++) {
        $h >= $batas ? $malam++ : $siang++;
    }
    $total = $siang * (int) $lapangan['harga_siang'] + $malam * (int) $lapangan['harga_malam'];
    return ['siang' => $siang, 'malam' => $malam, 'total' => $total];
}

/** Cek apakah slot bentrok dengan booking lain (rentang [mulai, selesai) saling tumpang tindih). */
function slot_bentrok(int $lapanganId, string $tanggal, int $mulai, int $selesai, int $kecualiId = 0): ?array
{
    return q("SELECT b.kode, b.jam_mulai, b.jam_selesai, p.nama FROM booking b JOIN pelanggan p ON p.id = b.pelanggan_id
              WHERE b.lapangan_id = ? AND b.tanggal = ? AND b.status <> 'batal' AND b.id <> ?
              AND b.jam_mulai < ? AND b.jam_selesai > ? LIMIT 1",
        [$lapanganId, $tanggal, $kecualiId, $selesai, $mulai])->fetch() ?: null;
}
