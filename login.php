<?php
require_once __DIR__ . '/includes/init.php';

// Bila sudah login, langsung ke dashboard
if (!empty($_SESSION['id_user'])) {
    redirect('dashboard.php');
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = input($_POST, 'username');
    $password = (isset($_POST['password']) && is_string($_POST['password'])) ? $_POST['password'] : '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } elseif (!csrf_valid()) {
        $error = 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } else {
        $stmt = $conn->prepare('SELECT id_user, username, password, nama_lengkap FROM users WHERE username = ? LIMIT 1');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && hash_equals($user['password'], hash('sha256', $password))) {
            session_regenerate_id(true);
            $_SESSION['id_user'] = $user['id_user'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            set_flash('ok', 'Selamat datang, ' . $user['nama_lengkap'] . '.');
            redirect('dashboard.php');
        } else {
            $error = 'Username atau password salah.';
        }
    }
}

$flash = get_flash();
if (!$error && $flash && $flash['tipe'] === 'error') {
    $error = $flash['pesan'];
}
$info = ($flash && $flash['tipe'] !== 'error') ? $flash['pesan'] : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Masuk - Armada Tambang</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 32 32%27%3E%3Crect width=%2732%27 height=%2732%27 rx=%278%27/%3E%3Cpath d=%27M5 24 13 11l4 6 3-4 7 11Z%27 fill=%27none%27 stroke=%27%23fff%27 stroke-width=%272%27/%3E%3C/svg%3E">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
  <div class="login-frame">
    <section class="login-form-side">
      <div class="login-brand">
        <span class="brand"><?= logo_mark() ?></span>
        <span>Armada Tambang</span>
      </div>

      <div class="login-body">
        <h1>Masuk ke inventaris</h1>
        <p class="muted">Kelola unit alat berat, status operasi, dan lokasi kerja dari satu tempat.</p>

        <?php if ($error): ?>
          <div class="toast toast-error" role="alert"><?= icon('alert', 18) ?><span><?= e($error) ?></span></div>
        <?php elseif ($info): ?>
          <div class="toast toast-ok" role="status"><?= icon('check', 18) ?><span><?= e($info) ?></span></div>
        <?php endif; ?>

        <form method="post" action="login.php" class="stack" novalidate>
          <?= csrf_field() ?>
          <label class="field">
            <span class="field-label">Username</span>
            <span class="input-icon">
              <?= icon('user', 18) ?>
              <input type="text" name="username" value="<?= e($username) ?>" autocomplete="username" placeholder="admin" required autofocus>
            </span>
          </label>
          <label class="field">
            <span class="field-label">Password</span>
            <span class="input-icon">
              <?= icon('lock', 18) ?>
              <input type="password" name="password" id="password" autocomplete="current-password" placeholder="Password" required>
              <button type="button" class="pw-toggle" data-toggle-password="password" aria-label="Tampilkan password"><?= icon('eye', 18) ?></button>
            </span>
          </label>
          <button type="submit" class="btn btn-dark btn-block">Masuk</button>
        </form>
      </div>

      <p class="login-foot">Taqiyuddin 'Afif &middot; 25.12.3506 &middot; S1SI 02</p>
    </section>

    <aside class="login-visual" aria-hidden="true">
      <svg class="contour" viewBox="0 0 400 400" preserveAspectRatio="xMidYMid slice">
        <g fill="none" stroke="currentColor" stroke-width="1">
          <path d="M-20 300c60-40 110-20 160-60s70-120 140-130 110 30 150 10"/>
          <path d="M-20 330c70-40 120-14 175-58s78-118 146-124 100 34 130 18"/>
          <path d="M-20 360c80-38 130-8 190-54s86-112 152-116 88 38 110 26"/>
          <path d="M-20 270c55-42 100-30 148-66s64-122 132-138 120 24 170 0"/>
          <path d="M-20 240c50-44 92-38 136-72s58-124 124-144 128 18 190-8"/>
          <path d="M-20 390c90-36 140 0 205-48s92-106 158-108 76 42 90 34"/>
        </g>
      </svg>
      <div class="visual-card">
        <span class="visual-kicker">Kondisi armada hari ini</span>
        <ul class="visual-list">
          <li><span class="pill pill-ok">Siap Operasi</span><span>Unit boleh dipakai di front kerja</span></li>
          <li><span class="pill pill-idle">Standby</span><span>Menunggu jadwal atau operator</span></li>
          <li><span class="pill pill-repair">Perbaikan</span><span>Sedang ditangani workshop</span></li>
          <li><span class="pill pill-down">Rusak</span><span>Tidak boleh dioperasikan</span></li>
        </ul>
      </div>
    </aside>
  </div>
  <script src="assets/js/app.js"></script>
</body>
</html>
