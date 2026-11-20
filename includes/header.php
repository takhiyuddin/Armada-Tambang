<?php
// Variabel yang bisa diisi halaman sebelum include header:
// $judul_halaman (string) dan $menu_aktif (string)
$judul_halaman = isset($judul_halaman) ? $judul_halaman : 'Dashboard';
$menu_aktif = isset($menu_aktif) ? $menu_aktif : '';

// Daftar menu sidebar (array) yang ditampilkan dengan perulangan
$menu = [
    ['kunci' => 'dashboard', 'label' => 'Dashboard',     'url' => 'dashboard.php', 'ikon' => 'grid'],
    ['kunci' => 'alat',      'label' => 'Data alat',     'url' => 'alat.php',      'ikon' => 'truck'],
    ['kunci' => 'tambah',    'label' => 'Tambah unit',   'url' => 'alat_form.php', 'ikon' => 'plus'],
    ['kunci' => 'kategori',  'label' => 'Kategori alat', 'url' => 'kategori.php',  'ikon' => 'layers'],
];
$nama_user = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'Pengguna';
$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($judul_halaman) ?> - Armada Tambang</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 32 32%27%3E%3Crect width=%2732%27 height=%2732%27 rx=%278%27/%3E%3Cpath d=%27M5 24 13 11l4 6 3-4 7 11Z%27 fill=%27none%27 stroke=%27%23fff%27 stroke-width=%272%27/%3E%3C/svg%3E">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="shell">
  <aside class="rail" aria-label="Menu utama">
    <a class="brand" href="dashboard.php" title="Armada Tambang"><?= logo_mark() ?></a>
    <nav class="rail-nav">
      <?php foreach ($menu as $item): ?>
        <a href="<?= e($item['url']) ?>"
           class="rail-link<?= $item['kunci'] === $menu_aktif ? ' is-active' : '' ?>"
           data-label="<?= e($item['label']) ?>" aria-label="<?= e($item['label']) ?>"
           <?= $item['kunci'] === $menu_aktif ? 'aria-current="page"' : '' ?>>
          <?= icon($item['ikon']) ?>
        </a>
      <?php endforeach; ?>
    </nav>
    <a class="rail-logout" href="logout.php" data-label="Logout" aria-label="Logout"><?= icon('logout', 18) ?></a>
  </aside>

  <div class="main">
    <header class="topbar">
      <a class="topbar-brand" href="dashboard.php">
        <span class="topbar-mark"><?= logo_mark() ?></span>
        <span>Armada Tambang<small>Inventaris alat berat</small></span>
      </a>
      <div class="usermenu" data-dropdown>
        <button type="button" class="user-chip" data-dropdown-toggle aria-expanded="false">
          <span class="avatar"><?= e(inisial($nama_user)) ?></span>
          <span class="user-text">
            <strong><?= e($nama_user) ?></strong>
            <small>@<?= e($username) ?></small>
          </span>
          <?= icon('chev-d', 16) ?>
        </button>
        <div class="dropdown" role="menu">
          <a href="dashboard.php" role="menuitem"><?= icon('grid', 17) ?> Dashboard</a>
          <a href="logout.php" role="menuitem" class="danger"><?= icon('logout', 17) ?> Logout</a>
        </div>
      </div>
    </header>

    <main class="content">
      <?php if ($flash): ?>
        <div class="toast toast-<?= $flash['tipe'] === 'error' ? 'error' : 'ok' ?>" role="status" data-toast>
          <?= icon($flash['tipe'] === 'error' ? 'alert' : 'check', 18) ?>
          <span><?= e($flash['pesan']) ?></span>
          <button type="button" class="toast-close" aria-label="Tutup" data-toast-close><?= icon('x', 16) ?></button>
        </div>
      <?php endif; ?>
