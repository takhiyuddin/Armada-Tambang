<?php
// Kumpulan function yang dipakai berulang di banyak halaman

// Mencetak teks dengan aman (mencegah XSS)
function e($teks)
{
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}

// Mengambil input teks dari $_GET / $_POST dengan aman (bukan array)
function input($sumber, $kunci)
{
    return (isset($sumber[$kunci]) && is_scalar($sumber[$kunci])) ? trim((string) $sumber[$kunci]) : '';
}

function redirect($url)
{
    header('Location: ' . $url);
    exit;
}

// Halaman yang memanggil function ini hanya bisa dibuka setelah login
function require_login()
{
    if (empty($_SESSION['id_user'])) {
        set_flash('error', 'Silakan masuk terlebih dahulu.');
        redirect('login.php');
    }
}

function set_flash($tipe, $pesan)
{
    $_SESSION['flash'] = ['tipe' => $tipe, 'pesan' => $pesan];
}

function get_flash()
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

// Token sederhana agar form tidak bisa dikirim dari situs lain
function csrf_token()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field()
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid()
{
    return isset($_POST['csrf']) && is_string($_POST['csrf']) && hash_equals(csrf_token(), $_POST['csrf']);
}

// Mengambil satu nilai dari query (dipakai untuk statistik)
function scalar($conn, $sql)
{
    $hasil = $conn->query($sql)->fetch_row();
    return $hasil ? $hasil[0] : 0;
}

// Daftar status alat beserta kelas warnanya (array asosiatif)
function daftar_status()
{
    return [
        'Siap Operasi' => 'ok',
        'Standby'      => 'idle',
        'Perbaikan'    => 'repair',
        'Rusak'        => 'down',
    ];
}

function daftar_kepemilikan()
{
    return ['Milik Sendiri', 'Sewa'];
}

function badge_status($status)
{
    $peta = daftar_status();
    $kelas = isset($peta[$status]) ? $peta[$status] : 'idle';
    return '<span class="pill pill-' . $kelas . '">' . e($status) . '</span>';
}

function format_tanggal($tanggal)
{
    if (!$tanggal || $tanggal === '0000-00-00') {
        return '-';
    }
    $bulan = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $t = strtotime($tanggal);
    return date('j', $t) . ' ' . $bulan[(int) date('n', $t)] . ' ' . date('Y', $t);
}

function format_angka($angka)
{
    return number_format((int) $angka, 0, ',', '.');
}

// Validasi dan simpan file foto unit. Mengembalikan nama file baru,
// null bila tidak ada file, atau melempar Exception bila file tidak valid.
function upload_foto($file)
{
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
        throw new Exception('Ukuran foto melebihi 2 MB.');
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Foto gagal diunggah, coba pilih ulang filenya.');
    }
    if ($file['size'] > MAX_UPLOAD) {
        throw new Exception('Ukuran foto melebihi 2 MB.');
    }

    $izin = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = mime_content_type($file['tmp_name']);
    if (!isset($izin[$mime])) {
        throw new Exception('Foto harus berformat JPG, PNG, atau WEBP.');
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0775, true);
    }
    $nama_baru = 'unit_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $izin[$mime];
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $nama_baru)) {
        throw new Exception('Foto tidak bisa disimpan ke folder uploads.');
    }
    return $nama_baru;
}

function hapus_foto($nama_file)
{
    if ($nama_file && is_file(UPLOAD_DIR . basename($nama_file))) {
        unlink(UPLOAD_DIR . basename($nama_file));
    }
}

// Membuat URL halaman saat ini dengan parameter GET yang diganti
function url_dengan($halaman, $ubah)
{
    $param = array_merge($_GET, $ubah);
    foreach ($param as $k => $v) {
        if ($v === '' || $v === null) {
            unset($param[$k]);
        }
    }
    return $halaman . ($param ? '?' . http_build_query($param) : '');
}

function inisial($nama)
{
    $kata = preg_split('/\s+/', trim($nama));
    $hasil = '';
    foreach (array_slice($kata, 0, 2) as $k) {
        $hasil .= mb_strtoupper(mb_substr($k, 0, 1));
    }
    return $hasil;
}

// Ikon SVG (garis) agar tampilan tidak bergantung pada CDN ikon
function icon($nama, $ukuran = 20)
{
    $path = [
        'grid'     => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'truck'    => '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.62l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/>',
        'layers'   => '<path d="M12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z"/><path d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65"/><path d="m22 12.65-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65"/>',
        'plus'     => '<path d="M5 12h14"/><path d="M12 5v14"/>',
        'search'   => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'pencil'   => '<path d="M21.17 6.81a1 1 0 0 0-3.99-3.99L3.84 16.17a2 2 0 0 0-.5.83l-1.32 4.35a.5.5 0 0 0 .62.62l4.35-1.32a2 2 0 0 0 .83-.5z"/><path d="m15 5 4 4"/>',
        'trash'    => '<path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>',
        'more'     => '<circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/>',
        'logout'   => '<path d="m12 19-7-7 7-7"/><path d="M19 12H5"/>',
        'chev-l'   => '<path d="m15 18-6-6 6-6"/>',
        'chev-r'   => '<path d="m9 18 6-6-6-6"/>',
        'chevs-l'  => '<path d="m11 17-5-5 5-5"/><path d="m18 17-5-5 5-5"/>',
        'chevs-r'  => '<path d="m6 17 5-5-5-5"/><path d="m13 17 5-5-5-5"/>',
        'chev-d'   => '<path d="m6 9 6 6 6-6"/>',
        'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/>',
        'upload'   => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m17 8-5-5-5 5"/><path d="M12 3v12"/>',
        'eye'      => '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>',
        'x'        => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
        'alert'    => '<circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/>',
        'check'    => '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>',
        'back'     => '<path d="m12 19-7-7 7-7"/><path d="M19 12H5"/>',
        'gauge'    => '<path d="m12 14 4-4"/><path d="M3.34 19a10 10 0 1 1 17.32 0"/>',
        'pin'      => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
        'wrench'   => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>',
        'image'    => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.09-3.09a2 2 0 0 0-2.82 0L6 21"/>',
        'export'   => '<path d="M12 3v12"/><path d="m17 8-5-5-5 5"/><path d="M5 14v5a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-5"/>',
        'user'     => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'lock'     => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
    ];
    $isi = isset($path[$nama]) ? $path[$nama] : '';
    return '<svg class="ic" width="' . $ukuran . '" height="' . $ukuran . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $isi . '</svg>';
}

// Logo aplikasi: siluet gunung tambang dengan jalur hauling
function logo_mark()
{
    return '<svg viewBox="0 0 32 32" width="22" height="22" aria-hidden="true"><path d="M3 25 12.5 9l5 8 3-4.5L29 25Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M9 25c3-3.2 7.5-3.6 11-1.6" fill="none" stroke="#f2b705" stroke-width="2" stroke-linecap="round"/></svg>';
}
