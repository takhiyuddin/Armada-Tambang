// Interaksi kecil untuk Armada Tambang (tanpa library tambahan)
(function () {
  "use strict";

  // ---- Dropdown (menu pengguna dan menu aksi tiap baris) ----
  function closeAllDropdowns(except) {
    document.querySelectorAll("[data-dropdown].is-open").forEach(function (dd) {
      if (dd !== except) {
        dd.classList.remove("is-open");
        var t = dd.querySelector("[data-dropdown-toggle]");
        if (t) t.setAttribute("aria-expanded", "false");
      }
    });
  }

  document.addEventListener("click", function (ev) {
    var toggle = ev.target.closest("[data-dropdown-toggle]");
    if (toggle) {
      var dd = toggle.closest("[data-dropdown]");
      var open = !dd.classList.contains("is-open");
      closeAllDropdowns(dd);
      dd.classList.toggle("is-open", open);
      toggle.setAttribute("aria-expanded", open ? "true" : "false");
      return;
    }
    if (!ev.target.closest(".dropdown")) closeAllDropdowns(null);
  });

  document.addEventListener("keydown", function (ev) {
    if (ev.key === "Escape") {
      closeAllDropdowns(null);
      hideModal();
    }
  });

  // ---- Konfirmasi hapus dengan dialog sendiri ----
  var modal = document.getElementById("confirmModal");
  var pendingForm = null;

  function hideModal() {
    if (modal && !modal.hidden) {
      modal.hidden = true;
      pendingForm = null;
    }
  }

  document.addEventListener("submit", function (ev) {
    var form = ev.target;
    if (!form.matches("[data-confirm]") || form.dataset.confirmed === "1")
      return;
    ev.preventDefault();
    closeAllDropdowns(null);
    if (!modal) {
      if (window.confirm(form.dataset.confirm)) {
        form.dataset.confirmed = "1";
        form.submit();
      }
      return;
    }
    pendingForm = form;
    document.getElementById("confirmText").textContent =
      form.dataset.confirm + " Tindakan ini tidak bisa dibatalkan.";
    modal.hidden = false;
    modal.querySelector("[data-modal-ok]").focus();
  });

  if (modal) {
    modal
      .querySelector("[data-modal-cancel]")
      .addEventListener("click", hideModal);
    modal.addEventListener("click", function (ev) {
      if (ev.target === modal) hideModal();
    });
    modal
      .querySelector("[data-modal-ok]")
      .addEventListener("click", function () {
        if (!pendingForm) return;
        var form = pendingForm;
        form.dataset.confirmed = "1";
        this.disabled = true;
        this.textContent = "Menghapus...";
        form.submit();
      });
  }

  // ---- Notifikasi hilang otomatis ----
  document.querySelectorAll("[data-toast]").forEach(function (toast) {
    function hide() {
      toast.classList.add("is-hiding");
      setTimeout(function () {
        toast.remove();
      }, 300);
    }
    var btn = toast.querySelector("[data-toast-close]");
    if (btn) btn.addEventListener("click", hide);
    if (toast.classList.contains("toast-ok")) setTimeout(hide, 4500);
  });

  // ---- Tampilkan / sembunyikan password ----
  document.querySelectorAll("[data-toggle-password]").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var input = document.getElementById(btn.dataset.togglePassword);
      var show = input.type === "password";
      input.type = show ? "text" : "password";
      btn.classList.toggle("is-on", show);
      btn.setAttribute(
        "aria-label",
        show ? "Sembunyikan password" : "Tampilkan password",
      );
    });
  });

  // ---- Pratinjau foto sebelum diunggah ----
  var fileInput = document.querySelector("[data-file-input]");
  if (fileInput) {
    var zone = document.querySelector("[data-dropzone]");
    var preview = zone.querySelector("[data-preview]");
    var empty = zone.querySelector("[data-dz-empty]");
    var nameEl = document.querySelector("[data-file-name]");
    var removeBox = document.querySelector("[data-remove-photo]");
    var originalSrc = preview.getAttribute("src");
    var MAX = 2 * 1024 * 1024;
    var allowed = ["image/jpeg", "image/png", "image/webp"];

    function showFile(file) {
      if (!file) {
        nameEl.textContent = originalSrc ? "Ganti foto" : "Pilih foto";
        if (originalSrc) {
          preview.src = originalSrc;
          preview.hidden = false;
          empty.hidden = true;
        } else {
          preview.hidden = true;
          empty.hidden = false;
        }
        return;
      }
      if (allowed.indexOf(file.type) === -1) {
        alert("Foto harus berformat JPG, PNG, atau WEBP.");
        fileInput.value = "";
        return showFile(null);
      }
      if (file.size > MAX) {
        alert("Ukuran foto melebihi 2 MB. Pilih file yang lebih kecil.");
        fileInput.value = "";
        return showFile(null);
      }
      nameEl.textContent = file.name;
      var reader = new FileReader();
      reader.onload = function (e) {
        preview.src = e.target.result;
        preview.hidden = false;
        empty.hidden = true;
      };
      reader.readAsDataURL(file);
      if (removeBox) {
        removeBox.checked = false;
        zone.classList.remove("is-removed");
      }
    }

    fileInput.addEventListener("change", function () {
      showFile(fileInput.files[0]);
    });

    ["dragenter", "dragover"].forEach(function (t) {
      zone.addEventListener(t, function (e) {
        e.preventDefault();
        zone.classList.add("is-drag");
      });
    });
    ["dragleave", "drop"].forEach(function (t) {
      zone.addEventListener(t, function (e) {
        e.preventDefault();
        zone.classList.remove("is-drag");
      });
    });
    zone.addEventListener("drop", function (e) {
      if (e.dataTransfer.files.length) {
        fileInput.files = e.dataTransfer.files;
        showFile(fileInput.files[0]);
      }
    });

    if (removeBox) {
      removeBox.addEventListener("change", function () {
        zone.classList.toggle("is-removed", removeBox.checked);
        if (removeBox.checked) {
          fileInput.value = "";
          showFile(null);
        }
      });
    }
  }

  // ---- Ganti jumlah baris langsung tanpa klik tombol ----
  document.querySelectorAll("[data-submit-on-change]").forEach(function (sel) {
    sel.addEventListener("change", function () {
      sel.form.submit();
    });
  });
  document.querySelectorAll("[data-autosubmit] select").forEach(function (sel) {
    sel.addEventListener("change", function () {
      sel.form.submit();
    });
  });
})();
