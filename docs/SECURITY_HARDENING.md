# Catatan Penguatan Keamanan CBT

## Perubahan pada branch ini

- Login guru dan siswa mencari akun berdasarkan identitas, bukan dengan mencocokkan kata sandi pada query database.
- Alur login admin, guru, dan siswa memverifikasi hash kata sandi, dan mendukung migrasi akun lama dari teks biasa ke `password_hash()` setelah autentikasi berhasil.
- Reset dan perubahan kata sandi siswa menyimpan hash; reset pemulihan memakai otorisasi sesi server yang kedaluwarsa setelah lima menit.
- ID sesi dirotasi setelah login berhasil.
- Permintaan login dibatasi ke metode POST dan nilai peran yang dikenali.
- Pesan kegagalan login dibuat generik untuk mengurangi kebocoran informasi tentang keberadaan akun.

## Risiko dan pekerjaan yang belum dianggap selesai

Daftar ini harus diselesaikan dan diuji sebelum sistem digunakan untuk ujian resmi.

- [ ] Audit semua controller untuk otorisasi peran di sisi server; menyembunyikan menu bukan kontrol akses.
- [ ] Audit setiap query mentah dan concatenation SQL, terutama ID kelas, guru, soal, ujian, dan hasil yang berasal dari request.
- [ ] Aktifkan CSRF protection dan verifikasi semua form/AJAX termasuk alur ujian.
- [ ] Audit output HTML untuk escaping XSS dan sanitasi konten soal.
- [ ] Terapkan pembatasan percobaan login dan pencatatan aktivitas yang tidak menyimpan kata sandi atau token rahasia.
- [ ] Audit unggahan gambar/media: tipe MIME, ukuran, nama acak, lokasi penyimpanan, dan larangan eksekusi file.
- [ ] Tetapkan cookie session `HttpOnly`, `Secure` saat HTTPS, dan `SameSite` sesuai kebutuhan.
- [ ] Periksa panjang kolom password agar dapat menyimpan hash `PASSWORD_DEFAULT` (gunakan VARCHAR(255)).
- [ ] Pastikan jawaban benar dan kunci penilaian tidak dikirim ke browser peserta.
- [ ] Jadikan timer server sebagai sumber kebenaran; tolak simpan jawaban setelah batas waktu dan buat submit idempoten.
- [ ] Simpan jawaban secara transaksional dan uji pemulihan setelah koneksi terputus atau refresh halaman.
- [ ] Tambahkan pengujian unit/integrasi untuk autentikasi, otorisasi, autosave, penilaian, dan ekspor.
- [ ] Jalankan pemeriksaan sintaks PHP dan uji pada salinan database staging sebelum merge.
- [ ] Lakukan uji beban yang sesuai jumlah peserta dan uji backup/restore.
- [ ] Tinjau lisensi dan versi semua dependensi sebelum deployment.

## Catatan penting

Branch ini adalah perubahan bertahap, bukan pernyataan bahwa semua risiko sudah tertangani. Jangan menggabungkan ke branch produksi atau menggunakannya untuk ujian resmi sebelum seluruh perubahan diuji pada lingkungan staging.
