Armada Tambang
Sistem Manajemen Inventaris Alat Berat & Tambang
Proyek UTS PWL - Taqiyuddin 'Afif - 25.12.3506 - S1SI 02

Cara menjalankan (XAMPP)
1. Salin folder ini ke C:\xampp\htdocs\
2. Jalankan Apache dan MySQL dari XAMPP Control Panel.
3. Buka http://localhost/phpmyadmin, pilih tab Import, lalu impor file
   database/alatberat_3506.sql (database alatberat_3506 dibuat otomatis).
4. Buka http://localhost/25.12.3506_Taqiyuddin_Afif_S1SI02/
5. Masuk dengan username: admin  password: admin

Bila MySQL memakai password atau port lain, ubah config/koneksi.php.

Struktur folder
  index.php            mengarahkan ke login / dashboard
  login.php            form login (session)
  logout.php           mengakhiri session, kembali ke login.php
  dashboard.php        ringkasan kesiapan armada
  alat.php             daftar unit: cari, filter, pagination
  alat_form.php        tambah & edit unit (text, textarea, radio, date, file)
  alat_hapus.php       hapus unit beserta fotonya (POST)
  alat_export.php      ekspor daftar unit ke CSV
  kategori.php         tambah, edit, daftar kategori
  kategori_hapus.php   hapus kategori (ditolak bila masih dipakai)
  config/koneksi.php   konfigurasi koneksi database
  includes/            init, functions, header, footer
  assets/css, assets/js
  uploads/             foto unit hasil upload
  database/alatberat_3506.sql
