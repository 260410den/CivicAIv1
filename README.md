# CivicAI

CivicAI adalah aplikasi pelaporan masalah lingkungan. Warga dapat membuat laporan, berdiskusi, memberi dukungan, dan mengikuti respons petugas. Analisis teks memberi rekomendasi kategori dan prioritas awal; keputusan tindak lanjut tetap berada pada petugas.

## Fitur

- Registrasi dan login warga, serta portal petugas.
- Pembuatan laporan dengan foto/video dan analisis teks lokal.
- Feed, detail laporan, diskusi, dukungan warga, dan moderasi laporan postingan.
- Dashboard petugas, perubahan status, serta peta laporan.
- Landing page responsif dengan navigasi seluler dan animasi yang menghormati pengaturan reduced motion.

## Persyaratan

- PHP 8.1 atau lebih baru dengan ekstensi `mysqli`, `fileinfo`, dan `curl` (cURL hanya diperlukan bila integrasi AI eksternal ditambahkan).
- MySQL 5.7+ atau MariaDB 10.5+.
- Apache/Nginx dengan PHP aktif. MAMP dapat digunakan untuk pengembangan lokal.

## Menjalankan secara lokal dengan MAMP

1. Letakkan folder proyek di document root MAMP, misalnya `C:\MAMP\htdocs\CivicAI`.
2. Jalankan Apache dan MySQL dari MAMP.
3. Buat database dengan menjalankan [`database/init.sql`](database/init.sql) di phpMyAdmin atau MySQL client. Skrip membuat database `civicai` beserta tabel yang dipakai aplikasi.
4. Atur kredensial database melalui environment `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, dan `DB_PASSWORD`. Nilai lokal bawaan ada di `.env.example` (MAMP Windows umumnya menggunakan `root` / `root`; sesuaikan bila berbeda). PHP tidak membaca file `.env` secara otomatis. `GEMINI_API_KEY` bersifat opsional; analisis lokal tetap berjalan tanpa API eksternal.
5. Pastikan folder `uploads/` dapat ditulis oleh proses web server.
6. Buka `http://localhost/CivicAI/`.

> **Akun petugas:** registrasi publik selalu membuat akun `warga`. Buat/promosikan akun petugas secara manual di database oleh administrator; jangan menambahkan pembuatan role petugas ke formulir publik.

## Struktur proyek

```text
CivicAI/
├── api/              # Endpoint JSON, analisis, dan alur kasus
├── assets/           # CSS dan JavaScript bersama
├── auth/              # Registrasi, login, logout
├── community/         # Feed, detail, dan pembuatan laporan warga
├── config/            # Koneksi dan konfigurasi aplikasi
├── database/          # Skema instalasi dan SQL fitur
├── landing/           # Halaman depan
├── officer/           # Dashboard dan moderasi petugas
├── uploads/           # Media laporan (tidak disimpan di Git)
├── .env.example
└── README.md
```

## Database dan penerapan

Aplikasi saat ini memakai PHP `mysqli` dan SQL MySQL/MariaDB. Supabase menyediakan PostgreSQL, sehingga aplikasi belum dapat dipindahkan hanya dengan mengganti variabel koneksi: skema SQL dan kueri `mysqli` harus dimigrasikan ke PostgreSQL/PDO atau klien Postgres, lalu alur autentikasi, unggahan, dan kebijakan akses perlu disesuaikan. [`database/init.sql`](database/init.sql) adalah skema untuk MySQL/MariaDB, bukan skrip Supabase.

Vercel Hobby saat ini gratis untuk proyek personal/nonkomersial, tetapi proyek ini belum dikemas untuk Vercel. PHP bukan runtime bawaan yang didukung resmi; konfigurasi PHP yang dicontohkan dokumentasi Vercel memakai runtime komunitas. Selain itu, folder `uploads/` lokal tidak cocok untuk penyimpanan permanen pada fungsi serverless. Sebelum deploy, aplikasi perlu runtime PHP yang dipilih, database PostgreSQL atau MySQL terkelola yang kompatibel, dan penyimpanan media persisten. Supabase Free menawarkan Postgres dengan batas penggunaan dan dapat menjeda proyek yang tidak aktif. Periksa batas layanan terbaru sebelum memilihnya.

