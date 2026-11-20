<?php
require_once __DIR__ . '/includes/init.php';
require_login();

// Mode edit bila ada ?id=..., selain itu mode tambah
$id = (int) input($_GET, 'id');
$mode_edit = $id > 0;

// Nilai awal form (array asosiatif)
$data = [
    'kode_unit'         => '',
    'nama_alat'         => '',
    'merek'             => '',
    'id_kategori'       => '',
    'nomor_seri'        => '',
    'lokasi'            => '',
    'jam_operasi'       => '0',
    'kepemilikan'       => 'Milik Sendiri',
    'status'            => 'Siap Operasi',
    'tanggal_perolehan' => '',
    'catatan'           => '',
    'foto'              => null,
];

if ($mode_edit) {
    $stmt = $conn->prepare('SELECT * FROM alat WHERE id_alat = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $lama = $stmt->get_result()->fetch_assoc();
    if (!$lama) {
        set_flash('error', 'Unit yang ingin diedit tidak ditemukan.');
        redirect('alat.php');
    }
    foreach ($data as $kolom => $isi) {
        $data[$kolom] = $lama[$kolom];
    }
}

$kategori = $conn->query('SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori')->fetch_all(MYSQLI_ASSOC);
$id_kategori_valid = array_map('intval', array_column($kategori, 'id_kategori'));
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Form yang terlalu besar membuat $_POST kosong
    if (empty($_POST) && !empty($_SERVER['CONTENT_LENGTH'])) {
        $errors['foto'] = 'Ukuran kiriman terlalu besar. Pilih foto maksimal 2 MB.';
    } elseif (!csrf_valid()) {
        $errors['umum'] = 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu kirim lagi.';
    } else {
        // Ambil input teks dengan perulangan
        foreach (['kode_unit', 'nama_alat', 'merek', 'nomor_seri', 'lokasi', 'catatan', 'tanggal_perolehan', 'kepemilikan', 'status'] as $kolom) {
            $data[$kolom] = input($_POST, $kolom);
        }
        $data['kode_unit'] = strtoupper($data['kode_unit']);
        $data['id_kategori'] = (int) input($_POST, 'id_kategori');
        $data['jam_operasi'] = input($_POST, 'jam_operasi');

        // Validasi dengan struktur percabangan
        if ($data['kode_unit'] === '') {
            $errors['kode_unit'] = 'Kode unit wajib diisi.';
        } elseif (!preg_match('/^[A-Z0-9-]{3,20}$/', $data['kode_unit'])) {
            $errors['kode_unit'] = 'Gunakan 3-20 huruf, angka, atau tanda minus. Contoh: EX-2101.';
        } else {
            $stmt = $conn->prepare('SELECT id_alat FROM alat WHERE kode_unit = ? AND id_alat <> ?');
            $stmt->bind_param('si', $data['kode_unit'], $id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $errors['kode_unit'] = 'Kode unit ' . $data['kode_unit'] . ' sudah dipakai unit lain.';
            }
        }

        if ($data['nama_alat'] === '') {
            $errors['nama_alat'] = 'Nama alat wajib diisi.';
        } elseif (mb_strlen($data['nama_alat']) > 120) {
            $errors['nama_alat'] = 'Nama alat maksimal 120 karakter.';
        }

        if ($data['merek'] === '') {
            $errors['merek'] = 'Merek wajib diisi.';
        } elseif (mb_strlen($data['merek']) > 60) {
            $errors['merek'] = 'Merek maksimal 60 karakter.';
        }

        if (!in_array($data['id_kategori'], $id_kategori_valid, true)) {
            $errors['id_kategori'] = 'Pilih kategori alat.';
        }

        if ($data['nomor_seri'] === '') {
            $errors['nomor_seri'] = 'Nomor seri wajib diisi.';
        } elseif (mb_strlen($data['nomor_seri']) > 40) {
            $errors['nomor_seri'] = 'Nomor seri maksimal 40 karakter.';
        }

        if ($data['lokasi'] === '') {
            $errors['lokasi'] = 'Lokasi kerja wajib diisi.';
        } elseif (mb_strlen($data['lokasi']) > 100) {
            $errors['lokasi'] = 'Lokasi maksimal 100 karakter.';
        }

        if ($data['jam_operasi'] === '' || !ctype_digit($data['jam_operasi'])) {
            $errors['jam_operasi'] = 'Isi hour meter dengan angka bulat 0 atau lebih.';
        } elseif ((int) $data['jam_operasi'] > 500000) {
            $errors['jam_operasi'] = 'Hour meter tidak wajar (maksimal 500.000 jam).';
        }

        if (!in_array($data['kepemilikan'], daftar_kepemilikan(), true)) {
            $errors['kepemilikan'] = 'Pilih status kepemilikan.';
        }

        if (!array_key_exists($data['status'], daftar_status())) {
            $errors['status'] = 'Pilih kondisi unit.';
        }

        $tgl = DateTime::createFromFormat('Y-m-d', $data['tanggal_perolehan']);
        if ($data['tanggal_perolehan'] === '') {
            $errors['tanggal_perolehan'] = 'Tanggal perolehan wajib diisi.';
        } elseif (!$tgl || $tgl->format('Y-m-d') !== $data['tanggal_perolehan']) {
            $errors['tanggal_perolehan'] = 'Format tanggal tidak valid.';
        } elseif ($data['tanggal_perolehan'] > date('Y-m-d')) {
            $errors['tanggal_perolehan'] = 'Tanggal perolehan tidak boleh melewati hari ini.';
        } elseif ($data['tanggal_perolehan'] < '1950-01-01') {
            $errors['tanggal_perolehan'] = 'Tanggal perolehan terlalu lama.';
        }

        // Upload foto hanya diproses bila field lain sudah valid
        $foto_baru = null;
        if (!$errors) {
            try {
                $foto_baru = upload_foto(isset($_FILES['foto']) ? $_FILES['foto'] : []);
            } catch (Exception $ex) {
                $errors['foto'] = $ex->getMessage();
            }
        }

        if (!$errors) {
            $foto_lama = $data['foto'];
            $hapus_lama = !empty($_POST['hapus_foto']);
            if ($foto_baru) {
                $data['foto'] = $foto_baru;
            } elseif ($hapus_lama) {
                $data['foto'] = null;
            }
            $jam = (int) $data['jam_operasi'];
            $catatan = $data['catatan'] === '' ? null : $data['catatan'];

            try {
                if ($mode_edit) {
                    $stmt = $conn->prepare(
                        'UPDATE alat SET kode_unit=?, nama_alat=?, merek=?, id_kategori=?, nomor_seri=?, lokasi=?,
                         jam_operasi=?, kepemilikan=?, status=?, tanggal_perolehan=?, catatan=?, foto=?
                         WHERE id_alat=?'
                    );
                    $stmt->bind_param('sssississsssi', $data['kode_unit'], $data['nama_alat'], $data['merek'], $data['id_kategori'],
                        $data['nomor_seri'], $data['lokasi'], $jam, $data['kepemilikan'], $data['status'],
                        $data['tanggal_perolehan'], $catatan, $data['foto'], $id);
                } else {
                    $stmt = $conn->prepare(
                        'INSERT INTO alat (kode_unit, nama_alat, merek, id_kategori, nomor_seri, lokasi,
                         jam_operasi, kepemilikan, status, tanggal_perolehan, catatan, foto)
                         VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
                    );
                    $stmt->bind_param('sssississsss', $data['kode_unit'], $data['nama_alat'], $data['merek'], $data['id_kategori'],
                        $data['nomor_seri'], $data['lokasi'], $jam, $data['kepemilikan'], $data['status'],
                        $data['tanggal_perolehan'], $catatan, $data['foto']);
                }
                $stmt->execute();

                // Foto lama dibuang bila diganti atau diminta dihapus
                if ($mode_edit && $foto_lama && ($foto_baru || $hapus_lama)) {
                    hapus_foto($foto_lama);
                }
                set_flash('ok', $mode_edit
                    ? 'Perubahan unit ' . $data['kode_unit'] . ' disimpan.'
                    : 'Unit ' . $data['kode_unit'] . ' ditambahkan.');
                redirect('alat.php');
            } catch (mysqli_sql_exception $ex) {
                hapus_foto($foto_baru);
                $data['foto'] = $foto_lama;
                if ($ex->getCode() === 1062) {
                    $errors['kode_unit'] = 'Kode unit ' . $data['kode_unit'] . ' sudah dipakai unit lain.';
                } else {
                    $errors['umum'] = 'Data gagal disimpan: ' . $ex->getMessage();
                }
            }
        }
    }
}

$judul_halaman = $mode_edit ? 'Edit unit' : 'Tambah unit';
$menu_aktif = $mode_edit ? 'alat' : 'tambah';

// Menampilkan pesan error di bawah field tertentu
function pesan_error($errors, $field)
{
    return isset($errors[$field]) ? '<span class="field-error">' . e($errors[$field]) . '</span>' : '';
}
function kelas_error($errors, $field)
{
    return isset($errors[$field]) ? ' has-error' : '';
}

require __DIR__ . '/includes/header.php';
?>
<a href="alat.php" class="back-link"><?= icon('back', 16) ?> Kembali ke data alat</a>
<div class="page-head">
  <div>
    <h1><?= $mode_edit ? 'Edit unit ' . e($data['kode_unit']) : 'Tambah unit' ?></h1>
    <p class="muted"><?= $mode_edit ? 'Perbarui identitas, kondisi, atau foto unit ini.' : 'Catat unit alat berat baru ke dalam inventaris.' ?></p>
  </div>
</div>

<?php if ($errors): ?>
  <div class="toast toast-error" role="alert">
    <?= icon('alert', 18) ?>
    <span><?= isset($errors['umum']) ? e($errors['umum']) : 'Periksa kembali isian yang ditandai merah.' ?></span>
  </div>
<?php endif; ?>

<?php if (!$kategori): ?>
  <div class="toast toast-error" role="alert"><?= icon('alert', 18) ?><span>Belum ada kategori. <a class="link" href="kategori.php">Buat kategori</a> terlebih dahulu.</span></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="unit-form" novalidate
      action="alat_form.php<?= $mode_edit ? '?id=' . $id : '' ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="MAX_FILE_SIZE" value="<?= MAX_UPLOAD ?>">

  <div class="form-main">
    <section class="panel">
      <h2 class="section-title">Identitas unit</h2>
      <div class="form-grid">
        <label class="field<?= kelas_error($errors, 'kode_unit') ?>">
          <span class="field-label">Kode unit</span>
          <input type="text" name="kode_unit" value="<?= e($data['kode_unit']) ?>" maxlength="20" placeholder="EX-2101" required>
          <?= pesan_error($errors, 'kode_unit') ?>
        </label>
        <label class="field<?= kelas_error($errors, 'id_kategori') ?>">
          <span class="field-label">Kategori</span>
          <span class="select">
            <select name="id_kategori" required>
              <option value="">Pilih kategori</option>
              <?php foreach ($kategori as $k): ?>
                <option value="<?= (int) $k['id_kategori'] ?>" <?= (int) $data['id_kategori'] === (int) $k['id_kategori'] ? 'selected' : '' ?>><?= e($k['nama_kategori']) ?></option>
              <?php endforeach; ?>
            </select>
          </span>
          <?= pesan_error($errors, 'id_kategori') ?>
        </label>
        <label class="field span-2<?= kelas_error($errors, 'nama_alat') ?>">
          <span class="field-label">Nama / tipe alat</span>
          <input type="text" name="nama_alat" value="<?= e($data['nama_alat']) ?>" maxlength="120" placeholder="PC2000-8 Hydraulic Excavator" required>
          <?= pesan_error($errors, 'nama_alat') ?>
        </label>
        <label class="field<?= kelas_error($errors, 'merek') ?>">
          <span class="field-label">Merek</span>
          <input type="text" name="merek" value="<?= e($data['merek']) ?>" maxlength="60" list="daftar-merek" placeholder="Komatsu" required>
          <datalist id="daftar-merek">
            <?php foreach (['Komatsu', 'Caterpillar', 'Hitachi', 'Volvo', 'Liebherr', 'Sany', 'Epiroc', 'Scania'] as $m): ?>
              <option value="<?= e($m) ?>">
            <?php endforeach; ?>
          </datalist>
          <?= pesan_error($errors, 'merek') ?>
        </label>
        <label class="field<?= kelas_error($errors, 'nomor_seri') ?>">
          <span class="field-label">Nomor seri</span>
          <input type="text" name="nomor_seri" value="<?= e($data['nomor_seri']) ?>" maxlength="40" placeholder="KMT-PC2000-30412" required>
          <?= pesan_error($errors, 'nomor_seri') ?>
        </label>
      </div>
    </section>

    <section class="panel">
      <h2 class="section-title">Operasional</h2>
      <div class="form-grid">
        <label class="field span-2<?= kelas_error($errors, 'lokasi') ?>">
          <span class="field-label">Lokasi kerja</span>
          <input type="text" name="lokasi" value="<?= e($data['lokasi']) ?>" maxlength="100" placeholder="Pit Utara - Blok A" required>
          <?= pesan_error($errors, 'lokasi') ?>
        </label>
        <label class="field<?= kelas_error($errors, 'jam_operasi') ?>">
          <span class="field-label">Hour meter (jam)</span>
          <input type="number" name="jam_operasi" value="<?= e($data['jam_operasi']) ?>" min="0" max="500000" step="1" inputmode="numeric" required>
          <?= pesan_error($errors, 'jam_operasi') ?>
        </label>
        <label class="field<?= kelas_error($errors, 'tanggal_perolehan') ?>">
          <span class="field-label">Tanggal perolehan</span>
          <input type="date" name="tanggal_perolehan" value="<?= e($data['tanggal_perolehan']) ?>" min="1950-01-01" max="<?= date('Y-m-d') ?>" required>
          <?= pesan_error($errors, 'tanggal_perolehan') ?>
        </label>

        <fieldset class="field span-2<?= kelas_error($errors, 'kepemilikan') ?>">
          <legend class="field-label">Kepemilikan</legend>
          <div class="radio-row">
            <?php foreach (daftar_kepemilikan() as $opsi): ?>
              <label class="radio">
                <input type="radio" name="kepemilikan" value="<?= e($opsi) ?>" <?= $data['kepemilikan'] === $opsi ? 'checked' : '' ?>>
                <span><?= e($opsi) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
          <?= pesan_error($errors, 'kepemilikan') ?>
        </fieldset>

        <fieldset class="field span-2<?= kelas_error($errors, 'status') ?>">
          <legend class="field-label">Kondisi unit</legend>
          <div class="status-picker">
            <?php foreach (daftar_status() as $opsi => $kelas): ?>
              <label class="status-opt status-<?= $kelas ?>">
                <input type="radio" name="status" value="<?= e($opsi) ?>" <?= $data['status'] === $opsi ? 'checked' : '' ?>>
                <span><span class="dot dot-<?= $kelas ?>"></span><?= e($opsi) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
          <?= pesan_error($errors, 'status') ?>
        </fieldset>
      </div>
    </section>

    <section class="panel">
      <h2 class="section-title">Catatan perawatan</h2>
      <label class="field">
        <span class="field-label">Catatan <span class="muted">(opsional)</span></span>
        <textarea name="catatan" rows="5" maxlength="2000" placeholder="Riwayat service, kerusakan, atau kebutuhan suku cadang"><?= e($data['catatan']) ?></textarea>
      </label>
    </section>
  </div>

  <aside class="form-side">
    <section class="panel">
      <h2 class="section-title">Foto unit</h2>
      <div class="dropzone<?= kelas_error($errors, 'foto') ?>" data-dropzone>
        <img src="<?= $data['foto'] ? 'uploads/' . e($data['foto']) : '' ?>" alt="Pratinjau foto unit" class="preview" data-preview <?= $data['foto'] ? '' : 'hidden' ?>>
        <div class="dz-empty" data-dz-empty <?= $data['foto'] ? 'hidden' : '' ?>>
          <?= icon('image', 28) ?>
          <span>Belum ada foto</span>
        </div>
      </div>
      <label class="btn btn-outline btn-block file-btn">
        <?= icon('upload', 18) ?> <span data-file-name><?= $data['foto'] ? 'Ganti foto' : 'Pilih foto' ?></span>
        <input type="file" name="foto" accept="image/jpeg,image/png,image/webp" data-file-input>
      </label>
      <p class="hint">JPG, PNG, atau WEBP. Maksimal 2 MB.</p>
      <?= pesan_error($errors, 'foto') ?>
      <?php if ($mode_edit && $data['foto']): ?>
        <label class="check">
          <input type="checkbox" name="hapus_foto" value="1" data-remove-photo>
          <span>Hapus foto saat disimpan</span>
        </label>
      <?php endif; ?>
    </section>

    <div class="form-actions">
      <button type="submit" class="btn btn-dark btn-block"><?= $mode_edit ? 'Simpan perubahan' : 'Simpan unit' ?></button>
      <a href="alat.php" class="btn btn-ghost btn-block">Batal</a>
    </div>
  </aside>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
