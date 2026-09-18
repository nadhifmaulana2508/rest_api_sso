# TTD IN BKK

Prototype aplikasi web PHP Native untuk TTD Digital Terpusat PT BPR BKK JATENG.

## Arsitektur
- Login consume BKK SSO 2026.
- Database TTD terpisah: `db_ttd_in_bkk`.
- Password pegawai tidak disimpan.
- Setelah login, middleware mengecek profil TTD dan PIN.
- Belum punya TTD -> wajib registrasi.
- Signing selalu meminta PIN.
- Output: gambar TTD + QR verifikasi + SHA-256 + audit trail.

## Instalasi
1. Copy `.env.example` menjadi `.env`.
2. Import `database/schema.sql`.
3. Isi konfigurasi DB dan `APP_URL`.
4. Pastikan PHP cURL aktif.
5. PHP GD direkomendasikan untuk background transparan.
6. Pastikan folder `storage/signatures` writable oleh PHP.
7. Buka aplikasi dan login menggunakan akun yang punya akses `simpeg` pada BKK SSO.

## Halaman
- `index.php`: login SSO
- `register-signature.php`: gambar/upload TTD + create PIN
- `dashboard.php`: dashboard
- `signature.php`: TTD Saya
- `documents.php`: dokumen dummy + signing
- `activity.php`: audit trail
- `verify.php`: QR verification

## Keamanan MVP
- PIN menggunakan `password_hash()`.
- 5 kali PIN salah -> lock 15 menit.
- CSRF pada form dan API signing.
- ID pegawai berasal dari session hasil SSO.
- Audit IP dan user-agent.

## Sebelum produksi
Gunakan private storage di luar webroot, HTTPS + secure cookie, rate limit, revoke/versioning TTD, server-side QR, CORS allowlist, serta Signature API terpisah. Jika dibutuhkan TTE tersertifikasi, integrasikan PSrE.
