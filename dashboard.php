<?php
require_once __DIR__ . '/includes/init.php';
require_login();

$judul_halaman = 'Dashboard';
$menu_aktif = 'dashboard';

$total_unit = (int) scalar($conn, 'SELECT COUNT(*) FROM alat');
$total_kategori = (int) scalar($conn, 'SELECT COUNT(*) FROM kategori');
$total_jam = (int) scalar($conn, 'SELECT COALESCE(SUM(jam_operasi),0) FROM alat');
$unit_sewa = (int) scalar($conn, "SELECT COUNT(*) FROM alat WHERE kepemilikan = 'Sewa'");

// Hitung jumlah unit untuk setiap status (array + perulangan)
$jumlah_status = [];
foreach (daftar_status() as $status => $kelas) {
    $jumlah_status[$status] = 0;
}
$hasil = $conn->query('SELECT status, COUNT(*) AS jml FROM alat GROUP BY status');
while ($row = $hasil->fetch_assoc()) {
    $jumlah_status[$row['status']] = (int) $row['jml'];
}
$siap = $jumlah_status['Siap Operasi'];
$ketersediaan = $total_unit > 0 ? round($siap / $total_unit * 100) : 0;

$per_kategori = $conn->query(
    'SELECT k.nama_kategori, COUNT(a.id_alat) AS jml
     FROM kategori k LEFT JOIN alat a ON a.id_kategori = k.id_kategori
     GROUP BY k.id_kategori, k.nama_kategori
     ORDER BY jml DESC, k.nama_kategori'
)->fetch_all(MYSQLI_ASSOC);
$maks_kategori = 1;
foreach ($per_kategori as $k) {
    $maks_kategori = max($maks_kategori, (int) $k['jml']);
}

$perhatian = $conn->query(
    "SELECT a.id_alat, a.kode_unit, a.nama_alat, a.lokasi, a.status, k.nama_kategori
     FROM alat a JOIN kategori k ON k.id_kategori = a.id_kategori
     WHERE a.status IN ('Perbaikan','Rusak')
     ORDER BY FIELD(a.status,'Rusak','Perbaikan'), a.kode_unit"
)->fetch_all(MYSQLI_ASSOC);

$terbaru = $conn->query(
    'SELECT a.id_alat, a.kode_unit, a.nama_alat, a.merek, a.status, a.created_at, k.nama_kategori
     FROM alat a JOIN kategori k ON k.id_kategori = a.id_kategori
     ORDER BY a.created_at DESC, a.id_alat DESC LIMIT 5'
)->fetch_all(MYSQLI_ASSOC);

require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
  <div>
    <h1>Dashboard</h1>
    <p class="muted">Ringkasan kesiapan armada per <?= e(format_tanggal(date('Y-m-d'))) ?>.</p>
  </div>
  <div class="head-actions">
    <a href="alat.php" class="btn btn-outline"><?= icon('truck', 18) ?> Lihat semua unit</a>
    <a href="alat_form.php" class="btn btn-dark"><?= icon('plus', 18) ?> Tambah unit</a>
  </div>
</div>

<section class="panel availability">
  <div class="avail-top">
    <div class="avail-figure">
      <span class="avail-number"><?= $ketersediaan ?><small>%</small></span>
      <span class="muted">unit siap operasi (<?= $siap ?> dari <?= $total_unit ?>)</span>
    </div>
    <dl class="avail-facts">
      <div><dt>Total unit</dt><dd><?= format_angka($total_unit) ?></dd></div>
      <div><dt>Kategori</dt><dd><?= format_angka($total_kategori) ?></dd></div>
      <div><dt>Unit sewa</dt><dd><?= format_angka($unit_sewa) ?></dd></div>
      <div><dt>Total hour meter</dt><dd><?= format_angka($total_jam) ?> <small>jam</small></dd></div>
    </dl>
  </div>

  <div class="statusbar" role="img" aria-label="Komposisi status unit">
    <?php foreach ($jumlah_status as $status => $jml): ?>
      <?php if ($jml > 0): ?>
        <span class="seg seg-<?= daftar_status()[$status] ?>" style="flex-grow: <?= $jml ?>" title="<?= e($status) ?>: <?= $jml ?> unit"></span>
      <?php endif; ?>
    <?php endforeach; ?>
    <?php if ($total_unit === 0): ?><span class="seg seg-empty" style="flex-grow:1"></span><?php endif; ?>
  </div>
  <ul class="legend">
    <?php foreach ($jumlah_status as $status => $jml): ?>
      <li>
        <a href="alat.php?status=<?= urlencode($status) ?>">
          <span class="dot dot-<?= daftar_status()[$status] ?>"></span>
          <?= e($status) ?> <strong><?= $jml ?></strong>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
</section>

<div class="grid-2">
  <section class="panel">
    <div class="panel-head">
      <h2>Unit per kategori</h2>
      <a href="kategori.php" class="link">Kelola kategori</a>
    </div>
    <?php if (!$per_kategori): ?>
      <p class="empty">Belum ada kategori. <a href="kategori.php" class="link">Buat kategori pertama</a>.</p>
    <?php else: ?>
      <ul class="bars">
        <?php foreach ($per_kategori as $k): ?>
          <li>
            <span class="bars-label"><?= e($k['nama_kategori']) ?></span>
            <span class="bars-track"><span style="width: <?= round($k['jml'] / $maks_kategori * 100) ?>%"></span></span>
            <span class="bars-val"><?= (int) $k['jml'] ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>

  <section class="panel">
    <div class="panel-head">
      <h2>Perlu perhatian workshop</h2>
      <span class="count-tag"><?= count($perhatian) ?> unit</span>
    </div>
    <?php if (!$perhatian): ?>
      <p class="empty">Tidak ada unit yang sedang diperbaiki atau rusak.</p>
    <?php else: ?>
      <ul class="watchlist">
        <?php foreach ($perhatian as $p): ?>
          <li>
            <a href="alat_form.php?id=<?= (int) $p['id_alat'] ?>">
              <span class="unit-code"><?= e($p['kode_unit']) ?></span>
              <span class="watch-main">
                <strong><?= e($p['nama_alat']) ?></strong>
                <small><?= icon('pin', 13) ?> <?= e($p['lokasi']) ?></small>
              </span>
              <?= badge_status($p['status']) ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>
</div>

<section class="panel">
  <div class="panel-head">
    <h2>Baru dicatat</h2>
    <a href="alat.php" class="link">Semua unit</a>
  </div>
  <?php if (!$terbaru): ?>
    <p class="empty">Belum ada unit tercatat. <a href="alat_form.php" class="link">Tambah unit pertama</a>.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Kode unit</th><th>Nama alat</th><th>Kategori</th><th>Merek</th><th>Status</th><th>Dicatat</th></tr></thead>
        <tbody>
          <?php foreach ($terbaru as $t): ?>
            <tr>
              <td><span class="unit-code"><?= e($t['kode_unit']) ?></span></td>
              <td><?= e($t['nama_alat']) ?></td>
              <td><?= e($t['nama_kategori']) ?></td>
              <td><?= e($t['merek']) ?></td>
              <td><?= badge_status($t['status']) ?></td>
              <td class="muted"><?= e(format_tanggal(substr($t['created_at'], 0, 10))) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
