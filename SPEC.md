# SPEC — BaleBolen Online Bakery

| Item | Keterangan |
|---|---|
| Nama produk | BaleBolen — website penjualan bolen pisang & paket hampers |
| Versi dokumen | 1.0 (draft untuk persetujuan) |
| Tanggal | 19 November 2026 |
| Status | **Diimplementasikan bertahap** — blok P0 & P1 teknis selesai (19 Nov 2026); data bisnis TBD dan UAT masih diperlukan sebelum go-live |
| Target teknologi | PHP 8.2+ (MVC tanpa framework) + MySQL 8, HTML/CSS/JS vanilla |
| Basis dokumen | Audit kode aktual di repositori + brief bisnis yang diberikan |

> Dokumen ini menjadi acuan perilaku sistem. Pada 19 November 2026 blok P0 (keamanan & alur uang) dan P1 teknis diimplementasikan; lihat Bagian 11.4 dan `TASKS.md` Bagian 12 untuk rincian serta bukti uji.

---

## 1. Ringkasan Eksekutif

BaleBolen adalah aplikasi web pemesanan bolen pisang untuk pelanggan umum (guest checkout, tanpa akun) dengan dashboard admin untuk mengelola produk, stok, pesanan, pembayaran, ongkos kirim, dan identitas bisnis.

Kondisi saat ini:

- **Fondasi aplikasi sudah jadi dan berfungsi**: katalog, keranjang, checkout, pembuatan pesanan, pengurangan stok transaksional, dashboard admin, CRUD produk, pengaturan, dan pengamanan dasar (CSRF, prepared statement, escaping, hashing, validasi upload).
- **Blok P0 sudah selesai dan teruji**: pelanggan dapat mengunggah bukti transfer/QRIS ke storage privat, halaman pesanan dilindungi verifikasi, login dibatasi per email+IP, pesanan yang melewati tenggat bayar dibatalkan otomatis beserta pengembalian stok, dan checkout dilindungi rate limit/honeypot.
- **Masih tersisa**: notifikasi otomatis, teks kebijakan refund yang harus ditetapkan pemilik usaha, serta pengujian lapangan (perangkat nyata, UAT) dan deployment.
- **Seluruh data bisnis yang belum diberikan tetap kosong (TBD)** dan diisi lewat dashboard; tidak ada harga, ongkir, rekening, alamat, atau stok yang dikarang.

Kesimpulan: sistem adalah **MVP fungsional yang valid**, tetapi belum menjadi **layanan produksi** sampai keputusan bisnis dan item P0 di Bagian 12 diselesaikan.

---

## 2. Tujuan, Aktor, dan Ruang Lingkup

### 2.1 Tujuan

1. Pelanggan dapat melihat menu, memilih bolen satuan atau menyusun paket hampers, lalu memesan tanpa membuat akun.
2. Pemilik usaha dapat mengelola katalog, stok, harga paket, pesanan, status pembayaran, dan ongkir dari satu dashboard.
3. Tidak ada transaksi yang dapat terjadi dengan harga, stok, atau komposisi yang tidak valid.
4. Identitas visual premium “modern artisan bakery” konsisten di seluruh halaman.

### 2.2 Aktor

| Aktor | Hak akses | Autentikasi |
|---|---|---|
| Pelanggan (guest) | Lihat menu, susun hampers, keranjang, checkout, lihat halaman pesanan berdasarkan kode | Tidak ada akun; sesi keranjang |
| Admin | Seluruh dashboard `/admin`, termasuk pesanan, produk, hampers, stok, pengaturan | Email + password, session |
| Pemilik usaha | Memberi data bisnis & kebijakan; diwakili oleh akun admin | Sama dengan admin |

### 2.3 Ruang lingkup (in scope)

- Situs customer: beranda, katalog, detail produk, builder hampers, keranjang, checkout, halaman pesanan, tentang, kontak.
- Dashboard admin: login, dashboard, pesanan, produk satuan, paket hampers, stok & varian, pengaturan bisnis & profil.
- Basis data MySQL, seeder, dokumentasi instalasi.

### 2.4 Di luar ruang lingkup (out of scope, kecuali disetujui)

- Akun/riwayat pesanan pelanggan, wishlist, loyalty point.
- Payment gateway otomatis (Midtrans/Xendit), verifikasi pembayaran otomatis.
- Perhitungan ongkir otomatis berbasis jarak/kurir.
- Multi-admin dengan peran berbeda, audit log menyeluruh.
- Blog/resep, multi-bahasa, aplikasi mobile native, program afiliasi.
- Faktur pajak / perhitungan PPN.

---

## 3. Teknologi dan Arsitektur

| Lapisan | Implementasi |
|---|---|
| Bahasa | PHP 8.2+ (`declare(strict_types=1)`), diuji pada PHP 8.4 |
| Pola | MVC: `routes/` → `app/Controllers` → `app/Models` → `app/Views` |
| Database | MySQL 8 / InnoDB, `utf8mb4_unicode_ci`, PDO dengan `ATTR_EMULATE_PREPARES = false` |
| Front controller | `public/index.php` + `public/router.php` (built-in server) + `.htaccess` (Apache) |
| View | PHP template murni, layout `main` / `admin` / `auth`, escaping via `e()` |
| Aset | CSS design system sendiri (`app.css`, `admin.css`), JS vanilla (`app.js`, `admin.js`) |
| Dependency | Tidak ada Composer/npm; font Google Fonts dengan fallback sistem |
| Zona waktu | `Asia/Jakarta` (dapat diubah via `.env`) |

**Keputusan teknologi:** tetap PHP native MVC + MySQL. Tidak ada alasan teknis kuat untuk mengganti stack pada tahap ini; risiko utama bukan pada teknologi, melainkan pada kelengkapan kebijakan operasional dan keamanan produksi.

### 3.1 Struktur folder aktual

```text
balebolen/
├── app/
│   ├── Controllers/           Home, Catalog, Cart, Checkout, Order, Page
│   │   └── Admin/             Auth, Dashboard, Order, Product, Hampers, Inventory, Settings
│   ├── Core/                  App, Router, Database, Controller, View, Session, Csrf,
│   │                          Auth, Validator, ImageUploader, Env, helpers
│   ├── Models/                Admin, Product, Variant, Cart, Order, Setting
│   └── Views/                 layouts/, partials/, admin/, errors/, halaman customer
├── bootstrap/app.php
├── config/config.php
├── database/                  schema.sql, seed.php, migrate.php, migrations/
├── bin/                       release-expired.php
├── public/                    index.php, router.php, .htaccess, assets/, uploads/
├── routes/web.php, admin.php
├── storage/                   logs/, sessions/, proofs/ (bukti bayar, privat)
├── .env.example, .gitignore, README.md, SPEC.md
```

### 3.2 Inventaris route

**Customer (`routes/web.php`)**

| Method | URI | Fungsi |
|---|---|---|
| GET | `/` | Beranda |
| GET | `/menu` | Katalog, filter `?kategori=bolen-satuan\|paket-hampers` |
| GET | `/menu/{slug}` | Detail produk / builder hampers |
| GET/POST | `/keranjang`, `/keranjang/tambah`, `/keranjang/ubah`, `/keranjang/hapus` | Keranjang + ubah jumlah |
| GET/POST | `/checkout` | Form & pembuatan pesanan |
| GET | `/pesanan/{code}` | Status pesanan (dilindungi verifikasi) |
| POST | `/pesanan/{code}/akses` | Verifikasi 4 digit terakhir WhatsApp |
| GET/POST | `/pesanan/{code}/bukti` | Lihat & unggah bukti pembayaran |
| GET | `/tentang`, `/kontak` | Halaman statis dinamis dari pengaturan |

**Admin (`routes/admin.php`)** — semua dilindungi `Auth::requireLogin()`, kecuali login.

| Method | URI | Fungsi |
|---|---|---|
| GET/POST | `/admin/login`, `/admin/logout` | Autentikasi |
| GET | `/admin` | Dashboard |
| GET | `/admin/pesanan`, `/admin/pesanan/{id}` | Daftar & detail pesanan |
| POST | `/admin/pesanan/{id}/status`, `/pembayaran`, `/ongkir` | Update status (dengan matriks transisi), pembayaran, ongkir |
| GET/POST | `/admin/pesanan/{id}/bukti`, `/bukti/hapus` | Lihat & hapus bukti pembayaran |
| GET/POST | `/admin/produk`, `/admin/produk/{id}` | CRUD produk satuan |
| GET/POST | `/admin/hampers`, `/admin/hampers/{id}` | Daftar & edit paket hampers |
| GET/POST | `/admin/stok`, `/admin/stok/varian/{id}` | Stok, varian, riwayat |
| GET/POST | `/admin/pengaturan`, `/admin/pengaturan/profil` | Pengaturan bisnis & admin |

---

## 4. Model Data dan Invarian

### 4.1 Tabel

| Tabel | Peran | Catatan penting |
|---|---|---|
| `admins` | Akun admin | `password_hash` bcrypt; belum ada peran |
| `business_settings` | Key-value identitas & kebijakan bisnis | 24 kunci; nilai kosong = belum diisi |
| `categories` | 2 kategori tetap | Tidak ada UI CRUD kategori |
| `product_variants` | **Sumber stok tunggal** per rasa | `stock`, `is_active` |
| `products` | Produk satuan & paket | `type`, `price NULL` = belum ditetapkan, `variant_id` untuk satuan, `package_size` untuk hampers |
| `orders` | Header pesanan | Snapshot pelanggan, subtotal, ongkir NULL, total, dua status terpisah |
| `order_items` | Snapshot item | Nama, harga satuan, qty, total baris, teks komposisi |
| `order_item_variants` | Komposisi terstruktur per item | Dipakai untuk pengembalian stok |
| `stock_movements` | Riwayat stok | `restock`, `order`, `cancel`, `adjustment` |

### 4.2 Invarian wajib

1. **Stok hanya di `product_variants`.** Tidak ada kolom stok di `products`. Stok paket = jumlah stok varian aktif; ketersediaan paket tetap divalidasi per rasa sesuai komposisi.
2. **`products.price IS NULL` berarti harga belum ditetapkan.** Produk tidak boleh masuk keranjang (hampers) atau checkout (semua tipe). Tidak ada nilai default/harga karangan.
3. **Total dihitung server.** Harga dari browser diabaikan; `subtotal = Σ(line_total)`, `total = subtotal + (shipping_cost ?? 0)`.
4. **Stok dikurangi di dalam transaksi** saat pesanan dibuat, memakai `SELECT ... FOR UPDATE`; gagal satu varian = rollback seluruh pesanan.
5. **Komposisi harus tepat `package_size`** dan setiap varian harus aktif serta stoknya cukup pada saat transaksi.
6. **Pembatalan mengembalikan stok sekali saja**, dijaga `orders.stock_restored`.
7. **Pesanan berstatus `cancelled` bersifat terminal**; tidak dapat diaktifkan kembali.
8. **Status pesanan dan status pembayaran independen** (dua kolom, dua aksi admin).
9. **Data pelanggan & item disimpan sebagai snapshot** agar riwayat tidak berubah saat produk diedit/dihapus.

### 4.3 Status

**Pesanan:** `new` → `awaiting_payment` → `confirmed` → `processing` → `ready_for_pickup` / `shipping` → `completed`; plus `cancelled`.

**Pembayaran:** `unpaid`, `awaiting_verification`, `paid`, `cancelled`.

Catatan: saat ini **tidak ada validasi urutan transisi** selain `cancelled` sebagai terminal. Admin dapat melompati status (mis. `new` → `completed`). Perilaku ini perlu keputusan (Bagian 12, PF-05).

---

## 5. Kebutuhan Fungsional — Customer

Legenda status: **SELESAI** = ada & teruji; **SEBAGIAN** = ada dengan keterbatasan; **BELUM** = tidak ada.

| ID | Kebutuhan | Status | Bukti / Catatan |
|---|---|---|---|
| FR-C01 | Beranda menampilkan hero, kategori, produk unggulan, promo hampers, CTA | SELESAI | `HomeController`, `Views/home.php` |
| FR-C02 | Katalog menampilkan bolen satuan & paket, filter kategori | SELESAI | Hanya filter kategori; **tidak ada filter harga/stok** |
| FR-C03 | Detail produk: harga, deskripsi, stok, stepper jumlah, produk terkait | SELESAI | `CatalogController::show` |
| FR-C04 | Builder hampers: pilih komposisi per rasa, total real-time, tombol aktif hanya bila tepat 6/12 & stok cukup | SELESAI | `app.js` + validasi server `CartController::addHampers` |
| FR-C05 | Keranjang session, subtotal server, ubah jumlah, hapus item | SELESAI | `POST /keranjang/ubah` dengan validasi stok & batas 50/baris |
| FR-C06 | Checkout guest tanpa registrasi | SELESAI | `CheckoutController` |
| FR-C07 | Pilihan pickup/delivery; alamat & jasa kirim wajib untuk delivery | SELESAI | Validasi server + pesan per field |
| FR-C08 | Metode bayar: transfer, QRIS, GoPay, COD (delivery), tunai (pickup) | SELESAI | COD pickup & tunai delivery ditolak |
| FR-C09 | Validasi tanggal: satuan ≥ hari ini; hampers ≥ hari ini + `hampers_preorder_days` | SELESAI | Null pre-order = paket diblokir |
| FR-C10 | Halaman pesanan menampilkan kode, status pesanan & pembayaran, rincian, total, instruksi bayar, tombol cetak & WhatsApp | SELESAI | `Views/order.php` |
| FR-C11 | Catatan pesanan global dari admin tampil di halaman pesanan | SELESAI | `setting('order_note')` |
| FR-C12 | Halaman Tentang & Kontak mengambil data dari pengaturan, dengan placeholder jelas | SELESAI | `about.php`, `contact.php` |
| FR-C13 | Pelanggan mengunggah bukti transfer/QRIS | SELESAI | `POST /pesanan/{code}/bukti`, JPG/PNG/WEBP/PDF maks 3 MB, disimpan di `storage/proofs` (privat) |
| FR-C14 | Pelanggan membatalkan/mengubah pesanan sendiri | BELUM | Sengaja manual via WhatsApp |
| FR-C15 | Riwayat pesanan pelanggan | BELUM | Tidak ada akun; hanya akses per kode |
| FR-C16 | Notifikasi otomatis WhatsApp/email saat pesanan dibuat/diubah | BELUM | Hanya tautan WhatsApp manual (`wa.me`) |
| FR-C17 | Pelacakan pesanan aman (verifikasi identitas) | SELESAI | 4 digit terakhir WhatsApp atau token 64 karakter; percobaan dibatasi 5/10 menit |

---

## 6. Kebutuhan Fungsional — Admin

| ID | Kebutuhan | Status | Bukti / Catatan |
|---|---|---|---|
| FR-A01 | Login/logout aman, session regenerate, password hash | SELESAI | `Auth`, `Admin` |
| FR-A02 | Pembatasan percobaan login | SELESAI | Tabel `login_attempts` per email (5/10 menit) dan per IP (20/10 menit) |
| FR-A03 | Dashboard: pesanan baru, menunggu bayar, pendapatan terverifikasi, total stok, checklist data | SELESAI | `Admin/DashboardController` |
| FR-A04 | Daftar pesanan: cari kode/nama/WA, filter status pesanan, status bayar, rentang tanggal, pagination | SELESAI | `Order::paginate` |
| FR-A05 | Detail pesanan lengkap termasuk komposisi & data pelanggan | SELESAI | `admin/orders/show.php` |
| FR-A06 | Ubah status pesanan (8 status) | SELESAI | `updateStatus` |
| FR-A07 | Ubah status pembayaran (4 status) terpisah | SELESAI | `updatePayment` |
| FR-A08 | Isi/kosongkan ongkir manual; total dihitung ulang | SELESAI | `updateShipping`, `Order::updateShippingCost` |
| FR-A09 | Pembatalan pesanan mengembalikan stok & tercatat di riwayat | SELESAI | `restoreStock` + `stock_movements` |
| FR-A10 | CRUD produk satuan + upload foto + aktif/nonaktif | SELESAI | `Admin/ProductController` |
| FR-A11 | Edit paket hampers: nama, harga (boleh kosong), isi, deskripsi, kemasan, foto | SELESAI | `Admin/HampersController` |
| FR-A12 | Atur stok: set/adjust, catatan, riwayat 80 pergerakan | SELESAI | `Admin/InventoryController` |
| FR-A13 | Kelola varian: ubah nama & aktif/nonaktif | SEBAGIAN | **Tidak bisa menambah/menghapus varian** lewat UI |
| FR-A14 | Pengaturan bisnis: identitas, kontak, pembayaran, pengiriman, pre-order, catatan | SELESAI | 21 field teks + logo + QRIS + pre-order |
| FR-A15 | Profil admin & ganti password (wajib verifikasi password lama) | SELESAI | `updateProfile` |
| FR-A16 | CRUD kategori | BELUM | 2 kategori dari seeder |
| FR-A17 | Kelola banyak admin/peran | BELUM | Hanya akun dari seeder |
| FR-A18 | Laporan penjualan, ekspor CSV/Excel | BELUM | Statistik dashboard saja |
| FR-A19 | Edit konten beranda (judul hero, angka “4 varian/2 paket”, teks katalog) | BELUM | Copywriting di-hardcode di view |
| FR-A20 | Audit log aksi admin | BELUM | Hanya pergerakan stok |

---

## 7. Kebutuhan Lintas Fungsi (Keamanan & Kualitas)

| ID | Kebutuhan | Status | Catatan |
|---|---|---|---|
| FR-X01 | CSRF pada semua POST | SELESAI | Token session, respons 419 |
| FR-X02 | Prepared statement seluruh query | SELESAI | PDO emulasi dimatikan |
| FR-X03 | Escaping output (XSS) | SELESAI | `e()` pada view |
| FR-X04 | Validasi input server-side | SELESAI | `Validator` + aturan kondisional |
| FR-X05 | Upload: batas ukuran, MIME asli, nama acak, PHP diblokir | SELESAI | `ImageUploader`, `.htaccess`, router |
| FR-X06 | Kredensial tidak di repo | SELESAI | `.env` gitignored; `.env.example` tanpa secret |
| FR-X07 | Error tidak membocorkan detail saat produksi | SELESAI | `APP_DEBUG=false` |
| FR-X08 | Rate limit checkout / anti-spam pesanan | SELESAI | Maks 5 pesanan/10 menit per IP, honeypot, tolakan submit < 3 detik |
| FR-X09 | Resize/kompresi gambar upload | SELESAI | Maks 1600 px, re-encode JPEG/WEBP/PNG via GD (fallback aman bila GD tidak ada) |
| FR-X10 | SEO: title unik per halaman | SELESAI | Meta description **global**, bukan per produk |
| FR-X11 | SEO: Open Graph, canonical, sitemap.xml, robots.txt | BELUM | Hanya `noindex` di admin |
| FR-X12 | Aksesibilitas (label, fokus, kontras, ARIA) | SEBAGIAN | Struktur semantik ada; belum diaudit menyeluruh |
| FR-X13 | Responsif 360–1440 px | SEBAGIAN | Breakpoint CSS 1080/900/720/480; belum diuji perangkat nyata |
| FR-X14 | Logging error aplikasi | SELESAI | `storage/logs/app.log` |
| FR-X15 | Uji otomatis (unit/integration) | BELUM | Pengujian manual via HTTP |
| FR-X16 | Backup & pemulihan | SEBAGIAN | Prosedur disebut di README; belum ada skrip |
| FR-X17 | Session timeout idle untuk admin | SELESAI | Idle 30 menit (`ADMIN_IDLE_MINUTES`), `session.use_strict_mode` |

---

## 8. Aturan Bisnis

| ID | Aturan | Implementasi saat ini |
|---|---|---|
| BR-01 | Bolen satuan Rp5.000/buah | Data produk (bisa diubah admin); copy katalog masih hardcode “Rp5.000” |
| BR-02 | Satuan dapat dipesan hari ini selama stok ada | Tanggal ≥ hari ini |
| BR-03 | Hampers wajib pre-order H-n | `hampers_preorder_days` (NULL = belum aktif) |
| BR-04 | Paket Spesial 6 pcs, Premium 12 pcs | `package_size`; admin dapat mengubah |
| BR-05 | Komposisi dipilih pelanggan, jumlah harus tepat | Validasi client + server + transaksi |
| BR-06 | Harga paket ditetapkan admin | `price NULL` = tidak dapat dipesan |
| BR-07 | Stok tidak boleh minus | Lock + cek dalam transaksi |
| BR-08 | Ongkir tidak otomatis; dikonfirmasi admin | `shipping_cost NULL` → “menunggu konfirmasi” |
| BR-09 | COD hanya delivery; tunai hanya pickup | Validasi checkout |
| BR-10 | Pembayaran manual diverifikasi admin | `payment_status` diubah manual |
| BR-11 | Pembatalan mengembalikan stok | Idempoten via `stock_restored` |
| BR-12 | Pesanan batal tidak dapat diaktifkan | Dicek di controller |
| BR-13 | Tidak ada refund otomatis | **Kebijakan belum ada** (TBD-11) |

---

## 9. Data Bisnis yang Belum Diberikan (TBD)

Semua nilai berikut **sengaja kosong** dan wajib diisi pemilik usaha. Tidak ada nilai contoh yang dipakai di transaksi.

| ID | Data | Lokasi pengisian | Perilaku bila kosong |
|---|---|---|---|
| TBD-01 | Harga Paket Spesial (6 pcs) | Admin → Paket Hampers | Paket tidak dapat dipesan |
| TBD-02 | Harga Paket Premium (12 pcs) | Admin → Paket Hampers | Paket tidak dapat dipesan |
| TBD-03 | Minimal hari pre-order hampers | Admin → Pengaturan | Seluruh paket diblokir |
| TBD-04 | Nomor WhatsApp resmi | Admin → Pengaturan | Tombol WhatsApp disembunyikan |
| TBD-05 | Alamat lengkap & patokan pickup | Admin → Pengaturan | Placeholder “dikonfirmasi admin” |
| TBD-06 | Jam operasional | Admin → Pengaturan | Placeholder di kontak/footer |
| TBD-07 | Logo & foto produk asli | Admin → Pengaturan/Produk | Monogram “B” & ilustrasi placeholder |
| TBD-08 | Rekening bank (nama, nomor, pemilik) | Admin → Pengaturan | Instruksi transfer generik |
| TBD-09 | QRIS (gambar/catatan) | Admin → Pengaturan | Instruksi QRIS generik |
| TBD-10 | E-wallet (nama, nomor) | Admin → Pengaturan | Instruksi e-wallet generik |
| TBD-11 | Kebijakan pembatalan/refund | Admin → Pengaturan (`cancellation_policy`) | Bagian kebijakan tidak ditampilkan; admin mencatat alasan batal |
| TBD-12 | Area jangkauan & tarif kirim | Admin → Pengaturan (teks) | Ongkir dikonfirmasi manual |
| TBD-13 | Stok awal tiap varian | Admin → Stok | Semua produk “Stok habis” |
| TBD-14 | Cerita/sejarah bisnis | Admin → Pengaturan | Placeholder “akan dilengkapi” |
| TBD-15 | Biaya kemasan / komponen harga paket | **Belum ada field** | Harga paket apa adanya |
| TBD-16 | Pajak/PPN & faktur | **Belum ada** | Tidak ada pajak dihitung |

---

## 10. Kebutuhan Non-Fungsional

| Aspek | Target | Status |
|---|---|---|
| Kompatibilitas | PHP 8.2+, MySQL 8, Apache/Laragon & PHP built-in server | Terpenuhi (diuji PHP 8.4/MySQL 8.4) |
| Browser | 2 versi terakhir Chrome/Edge/Firefox/Safari, mobile 360 px+ | Sebagian (belum uji perangkat) |
| Performa | Halaman katalog < 500 KB tanpa foto, LCP < 2,5 s pada 4G | **Belum diukur**; belum ada kompresi gambar |
| Keamanan | OWASP Top 10 dasar: injeksi, XSS, CSRF, auth, upload | Sebagian; throttle login & anti-spam belum kuat |
| Privasi | Data pelanggan hanya untuk operasional pesanan | Sebagian; halaman pesanan terbuka via kode |
| Aksesibilitas | Struktur heading, label form, kontras WCAG AA | Belum diaudit |
| Reliabilitas | Transaksi stok atomik, tidak ada oversell | Terpenuhi & teruji |
| Observabilitas | Error log + riwayat stok | Sebagian; tidak ada monitoring uptime |
| Maintainability | MVC terpisah, tanpa framework, dokumentasi | Terpenuhi |

---

## 11. Status Implementasi Terperinci

### 11.1 Sudah dibuat dan berfungsi

1. **Core & infrastruktur**: router dengan parameter, PDO wrapper, view layout, session project-local, CSRF, validator, auth, upload handler, helper format Rupiah/tanggal/WhatsApp.
2. **Database**: 9 tabel, foreign key, indeks, seeder idempoten, data awal tanpa stok/harga fiktif.
3. **Customer**: beranda, katalog + filter kategori, detail produk, builder hampers, keranjang, checkout guest lengkap dengan validasi kondisional, halaman pesanan, tentang, kontak, 404/419/500.
4. **Admin**: login & proteksi, dashboard + checklist kelengkapan, daftar/filter/detail pesanan, update status pesanan/pembayaran/ongkir, pembatalan + restore stok, CRUD produk satuan, edit paket hampers, stok & riwayat, pengaturan bisnis, profil/password.
5. **Keamanan dasar**: prepared statement, escaping, CSRF, hash password, validasi upload, blokir PHP di uploads, kredensial di `.env`.
6. **Dokumentasi**: README instalasi, ERD, aturan bisnis, hasil uji, daftar data TBD.

### 11.2 Belum dibuat

| ID | Fitur | Dampak bisnis |
|---|---|---|
| NF-01 | Unggah bukti pembayaran oleh pelanggan | Admin harus meminta bukti via WhatsApp; rawan lupa |
| NF-02 | Notifikasi otomatis (WA/email) | Pesanan baru tidak diketahui tanpa membuka dashboard |
| NF-03 | Kebijakan & alur refund/pembatalan pelanggan | Tidak ada pegangan saat pelanggan minta batal |
| NF-04 | Riwayat pesanan pelanggan / akun opsional | Pelanggan harus menyimpan tautan kode |
| NF-05 | CRUD kategori | Kategori terkunci pada 2 nilai seeder |
| NF-06 | Tambah/hapus varian rasa | Varian hanya bisa diubah nama & status |
| NF-07 | Laporan & ekspor pesanan | Rekap manual dari dashboard |
| NF-08 | Rate limit checkout/anti-spam | Risiko pesanan palsu menghabiskan stok |
| NF-09 | SEO lanjutan (OG, canonical, sitemap, robots) | Berbagi tautan & indeks mesin pencari kurang optimal |
| NF-10 | Konten beranda dapat diedit admin | Perubahan copy butuh developer |
| NF-11 | Uji otomatis | Regresi bergantung pengujian manual |
| NF-12 | Session timeout admin | Session terbuka berisiko pada perangkat bersama |

### 11.3 Perlu diperbaiki / diputuskan


| ID | Temuan | Bukti | Tingkat | Rekomendasi |
|---|---|---|---|---|
| PF-01 | Throttle login disimpan di session, bukan per akun/IP | `Core/Auth.php` (`login_attempts` di session) | **Tinggi** | Tabel `login_attempts` per email+IP dengan waktu |
| PF-02 | Halaman `/pesanan/{code}` terbuka bagi siapa pun yang tahu kode | `OrderController::show` tanpa verifikasi | **Tinggi** | Tambah verifikasi 4 digit terakhir WhatsApp atau token acak panjang; putuskan kenyamanan vs privasi |
| PF-03 | Tidak ada pelepasan stok untuk pesanan `unpaid` yang ditinggalkan | Stok dipotong saat create; tidak ada expiry | **Tinggi** | Kebijakan batas bayar (mis. 24 jam) + job/manual release + status `cancelled` otomatis |
| PF-04 | Status pembayaran tidak otomatis mengikuti pembatalan pesanan | `updateStatus` hanya mengubah `order_status` | Sedang | Saat batal, tawarkan/atur `payment_status = cancelled` (kecuali sudah `paid` + refund) |
| PF-05 | Transisi status pesanan bebas melompat | `Order::updateStatus` hanya memvalidasi enum | Sedang | Matriks transisi yang diizinkan |
| PF-06 | Pesanan `completed` masih bisa dibatalkan & stok dikembalikan | `updateStatus` tanpa cek status akhir | Sedang | Larang pembatalan setelah `completed`, atau minta konfirmasi khusus |
| PF-07 | Copy katalog/beranda hardcode “Rp5.000”, “4 varian”, “2 paket”, “6/12 buah” | `Views/catalog.php`, `Views/home.php` | Sedang | Ambil dari DB atau jadikan setting |
| PF-08 | README menyebut filter harga & status stok yang tidak ada | README vs `CatalogController` | Rendah | Koreksi dokumentasi atau implementasikan filter |
| PF-09 | Harga produk satuan boleh 0 | `ProductController::validated` (`< 0`) | Rendah | Tolak 0 atau beri flag “gratis” eksplisit |
| PF-10 | Upload gambar tanpa resize/kompresi | `ImageUploader` menyimpan file asli | Sedang | Resize maks. 1600 px + konversi WebP |
| PF-11 | Meta description sama untuk semua halaman & tanpa OG | `layouts/main.php` | Rendah | Meta per produk + OG image |
| PF-12 | Folder `storage/uploads/` tidak dipakai | Struktur proyek | Rendah | Hapus atau dokumentasikan |
| PF-13 | Tidak ada pembatasan jumlah item/kuantitas per pesanan | Checkout | Rendah | Batas wajar (mis. 50 pcs) untuk mencegah penyalahgunaan |
| PF-14 | Validasi nomor WhatsApp hanya regex dasar | `Validator::phone` | Rendah | Normalisasi ke format 62 dan cek panjang |
| PF-15 | Tidak ada konfirmasi ulang saat admin mengosongkan ongkir | `updateShipping` | Rendah | Konfirmasi UI |

### 11.4 Pembaruan implementasi — 19 November 2026

Blok P0 dan P1 teknis sudah dikerjakan dan diuji di lingkungan lokal. Ringkasan:

| Temuan | Status | Implementasi |
|---|---|---|
| PF-01 | **Selesai** | Tabel `login_attempts`, batas per email dan per IP, riwayat gagal dihapus saat login berhasil |
| PF-02 | **Selesai** | Halaman pesanan wajib verifikasi 4 digit terakhir WhatsApp; token acak 64 karakter untuk tautan sah; maks 5 percobaan/10 menit |
| PF-03 | **Selesai** | `payment_due_at` + `bin/release-expired.php`; hanya pesanan `unpaid` yang dibatalkan otomatis, stok kembali sekali |
| PF-04 | **Selesai** | Pembatalan menyetel `payment_status = cancelled` kecuali sudah `paid` (refund manual) |
| PF-05 | **Selesai** | `Order::TRANSITIONS`; transisi tidak sah ditolak server dan tidak muncul di dropdown |
| PF-06 | **Selesai** | `completed` bersifat final, tidak dapat dibatalkan |
| PF-07 | **Selesai** | Harga, jumlah varian, jumlah/size paket, dan nama paket di beranda/katalog/tentang/topbar dibaca dari database |
| PF-09 | **Selesai** | Harga produk satuan 0 ditolak |
| PF-10 | **Selesai** | Resize maks 1600 px + re-encode |
| PF-13 | **Selesai** | Batas 50 buah per baris (dapat dikonfigurasi `MAX_ITEM_QTY`) |
| PF-14 | **Selesai** | `normalize_phone()` menyimpan nomor dalam format `62…` |
| PF-15 | **Selesai** | Dialog konfirmasi aksesibel untuk status, pembayaran, ongkir, hapus bukti, hapus produk |
| NF-01 | **Selesai** | Unggah bukti pembayaran + verifikasi admin |
| NF-08 | **Selesai** | Rate limit checkout per IP + honeypot + tolakan submit terlalu cepat |
| NF-12 | **Selesai** | Idle timeout admin 30 menit |
| D-15 | **Selesai** | Header keamanan + CSP report-only + redirect HTTPS di `.htaccess` |
| D-18 | **Selesai** | `Order::paymentBadgeClass()`: `cancelled` = danger, `awaiting_verification` = info |

**Belum berubah:** NF-02 (notifikasi otomatis), NF-03 (teks kebijakan refund diisi pemilik usaha), NF-04/05/06/07, NF-09, NF-10, NF-11; PF-08 dan PF-11 (dokumentasi/meta) serta PF-12.

**Catatan keamanan:** bukti pembayaran disimpan di `storage/proofs` (di luar document root) dan hanya dapat diakses lewat route terautentikasi. Folder tersebut harus tetap tidak dapat diakses langsung dari web pada konfigurasi hosting.

---

## 12. Kesiapan Menuju Pelanggan Sungguhan

### 12.1 Definisi “siap produksi” (Definition of Done)

Sistem dinyatakan siap menerima pelanggan nyata jika **seluruh** poin berikut terpenuhi. Status per 19 November 2026:

1. TBD-01 s.d. TBD-13 sudah diisi dan diverifikasi pemilik usaha. — **Belum** (menunggu data).
2. Alur pembayaran jelas: pelanggan dapat mengunggah bukti, admin dapat memverifikasi, dan status terlihat oleh pelanggan. — **Selesai.**
3. Kebijakan pembatalan, refund, dan batas waktu pembayaran tertulis serta tercermin di sistem/UX. — **Sebagian:** mekanisme & field tersedia, teks kebijakan menunggu pemilik usaha.
4. PF-01, PF-02, PF-03 selesai. — **Selesai.**
5. Notifikasi pesanan baru minimal berupa email/WhatsApp otomatis **atau** prosedur manual yang disepakati (cek dashboard berkala). — **Belum** (SOP belum ditulis).
6. Uji end-to-end ulang pada data produksi dan uji responsif di minimal 2 perangkat nyata. — **Belum.**
7. Backup database + folder upload terjadwal, dan prosedur restore diuji. — **Belum.**
8. HTTPS aktif, `APP_DEBUG=false`, password admin default diganti, dan kredensial DB produksi terpisah. — **Belum** (konfigurasi hosting).

### 12.2 Gap analysis terhadap tujuan akhir

| Pilar | Kondisi | Kesiapan |
|---|---|---|
| Katalog & pemesanan | Lengkap dan tervalidasi | **Siap** |
| Perhitungan harga & stok | Kuat, atomik, tidak mempercayai browser | **Siap** |
| Pembayaran | Manual, tanpa bukti terunggah | **Belum** |
| Operasional pesanan | Dashboard lengkap, notifikasi manual | **Sebagian** |
| Kebijakan bisnis | Refund/expiry belum ada | **Belum** |
| Identitas merek | Desain jadi, aset asli belum ada | **Sebagian** |
| Keamanan produksi | Dasar kuat, throttle & privasi pesanan lemah | **Sebagian** |
| Dokumentasi | README + SPEC ada | **Siap (setelah disetujui)** |

---

## 13. Rencana Pengujian

### 13.1 Sudah dijalankan

Pengujian HTTP langsung dengan session & token asli mencakup: seluruh halaman customer, login/proteksi admin, stok, penjumlahan keranjang, checkout satuan & hampers, penolakan oversell, perubahan stok setelah item masuk keranjang, aturan pre-order, komposisi tidak tepat, COD pickup, update status/pembayaran/ongkir, pembatalan & restore stok, CRUD produk, upload valid/invalid, filter pesanan, CSRF 419, 404, dan blokir PHP di uploads. Rincian ada di README Bagian 9.

Tambahan 19 November 2026 (P0/P1): semua halaman customer + login admin 200; checkout satuan menghasilkan token akses 64 karakter dan tenggat bayar +24 jam; gate verifikasi menolak 4 digit salah tanpa membocorkan data; 4 digit benar membuka pesanan; unggah bukti PNG tersimpan privat dan status menjadi `awaiting_verification`; admin dapat menampilkan bukti; transisi `new→completed` dan `confirmed→completed` ditolak; `confirmed→cancelled` mengembalikan stok tepat dan menyimpan alasan; 5 login gagal mengunci percobaan berikutnya; ubah jumlah keranjang memperbarui subtotal; header keamanan terkirim. Data uji dibersihkan setelah pengujian. Rincian di `TASKS.md` Bagian 12.3.

### 13.2 Wajib ditambahkan sebelum produksi

| Area | Skenario | Status |
|---|---|---|
| Bukti bayar | Upload JPG/PDF valid, file berbahaya ditolak, admin melihat & memverifikasi | Sebagian teruji lokal (PNG); uji PDF & file berbahaya menyusul di T9.3 |
| Privasi pesanan | Kode benar + verifikasi salah ditolak; rate limit akses kode | Teruji lokal |
| Stok kedaluwarsa | Pesanan unpaid melewati batas waktu → stok dilepas sekali | Teruji lewat command; perlu uji cron di hosting |
| Konsistensi status | Batal setelah `paid` tidak mengubah data finansial secara keliru | Logika terpasang; uji skenario belum |
| Beban | 50 checkout bersamaan pada stok terbatas → tidak oversell | Belum (T9.7) |
| Responsif | 360 px, 768 px, 1440 px pada perangkat/emulator nyata | Belum (T9.4) |
| Aksesibilitas | Navigasi keyboard, label form, kontras | Sebagian: skip-link, fokus, rasio AA dihitung; audit penuh belum |
| Keamanan | Brute force lintas session, XSS pada catatan/kartu ucapan, CSRF semua form | Brute force teruji; XSS/CSRF regression menyusul |
| Backup | Restore dump + upload ke lingkungan bersih | Belum (T9.9) |

---

## 14. Rekomendasi Tahapan Pengembangan

Urutan ini menjaga agar fitur baru tidak dibangun di atas keputusan bisnis yang belum ada.

### Tahap 0 — Persetujuan spesifikasi (sekarang)
- Pemilik usaha menjawab pertanyaan Bagian 15 dan mengisi TBD-01 s.d. TBD-16.
- Setujui atau revisi dokumen ini. **Tidak ada coding fitur baru sebelum ini selesai.**

### Tahap 1 — P0: keamanan & alur uang — **selesai 19 November 2026**
1. Unggah bukti pembayaran + verifikasi admin (NF-01).
2. Verifikasi akses halaman pesanan (PF-02).
3. Throttle login per akun/IP (PF-01).
4. Kebijakan batas waktu bayar + pelepasan stok (PF-03).
5. Kebijakan & alur pembatalan/refund (NF-03, PF-04, PF-06).
6. Rate limit/anti-spam checkout (NF-08).

### Tahap 2 — P1: operasional & kualitas
7. Notifikasi pesanan baru (NF-02).
8. Resize/kompresi gambar (PF-10).
9. Edit jumlah di keranjang (FR-C05).
10. Matriks transisi status (PF-05).
11. Copy dinamis dari DB (PF-07), koreksi README (PF-08).
12. CRUD kategori & varian (NF-05, NF-06).
13. Laporan & ekspor pesanan (NF-07).
14. Uji responsif perangkat nyata + audit aksesibilitas.

### Tahap 3 — P2: pertumbuhan
15. Akun opsional & riwayat pesanan (NF-04).
16. SEO lanjutan: meta per produk, OG, sitemap, robots (NF-09).
17. Konten beranda editable (NF-10).
18. Multi-admin & peran, audit log (FR-A17, FR-A20).
19. Uji otomatis & CI (NF-11).
20. Payment gateway opsional bila volume meningkat.

---

## 15. Pertanyaan Terbuka untuk Pemilik Usaha

1. Berapa harga **Paket Spesial (6 pcs)** dan **Paket Premium (12 pcs)**?
2. Berapa minimal hari pre-order hampers (H-1, H-2, H-3, atau lain)?
3. Bagaimana ongkir ditentukan: flat per area, per km, atau tetap manual per pesanan?
4. Apakah pelanggan wajib mengunggah bukti bayar, atau cukup konfirmasi WhatsApp?
5. Berapa batas waktu pembayaran sebelum pesanan otomatis dibatalkan dan stok dilepas?
6. Apa kebijakan pembatalan/refund (sebelum diproses, setelah diproses, produk rusak)?
7. Apakah ada jam cut-off pemesanan same-day dan slot pengambilan?
8. Apakah perlu pajak/faktur resmi?
9. Apakah notifikasi cukup via WhatsApp manual, atau perlu otomatis (biaya layanan)?
10. Siapa saja yang akan memakai dashboard (butuh multi-admin/peran)?
11. Aset merek: logo, foto produk, alamat, nomor WhatsApp, jam buka — kapan tersedia?

---

## 16. Catatan Keputusan & Perubahan

| Tanggal | Keputusan | Alasan |
|---|---|---|
| Awal proyek | PHP native MVC + MySQL, tanpa framework/Composer | Sesuai brief; deployment sederhana di shared hosting/Laragon |
| Awal proyek | Guest checkout tanpa akun | Mengurangi hambatan pembelian |
| Awal proyek | Stok tunggal di `product_variants` | Menghindari stok ganda satuan vs hampers |
| Awal proyek | `price NULL` untuk paket belum berharga | Larangan mengarang harga |
| Awal proyek | Stok dipotong saat pesanan dibuat | Mencegah oversell |
| Awal proyek | Ongkir manual | Tarif belum diberikan |
| 19 Nov 2026 | Pengembangan fitur dihentikan sementara | Menyusun & mengevaluasi spesifikasi lebih dulu |
| 19 Nov 2026 | Blok P0 & P1 teknis diimplementasikan dan diuji | Menutup celah uang, privasi pesanan, stok tertahan, brute force, dan spam checkout |
| 19 Nov 2026 | Bukti bayar disimpan di `storage/proofs` (privat) | Data pembayaran sensitif tidak boleh dapat diakses langsung dari web |
| 19 Nov 2026 | Tenggat bayar bawaan 24 jam dan batas 50 buah/baris, keduanya dapat diubah admin | Nilai awal konservatif; bukan data bisnis final |
| 19 Nov 2026 | Zona waktu MySQL disamakan dengan PHP saat koneksi | Tanpa ini `NOW()` dan waktu aplikasi meleset sehingga tenggat & throttle salah hitung |

---

## 17. Persetujuan

Dokumen ini menjadi **baseline** pengembangan. Setelah disetujui:

- Perubahan kebutuhan harus dicatat di Bagian 16 beserta alasannya.
- Item TBD yang sudah diisi dicatat sebagai keputusan bisnis, bukan diubah diam-diam di kode.
- Prioritas pengerjaan mengikuti Bagian 14 kecuali pemilik usaha menentukan lain.
