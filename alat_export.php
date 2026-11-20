<?php
require_once __DIR__ . '/includes/init.php';
require_login();

// Ekspor data alat (mengikuti filter yang sedang aktif) ke file CSV
$q = input($_GET, 'q');
$f_status = input($_GET, 'status');
$f_kategori = (int) input($_GET, 'kategori');

$kondisi = [];
$tipe = '';
$nilai = [];
if ($q !== '') {
    $kondisi[] = '(a.kode_unit LIKE ? OR a.nama_alat LIKE ? OR a.merek LIKE ? OR a.lokasi LIKE ? OR a.nomor_seri LIKE ?)';
    for ($i = 0; $i < 5; $i++) {
        $tipe .= 's';
        $nilai[] = '%' . $q . '%';
    }
}
if (array_key_exists($f_status, daftar_status())) {
    $kondisi[] = 'a.status = ?';
    $tipe .= 's';
    $nilai[] = $f_status;
}
if ($f_kategori > 0) {
    $kondisi[] = 'a.id_kategori = ?';
    $tipe .= 'i';
    $nilai[] = $f_kategori;
}
$where = $kondisi ? 'WHERE ' . implode(' AND ', $kondisi) : '';

$stmt = $conn->prepare(
    "SELECT a.kode_unit, a.nama_alat, a.merek, k.nama_kategori, a.nomor_seri, a.lokasi,
            a.jam_operasi, a.kepemilikan, a.status, a.tanggal_perolehan, a.catatan
     FROM alat a JOIN kategori k ON k.id_kategori = a.id_kategori
     $where ORDER BY a.kode_unit"
);
if ($tipe !== '') {
    $stmt->bind_param($tipe, ...$nilai);
}
$stmt->execute();
$hasil = $stmt->get_result();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="inventaris_alat_' . date('Ymd') . '.csv"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // BOM agar terbaca rapi di Excel
fputcsv($out, ['Kode unit', 'Nama alat', 'Merek', 'Kategori', 'Nomor seri', 'Lokasi', 'Hour meter', 'Kepemilikan', 'Status', 'Tanggal perolehan', 'Catatan'], ';');
while ($row = $hasil->fetch_row()) {
    fputcsv($out, $row, ';');
}
fclose($out);
exit;
