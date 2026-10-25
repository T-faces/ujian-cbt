# Panduan Instalasi Ujian CBT (CodeIgniter 3)

Dokumen ini menjelaskan instalasi awal repositori **ujian-cbt** pada lingkungan lokal. Aplikasi ini adalah basis CodeIgniter 3 yang sudah ada; panduan ini tidak menyatakan bahwa seluruh spesifikasi CBT SMART EXAM modern telah selesai.

## 1. Persyaratan

- Apache dan MySQL/MariaDB (XAMPP atau Laragon untuk lokal).
- PHP 7.4 disarankan untuk menguji aplikasi lama ini terlebih dahulu. Jangan menganggap aplikasi kompatibel dengan PHP terbaru sebelum seluruh kode dan dependensi diuji.
- Ekstensi PHP MySQLi, session, mbstring, json.
- Browser modern.

> CodeIgniter 3 dan beberapa dependensi di repositori ini merupakan teknologi lama. Gunakan lingkungan uji terisolasi dan jangan langsung memasangnya pada server ujian produksi.

## 2. Pasang di XAMPP

1. Unduh atau clone repositori ke `C:\xampp\htdocs\ujian-cbt`.
2. Jalankan Apache dan MySQL dari XAMPP.
3. Buka phpMyAdmin di `http://localhost/phpmyadmin`.
4. Buat database bernama `cbt` (atau nama lain yang akan dipakai pada konfigurasi).
5. Impor berkas `cbt.sql` dari direktori utama repositori.
6. Buka `admin/application/config/database.php` dan sesuaikan `hostname`, `username`, `password`, dan `database`. Jangan menggunakan akun database root tanpa kata sandi pada server produksi.
7. Buka `admin/application/config/config.php`. Tetapkan `$config['base_url']` ke URL lokal yang benar, misalnya `http://localhost/ujian-cbt/admin/`. Hindari mengandalkan host dari header permintaan pada produksi.
8. Pastikan direktori cache dan log dapat ditulis oleh PHP, tetapi jangan membuat direktori aplikasi dapat ditulis secara umum.
9. Buka `http://localhost/ujian-cbt/admin/login` atau rute login yang dikonfigurasi. Jika rute tidak sesuai dengan struktur folder, periksa konfigurasi Apache dan `index_page`.

## 3. Login dan migrasi kata sandi

Sebelum memasang perubahan branch ini pada database lama, buat backup lalu jalankan `docs/migrations/2026-10-25-password-hash.sql` agar kolom password dapat menampung hash. Untuk instalasi baru, skema `cbt.sql` sudah memakai `VARCHAR(255)`.

Akun awal bergantung pada data yang ada di `cbt.sql`; jangan mengasumsikan kredensial contoh dari dokumentasi lama masih aman atau berlaku. Periksa isi SQL secara lokal dan segera ganti semua kata sandi contoh sebelum digunakan.

Pada branch `feature/cbt-smart-exam-hardening`, alur login admin, guru, dan siswa diperbarui agar kata sandi diverifikasi di aplikasi. Reset kata sandi siswa juga memerlukan verifikasi jawaban pemulihan yang disimpan dalam sesi server dengan masa berlaku lima menit. Jika akun lama masih menyimpan kata sandi teks biasa, kode mencoba mengubahnya menjadi hash setelah login berhasil. Proses ini hanya terjadi untuk akun yang berhasil login; tetap lakukan reset kata sandi dan audit database sebelum produksi. Pastikan kolom `password` cukup panjang untuk hash modern (minimal VARCHAR(255)); periksa skema sebelum deployment.

## 4. Konfigurasi sebelum digunakan

- Aktifkan HTTPS dan konfigurasi cookie session yang aman di lingkungan HTTPS.
- Pastikan `ENVIRONMENT` bernilai `production` pada server produksi sehingga detail error database tidak ditampilkan kepada pengguna.
- Aktifkan dan uji CSRF protection; periksa seluruh form dan permintaan AJAX agar mengirim token CSRF.
- Gunakan akun database khusus dengan hak minimum.
- Lindungi berkas konfigurasi, dump SQL, log, dan folder upload agar tidak dapat diakses langsung dari web.
- Hapus kredensial bawaan, nonaktifkan akun tidak terpakai, dan gunakan kata sandi unik.
- Siapkan backup database dan uji pemulihan backup.
- Jangan melakukan ujian resmi sebelum uji alur login, izin peran, autosave, timer server, submit ganda, pemutusan jaringan, penilaian, dan beban serentak.

## 5. Troubleshooting

- **Database connection error:** cek nama database dan kredensial di `admin/application/config/database.php`; pastikan MySQL berjalan.
- **404 / URL tidak ditemukan:** coba URL dengan `index.php`, periksa `.htaccess`, modul Apache `mod_rewrite`, dan konfigurasi `index_page`.
- **Redirect URL salah:** tetapkan `base_url` secara eksplisit untuk lokasi instalasi.
- **Login gagal:** periksa struktur tabel akun dan format kata sandi di `cbt.sql`; jangan mematikan verifikasi kata sandi untuk mengatasi masalah.
- **Session tidak tersimpan:** periksa konfigurasi session CodeIgniter, direktori penyimpanan, izin tulis, dan cookie browser.

## 6. Batasan saat ini

Panduan instalasi ini bukan sertifikasi kesiapan produksi. Fitur yang belum ditinjau atau diuji harus dianggap belum terverifikasi. Lihat `docs/SECURITY_HARDENING.md` untuk daftar pemeriksaan dan pekerjaan lanjutan.
