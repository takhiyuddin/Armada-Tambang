<?php
// Konfigurasi koneksi database (sesuaikan bila memakai port/password lain)
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'alatberat_3506';
$db_port = 3306;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    exit('Koneksi database gagal: ' . htmlspecialchars($e->getMessage())
        . '. Pastikan MySQL di XAMPP sudah berjalan dan file database/alatberat_3506.sql sudah diimpor.');
}
