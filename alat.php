<?php
require_once __DIR__ . '/includes/init.php';
require_login();

$judul_halaman = 'Data alat';
$menu_aktif = 'alat';

// Ambil filter dari URL
$q = input($_GET, 'q');
$f_status = input($_GET, 'status');
$f_kategori = (int) input($_GET, 'kategori');
$pilihan_tampil = [5, 7, 10, 20];
$per_halaman = (int) input($_GET, 'tampil');
if (!in_array($per_halaman, $pilihan_tampil, true)) {
    $per_halaman = 7;
}
if (!array_key_exists($f_status, daftar_status())) {
    $f_status = '';
}

// Susun kondisi WHERE secara dinamis dengan prepared statement
$kondisi = [];
$tipe = '';
$nilai = [];
if ($q !== '') {
    $kondisi[] = '(a.kode_unit LIKE ? OR a.nama_alat LIKE ? OR a.merek LIKE ? OR a.lokasi LIKE ? OR a.nomor_seri LIKE ?)';
    $cari = '%' . $q . '%';
    for ($i = 0; $i < 5; $i++) {
        $tipe .= 's';
        $nilai[] = $cari;
    }
}
if ($f_status !== '') {
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

// Hitung total data untuk pagination
$stmt = $conn->prepare("SELECT COUNT(*) FROM alat a $where");
if ($tipe !== '') {
    $stmt->bind_param($tipe, ...$nilai);
}
$stmt->execute();
$total = (int) $stmt->get_result()->fetch_row()[0];

$jumlah_halaman = max(1, (int) ceil($total / $per_halaman));
$halaman = (int) input($_GET, 'hal');
$halaman = min(max(1, $halaman), $jumlah_halaman);
$offset = ($halaman - 1) * $per_halaman;

$stmt = $conn->prepare(
    "SELECT a.*, k.nama_kategori
     FROM alat a JOIN kategori k ON k.id_kategori = a.id_kategori
     $where
     ORDER BY a.kode_unit ASC
     LIMIT ? OFFSET ?"
);
$tipe_data = $tipe . 'ii';
$nilai_data = array_merge($nilai, [$per_halaman, $offset]);
$stmt->bind_param($tipe_data, ...$nilai_data);
$stmt->execute();
$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$kategori = $conn->query('SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori')->fetch_all(MYSQLI_ASSOC);

// Nomor halaman yang ditampilkan (maks 5 di sekitar halaman aktif)
$awal = max(1, $halaman - 2);
$akhir = min($jumlah_halaman, $awal + 4);
$awal = max(1, $akhir - 4);

require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
  <div>
    <h1>Data alat</h1>
    <p class="muted">Lihat dan kelola seluruh unit alat berat, mulai dari lokasi kerja sampai catatan perawatan.</p>
  </div>
  <div class="head-actions">
    <a href="<?= e(url_dengan('alat_export.php', ['hal' => '', 'tampil' => ''])) ?>" class="btn btn-outline"><?= icon('export', 18) ?> Ekspor CSV</a>
    <a href="alat_form.php" class="btn btn-dark"><?= icon('plus', 18) ?> Tambah unit</a>
  </div>
</div>

<form class="filters" method="get" action="alat.php" data-autosubmit>
  <label class="search">
    <?= icon('search', 18) ?>
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Cari kode, nama, merek, lokasi" aria-label="Cari unit">
  </label>
  <label class="select">
    <span class="sr-only">Kategori</span>
    <select name="kategori" aria-label="Filter kategori">
      <option value="">Semua kategori</option>
      <?php foreach ($kategori as $k): ?>
        <option value="<?= (int) $k['id_kategori'] ?>" <?= $f_kategori === (int) $k['id_kategori'] ? 'selected' : '' ?>><?= e($k['nama_kategori']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label class="select">
    <span class="sr-only">Status</span>
    <select name="status" aria-label="Filter status">
      <option value="">Semua status</option>
      <?php foreach (daftar_status() as $s => $kelas): ?>
        <option value="<?= e($s) ?>" <?= $f_status === $s ? 'selected' : '' ?>><?= e($s) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <input type="hidden" name="tampil" value="<?= $per_halaman ?>">
  <button type="submit" class="btn btn-outline">Terapkan</button>
  <?php if ($q !== '' || $f_status !== '' || $f_kategori > 0): ?>
    <a href="alat.php" class="link">Hapus filter</a>
  <?php endif; ?>
</form>

<section class="panel table-panel">
  <?php if (!$data): ?>
    <div class="empty-state">
      <?= icon('truck', 32) ?>
      <?php if ($q !== '' || $f_status !== '' || $f_kategori > 0): ?>
        <h2>Tidak ada unit yang cocok</h2>
        <p class="muted">Ubah kata kunci atau filter, atau <a class="link" href="alat.php">tampilkan semua unit</a>.</p>
      <?php else: ?>
        <h2>Belum ada unit tercatat</h2>
        <p class="muted">Mulai dengan menambahkan unit alat berat pertama.</p>
        <a href="alat_form.php" class="btn btn-dark"><?= icon('plus', 18) ?> Tambah unit</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table table-units">
        <thead>
          <tr>
            <th>Unit</th>
            <th>Kode</th>
            <th>Kategori</th>
            <th>Lokasi</th>
            <th class="num">Hour meter</th>
            <th>Status</th>
            <th>Kepemilikan</th>
            <th>Diperoleh</th>
            <th><span class="sr-only">Aksi</span></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($data as $row): ?>
            <tr>
              <td>
                <div class="unit-cell">
                  <?php if ($row['foto']): ?>
                    <img src="uploads/<?= e($row['foto']) ?>" alt="" class="thumb" loading="lazy">
                  <?php else: ?>
                    <span class="thumb thumb-empty"><?= icon('truck', 18) ?></span>
                  <?php endif; ?>
                  <span>
                    <strong><?= e($row['nama_alat']) ?></strong>
                    <small><?= e($row['merek']) ?> &middot; SN <?= e($row['nomor_seri']) ?></small>
                  </span>
                </div>
              </td>
              <td><span class="unit-code"><?= e($row['kode_unit']) ?></span></td>
              <td><?= e($row['nama_kategori']) ?></td>
              <td><?= e($row['lokasi']) ?></td>
              <td class="num"><?= format_angka($row['jam_operasi']) ?></td>
              <td><?= badge_status($row['status']) ?></td>
              <td><?= e($row['kepemilikan']) ?></td>
              <td class="muted nowrap"><?= e(format_tanggal($row['tanggal_perolehan'])) ?></td>
              <td class="actions">
                <div class="rowmenu" data-dropdown>
                  <button type="button" class="icon-btn" data-dropdown-toggle aria-expanded="false" aria-label="Aksi untuk <?= e($row['kode_unit']) ?>"><?= icon('more', 18) ?></button>
                  <div class="dropdown" role="menu">
                    <a href="alat_form.php?id=<?= (int) $row['id_alat'] ?>" role="menuitem"><?= icon('pencil', 17) ?> Edit unit</a>
                    <form method="post" action="alat_hapus.php" data-confirm="Unit <?= e($row['kode_unit']) ?> (<?= e($row['nama_alat']) ?>) beserta fotonya akan dihapus permanen.">
                      <?= csrf_field() ?>
                      <input type="hidden" name="id" value="<?= (int) $row['id_alat'] ?>">
                      <button type="submit" role="menuitem" class="danger"><?= icon('trash', 17) ?> Hapus unit</button>
                    </form>
                  </div>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<div class="table-foot">
  <form method="get" action="alat.php" class="show-data">
    <?php foreach (['q' => $q, 'status' => $f_status, 'kategori' => $f_kategori ?: ''] as $k => $v): ?>
      <?php if ($v !== ''): ?><input type="hidden" name="<?= $k ?>" value="<?= e($v) ?>"><?php endif; ?>
    <?php endforeach; ?>
    <label for="tampil">Tampilkan</label>
    <span class="select select-sm">
      <select name="tampil" id="tampil" data-submit-on-change>
        <?php foreach ($pilihan_tampil as $n): ?>
          <option value="<?= $n ?>" <?= $n === $per_halaman ? 'selected' : '' ?>><?= $n ?></option>
        <?php endforeach; ?>
      </select>
    </span>
    <span class="muted"><?= $total ?> unit</span>
  </form>

  <?php if ($jumlah_halaman > 1): ?>
    <nav class="pager" aria-label="Halaman">
      <?php
      $sebelum = max(1, $halaman - 1);
      $sesudah = min($jumlah_halaman, $halaman + 1);
      ?>
      <a class="pg<?= $halaman === 1 ? ' is-disabled' : '' ?>" href="<?= e(url_dengan('alat.php', ['hal' => 1])) ?>" aria-label="Halaman pertama"><?= icon('chevs-l', 16) ?></a>
      <a class="pg<?= $halaman === 1 ? ' is-disabled' : '' ?>" href="<?= e(url_dengan('alat.php', ['hal' => $sebelum])) ?>" aria-label="Sebelumnya"><?= icon('chev-l', 16) ?></a>
      <?php for ($i = $awal; $i <= $akhir; $i++): ?>
        <a class="pg<?= $i === $halaman ? ' is-current' : '' ?>" href="<?= e(url_dengan('alat.php', ['hal' => $i])) ?>" <?= $i === $halaman ? 'aria-current="page"' : '' ?>><?= $i ?></a>
      <?php endfor; ?>
      <a class="pg<?= $halaman === $jumlah_halaman ? ' is-disabled' : '' ?>" href="<?= e(url_dengan('alat.php', ['hal' => $sesudah])) ?>" aria-label="Berikutnya"><?= icon('chev-r', 16) ?></a>
      <a class="pg<?= $halaman === $jumlah_halaman ? ' is-disabled' : '' ?>" href="<?= e(url_dengan('alat.php', ['hal' => $jumlah_halaman])) ?>" aria-label="Halaman terakhir"><?= icon('chevs-r', 16) ?></a>
    </nav>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
