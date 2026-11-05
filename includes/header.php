<?php
$menu = [
    ['dashboard', 'Beranda', 'home'],
    ['jadwal', 'Jadwal', 'grid'],
    ['booking', 'Booking', 'calendar'],
    ['lapangan', 'Lapangan', 'goal'],
    ['pelanggan', 'Pelanggan', 'users'],
    ['laporan', 'Laporan', 'chart'],
];
if (user()['role'] === 'admin') $menu[] = ['pengaturan', 'Pengaturan', 'settings'];
$current = explode('.', $_GET['page'] ?? 'dashboard')[0];
$flash = flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Dashboard') ?> · <?= e(setting()['nama_usaha'] ?? 'Booking Futsal') ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="topnav-layout">
<header class="navbar">
    <div class="navbar-inner">
        <a class="nav-brand" href="<?= url('dashboard') ?>">
            <span class="brand-mark"><?= icon('goal', 20) ?></span>
            <span><strong><?= e(setting()['nama_usaha'] ?? 'Booking Futsal') ?></strong><small>Sistem Booking Futsal</small></span>
        </a>
        <button class="icon-btn nav-toggle" onclick="document.querySelector('.nav-links').classList.toggle('open')"><?= icon('menu') ?></button>
        <nav class="nav-links">
            <?php foreach ($menu as [$key, $label, $ic]): ?>
                <a href="<?= url($key) ?>" class="<?= $current === $key ? 'active' : '' ?>"><?= icon($ic, 16) ?> <?= $label ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="nav-user">
            <a href="<?= url('publik') ?>" target="_blank" class="nav-public" title="Jadwal publik untuk pelanggan"><?= icon('external', 16) ?></a>
            <span class="avatar"><?= e(strtoupper(substr(user()['nama'], 0, 1))) ?></span>
            <span class="nav-user-name"><strong><?= e(user()['nama']) ?></strong><small><?= e(ucfirst(user()['role'])) ?></small></span>
            <a href="<?= url('logout') ?>" title="Keluar" class="icon-btn"><?= icon('logout', 16) ?></a>
        </div>
    </div>
</header>
<main class="page">
    <div class="page-head">
        <h1><?= e($title ?? 'Dashboard') ?></h1>
        <div class="topbar-actions"><?= $actions ?? '' ?></div>
    </div>
    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
    <?php endif; ?>
