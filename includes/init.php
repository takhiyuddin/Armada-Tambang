<?php
// Dipanggil paling awal oleh setiap halaman
session_start();
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/functions.php';

define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_UPLOAD', 2 * 1024 * 1024); // 2 MB
