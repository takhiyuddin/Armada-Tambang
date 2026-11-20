    </main>
    <footer class="foot">
      Armada Tambang &mdash; Taqiyuddin 'Afif (25.12.3506)
    </footer>
  </div>
</div>

<!-- Dialog konfirmasi hapus (dipakai oleh semua tombol hapus) -->
<div class="modal" id="confirmModal" hidden>
  <div class="modal-card" role="alertdialog" aria-modal="true" aria-labelledby="confirmTitle">
    <span class="modal-icon"><?= icon('trash', 22) ?></span>
    <h2 id="confirmTitle">Hapus data ini?</h2>
    <p id="confirmText">Data yang dihapus tidak bisa dikembalikan.</p>
    <div class="modal-actions">
      <button type="button" class="btn btn-ghost" data-modal-cancel>Batal</button>
      <button type="button" class="btn btn-danger" data-modal-ok>Ya, hapus</button>
    </div>
  </div>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>
