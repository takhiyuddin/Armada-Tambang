<?php
require_once __DIR__ . '/includes/init.php';
require_login();

$judul_halaman = 'Kategori alat';
$menu_aktif = 'kategori';

$edit_id = (int) input($_GET, 'edit');
$form = ['nama_kategori' => '', 'deskripsi' => ''];
$errors = [];

if ($edit_id > 0) {
    $stmt = $conn->prepare('SELECT nama_kategori, deskripsi FROM kategori WHERE id_kategori = ?');
    $stmt->bind_param('i', $edit_id);
    $stmt->execute();
    $lama = $stmt->get_result()->fetch_assoc();
    if (!$lama) {
        set_flash('error', 'Kategori tidak ditemukan.');
        redirect('kategori.php');
    }
    $form = ['nama_kategori' => $lama['nama_kategori'], 'deskripsi' => (string) $lama['deskripsi']];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['nama_kategori'] = input($_POST, 'nama_kategori');
    $form['deskripsi'] = input($_POST, 'deskripsi');

    if (!csrf_valid()) {
        $errors['umum'] = 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu kirim lagi.';
    } elseif ($form['nama_kategori'] === '') {
        $errors['nama_kategori'] = 'Nama kategori wajib diisi.';
    } elseif (mb_strlen($form['nama_kategori']) > 80) {
        $errors['nama_kategori'] = 'Nama kategori maksimal 80 karakter.';
    } else {
        $stmt = $conn->prepare('SELECT id_kategori FROM kategori WHERE nama_kategori = ? AND id_kategori <> ?');
        $stmt->bind_param('si', $form['nama_kategori'], $edit_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors['nama_kategori'] = 'Kategori "' . $form['nama_kategori'] . '" sudah ada.';
        }
    }

    if (!$errors) {
        $deskripsi = $form['deskripsi'] === '' ? null : $form['deskripsi'];
        if ($edit_id > 0) {
            $stmt = $conn->prepare('UPDATE kategori SET nama_kategori = ?, deskripsi = ? WHERE id_kategori = ?');
            $stmt->bind_param('ssi', $form['nama_kategori'], $deskripsi, $edit_id);
            $stmt->execute();
            set_flash('ok', 'Kategori ' . $form['nama_kategori'] . ' diperbarui.');
        } else {
            $stmt = $conn->prepare('INSERT INTO kategori (nama_kategori, deskripsi) VALUES (?, ?)');
            $stmt->bind_param('ss', $form['nama_kategori'], $deskripsi);
            $stmt->execute();
            set_flash('ok', 'Kategori ' . $form['nama_kategori'] . ' ditambahkan.');
        }
        redirect('kategori.php');
    }
}

$daftar = $conn->query(
    'SELECT k.id_kategori, k.nama_kategori, k.deskripsi, COUNT(a.id_alat) AS jml
     FROM kategori k LEFT JOIN alat a ON a.id_kategori = k.id_kategori
     GROUP BY k.id_kategori, k.nama_kategori, k.deskripsi
     ORDER BY k.nama_kategori'
)->fetch_all(MYSQLI_ASSOC);

require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
  <div>
    <h1>Kategori alat</h1>
    <p class="muted">Jenis alat berat yang dipakai untuk mengelompokkan unit. Kategori yang masih dipakai unit tidak bisa dihapus.</p>
  </div>
</div>

<div class="split">
  <section class="panel">
    <h2 class="section-title"><?= $edit_id ? 'Edit kategori' : 'Kategori baru' ?></h2>
    <?php if (isset($errors['umum'])): ?>
      <div class="toast toast-error" role="alert"><?= icon('alert', 18) ?><span><?= e($errors['umum']) ?></span></div>
    <?php endif; ?>
    <form method="post" action="kategori.php<?= $edit_id ? '?edit=' . $edit_id : '' ?>" class="stack" novalidate>
      <?= csrf_field() ?>
      <label class="field<?= isset($errors['nama_kategori']) ? ' has-error' : '' ?>">
        <span class="field-label">Nama kategori</span>
        <input type="text" name="nama_kategori" value="<?= e($form['nama_kategori']) ?>" maxlength="80" placeholder="Excavator" required>
        <?php if (isset($errors['nama_kategori'])): ?><span class="field-error"><?= e($errors['nama_kategori']) ?></span><?php endif; ?>
      </label>
      <label class="field">
        <span class="field-label">Deskripsi <span class="muted">(opsional)</span></span>
        <textarea name="deskripsi" rows="4" maxlength="1000" placeholder="Fungsi alat di area tambang"><?= e($form['deskripsi']) ?></textarea>
      </label>
      <div class="btn-row">
        <button type="submit" class="btn btn-dark"><?= $edit_id ? 'Simpan perubahan' : 'Tambah kategori' ?></button>
        <?php if ($edit_id): ?><a href="kategori.php" class="btn btn-ghost">Batal</a><?php endif; ?>
      </div>
    </form>
  </section>

  <section class="panel table-panel">
    <?php if (!$daftar): ?>
      <div class="empty-state">
        <?= icon('layers', 32) ?>
        <h2>Belum ada kategori</h2>
        <p class="muted">Isi form di samping untuk membuat kategori pertama.</p>
      </div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>Kategori</th><th class="num">Unit</th><th><span class="sr-only">Aksi</span></th></tr></thead>
          <tbody>
            <?php foreach ($daftar as $k): ?>
              <tr class="<?= (int) $k['id_kategori'] === $edit_id ? 'is-editing' : '' ?>">
                <td>
                  <strong><?= e($k['nama_kategori']) ?></strong>
                  <?php if ($k['deskripsi']): ?><small class="desc"><?= e($k['deskripsi']) ?></small><?php endif; ?>
                </td>
                <td class="num">
                  <?php if ($k['jml'] > 0): ?>
                    <a class="link" href="alat.php?kategori=<?= (int) $k['id_kategori'] ?>"><?= (int) $k['jml'] ?></a>
                  <?php else: ?>
                    <span class="muted">0</span>
                  <?php endif; ?>
                </td>
                <td class="actions">
                  <div class="rowmenu" data-dropdown>
                    <button type="button" class="icon-btn" data-dropdown-toggle aria-expanded="false" aria-label="Aksi untuk <?= e($k['nama_kategori']) ?>"><?= icon('more', 18) ?></button>
                    <div class="dropdown" role="menu">
                      <a href="kategori.php?edit=<?= (int) $k['id_kategori'] ?>" role="menuitem"><?= icon('pencil', 17) ?> Edit kategori</a>
                      <form method="post" action="kategori_hapus.php" data-confirm="Kategori <?= e($k['nama_kategori']) ?> akan dihapus permanen.">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $k['id_kategori'] ?>">
                        <button type="submit" role="menuitem" class="danger"><?= icon('trash', 17) ?> Hapus kategori</button>
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
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
