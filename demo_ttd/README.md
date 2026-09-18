# SIMPEG - TTD Central Demo (PHP Native)

Prototype untuk memperagakan flow **SSO SIMPEG -> middleware TTD -> registrasi TTD + PIN -> pemakaian TTD -> QR verifikasi**.

## Tujuan arsitektur

SSO tetap menjadi sumber autentikasi. TTD tidak ditambahkan ke endpoint login SSO global agar aplikasi lain tidak terganggu. Middleware TTD diletakkan pada SIMPEG/demonstration layer:

1. Login ke BKK SSO 2026 menggunakan `app=simpeg`.
2. Ambil profil melalui `/api/auth/whoami`.
3. Middleware lokal mengecek tabel `ttd_profiles`.
4. Jika belum ada, user wajib gambar/upload TTD dan membuat PIN 6 digit.
5. PIN disimpan dengan `password_hash()`.
6. Ketika menandatangani dokumen, PIN diminta kembali.
7. Jika PIN benar, sistem membuat SHA-256, audit log, verification token, lalu merespons **TTD Pegawai + QR Verifikasi**.

## Instalasi cepat

1. Jalankan SQL `database/ttd_demo.sql` pada database yang sama yang dapat membaca master pegawai SSO.
2. Pastikan folder `demo_ttd/storage` dapat dibuat/ditulis oleh PHP.
3. Pastikan extension PHP cURL aktif. GD opsional; jika aktif, background putih TTD akan dicoba dibuat transparan.
4. Buka `/demo_ttd/`.
5. Login menggunakan akun SIMPEG yang valid pada BKK SSO.

## Catatan keamanan prototype

- Password SSO tidak disimpan.
- PIN TTD disimpan sebagai hash.
- Maksimum 5 PIN salah lalu lock 15 menit.
- ID pegawai selalu diambil dari session hasil SSO, bukan request form.
- Untuk produksi, file TTD sebaiknya dipindah ke private object storage / folder di luar webroot dan hanya dirender oleh Signature Service setelah otorisasi.
- Secret, CORS, cookie secure/httponly/samesite, CSRF, rate limit, TLS, log retention, dan mekanisme revocation perlu diperkeras sebelum produksi.
- QR pada demo berfungsi sebagai URL verifikasi audit internal. Ini **bukan** TTE tersertifikasi PSrE.

## Struktur

- `index.php` login SSO
- `middleware/ttd.php` gate TTD
- `register-ttd.php` canvas/upload + PIN
- `dashboard.php` status middleware
- `sign-demo.php` dokumen dummy
- `api/sign.php` validasi PIN + sign response
- `verify.php` halaman yang dibuka QR
- `database/ttd_demo.sql` schema demo
