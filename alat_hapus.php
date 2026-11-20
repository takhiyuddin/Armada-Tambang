<?php
require_once __DIR__ . '/includes/init.php';
require_login();

// Hapus hanya boleh lewat POST agar tidak terpicu oleh link biasa
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('alat.php');
}
if (!csrf_valid()) {
    set_flash('error', 'Sesi formulir kedaluwarsa, data tidak dihapus. Coba lagi.');
    redirect('alat.php');
}

$id = (int) input($_POST, 'id');

$stmt = $conn->prepare('SELECT kode_unit, foto FROM alat WHERE id_alat = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$alat = $stmt->get_result()->fetch_assoc();

if (!$alat) {
    set_flash('error', 'Unit tidak ditemukan atau sudah dihapus.');
} else {
    $stmt = $conn->prepare('DELETE FROM alat WHERE id_alat = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    hapus_foto($alat['foto']);
    set_flash('ok', 'Unit ' . $alat['kode_unit'] . ' dihapus.');
}

// Kembali ke halaman asal bila masih di aplikasi ini
$kembali = 'alat.php';
if (!empty($_SERVER['HTTP_REFERER'])) {
    $ref = parse_url($_SERVER['HTTP_REFERER']);
    if (isset($ref['path']) && basename($ref['path']) === 'alat.php') {
        $kembali = 'alat.php' . (isset($ref['query']) ? '?' . $ref['query'] : '');
    }
}
redirect($kembali);
