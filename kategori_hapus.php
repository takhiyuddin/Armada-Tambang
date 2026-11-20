<?php
require_once __DIR__ . '/includes/init.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('kategori.php');
}
if (!csrf_valid()) {
    set_flash('error', 'Sesi formulir kedaluwarsa, kategori tidak dihapus. Coba lagi.');
    redirect('kategori.php');
}

$id = (int) input($_POST, 'id');

$stmt = $conn->prepare(
    'SELECT k.nama_kategori, COUNT(a.id_alat) AS jml
     FROM kategori k LEFT JOIN alat a ON a.id_kategori = k.id_kategori
     WHERE k.id_kategori = ? GROUP BY k.id_kategori, k.nama_kategori'
);
$stmt->bind_param('i', $id);
$stmt->execute();
$kat = $stmt->get_result()->fetch_assoc();

if (!$kat) {
    set_flash('error', 'Kategori tidak ditemukan atau sudah dihapus.');
} elseif ($kat['jml'] > 0) {
    // Foreign key ON DELETE RESTRICT: kategori yang masih dipakai tidak boleh dihapus
    set_flash('error', 'Kategori ' . $kat['nama_kategori'] . ' masih dipakai ' . $kat['jml'] . ' unit. Pindahkan atau hapus unitnya dulu.');
} else {
    try {
        $stmt = $conn->prepare('DELETE FROM kategori WHERE id_kategori = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        set_flash('ok', 'Kategori ' . $kat['nama_kategori'] . ' dihapus.');
    } catch (mysqli_sql_exception $ex) {
        set_flash('error', 'Kategori tidak bisa dihapus karena masih dipakai data lain.');
    }
}
redirect('kategori.php');
