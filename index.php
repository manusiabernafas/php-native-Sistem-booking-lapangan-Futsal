<?php
require __DIR__ . '/includes/bootstrap.php';

$routes = [
    'login'           => 'pages/login.php',
    'logout'          => 'pages/logout.php',
    'publik'          => 'pages/publik.php',
    'dashboard'       => 'pages/dashboard.php',
    'jadwal'          => 'pages/jadwal.php',
    'booking'         => 'pages/booking/index.php',
    'booking.baru'    => 'pages/booking/baru.php',
    'booking.bayar'   => 'pages/booking/bayar.php',
    'booking.status'  => 'pages/booking/status.php',
    'booking.struk'   => 'pages/booking/struk.php',
    'lapangan'        => 'pages/lapangan/index.php',
    'lapangan.form'   => 'pages/lapangan/form.php',
    'pelanggan'       => 'pages/pelanggan/index.php',
    'pelanggan.form'  => 'pages/pelanggan/form.php',
    'laporan'         => 'pages/laporan.php',
    'pengaturan'      => 'pages/pengaturan.php',
];

$page = $_GET['page'] ?? 'dashboard';
if (!isset($routes[$page])) {
    http_response_code(404);
    $page = 'dashboard';
}
if (!in_array($page, ['login', 'publik'], true)) require_login();
verify_csrf();

require __DIR__ . '/' . $routes[$page];
