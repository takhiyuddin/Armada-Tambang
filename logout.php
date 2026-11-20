<?php
session_start();

// Kosongkan semua data session
$_SESSION = [];

// Hapus cookie session di browser
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}

session_destroy();

// Mulai session baru (ID baru) hanya untuk menampilkan pesan di halaman login
session_id(session_create_id());
session_start();
$_SESSION['flash'] = ['tipe' => 'ok', 'pesan' => 'Anda sudah keluar dari aplikasi.'];

header('Location: login.php');
exit;
