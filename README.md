# BaleBolen — Website Bakery & Pemesanan Online

Website penjualan BaleBolen: katalog bolen satuan, paket hampers yang bisa disusun sendiri,
checkout tanpa akun, dan dashboard admin untuk mengelola produk, stok, pesanan, serta pengaturan bisnis.

Aplikasi ini benar-benar berjalan (frontend + backend + database), bukan landing page statis.

---

## 1. Teknologi

| Bagian | Teknologi |
|---|---|
| Frontend | HTML5, CSS3 khusus (design system BaleBolen), JavaScript vanilla |
| Backend | PHP 8.2+ (diuji pada PHP 8.4), arsitektur MVC tanpa framework |
| Database | MySQL 8 (InnoDB, utf8mb4), PDO prepared statements |
| Dev server | PHP built-in server atau Laragon/Apache |
| Font | Playfair Display + DM Sans (Google Fonts, dengan fallback sistem) |

Tidak ada dependency Composer/NPM. Seluruh aset disajikan dari folder `public/`.

---

## 2. Struktur Proyek

```
balebolen/
├── app/
│   ├── Controllers/           # Customer: Home, Catalog, Cart, Checkout, Order, Page
│   │   └── Admin/             # Admin: Auth, Dashboard, Product, Hampers, Inventory, Order, Settings
│   ├── Core/                  # App, Router, Database, Controller, View, Session, Csrf,
│   │                          # Auth, Validator, ImageUploader, Env, helpers
│   ├── Models/                # Admin, Product, Variant, Cart, Order, Setting
│   └── Views/                 # layouts, partials, halaman customer, admin, error
├── bootstrap/app.php          # bootstrap aplikasi
├── config/config.php          # konfigurasi dari .env
├── database/
│   ├── schema.sql             # struktur database lengkap (drop + create)
│   └── seed.php               # data awal: admin, kategori, varian, produk, settings
├── public/                    # document root
│   ├── index.php              # front controller
│   ├── router.php             # router PHP built-in server
│   ├── .htaccess              # rewrite Apache
│   ├── assets/                # css, js, gambar placeholder
│   └── uploads/               # upload produk & brand (gitignored)
├── routes/
│   ├── web.php                # route customer
│   └── admin.php              # route admin
├── storage/
│   ├── logs/                  # app.log
│   ├── sessions/              # session PHP (project-local)
│   └── uploads/               # (cadangan, tidak dipakai runtime)
├── .env.example
└── README.md
```

---

## 3. Instalasi

### Prasyarat
- PHP 8.2+ dengan ekstensi `pdo_mysql`, `fileinfo`, `mbstring`, dan `gd` (dipakai untuk resize/kompresi gambar upload; tanpa GD aplikasi tetap berjalan)
- MySQL 8 (Laragon/XAMPP/DB server mandiri)

### Langkah

1. **Salin project** ke direktori web, misalnya `C:\laragon\www\balebolen`.

2. **Salin konfigurasi environment**
   ```bash
   cp .env.example .env
   ```
   Sesuaikan `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.
   Default: database `balebolen`, user `root`, port `3306`.

3. **Buat database & tabel**
   ```bash
   mysql -u root -p < database/schema.sql
   ```
   Perintah ini membuat database `balebolen` (bila belum ada) dan seluruh tabel.

   Untuk database yang **sudah berisi data** (upgrade), jangan jalankan `schema.sql`
   karena skrip itu menghapus tabel. Gunakan migrasi:
   ```bash
   php database/migrate.php
   ```
   Migrasi yang tersedia: kolom keamanan pesanan + tabel `login_attempts`/`rate_limits`,
   serta kunci pengaturan tenggat bayar. Rollback tersedia di
   `database/migrations/*_rollback.sql`.

4. **Isi data awal**
   ```bash
   php database/seed.php
   ```
   Seeder membuat:
   - akun admin,
   - 2 kategori,
   - 4 varian rasa (**stok 0**, sengaja tidak diisi fiktif),
   - 4 produk bolen satuan Rp5.000,
   - 2 paket hampers (**harga kosong**, sengaja tidak ditentukan),
   - pengaturan awal termasuk tenggat bayar bawaan 24 jam (dapat diubah/dinonaktifkan admin).

   Kredensial admin default (ubah setelah login):
   - Email: `admin@balebolen.id`
   - Password: `BaleBolen123!`

5. **Jalankan aplikasi**

   Dengan PHP built-in server:
   ```bash
   php -S localhost:8000 -t public public/router.php
   ```
   Buka `http://localhost:8000`.

   Dengan Laragon/Apache: arahkan **document root** ke folder `public/`
   (mod_rewrite harus aktif; `.htaccess` sudah disiapkan).

6. **Lengkapi data bisnis** melalui `/admin/pengaturan` dan isi stok di `/admin/stok`.
   Tanpa stok, produk tampil "Stok habis". Tanpa harga & pre-order, paket hampers belum bisa dipesan.

7. **Pastikan folder privat dapat ditulis** oleh web server:
   - `storage/sessions/`, `storage/logs/`,
   - `storage/proofs/` (bukti pembayaran; **jangan** arahkan document root ke `storage/`).

8. **Jadwalkan pembatalan pesanan kedaluwarsa** (opsional tetapi disarankan):
   ```bash
   # cron tiap 15 menit
   0,15,30,45 * * * * php /path/ke/balebolen/bin/release-expired.php
   ```
   Tanpa cron, command dapat dijalankan manual dari dashboard hosting.

---

## 4. Struktur Database (ERD ringkas)

```
admins (id, name, email, password_hash)
business_settings (setting_key PK, setting_value)

categories (id, slug, name, description)
    │ 1..n
products (id, category_id FK, variant_id FK NULL, name, slug, description,
          type ENUM(single,hampers), price NULL, package_size NULL,
          packaging_info, image, is_active)
    │                         │
    │                         │ 0..n
    │                 product_variants (id, name, slug, stock, is_active)   ← sumber stok tunggal
    │                         │
orders (id, order_code UNIQUE, customer_name, whatsapp, fulfillment,
        delivery_method, delivery_address, delivery_date, notes,
        gift_recipient, gift_message, subtotal, shipping_cost NULL, total,
        payment_method, payment_status, order_status, stock_restored)
    │ 1..n
order_items (id, order_id FK, product_id FK NULL, product_name, product_type,
             package_size, unit_price, quantity, line_total, composition_text)
    │ 1..n
order_item_variants (id, order_item_id FK, variant_id FK NULL, variant_name, quantity)

stock_movements (id, variant_id FK, change_qty, stock_after,
                 reason ENUM(restock,order,cancel,adjustment), note,
                 order_id FK NULL, admin_id FK NULL)
```

Catatan desain:
- **Stok hanya ada di `product_variants`.** Produk satuan dan paket hampers mengambil stok dari sumber yang sama, sehingga tidak terjadi stok ganda.
- **`products.price` boleh NULL** khusus paket. NULL berarti harga belum ditetapkan; checkout akan menolak paket tersebut, sehingga tidak ada transaksi dengan harga fiktif.
- **`order_item_variants` menyimpan komposisi terstruktur**, bukan sekadar teks. `composition_text` hanya untuk tampilan.
- **Total** dihitung ulang di backend dari data produk di database.

---

## 5. Fitur Utama

### Customer
- **Beranda** editorial: hero, kategori, produk unggulan, promo hampers, CTA.
- **Katalog** `/menu` dengan filter kategori; harga dan status stok ditampilkan per produk (belum ada filter harga/stok).
- **Detail produk** `/menu/{slug}`: foto, deskripsi, harga, stok, stepper jumlah.
- **Builder hampers**: tambah/kurang per rasa, total dihitung real-time, tombol pesan hanya aktif bila komposisi tepat 6/12 buah dan stok cukup.
- **Keranjang** berbasis session; subtotal dihitung server; jumlah item dapat diubah langsung (divalidasi stok dan batas 50/baris).
- **Checkout guest**: nama, WhatsApp, pickup/delivery, alamat & jasa kirim (bila delivery), tanggal, catatan, kartu ucapan untuk hampers, metode pembayaran.
- **Halaman pesanan** `/pesanan/{kode}`: dilindungi verifikasi 4 digit terakhir WhatsApp (atau tautan dengan token 64 karakter), status pesanan & pembayaran, tenggat bayar, rincian komposisi, total, instruksi pembayaran, unggah bukti transfer/QRIS, tombol cetak dan WhatsApp.
- Halaman **Tentang** dan **Kontak** dengan placeholder jelas untuk data yang belum tersedia.

### Admin
- Login/logout dengan hashing password, session aman, proteksi seluruh route `/admin`, pembatasan percobaan login per email (5/10 menit) dan per IP (20/10 menit) yang disimpan di database, serta idle timeout 30 menit.
- Dashboard: total pesanan, pesanan baru, menunggu pembayaran, pendapatan dari pesanan berstatus Dibayar, stok total, checklist kelengkapan data.
- **Pesanan**: pencarian kode/nama/WhatsApp, filter status pesanan, status pembayaran, dan rentang tanggal; pagination; detail lengkap termasuk bukti bayar dan tenggat pembayaran.
- **Update status pesanan** (8 status) dan **status pembayaran** (4 status) terpisah, dengan matriks transisi (status tidak sah ditolak) dan alasan pembatalan.
- **Ongkos kirim manual**: diisi admin, total dihitung ulang (subtotal + ongkir). Bila kosong, total tetap subtotal dan ditandai "menunggu konfirmasi".
- **Pembatalan pesanan** otomatis mengembalikan stok varian dan tercatat di riwayat stok.
- **Produk satuan**: CRUD, upload foto (validasi MIME & ukuran), harga, varian, aktif/nonaktif.
- **Paket hampers**: atur harga (boleh kosong), jumlah isi, deskripsi, kemasan, foto.
- **Verifikasi pembayaran**: lihat/hapus bukti bayar yang diunggah pelanggan, lalu ubah status menjadi Dibayar.
- **Stok & varian**: set stok / tambah-kurangi, catatan, riwayat pergerakan stok, aktif/nonaktif varian.
- **Pengaturan**: nama bisnis, tagline, logo, WhatsApp, alamat, Maps embed, jam operasional, Instagram/TikTok, cerita bisnis, rekening, QRIS (teks + gambar), e-wallet, area & catatan pengiriman, minimal hari pre-order hampers, catatan pesanan, tenggat pembayaran, kebijakan pembatalan/refund, serta profil & password admin.
- **Konfirmasi aksesibel** sebelum aksi sensitif (ubah status, pembayaran, ongkir, hapus bukti/produk).

---

## 6. Aturan Bisnis yang Diterapkan

| Aturan | Implementasi |
|---|---|
| Harga bolen satuan Rp5.000 | Data produk (dapat diubah admin) |
| Same-day order satuan | Tanggal penerimaan minimal hari ini, selama stok cukup |
| Hampers pre-order | Tanggal minimal = hari ini + `hampers_preorder_days` (diatur admin) |
| Harga paket belum ada | Disimpan NULL; paket tidak bisa ditambahkan ke keranjang / checkout |
| Pre-order belum diatur | Paket tidak bisa dipesan, dengan pesan jelas |
| Komposisi tepat 6/12 | Ditampilkan real-time dan divalidasi ulang di server + transaksi DB |
| Stok tidak boleh minus | Dicek ulang di dalam transaksi dengan `SELECT ... FOR UPDATE` |
| Guest checkout | Tidak ada registrasi pelanggan |
| Pembayaran | Transfer, QRIS, GoPay/e-wallet, COD (delivery), tunai (pickup) |
| COD/tunai | Tidak wajib dibayar sebelum pesanan diproses; status pembayaran tetap manual |
| Ongkir | Tidak ada tarif otomatis; hanya admin yang mengisi |
| Status pesanan vs pembayaran | Dua kolom terpisah |

---

## 7. Keamanan

- Password admin: `password_hash()` / `password_verify()` (bcrypt), rehash otomatis bila algoritma berubah.
- Seluruh query memakai **PDO prepared statements** (`ATTR_EMULATE_PREPARES = false`).
- **CSRF token** wajib pada semua POST (checkout, keranjang, login, admin).
- Output di-escape dengan `e()` (`htmlspecialchars`) untuk mencegah XSS.
- Session: cookie `HttpOnly`, `SameSite=Lax`, `Secure` otomatis pada HTTPS, regenerasi ID setelah login, `use_strict_mode`, dan idle timeout admin.
- Halaman admin dilindungi `Auth::requireLogin()`; route admin tidak dapat diakses pelanggan.
- Throttle login disimpan di tabel `login_attempts` sehingga tidak dapat dilewati dengan sesi baru.
- Rate limit berbasis database untuk checkout, verifikasi akses pesanan, dan unggah bukti; ditambah honeypot pada form checkout.
- Header keamanan (`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, CSP report-only) dan redirect HTTPS pada `.htaccess` (kecuali localhost).
- Bukti pembayaran disimpan di `storage/proofs` dan hanya disajikan lewat route terautentikasi.
- Harga, subtotal, total, dan komposisi **tidak pernah dipercaya dari browser** — semua dihitung ulang dari database.
- Upload: validasi ukuran, MIME asli (finfo), nama file acak; eksekusi PHP di `/uploads` diblokir (`.htaccess` + router).
- Kredensial database hanya di `.env` (gitignored); contoh tersedia di `.env.example`.
- Error ditampilkan ke pengguna hanya saat `APP_DEBUG=true`.

---

## 8. Informasi Bisnis yang Masih Harus Dilengkapi

Semua item di bawah **tidak diisi dengan data karangan**. Isi melalui dashboard admin.

| Data | Lokasi pengisian | Dampak bila kosong |
|---|---|---|
| Harga Paket Spesial | Admin → Paket Hampers | Paket tidak dapat dipesan |
| Harga Paket Premium | Admin → Paket Hampers | Paket tidak dapat dipesan |
| Minimal hari pre-order hampers | Admin → Pengaturan → Pre-order | Paket tidak dapat dipesan |
| Nomor WhatsApp resmi | Admin → Pengaturan | Tombol WhatsApp disembunyikan |
| Alamat lengkap usaha | Admin → Pengaturan | Placeholder di kontak & footer |
| Jam operasional | Admin → Pengaturan | Placeholder di kontak & footer |
| Logo asli | Admin → Pengaturan | Memakai monogram "B" |
| Foto asli produk | Admin → Produk / Hampers | Memakai ilustrasi placeholder |
| Nomor rekening & atas nama | Admin → Pengaturan → Pembayaran | Instruksi transfer tidak tampil detail |
| Gambar / catatan QRIS | Admin → Pengaturan → Pembayaran | Instruksi QRIS tidak tampil detail |
| Nomor e-wallet | Admin → Pengaturan → Pembayaran | Instruksi e-wallet tidak tampil detail |
| Area jangkauan & tarif kirim | Admin → Pengaturan → Pengiriman | Ongkir tetap dikonfirmasi manual |
| Stok awal setiap varian | Admin → Stok & Varian | Semua produk tampil "Stok habis" |
| Cerita/sejarah bisnis | Admin → Pengaturan | Placeholder "akan dilengkapi" di halaman Tentang |
| Kebijakan pembatalan/refund | Belum ada fitur khusus | **TODO keputusan pemilik usaha** |
| Biaya kemasan per paket | Belum ada kolom terpisah | **TODO keputusan pemilik usaha** |

Catatan keputusan yang sengaja tidak diambil sendiri:
- Tidak menetapkan nilai H-1/H-2/H-3 untuk hampers.
- Tidak membuat tarif ongkos kirim.
- Tidak memakai rekening/QRIS/e-wallet contoh.
- Tidak menambah stok awal fiktif.
- Tidak menulis testimoni atau klaim kualitas yang belum diberikan pemilik usaha.

---

## 9. Hasil Pengujian

Pengujian dilakukan langsung terhadap aplikasi yang berjalan (PHP 8.4 + MySQL 8.4),
lewat HTTP dengan cookie session dan CSRF token asli.

| # | Skenario | Hasil |
|---|---|---|
| 1 | Semua halaman customer (`/`, `/menu`, detail, tentang, kontak, keranjang) merespons 200 | Lulus |
| 2 | Login admin valid → dashboard 200; akses `/admin` tanpa login → redirect `/admin/login` | Lulus |
| 3 | Login salah 5x → percobaan berikutnya diblokir 10 menit (tabel `login_attempts`, lintas sesi) | Lulus |
| 4 | Isi stok varian lewat admin (mode set) → stok & riwayat `stock_movements` tercatat | Lulus |
| 5 | Tambah 2 + 3 bolen satuan → subtotal server Rp25.000 | Lulus |
| 6 | Checkout pickup + tunai → pesanan tersimpan, stok berkurang, kode pesanan dibuat | Lulus |
| 7 | Tambah melebihi stok (999 buah) → ditolak dengan pesan stok | Lulus |
| 8 | Stok diturunkan setelah item masuk keranjang → checkout ditolak, tidak ada pesanan baru | Lulus |
| 9 | Paket hampers tanpa harga/pre-order → tidak dapat ditambahkan, pesan jelas | Lulus |
| 10 | Komposisi 5 buah untuk Paket Spesial → ditolak; 6 buah → diterima | Lulus |
| 11 | Checkout hampers dengan tanggal < H+2 → ditolak; tanggal H+2 → diterima | Lulus |
| 12 | Komposisi tersimpan terstruktur di `order_item_variants`; stok per rasa berkurang tepat | Lulus |
| 13 | COD dipilih untuk pickup → ditolak dengan pesan valid | Lulus |
| 14 | Admin melihat detail pesanan, mengubah status pesanan, status pembayaran, dan ongkir | Lulus |
| 15 | Total pesanan berubah otomatis: subtotal 45.000 + ongkir 15.000 = 60.000 | Lulus |
| 16 | Pembatalan pesanan → stok seluruh varian kembali, `stock_restored = 1` | Lulus |
| 17 | Pesanan batal tidak dapat diaktifkan kembali; `completed` tidak dapat dibatalkan | Lulus |
| 18 | CRUD produk: tambah, edit, hapus; produk dengan pesanan tidak dapat dihapus | Lulus |
| 19 | Upload gambar valid (PNG) tersimpan & tersaji 200; file non-gambar ditolak | Lulus |
| 20 | Pencarian & filter pesanan admin (kode, status, pembayaran, tanggal) berjalan | Lulus |
| 21 | POST tanpa token CSRF → HTTP 419 | Lulus |
| 22 | Halaman 404 (produk, pesanan, URL) & blokir eksekusi PHP di `/uploads` → 403 | Lulus |
| 23 | Data uji dibersihkan: 0 pesanan, stok 0, harga paket NULL, pengaturan bisnis kosong | Lulus |

Pengujian tambahan setelah implementasi P0/P1 (19 November 2026):

| # | Skenario | Hasil |
|---|---|---|
| 24 | Checkout satuan membuat `access_token` 64 karakter dan `payment_due_at` = +24 jam | Lulus |
| 25 | Halaman pesanan di sesi baru menampilkan gate, tanpa membocorkan nama/alamat | Lulus |
| 26 | Verifikasi 4 digit salah → tetap terkunci; 4 digit benar → pesanan terbuka | Lulus |
| 27 | Unggah bukti PNG → tersimpan di `storage/proofs`, status `awaiting_verification` | Lulus |
| 28 | Admin membuka bukti bayar → 200 `image/png`; tombol hapus tersedia | Lulus |
| 29 | Transisi `new→completed` ditolak; `new→confirmed` diterima; `confirmed→completed` ditolak | Lulus |
| 30 | Pembatalan menyimpan alasan, menyetel pembayaran `cancelled`, stok kembali tepat sekali | Lulus |
| 31 | Ubah jumlah keranjang 3 → 4 memperbarui subtotal menjadi Rp20.000 | Lulus |
| 32 | Header keamanan terkirim pada respons HTML | Lulus |
| 33 | Migrasi dijalankan dua kali → tidak ada penerapan ganda | Lulus |

Catatan: pengujian dilakukan pada lebar desktop melalui HTTP. Layout responsif memakai
breakpoint CSS (1080/900/720/480 px) dan belum diuji dengan screenshot perangkat fisik.

---

## 10. Catatan Pengembangan

- **Session path**: aplikasi otomatis memakai `storage/sessions/` bila folder session bawaan PHP tidak writable (sering terjadi di Windows/Laragon).
- **Placeholder foto**: `public/assets/img/placeholder-unit.svg` dan `placeholder-hampers.svg`. Foto asli yang diunggah admin disimpan di `public/uploads/products/` dan otomatis menggantikan placeholder.
- **Zona waktu**: `Asia/Jakarta` (dapat diubah di `.env`). Koneksi MySQL otomatis memakai offset yang sama agar `NOW()`/`CURDATE()` konsisten dengan waktu aplikasi.
- **Tenggat pembayaran**: `payment_due_hours` (bawaan 24, 0 = tanpa tenggat). Pesanan `unpaid` yang melewati tenggat dibatalkan oleh `php bin/release-expired.php`; jadwalkan via cron.
- **Debug**: set `APP_DEBUG=false` di produksi agar detail error tidak tampil.
- **Backup**: cadangkan database, folder `public/uploads/`, dan `storage/proofs/` (bukti bayar).
- Halaman admin memakai `<meta name="robots" content="noindex, nofollow">`.

---

## 11. Akun & URL Penting

| Halaman | URL |
|---|---|
| Beranda | `/` |
| Katalog | `/menu` |
| Keranjang | `/keranjang` |
| Checkout | `/checkout` |
| Lacak pesanan | `/pesanan/{kode}` |
| Tentang | `/tentang` |
| Kontak | `/kontak` |
| Login admin | `/admin/login` |
| Dashboard admin | `/admin` |

Segera ganti password admin default setelah instalasi melalui **Admin → Pengaturan → Profil admin**.
