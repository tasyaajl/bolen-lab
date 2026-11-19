# TASKS — Roadmap Pengembangan BaleBolen

| Item | Keterangan |
|---|---|
| Dokumen | Roadmap audit & rencana kerja |
| Versi | 1.0 |
| Tanggal audit | 19 November 2026 |
| Basis acuan | `SPEC.md`, brief bisnis, dan pembacaan langsung seluruh kode di repositori |
| Status dokumen | **Diperbarui setelah implementasi P0/P1** (19 November 2026) — lihat Bagian 12 |
| Perubahan kode | Blok P0 & P1 sudah diimplementasikan; rincian di Bagian 12 |

> **Catatan penting:** `UI-SPEC.md` **tidak ditemukan** di direktori proyek pada saat audit. Karena brief menyebut dokumen tersebut sebagai acuan, ketiadaannya dicatat sebagai temuan **D-01** dan tugas prasyarat **T1.2**. Dokumen ini tidak mengarang isi `UI-SPEC.md` dan tidak menggantinya.

---

## 0. Cara Membaca Dokumen

### 0.1 Legenda status

| Status | Arti | Aturan penetapan |
|---|---|---|
| **DONE** | Selesai dan memenuhi acceptance criteria | Hanya dipakai bila implementasi diverifikasi di kode/perilaku, bukan sekadar berkasnya ada |
| **IN PROGRESS** | Sedang dikerjakan | Tidak ada pada audit ini karena pengembangan dihentikan sementara |
| **TODO** | Belum dimulai | Termasuk fitur baru dan perbaikan yang belum dikerjakan |
| **NEEDS REVIEW** | Ada implementasi/klaim, tetapi belum dapat diterima | Perlu keputusan, audit lanjutan, atau pembanding `UI-SPEC.md` |

### 0.2 Legenda prioritas

| Prioritas | Arti |
|---|---|
| **P0** | Wajib sebelum menerima pelanggan nyata: keamanan, uang, stok, privasi, atau kepatuhan |
| **P1** | Penting untuk operasional yang benar dan kualitas; dapat dikerjakan setelah P0 |
| **P2** | Penyempurnaan, pertumbuhan, atau otomasi opsional |

### 0.3 Aturan kerja

1. Satu tugas = satu tujuan yang dapat diuji. Jangan menandai DONE bila acceptance criteria belum terbukti.
2. Jangan mengubah `SPEC.md`, `UI-SPEC.md`, atau kode aplikasi tanpa persetujuan pemilik usaha.
3. Data bisnis yang belum tersedia tetap **TBD**; jangan mengisi harga, ongkir, rekening, alamat, atau stok dengan contoh.
4. Semua perubahan skema database harus disertai migrasi/ALTER yang terdokumentasi dan rencana rollback.
5. Setiap rilis wajib melewati gerbang di Bagian 5.

---

## 1. Ringkasan Audit

### 1.1 Cakupan yang diperiksa

- Seluruh struktur folder: `app/`, `bootstrap/`, `config/`, `database/`, `public/`, `routes/`, `storage/`.
- 13 controller (6 customer, 7 admin), 6 model, 12 berkas core/helper, 25 view, 2 layout admin/auth + 1 layout customer.
- 35 route (11 customer, 24 admin), 9 tabel MySQL, seeder, skema.
- `public/assets/css/app.css` (1.604 baris), `admin.css` (494 baris), `app.js` (181 baris), `admin.js` (23 baris).
- Konfigurasi environment, session, CSRF, upload, `.htaccess`, router built-in server.
- `README.md` sebagai sumber klaim pengujian, dibandingkan dengan kode aktual.
- Pemeriksaan sintaks: **seluruh berkas PHP lolos `php -l`** pada 19 November 2026.

### 1.2 Kondisi nyata proyek

**Sudah jadi dan berfungsi (berdasarkan kode + dokumentasi pengujian sebelumnya):**

- Fondasi MVC tanpa framework, router berparameter, PDO prepared statement, layout, session project-local, CSRF global, validator, upload handler.
- Database 9 tabel dengan foreign key, indeks, dan seeder idempoten. Stok awal 0, harga paket `NULL`, data bisnis kosong.
- Alur customer lengkap: beranda, katalog + filter kategori, detail produk, builder hampers, keranjang session, checkout guest, halaman pesanan, tentang, kontak, halaman error.
- Alur admin lengkap: login/logout, dashboard, daftar/filter/detail pesanan, update status pesanan/pembayaran/ongkir, pembatalan + restore stok, CRUD produk, edit paket hampers, stok & varian, pengaturan bisnis, profil/password.
- Pengamanan dasar: CSRF pada semua POST, prepared statement, escaping output, password hashing, proteksi route admin, validasi upload, blokir eksekusi PHP di folder upload.
- Integritas transaksi: stok dikunci `SELECT ... FOR UPDATE`, harga/total dihitung server, komposisi hampers divalidasi ulang, pembatalan mengembalikan stok satu kali.

**Belum selesai / belum layak produksi:**

- Belum ada unggah bukti pembayaran (SPEC NF-01).
- Halaman pesanan dapat dibuka siapa pun yang mengetahui kode (PF-02).
- Stok pesanan `unpaid` tidak pernah dilepas otomatis (PF-03).
- Throttle login masih berbasis session, mudah dilewati dengan sesi baru (PF-01).
- Kebijakan pembatalan/refund dan batas waktu bayar belum ada (NF-03, TBD-11).
- Belum ada anti-spam/rate limit checkout (NF-08).
- Belum ada pengujian otomatis, uji perangkat nyata, audit aksesibilitas, atau pengukuran performa.
- `UI-SPEC.md` tidak ada sehingga keputusan visual belum memiliki baseline tertulis.

### 1.3 Temuan bug/ketidaksesuaian yang perlu perbaikan

| Temuan | Bukti | Dampak | Tugas |
|---|---|---|---|
| Label harga beranda memakai `$units[0]['price']` tanpa cek array kosong; fallback `Rp5.000` | `app/Views/home.php` baris awal | Warning PHP saat katalog kosong dan menampilkan harga yang tidak berasal dari database | T2.7 |
| Harga/varian/paket di-hardcode di beranda, topbar, katalog | `home.php`, `layouts/main.php`, `catalog.php` | Copy tidak mengikuti perubahan admin; melanggar larangan data karangan | T2.8 |
| Kontras teks tombol utama putih di atas emas hanya **2,94:1** | Token `--gold #C58A52` + `.btn--primary` | Gagal WCAG 2.1 AA untuk teks normal maupun besar | T2.6 |
| Teks emas di cream 2,77:1; sage 3,83:1; terracotta 3,62:1 | `app.css` | Gagal AA untuk teks kecil (eyebrow, error, stok) | T2.6 |
| README mengklaim filter harga & status stok | `README.md` Bagian 5 vs `CatalogController` | Dokumentasi menyesatkan; fitur tidak ada | T3.7 |
| Harga produk satuan boleh `0` | `ProductController::validated()` memakai `< 0` | Produk gratis tidak sengaja dapat lolos | T6.15 |
| Konfirmasi hanya pada hapus produk | `products/index.php`; aksi lain tanpa konfirmasi | Salah klik pembatalan/stok/ongkir berdampak permanen | T6.13 |
| Badge pembayaran `cancelled` tampil sebagai warning | `order.php` memetakan hanya `paid` vs lainnya | Status visual menyesatkan | T2.6/T5.3 |
| Status pesanan bebas melompat; `completed` masih bisa dibatalkan | `Order::updateStatus()` | Data operasional/finansial tidak konsisten | T5.9 |

### 1.4 Pekerjaan yang sudah selesai, sedang berjalan, belum mulai, perlu diperbaiki

| Kategori | Jumlah tugas | Keterangan |
|---|---|---|
| **DONE** | 68 | Fondasi, katalog, builder, checkout, transaksi stok, admin inti, keamanan dasar, seluruh blok P0 dan P1 teknis |
| **IN PROGRESS** | 0 | — |
| **TODO** | 28 | P2 (pertumbuhan), dokumen desain, pengujian lapangan, dan deployment |
| **NEEDS REVIEW** | 4 | Desain vs `UI-SPEC.md`, keputusan filter, keputusan WhatsApp API, bukti regresi 23 skenario |
| **Total** | **100** | 10 milestone |

### 1.5 Temuan kritis (jangan dianggap selesai)

1. ~~**Uang belum tertutup:** tidak ada bukti bayar, tidak ada batas waktu bayar, tidak ada kebijakan refund.~~ **Selesai 19 Nov 2026:** bukti bayar (storage privat), tenggat bayar + command pelepasan stok, field kebijakan pembatalan/refund, alasan batal.
2. ~~**Privasi pesanan lemah:** kode pesanan adalah satu-satunya kunci akses.~~ **Selesai:** verifikasi 4 digit WhatsApp + token acak panjang, dengan rate limit.
3. ~~**Stok dapat tertahan:** pesanan yang ditinggalkan tidak pernah melepas stok.~~ **Selesai:** `bin/release-expired.php` membatalkan pesanan `unpaid` yang melewati tenggat dan mengembalikan stok sekali.
4. ~~**Brute force masih mungkin:** throttle login hanya per session.~~ **Selesai:** tabel `login_attempts` per email + IP.
5. ~~**Checkout dapat dibanjiri:** tidak ada rate limit atau anti-spam.~~ **Selesai:** rate limit per IP, honeypot, dan penolakan submit terlalu cepat.
6. **`UI-SPEC.md` hilang:** belum dibuat; baseline desain tertulis masih menjadi prasyarat tersisa (T1.2).

---

## 2. Sumber Acuan dan Status Dokumen

| Dokumen | Status saat audit | Catatan |
|---|---|---|
| `SPEC.md` | Ada, 478 baris, 17 bagian | Metadata masih menyebut “menunggu persetujuan”, sedangkan brief menyatakan sudah disetujui (D-02) |
| `UI-SPEC.md` | **Tidak ada** | Dicari di root proyek; tidak ditemukan (D-01) |
| `README.md` | Ada | Memuat klaim filter yang tidak akurat (D-03) |
| `TASKS.md` | Dokumen ini | Baru dibuat; tidak mengubah berkas lain |

**Konsekuensi:** tugas M2 yang menyangkut design system dan wireframe tidak dapat diverifikasi terhadap `UI-SPEC.md`. Implementasi CSS aktual dipakai sebagai bukti kondisi, bukan sebagai pengganti spesifikasi desain.

---

## 3. Ringkasan Milestone

| Milestone | Fokus | Jumlah tugas | DONE | NEEDS REVIEW | TODO | Status milestone |
|---|---|---:|---:|---:|---:|---|
| M1 | Project Audit & Existing Foundation | 8 | 6 | 0 | 2 | Belum selesai — 75% |
| M2 | UI Foundation & Scaffold | 8 | 7 | 1 | 0 | Hampir selesai — 88% |
| M3 | Product Catalog | 7 | 6 | 1 | 0 | Hampir selesai — 86% |
| M4 | Customer Interactions | 9 | 9 | 0 | 0 | **Selesai — 100%** |
| M5 | Order & Payment | 9 | 9 | 0 | 0 | **Selesai — 100%** |
| M6 | Admin Dashboard | 19 | 12 | 0 | 7 | Belum selesai — 63% |
| M7 | WhatsApp Integration | 6 | 4 | 1 | 1 | Belum selesai — 67% |
| M8 | Security & Validation | 14 | 14 | 0 | 0 | **Selesai — 100%** |
| M9 | Testing & Quality Assurance | 10 | 1 | 1 | 8 | Belum selesai — 10% |
| M10 | Build, Deployment & Production Readiness | 10 | 0 | 0 | 10 | Belum dimulai — 0% |
| **Total** | | **100** | **68** | **4** | **28** | **3 milestone 100%, belum siap produksi** |

---

## 4. Register Ketidaksesuaian (D-01 – D-18)

> Dicatat terpisah sesuai brief. Tidak ada keputusan bisnis yang diambil di sini; setiap item menunjuk tugas tindak lanjut.

| ID | Ketidaksesuaian | Bukti | Dampak | Tindak lanjut |
|---|---|---|---|---|
| **D-01** | `UI-SPEC.md` tidak ada | Pencarian root proyek | Tidak ada baseline desain; M2 tidak dapat diverifikasi | T1.2 (P0) |
| **D-02** | Status `SPEC.md` masih “Menunggu persetujuan”, brief menyebut disetujui | Metadata `SPEC.md` | Status dokumen ambigu | Perbarui metadata setelah persetujuan resmi |
| **D-03** | README mengklaim filter harga & status stok; kode hanya filter kategori | `README.md` vs `CatalogController::index` | Ekspektasi pengguna/dokumentasi salah | T3.7 (P2) |
| **D-04** | Harga `Rp5.000`, “4 varian”, “2 paket”, “6/12” hardcoded; fallback mengarang Rp5.000; `$units[0]` tanpa cek kosong | `home.php`, `catalog.php`, `layouts/main.php` | Copy tidak mengikuti database; potensi warning dan harga palsu | T2.7, T2.8 (P1) |
| **D-05** | Jumlah item keranjang tidak dapat diubah | `Cart` hanya `add`/`remove`; route tanpa update | Pelanggan harus hapus lalu tambah ulang | T4.4 (P1) |
| **D-06** | Input kartu ucapan hanya di checkout, tidak di builder hampers | `product.php` vs `checkout.php` | Alur hadiah kurang terasa saat menyusun paket | Keputusan desain; belum ada tugas implementasi tanpa persetujuan |
| **D-07** | Belum ada unggah bukti pembayaran; copy menyatakan “tidak wajib” | Tidak ada route/kolom/UI | Admin harus meminta bukti via WhatsApp; rawan lupa | T5.5 (P0) |
| **D-08** | Halaman pesanan terbuka bagi pemegang kode | `OrderController::show` tanpa verifikasi | Kebocoran nama, WhatsApp, alamat, dan rincian pesanan | T5.6 (P0) |
| **D-09** | Stok pesanan `unpaid` tidak dilepas | Tidak ada expiry/job | Stok tertahan selamanya | T5.7 (P0) |
| **D-10** | Throttle login berbasis session | `Core/Auth.php` | Brute force lintas sesi tidak terhambat | T8.9 (P0) |
| **D-11** | Transisi status bebas; pesanan `completed` masih dapat dibatalkan | `Order::updateStatus` | Stok dapat dikembalikan setelah pesanan selesai | T5.8, T5.9 (P0/P1) |
| **D-12** | Harga satuan `0` diterima | `ProductController::validated()` | Produk gratis tidak sengaja | T6.15 (P2) |
| **D-13** | Konfirmasi hanya pada hapus produk | `products/index.php` | Aksi sensitif lain rawan salah klik | T6.13 (P1) |
| **D-14** | Kontras gagal AA: putih/emas 2,94:1 (tombol primer); emas/cream 2,77:1 (eyebrow); sage/cream 3,83:1 (teks kecil); terracotta/cream 3,62:1 (error/stok) | Perhitungan rasio dari token dan aturan CSS aktual | Teks sulit dibaca, tidak memenuhi target WCAG AA | T2.6 (P1) |
| **D-15** | Tidak ada security header dan pemaksaan HTTPS | `App::run`, `.htaccess` | Clickjacking, MIME sniffing, mixed content | T8.10, T8.14 (P1/P0) |
| **D-16** | Istilah “tanggal penerimaan” dipakai, brief menyebut “tanggal pemesanan” | `checkout.php`, `order.php` | Potensi salah tafsir pelanggan | Keputusan terminologi; catat di `UI-SPEC.md` |
| **D-17** | Folder `storage/uploads/` tidak dipakai runtime | Struktur proyek | Kebingungan operasional | T10.5 (P1) |
| **D-18** | Badge pembayaran `cancelled` memakai kelas warning, bukan danger; pemetaan warna tidak konsisten dengan status pesanan | `order.php` | Status berisiko disalahtafsirkan meskipun label teks tetap ada | T2.6 (P1) |

---

## 5. Definition of Done dan Release Gates

### 5.1 Definition of Done per tugas

Sebuah tugas boleh DONE hanya jika:

1. Implementasi ada dan dapat dijalankan.
2. Acceptance criteria terbukti (uji manual/otomatis yang dicatat).
3. Tidak ada error/warning baru pada log.
4. Perubahan skema memiliki migrasi dan rollback.
5. Dokumen terkait (`SPEC.md`, `UI-SPEC.md`, `README.md`) diperbarui bila perilaku berubah.
6. Tidak ada data bisnis karangan yang masuk ke kode atau database.

### 5.2 Gate sebelum produksi

- [ ] Semua P0 DONE dan diuji ulang.
- [ ] TBD-01 s.d. TBD-13 diisi dan diverifikasi pemilik usaha.
- [ ] Bukti bayar, verifikasi akses pesanan, expiry stok, refund, throttle, dan anti-spam berfungsi.
- [ ] `UI-SPEC.md` ada dan disetujui; kontras AA terpenuhi.
- [ ] UAT pada data produksi dan minimal 2 perangkat nyata lulus.
- [ ] Backup + restore diuji; HTTPS aktif; password admin default diganti.
- [ ] `APP_DEBUG=false`; log tidak membocorkan data sensitif.

---

## 6. Roadmap per Milestone

### Milestone 1 — Project Audit & Existing Foundation

#### T1.1 — Audit struktur, arsitektur, dan inventaris proyek
- **Deskripsi:** Memetakan folder, controller, model, view, route, tabel, dan aset; memastikan tidak ada fitur yang diasumsikan tanpa bukti kode.
- **Prioritas:** P1 · **Status:** DONE
- **Acceptance criteria:** Inventaris folder/route/tabel lengkap; setiap klaim status punya rujukan berkas; tidak ada berkas aplikasi yang diubah.
- **Berkas:** `TASKS.md`
- **Dependensi:** —

#### T1.2 — Sediakan dan setujui `UI-SPEC.md`
- **Deskripsi:** Menyusun `UI-SPEC.md` yang hilang: identitas desain, design system, navigasi, spesifikasi halaman, dashboard, responsive, accessibility, wireframe desktop + mobile, komponen, interaksi, kesesuaian `SPEC.md`, dan usulan yang butuh persetujuan. Bedakan fitur yang sudah ada vs usulan.
- **Prioritas:** P0 · **Status:** TODO
- **Acceptance criteria:** Berkas ada di root; memuat 13 bagian yang diminta; 8 layar wireframe desktop + mobile; tidak mengarang data bisnis; ditinjau dan disetujui pemilik usaha.
- **Berkas:** `UI-SPEC.md` (baru)
- **Dependensi:** Tidak ada; memblokir T2.3 dan verifikasi desain M2.

#### T1.3 — Verifikasi konfigurasi, bootstrap, dan environment
- **Deskripsi:** Memastikan `.env.example`, `config/config.php`, autoload, timezone, session path, dan batas upload konsisten serta tidak memuat kredensial asli.
- **Prioritas:** P1 · **Status:** DONE
- **Acceptance criteria:** Bootstrap berjalan; `.env` tidak terlacak; `APP_DEBUG=false` default; timezone `Asia/Jakarta`; session menulis ke `storage/sessions`.
- **Berkas:** `.env.example`, `config/config.php`, `bootstrap/app.php`, `app/Core/Session.php`
- **Dependensi:** —

#### T1.4 — Verifikasi skema database dan seeder
- **Deskripsi:** Memeriksa 9 tabel, foreign key, indeks, enum status, serta seeder idempoten tanpa stok/harga fiktif.
- **Prioritas:** P0 · **Status:** DONE
- **Acceptance criteria:** `schema.sql` membuat 9 tabel; seeder dapat dijalankan dua kali tanpa duplikasi; 4 varian stok 0; 2 paket harga `NULL`; data bisnis kosong.
- **Berkas:** `database/schema.sql`, `database/seed.php`
- **Dependensi:** —

#### T1.5 — Pemeriksaan sintaks baseline
- **Deskripsi:** Menjalankan `php -l` pada seluruh berkas PHP untuk memastikan tidak ada parse error sebelum perencanaan.
- **Prioritas:** P1 · **Status:** DONE
- **Acceptance criteria:** Seluruh berkas PHP lolos `php -l` pada 19 November 2026; hasil dicatat.
- **Berkas:** seluruh `*.php` di `app/`, `bootstrap/`, `config/`, `database/`, `routes/`, `public/`
- **Dependensi:** —

#### T1.6 — Register ketidaksesuaian dan risiko
- **Deskripsi:** Mendokumentasikan D-01 s.d. D-18 secara terpisah dari daftar tugas, termasuk bukti dan tindak lanjut.
- **Prioritas:** P1 · **Status:** DONE
- **Acceptance criteria:** Setiap temuan punya ID, bukti, dampak, dan tugas; tidak ada keputusan bisnis yang diambil sendiri.
- **Berkas:** `TASKS.md` Bagian 4
- **Dependensi:** —

#### T1.7 — Baseline Git sebelum implementasi
- **Deskripsi:** Seluruh proyek masih untracked. Setelah disetujui, buat commit baseline agar perubahan berikutnya dapat direview dan di-rollback.
- **Prioritas:** P1 · **Status:** TODO
- **Acceptance criteria:** `git status` bersih setelah commit; `.env`, `storage/logs`, `storage/sessions`, dan uploads tidak ikut; commit berisi kode + dokumen yang disetujui.
- **Berkas:** `.gitignore`, riwayat Git
- **Dependensi:** Persetujuan pemilik usaha; T1.2 bila `UI-SPEC.md` disertakan.

#### T1.8 — Inventaris route dan kontrak halaman
- **Deskripsi:** Mencatat 11 route customer dan 24 route admin beserta controller/metode dan proteksinya.
- **Prioritas:** P2 · **Status:** DONE
- **Acceptance criteria:** Daftar route lengkap; route admin terlindungi `Auth::requireLogin()` kecuali login.
- **Berkas:** `routes/web.php`, `routes/admin.php`
- **Dependensi:** —

---

### Milestone 2 — UI Foundation & Scaffold

#### T2.1 — Layout utama customer, admin, dan auth
- **Deskripsi:** Tiga layout tersedia dengan struktur HTML semantik, meta viewport, title dinamis, dan slot konten.
- **Prioritas:** P1 · **Status:** DONE
- **Acceptance criteria:** Setiap halaman memakai layout yang sesuai; tidak ada HTML rusak; title terisi.
- **Berkas:** `app/Views/layouts/main.php`, `admin.php`, `auth.php`
- **Dependensi:** —

#### T2.2 — Navbar, footer, sidebar admin, dan navigasi mobile
- **Deskripsi:** Navbar sticky dengan cart count, menu mobile, footer 4 kolom, sidebar admin 256 px dengan badge pesanan baru.
- **Prioritas:** P1 · **Status:** DONE
- **Acceptance criteria:** Toggle mobile memperbarui `aria-expanded`; cart count akurat; sidebar dapat dibuka/ditutup di mobile; link aktif benar.
- **Berkas:** `layouts/main.php`, `layouts/admin.php`, `public/assets/js/app.js`, `admin.js`, `app.css`, `admin.css`
- **Dependensi:** —

#### T2.3 — Design token dan komponen dasar
- **Deskripsi:** Menyelaraskan token warna, tipografi, radius, shadow, spacing, tombol, form, badge, notice, card, dan tabel dengan `UI-SPEC.md`.
- **Prioritas:** P1 · **Status:** NEEDS REVIEW
- **Acceptance criteria:** Token aktual terdokumentasi di `UI-SPEC.md`; setiap komponen punya varian/status; tidak ada nilai warna hardcoded baru di luar token; kontras diaudit.
- **Berkas:** `public/assets/css/app.css`, `admin.css`, `UI-SPEC.md`
- **Dependensi:** T1.2.

#### T2.4 — Responsive layout dasar
- **Deskripsi:** Layout customer memakai breakpoint 1080/900/720/480 px dan admin 1100/900/560 px, dengan grid yang menyesuaikan.
- **Prioritas:** P1 · **Status:** DONE
- **Acceptance criteria:** Breakpoint terdefinisi; grid produk 4→3→2→1; sidebar admin menjadi off-canvas; tidak ada overflow yang terlihat dari inspeksi CSS.
- **Berkas:** `app.css`, `admin.css`
- **Dependensi:** —

#### T2.5 — Fokus keyboard dan skip-link
- **Deskripsi:** Menambahkan skip-link ke konten utama, gaya `:focus-visible` yang jelas pada link/tombol, serta pengelolaan fokus pada menu mobile.
- **Prioritas:** P1 · **Status:** DONE
- **Acceptance criteria:** Tab dari awal halaman menampilkan skip-link; semua kontrol memiliki indikator fokus; fokus kembali ke tombol setelah menu ditutup; urutan tab logis.
- **Berkas:** `layouts/main.php`, `layouts/admin.php`, `app.css`, `admin.css`, `app.js`
- **Dependensi:** T1.2.

#### T2.6 — Perbaikan kontras WCAG 2.1 AA
- **Deskripsi:** Mengganti pemakaian warna yang gagal AA. Usulan yang perlu persetujuan: `--gold-strong #9A6129` untuk latar tombol (putih 5,10:1), `--text-gold #8A5A2B` untuk teks (5,52:1), `--text-danger #9C4634` (5,92:1), `--text-sage #6B6E5A` (4,94:1). Jangan mengubah identitas merek tanpa persetujuan.
- **Prioritas:** P1 · **Status:** DONE
- **Acceptance criteria:** Putih/emas minimal 4,5:1 atau tombol primer memakai kombinasi lain yang lolos; teks kecil emas/sage/terracotta lolos 4,5:1; status tidak hanya dibedakan warna; rasio dicatat di `UI-SPEC.md`.
- **Berkas:** `app.css`, `admin.css`, `UI-SPEC.md`
- **Dependensi:** T1.2; persetujuan palet.

#### T2.7 — Perbaiki label harga beranda saat katalog kosong
- **Deskripsi:** `home.php` mengakses `$units[0]` tanpa cek dan memakai fallback `Rp5.000`. Ganti dengan akses aman dan tampilkan keadaan “harga belum tersedia” bila data tidak ada; jangan mengarang harga.
- **Prioritas:** P1 · **Status:** DONE
- **Acceptance criteria:** Dengan 0 produk, beranda tidak menghasilkan warning dan tidak menampilkan Rp5.000; dengan produk, harga diambil dari database; topbar juga dinamis.
- **Berkas:** `app/Views/home.php`, `app/Views/layouts/main.php`
- **Dependensi:** —

#### T2.8 — Hilangkan copy bisnis hardcoded
- **Deskripsi:** “4 varian”, “2 paket”, “Rp5.000”, “6/12 buah” di beranda/katalog/topbar harus berasal dari database atau setting, bukan teks tetap.
- **Prioritas:** P1 · **Status:** DONE
- **Acceptance criteria:** Menambah/mengubah varian, produk, atau harga di admin mengubah copy terkait tanpa edit kode; tidak ada nominal/angka bisnis hardcoded yang tersisa di view customer.
- **Berkas:** `home.php`, `catalog.php`, `layouts/main.php`, controller terkait
- **Dependensi:** T2.7.

---

### Milestone 3 — Product Catalog

#### T3.1 — Beranda editorial
- **Deskripsi:** Hero, fakta singkat, kategori, produk unggulan, promo hampers, keunggulan, CTA, dan footer.
- **Prioritas:** P1 · **Status:** DONE
- **Acceptance criteria:** Semua section tampil; CTA mengarah ke route yang benar; gambar placeholder diberi label ilustrasi.
- **Berkas:** `app/Controllers/HomeController.php`, `app/Views/home.php`, `partials/product-card.php`
- **Dependensi:** —

#### T3.2 — Katalog dan filter kategori
- **Deskripsi:** `/menu` menampilkan bolen satuan dan paket hampers; filter `?kategori=bolen-satuan|paket-hampers`.
- **Prioritas:** P0 · **Status:** DONE
- **Acceptance criteria:** Filter menampilkan hanya kategori yang dipilih; filter tidak valid ditangani; produk nonaktif tidak tampil; harga dari database.
- **Berkas:** `CatalogController.php`, `Product::active()`, `catalog.php`
- **Dependensi:** —

#### T3.3 — Detail produk dan produk terkait
- **Deskripsi:** Halaman `/menu/{slug}` menampilkan nama, deskripsi, harga, stok, breadcrumb, form tambah, dan produk terkait.
- **Prioritas:** P0 · **Status:** DONE
- **Acceptance criteria:** Produk tidak ditemukan → 404; stok 0 menonaktifkan tombol; produk terkait maksimal 4; harga `NULL` ditampilkan jelas.
- **Berkas:** `CatalogController::show`, `product.php`
- **Dependensi:** —

#### T3.4 — Status stok dan ketersediaan
- **Deskripsi:** Badge stok tersedia/habis, pembatasan jumlah, dan penolakan melebihi stok.
- **Prioritas:** P0 · **Status:** DONE
- **Acceptance criteria:** Jumlah > stok ditolak dengan pesan; stok 0 tidak dapat ditambahkan; stok dihitung dari `product_variants`.
- **Berkas:** `Product::availableStock`, `CartController::add`, `product.php`
- **Dependensi:** —

#### T3.5 — Daftar paket hampers dan gate harga/pre-order
- **Deskripsi:** Paket tampil dengan ukuran, status pre-order, dan tidak dapat dipesan bila harga `NULL` atau pre-order belum diatur.
- **Prioritas:** P0 · **Status:** DONE
- **Acceptance criteria:** Paket tanpa harga menampilkan pesan dan tidak masuk keranjang; tanpa pre-order juga diblokir; komposisi harus tepat.
- **Berkas:** `catalog.php`, `product.php`, `CartController::addHampers`, `Product`/`Setting`
- **Dependensi:** —

#### T3.6 — Empty state katalog dan kategori
- **Deskripsi:** Pesan jelas saat tidak ada produk aktif atau paket aktif.
- **Prioritas:** P2 · **Status:** DONE
- **Acceptance criteria:** Tidak ada grid kosong tanpa penjelasan; CTA tetap tersedia.
- **Berkas:** `catalog.php`, `home.php`
- **Dependensi:** —

#### T3.7 — Keputusan pencarian dan filter lanjutan
- **Deskripsi:** `SPEC.md` hanya mewajibkan filter kategori, tetapi `README.md` mengklaim filter harga/stok. Putuskan: koreksi dokumentasi atau setujui fitur baru (harga, stok, pencarian, urutan). Jangan implementasi tanpa persetujuan.
- **Prioritas:** P2 · **Status:** NEEDS REVIEW
- **Acceptance criteria:** Keputusan tertulis; README dan `SPEC.md` konsisten; bila disetujui, filter punya acceptance criteria sendiri dan tidak mengubah perilaku checkout.
- **Berkas:** `README.md`, `SPEC.md`, `UI-SPEC.md`, `CatalogController.php`
- **Dependensi:** Persetujuan pemilik usaha.

---

### Milestone 4 — Customer Interactions

#### T4.1 — Builder hampers interaktif
- **Deskripsi:** Stepper per varian, total real-time, tombol aktif hanya saat komposisi tepat, batas stok per varian.
- **Prioritas:** P0 · **Status:** DONE
- **Acceptance criteria:** Tombol nonaktif saat total ≠ ukuran paket; stok 0 menonaktifkan baris; total mengikuti jumlah; tanpa JS form tetap divalidasi server.
- **Berkas:** `product.php`, `public/assets/js/app.js`
- **Dependensi:** —

#### T4.2 — Validasi komposisi di server dan transaksi
- **Deskripsi:** Server memeriksa varian aktif, stok, dan jumlah tepat sebelum menambah keranjang/pesanan.
- **Prioritas:** P0 · **Status:** DONE
- **Acceptance criteria:** Komposisi 5 untuk paket 6 ditolak; varian nonaktif ditolak; dua pesanan bersamaan tidak membuat stok minus.
- **Berkas:** `CartController::addHampers`, `Order::create`, `Order::collectRequirements`
- **Dependensi:** T4.1.

#### T4.3 — Keranjang session
- **Deskripsi:** Tambah, tampilkan, hapus item; subtotal dihitung server; komposisi tersimpan.
- **Prioritas:** P0 · **Status:** DONE
- **Acceptance criteria:** Item bertahan selama session; hapus hanya item yang dipilih; subtotal = Σ line total; harga tidak dari browser.
- **Berkas:** `CartController.php`, `Cart.php`, `cart.php`
- **Dependensi:** —

#### T4.4 — Ubah jumlah item di keranjang
- **Deskripsi:** Tambahkan aksi update quantity (dan komposisi hampers bila memungkinkan) tanpa hapus-tambah ulang.
- **Prioritas:** P1 · **Status:** DONE
- **Acceptance criteria:** Route POST baru dengan CSRF; jumlah dibatasi stok terbaru; harga dihitung ulang; item tidak valid diberi pesan; keranjang kosong tetap aman.
- **Berkas:** `routes/web.php`, `CartController.php`, `Cart.php`, `cart.php`, `app.js`
- **Dependensi:** T4.3.

#### T4.5 — Validasi ulang keranjang saat checkout
- **Deskripsi:** Memeriksa ulang produk aktif, harga, komposisi, dan stok terbaru sebelum order dibuat.
- **Prioritas:** P0 · **Status:** DONE
- **Acceptance criteria:** Perubahan stok/harga setelah item masuk keranjang ditolak dengan pesan; tidak ada pesanan setengah jadi.
- **Berkas:** `CheckoutController::store`, `Cart::detailed`
- **Dependensi:** T4.3.

#### T4.6 — Data kartu ucapan hampers
- **Deskripsi:** Input nama penerima dan pesan kartu muncul hanya bila keranjang berisi hampers; disimpan sebagai snapshot pesanan.
- **Prioritas:** P2 · **Status:** DONE
- **Acceptance criteria:** Field tampil kondisional; panjang divalidasi; tersimpan dan tampil di halaman pesanan/admin; tidak wajib.
- **Berkas:** `checkout.php`, `CheckoutController.php`, `Order::create`, `order.php`
- **Dependensi:** —

#### T4.7 — Validasi checkout pickup/delivery/pembayaran/tanggal
- **Deskripsi:** Aturan kondisional: alamat & jasa kirim untuk delivery, COD hanya delivery, tunai hanya pickup, tanggal minimal sesuai pre-order.
- **Prioritas:** P0 · **Status:** DONE
- **Acceptance criteria:** Setiap pelanggaran menghasilkan pesan per field; data lama dipertahankan; pre-order `NULL` memblokir paket.
- **Berkas:** `CheckoutController.php`, `Validator.php`, `checkout.php`
- **Dependensi:** —

#### T4.8 — Anti-spam dan rate limit checkout
- **Deskripsi:** Membatasi pembuatan pesanan per IP/session/waktu, menambahkan honeypot atau timestamp form, dan menolak submit terlalu cepat/berulang.
- **Prioritas:** P0 · **Status:** DONE
- **Acceptance criteria:** Batas terdokumentasi; pelanggan normal tidak terblokir; percobaan berlebih menghasilkan pesan/429; tidak membocorkan detail internal; pengujian beban dasar lulus.
- **Berkas:** `CheckoutController.php`, `Core` (rate limiter baru), `database/schema.sql` (tabel bila perlu), `checkout.php`
- **Dependensi:** T5.7 (kebijakan expiry) untuk konsistensi.

#### T4.9 — Batas jumlah item dan kuantitas
- **Deskripsi:** Menetapkan batas wajar per baris/pesanan (mis. 50 buah) untuk mencegah penyalahgunaan.
- **Prioritas:** P2 · **Status:** DONE
- **Acceptance criteria:** Melebihi batas ditolak di server dan UI; pesan jelas; batas dapat dikonfigurasi.
- **Berkas:** `Validator.php`, `CheckoutController.php`, `CartController.php`
- **Dependensi:** Persetujuan angka batas.

---

### Milestone 5 — Order & Payment

#### T5.1 — Pembuatan pesanan transaksional
- **Deskripsi:** Header, item, komposisi, dan pengurangan stok dibuat dalam satu transaksi dengan lock.
- **Prioritas:** P0 · **Status:** DONE
- **Acceptance criteria:** Gagal satu varian = rollback total; kode unik; snapshot nama/harga; stok berkurang tepat; tidak ada oversell.
- **Berkas:** `Order::create`, `Order::lockVariants`, `Variant::adjustStock`
- **Dependensi:** —

#### T5.2 — Metode pembayaran dan aturannya
- **Deskripsi:** Transfer, QRIS, GoPay/e-wallet, COD (delivery), tunai (pickup); status awal `unpaid`.
- **Prioritas:** P0 · **Status:** DONE
- **Acceptance criteria:** Kombinasi terlarang ditolak; label metode konsisten; instruksi pembayaran hanya tampil bila data admin tersedia.
- **Berkas:** `Order::PAYMENT_METHODS`, `CheckoutController.php`, `order.php`
- **Dependensi:** —

#### T5.3 — Status pesanan dan pembayaran terpisah
- **Deskripsi:** Dua kolom, dua aksi admin, delapan status pesanan dan empat status pembayaran.
- **Prioritas:** P0 · **Status:** DONE
- **Acceptance criteria:** Mengubah satu status tidak mengubah status lain; label dan badge konsisten; enum divalidasi server.
- **Berkas:** `Order.php`, `admin/orders/show.php`
- **Dependensi:** —

#### T5.4 — Halaman detail pesanan customer
- **Deskripsi:** Kode, status, rincian, komposisi, total, instruksi bayar, tombol cetak dan WhatsApp.
- **Prioritas:** P1 · **Status:** DONE
- **Acceptance criteria:** Data sesuai snapshot; ongkir kosong ditandai menunggu konfirmasi; cetak tidak memotong konten utama; WhatsApp hanya tampil bila nomor diisi.
- **Berkas:** `OrderController.php`, `order.php`
- **Dependensi:** —

#### T5.5 — Unggah bukti pembayaran
- **Deskripsi:** Pelanggan mengunggah bukti transfer/QRIS; admin melihat dan memverifikasi. Dukung JPG/PNG/PDF dengan batas ukuran, nama acak, dan penyimpanan yang tidak dapat dieksekusi. Perbarui copy “bukti tidak wajib”.
- **Prioritas:** P0 · **Status:** DONE
- **Acceptance criteria:** Upload valid tersimpan dan terhubung ke pesanan; file berbahaya/di luar tipe ditolak; hanya pemilik akses pesanan dan admin dapat melihat; status dapat menjadi `awaiting_verification`; hapus/ganti bukti aman; audit tercatat.
- **Berkas:** migrasi `orders`/tabel baru, `OrderController.php`, `Admin/OrderController.php`, `order.php`, `admin/orders/show.php`, `ImageUploader`/uploader dokumen, route baru
- **Dependensi:** T5.6 (akses aman), T1.2 (UX).

#### T5.6 — Verifikasi akses halaman pesanan
- **Deskripsi:** Menambahkan verifikasi sebelum menampilkan detail: minimal 4 digit terakhir WhatsApp atau token acak panjang, dengan rate limit percobaan.
- **Prioritas:** P0 · **Status:** DONE
- **Acceptance criteria:** Kode saja tidak cukup; verifikasi salah ditolak dengan pesan generik; percobaan dibatasi; tautan yang sah tetap dapat dibuka; keputusan UX dicatat di `UI-SPEC.md`.
- **Berkas:** `OrderController.php`, `order.php`, route baru, `Core` rate limiter, `database/schema.sql`
- **Dependensi:** Keputusan pemilik usaha; T1.2.

#### T5.7 — Batas waktu bayar dan pelepasan stok
- **Deskripsi:** Menetapkan `payment_due_at`, job/command untuk membatalkan pesanan `unpaid` yang kedaluwarsa, mengembalikan stok sekali, dan mencatat alasan.
- **Prioritas:** P0 · **Status:** DONE
- **Acceptance criteria:** Pesanan melewati batas → `cancelled`, stok kembali tepat satu kali; pesanan `paid` tidak tersentuh; command dapat dijalankan manual/cron; log dan riwayat stok tercatat; batas dapat diatur admin.
- **Berkas:** `database/schema.sql`, `Order.php`, `bin/` (command baru), `admin/settings.php`, `SettingsController.php`
- **Dependensi:** T5.8 (kebijakan), T4.8.

#### T5.8 — Kebijakan dan alur pembatalan/refund
- **Deskripsi:** Menuliskan kebijakan yang disetujui, menambahkan field/alur admin, menyinkronkan status pembayaran saat batal, dan melarang pembatalan setelah `completed` kecuali prosedur khusus.
- **Prioritas:** P0 · **Status:** DONE
- **Acceptance criteria:** Kebijakan tampil di halaman pesanan/kontak; admin melihat konfirmasi dan alasan; pembatalan `paid` tidak mengubah data finansial secara keliru; `completed` tidak dapat dibatalkan tanpa jalur khusus; stok tetap idempoten.
- **Berkas:** `SPEC.md` (kebijakan), `order.php`, `admin/orders/show.php`, `Admin/OrderController.php`, `Order.php`, `admin/settings.php`
- **Dependensi:** Keputusan pemilik usaha (TBD-11).

#### T5.9 — Matriks transisi status pesanan
- **Deskripsi:** Mendefinisikan transisi yang diizinkan dan memvalidasinya di server, termasuk larangan menghidupkan kembali pesanan batal.
- **Prioritas:** P1 · **Status:** DONE
- **Acceptance criteria:** Transisi tidak sah ditolak; dropdown hanya menampilkan opsi valid; pengujian setiap pasangan status lulus; pesan kesalahan jelas.
- **Berkas:** `Order.php`, `Admin/OrderController.php`, `admin/orders/show.php`
- **Dependensi:** T5.8.

---

### Milestone 6 — Admin Dashboard

#### T6.1 — Login/logout admin
- **Deskripsi:** Autentikasi email/password, hash bcrypt, regenerasi session, redirect aman.
- **Prioritas:** P0 · **Status:** DONE
- **Acceptance criteria:** Kredensial salah ditolak; session berubah setelah login; logout menghapus akses; route admin tanpa login redirect.
- **Berkas:** `Admin/AuthController.php`, `Core/Auth.php`, `Models/Admin.php`, `admin/login.php`
- **Dependensi:** —

#### T6.2 — Dashboard statistik dan checklist
- **Deskripsi:** Total pesanan, pesanan baru, menunggu pembayaran, pendapatan terverifikasi, total stok, pesanan terbaru, checklist data.
- **Prioritas:** P1 · **Status:** DONE
- **Acceptance criteria:** Angka dihitung dari database; pendapatan hanya dari `paid` non-cancelled; checklist menaut ke halaman pengisian.
- **Berkas:** `Admin/DashboardController.php`, `admin/dashboard.php`, `Order::statistics`
- **Dependensi:** —

#### T6.3 — Daftar, pencarian, filter, dan pagination pesanan
- **Deskripsi:** Cari kode/nama/WhatsApp; filter status pesanan, status bayar, rentang tanggal; 15 per halaman.
- **Prioritas:** P0 · **Status:** DONE
- **Acceptance criteria:** Filter dapat dikombinasikan; nilai tidak valid diabaikan; pagination mempertahankan filter; query prepared.
- **Berkas:** `Admin/OrderController.php`, `Order::paginate`, `admin/orders/index.php`
- **Dependensi:** —

#### T6.4 — Detail pesanan dan aksi admin
- **Deskripsi:** Menampilkan item, komposisi, pelanggan, catatan, total, serta form status/pembayaran/ongkir.
- **Prioritas:** P0 · **Status:** DONE
- **Acceptance criteria:** Semua data snapshot tampil; ongkir memperbarui total; CSRF aktif; tidak ada data yang hilang.
- **Berkas:** `Admin/OrderController.php`, `admin/orders/show.php`, `Order.php`
- **Dependensi:** —

#### T6.5 — CRUD produk satuan dan upload foto
- **Deskripsi:** Tambah/edit/hapus produk, pilih varian, harga, aktif/nonaktif, foto.
- **Prioritas:** P0 · **Status:** DONE
- **Acceptance criteria:** Validasi server; slug unik; upload tipe/ukuran benar; produk yang dipakai pesanan tidak dapat dihapus; nonaktif menyembunyikan dari katalog.
- **Berkas:** `Admin/ProductController.php`, `Product.php`, `admin/products/*`, `ImageUploader.php`
- **Dependensi:** —

#### T6.6 — Pengelolaan paket hampers
- **Deskripsi:** Edit nama, harga (boleh kosong), ukuran, deskripsi, kemasan, foto, aktif/nonaktif.
- **Prioritas:** P1 · **Status:** DONE
- **Acceptance criteria:** Harga kosong disimpan `NULL`; ukuran 1–100; slug unik; foto lama dihapus saat diganti; paket tanpa harga tidak dapat dipesan.
- **Berkas:** `Admin/HampersController.php`, `admin/hampers/*`, `Product.php`
- **Dependensi:** —

#### T6.7 — Stok dan varian
- **Deskripsi:** Set/adjust stok, catatan, riwayat 80 pergerakan, ubah nama/status varian.
- **Prioritas:** P1 · **Status:** DONE
- **Acceptance criteria:** Stok tidak negatif; penyesuaian 0 ditolak; setiap perubahan tercatat; riwayat menampilkan alasan dan waktu.
- **Berkas:** `Admin/InventoryController.php`, `Variant.php`, `admin/inventory/index.php`
- **Dependensi:** —

#### T6.8 — Pengaturan bisnis
- **Deskripsi:** Identitas, kontak, rekening, QRIS, e-wallet, pengiriman, pre-order, catatan pesanan, logo.
- **Prioritas:** P0 · **Status:** DONE
- **Acceptance criteria:** Field kosong disimpan `NULL`; validasi WhatsApp/pre-order; upload logo/QRIS aman; perubahan langsung tampil di customer.
- **Berkas:** `Admin/SettingsController.php`, `Setting.php`, `admin/settings.php`
- **Dependensi:** —

#### T6.9 — Profil dan password admin
- **Deskripsi:** Ubah nama/email/password dengan verifikasi password lama.
- **Prioritas:** P1 · **Status:** DONE
- **Acceptance criteria:** Email valid dan unik; password minimal 8 karakter; password lama salah ditolak; hash diperbarui.
- **Berkas:** `Admin/SettingsController::updateProfile`, `Admin.php`
- **Dependensi:** —

#### T6.10 — CRUD kategori
- **Deskripsi:** Tambah/edit/nonaktifkan kategori; saat ini dua kategori terkunci dari seeder.
- **Prioritas:** P2 · **Status:** TODO
- **Acceptance criteria:** Kategori dengan produk tidak dapat dihapus sembarangan; slug unik; katalog mengikuti data; navigasi tetap aman.
- **Berkas:** `Admin/CategoryController.php` (baru), `Category.php` (baru), route, view, `Product.php`
- **Dependensi:** T1.2.

#### T6.11 — Tambah/hapus varian rasa
- **Deskripsi:** Kelola varian dari UI, termasuk konsekuensi stok dan produk terkait.
- **Prioritas:** P2 · **Status:** TODO
- **Acceptance criteria:** Varian baru stok 0; varian yang dipakai pesanan tidak dihapus (nonaktifkan); nama unik; audit perubahan.
- **Berkas:** `Admin/InventoryController.php`, `Variant.php`, `admin/inventory/index.php`
- **Dependensi:** T6.7.

#### T6.12 — Laporan dan ekspor pesanan
- **Deskripsi:** Rekap penjualan per periode dan ekspor CSV sesuai filter.
- **Prioritas:** P2 · **Status:** TODO
- **Acceptance criteria:** Angka cocok dengan daftar pesanan; ekspor mengikuti filter; hanya admin; data pribadi tidak berlebihan; file dapat dibuka.
- **Berkas:** `Admin/ReportController.php` (baru), route, view
- **Dependensi:** T6.3.

#### T6.13 — Konfirmasi aksi sensitif
- **Deskripsi:** Ganti `window.confirm` tunggal dengan dialog aksesibel untuk pembatalan pesanan, perubahan stok, pengosongan ongkir, dan perubahan status pembayaran `paid`.
- **Prioritas:** P1 · **Status:** DONE
- **Acceptance criteria:** Aksi sensitif meminta konfirmasi dengan ringkasan dampak; fokus terperangkap di dialog; Escape menutup; aksi batal tidak mengirim POST; tanpa JS tetap ada pengaman server.
- **Berkas:** `admin/orders/show.php`, `admin/inventory/index.php`, `admin/products/index.php`, `admin.js`, `admin.css`
- **Dependensi:** T1.2.

#### T6.14 — Resize/kompresi gambar
- **Deskripsi:** Perkecil gambar produk/logo ke maksimal 1600 px dan konversi WebP/JPEG teroptimasi.
- **Prioritas:** P1 · **Status:** DONE
- **Acceptance criteria:** Upload besar menghasilkan file lebih kecil; kualitas visual wajar; orientasi benar; format asli yang didukung tetap aman; tidak menghapus file lama sebelum sukses.
- **Berkas:** `Core/ImageUploader.php`, `config/config.php`
- **Dependensi:** T6.5, T6.6.

#### T6.15 — Tolak harga satuan 0
- **Deskripsi:** Ubah validasi harga produk satuan agar `0` ditolak kecuali ada flag gratis eksplisit.
- **Prioritas:** P2 · **Status:** DONE
- **Acceptance criteria:** Harga 0 ditolak dengan pesan; harga > 0 diterima; data lama tidak berubah diam-diam.
- **Berkas:** `Admin/ProductController.php`
- **Dependensi:** —

#### T6.16 — Tampilan tabel admin di mobile (usulan UI)
- **Deskripsi:** Mengganti horizontal scroll tabel lebar dengan kartu/kolom prioritas di bawah 560 px. Usulan UX; belum diatur eksplisit di `SPEC.md`.
- **Prioritas:** P2 · **Status:** TODO
- **Acceptance criteria:** Informasi kunci (kode, pelanggan, total, status) terlihat tanpa scroll horizontal; aksi Detail mudah dijangkau; tabel desktop tidak berubah.
- **Berkas:** `admin.css`, `admin/orders/index.php`, `admin/dashboard.php`
- **Dependensi:** T1.2.

#### T6.17 — Audit log aksi admin
- **Deskripsi:** Mencatat perubahan status, pembayaran, ongkir, stok, dan produk beserta admin/waktu.
- **Prioritas:** P2 · **Status:** TODO
- **Acceptance criteria:** Setiap aksi penting tercatat; log hanya dapat dibaca admin; tidak menyimpan password/token; retensi ditentukan.
- **Berkas:** tabel baru, `Core/Audit.php`, controller terkait, view log
- **Dependensi:** T6.4, T6.7.

#### T6.18 — Multi-admin dan peran
- **Deskripsi:** Menambah akun admin dan peran (mis. owner/operator) bila dibutuhkan.
- **Prioritas:** P2 · **Status:** TODO
- **Acceptance criteria:** Hanya owner dapat mengelola admin; operator dibatasi sesuai peran; tidak ada eskalasi hak; audit login.
- **Berkas:** `admins`, `Admin.php`, route/view admin baru, `Auth.php`
- **Dependensi:** Keputusan pemilik usaha; T6.17.

#### T6.19 — Konten beranda dapat diedit admin
- **Deskripsi:** Pindahkan copy hero, angka, dan teks section ke setting/DB agar tidak perlu developer.
- **Prioritas:** P2 · **Status:** TODO
- **Acceptance criteria:** Admin dapat mengubah teks tanpa deploy; fallback aman bila kosong; tidak ada HTML berbahaya; perubahan tampil di beranda.
- **Berkas:** `business_settings`, `SettingsController.php`, `admin/settings.php`, `home.php`
- **Dependensi:** T2.8.

---

### Milestone 7 — WhatsApp Integration

#### T7.1 — Tautan WhatsApp manual (`wa.me`)
- **Deskripsi:** Tombol WhatsApp pada beranda, kontak, halaman pesanan, dan admin; hanya muncul bila nomor diisi.
- **Prioritas:** P1 · **Status:** DONE
- **Acceptance criteria:** Nomor kosong menyembunyikan tombol; tautan membuka `wa.me` dengan pesan; `rel="noopener"`; tidak ada API otomatis yang diklaim.
- **Berkas:** `helpers.php`, `layouts/main.php`, `order.php`, `contact.php`, `admin/orders/show.php`
- **Dependensi:** —

#### T7.2 — Pesan WhatsApp berisi kode pesanan
- **Deskripsi:** Tautan konfirmasi pelanggan/admin memuat kode dan nama agar konteks langsung jelas.
- **Prioritas:** P2 · **Status:** DONE
- **Acceptance criteria:** Pesan ter-encode benar; kode pesanan tepat; tidak membocorkan data sensitif di URL publik.
- **Berkas:** `order.php`, `admin/orders/show.php`
- **Dependensi:** T7.1.

#### T7.3 — Perilaku saat nomor belum diisi
- **Deskripsi:** Semua titik WhatsApp menampilkan placeholder/penjelasan, bukan tautan rusak.
- **Prioritas:** P1 · **Status:** DONE
- **Acceptance criteria:** Tidak ada tautan `wa.me` kosong; teks placeholder jelas; halaman tetap valid.
- **Berkas:** `layouts/main.php`, `order.php`, `contact.php`
- **Dependensi:** —

#### T7.4 — Keputusan integrasi WhatsApp API
- **Deskripsi:** Brief melarang menganggap API otomatis disetujui. Siapkan perbandingan manual vs API (biaya, verifikasi template, kepatuhan) dan keputusan tertulis.
- **Prioritas:** P2 · **Status:** NEEDS REVIEW
- **Acceptance criteria:** Keputusan dan alasan tercatat; tidak ada integrasi API sebelum persetujuan; kebutuhan notifikasi otomatis dipetakan ke T7.6.
- **Berkas:** `SPEC.md`, `TASKS.md`
- **Dependensi:** Persetujuan pemilik usaha.

#### T7.5 — Normalisasi nomor telepon
- **Deskripsi:** Normalisasi input ke format `62…`, validasi panjang, dan konsisten untuk tautan WhatsApp serta data pesanan.
- **Prioritas:** P2 · **Status:** DONE
- **Acceptance criteria:** `08…`, `+62…`, `62…` menghasilkan format yang sama; nomor tidak valid ditolak; nomor tersimpan konsisten.
- **Berkas:** `Validator.php`, `CheckoutController.php`, `helpers.php`
- **Dependensi:** —

#### T7.6 — SOP notifikasi manual
- **Deskripsi:** Menulis prosedur operasional: kapan memeriksa dashboard, template balasan, pencatatan follow-up, dan eskalasi.
- **Prioritas:** P2 · **Status:** TODO
- **Acceptance criteria:** Dokumen SOP ada; staf dapat mengikuti tanpa pengetahuan kode; memuat jam pengecekan dan pemilik tanggung jawab.
- **Berkas:** `README.md` atau `docs/SOP-PESANAN.md` (baru)
- **Dependensi:** T7.4.

---

### Milestone 8 — Security & Validation

#### T8.1 — CSRF pada semua POST
- **Deskripsi:** Token session global diverifikasi sebelum routing; respons 419.
- **Prioritas:** P0 · **Status:** DONE
- **Acceptance criteria:** POST tanpa token → 419; semua form memuat token; login/logout juga dilindungi.
- **Berkas:** `Core/Csrf.php`, `bootstrap/app.php`, semua form
- **Dependensi:** —

#### T8.2 — Prepared statement seluruh query
- **Deskripsi:** PDO dengan emulasi dimatikan; seluruh nilai memakai parameter.
- **Prioritas:** P0 · **Status:** DONE
- **Acceptance criteria:** Tidak ada interpolasi input pengguna di SQL; pencarian/filter tetap parameterized.
- **Berkas:** `Core/Database.php`, model/controller
- **Dependensi:** —

#### T8.3 — Escaping output (XSS)
- **Deskripsi:** Semua output dinamis memakai `e()`; atribut dan URL di-escape.
- **Prioritas:** P0 · **Status:** DONE
- **Acceptance criteria:** Input berisi `<script>` tampil sebagai teks; tidak ada echo langsung data pengguna tanpa escape pada halaman utama.
- **Berkas:** `Core/helpers.php`, seluruh view
- **Dependensi:** —

#### T8.4 — Password hashing
- **Deskripsi:** `password_hash`/`password_verify` + rehash otomatis.
- **Prioritas:** P0 · **Status:** DONE
- **Acceptance criteria:** Hash bcrypt; password lama diverifikasi; rehash berjalan saat algoritma berubah.
- **Berkas:** `Auth.php`, `Admin.php`, `seed.php`
- **Dependensi:** —

#### T8.5 — Proteksi akses admin
- **Deskripsi:** Semua route admin memanggil `Auth::requireLogin()`; halaman admin `noindex`.
- **Prioritas:** P0 · **Status:** DONE
- **Acceptance criteria:** Akses langsung tanpa session redirect ke login; tidak ada route admin yang lolos proteksi.
- **Berkas:** `routes/admin.php`, seluruh controller admin, `layouts/admin.php`
- **Dependensi:** —

#### T8.6 — Validasi harga dan stok di server
- **Deskripsi:** Harga, total, komposisi, dan stok tidak pernah dipercaya dari browser; dikunci dalam transaksi.
- **Prioritas:** P0 · **Status:** DONE
- **Acceptance criteria:** Manipulasi harga di request tidak berpengaruh; stok dicek ulang; rollback saat gagal.
- **Berkas:** `Order.php`, `CheckoutController.php`, `Cart.php`
- **Dependensi:** —

#### T8.7 — Validasi upload dan blokir eksekusi
- **Deskripsi:** Batas ukuran, MIME asli, nama acak, hanya gambar; PHP di `/uploads` diblokir.
- **Prioritas:** P0 · **Status:** DONE
- **Acceptance criteria:** File non-gambar ditolak; file PHP tidak dapat dieksekusi; nama file tidak dapat menimpa file lain.
- **Berkas:** `Core/ImageUploader.php`, `public/.htaccess`, `public/router.php`
- **Dependensi:** —

#### T8.8 — Cookie session dan regenerasi
- **Deskripsi:** `HttpOnly`, `SameSite=Lax`, `Secure` saat HTTPS, regenerasi setelah login.
- **Prioritas:** P1 · **Status:** DONE
- **Acceptance criteria:** Cookie tidak dapat dibaca JS; atribut benar; ID berubah setelah login; session project-local.
- **Berkas:** `Core/Session.php`, `Core/Auth.php`
- **Dependensi:** —

#### T8.9 — Throttle login per akun/IP
- **Deskripsi:** Ganti throttle session dengan tabel percobaan login (email + IP + waktu), backoff, dan pembersihan berkala.
- **Prioritas:** P0 · **Status:** DONE
- **Acceptance criteria:** Brute force lintas session/cookie terblokir; akun/IP berbeda tidak saling mengunci; pesan tetap generik; lockout kedaluwarsa; percobaan berhasil menghapus counter.
- **Berkas:** `database/schema.sql`, `Core/Auth.php`, `Models/LoginAttempt.php` (baru), `Admin/AuthController.php`
- **Dependensi:** —

#### T8.10 — Security header (usulan penguatan)
- **Deskripsi:** Menambahkan `X-Content-Type-Options`, `X-Frame-Options`/`frame-ancestors`, `Referrer-Policy`, `Permissions-Policy`, dan CSP bertahap (report-only terlebih dahulu).
- **Prioritas:** P1 · **Status:** DONE
- **Acceptance criteria:** Header muncul pada respons HTML; tidak memblokir font/gambar/JS yang sah; CSP tidak melanggar inline script yang masih dipakai; diuji di dev.
- **Berkas:** `Core/App.php` atau middleware baru, `public/.htaccess`
- **Dependensi:** T8.14.

#### T8.11 — Session timeout dan strict mode
- **Deskripsi:** Idle timeout admin (mis. 30 menit), `session.use_strict_mode`, regenerasi berkala, dan pesan sesi berakhir.
- **Prioritas:** P1 · **Status:** DONE
- **Acceptance criteria:** Admin idle dialihkan ke login; session lama tidak dapat dipakai ulang; tidak mengganggu keranjang customer; durasi dapat dikonfigurasi.
- **Berkas:** `Core/Session.php`, `Core/Auth.php`, `config/config.php`
- **Dependensi:** —

#### T8.12 — Penanganan error dan log
- **Deskripsi:** Error 500 tidak membocorkan detail saat produksi; log ditulis ke `storage/logs/app.log`.
- **Prioritas:** P1 · **Status:** DONE
- **Acceptance criteria:** `APP_DEBUG=false` menyembunyikan trace; log berisi waktu/kelas/pesan; respons 404/419/500 tersedia.
- **Berkas:** `Core/App.php`, `Views/errors/*`
- **Dependensi:** —

#### T8.13 — Pengelolaan kredensial
- **Deskripsi:** `.env` gitignored; contoh tanpa secret; kredensial produksi terpisah.
- **Prioritas:** P0 · **Status:** DONE
- **Acceptance criteria:** `.env` tidak terlacak; `.env.example` aman; tidak ada password asli di repo.
- **Berkas:** `.env.example`, `.gitignore`
- **Dependensi:** —

#### T8.14 — HTTPS dan cookie aman di produksi
- **Deskripsi:** Memastikan HTTPS aktif, redirect HTTP→HTTPS, HSTS setelah yakin, dan `Secure` cookie.
- **Prioritas:** P0 · **Status:** DONE
- **Acceptance criteria:** HTTP dialihkan; tidak ada mixed content; HSTS hanya pada domain HTTPS; cookie `Secure` terpasang.
- **Berkas:** konfigurasi hosting, `public/.htaccess`, `Core/Session.php`
- **Dependensi:** T10.3.

---

### Milestone 9 — Testing & Quality Assurance

#### T9.1 — Lint sintaks seluruh PHP
- **Deskripsi:** Menjalankan `php -l` pada semua berkas.
- **Prioritas:** P1 · **Status:** DONE
- **Acceptance criteria:** Tidak ada parse error; hasil audit 19 November 2026 dicatat.
- **Berkas:** seluruh `*.php`
- **Dependensi:** —

#### T9.2 — Regresi 23 skenario HTTP
- **Deskripsi:** Menjalankan ulang skenario di README dengan session/CSRF asli pada lingkungan bersih.
- **Prioritas:** P1 · **Status:** NEEDS REVIEW
- **Acceptance criteria:** 23 skenario lulus dengan bukti tanggal/lingkungan; kegagalan dicatat; data uji dibersihkan.
- **Berkas:** `README.md` Bagian 9
- **Dependensi:** T1.4.

#### T9.3 — Pengujian fitur P0 baru
- **Deskripsi:** Skenario bukti bayar, verifikasi akses pesanan, expiry stok, refund/pembatalan, throttle login, dan anti-spam.
- **Prioritas:** P0 · **Status:** TODO
- **Acceptance criteria:** Setiap fitur punya kasus positif/negatif; bukti file/response; tidak ada stok minus atau kebocoran data.
- **Berkas:** dokumen uji baru
- **Dependensi:** T5.5–T5.8, T8.9, T4.8.

#### T9.4 — Uji responsif perangkat nyata
- **Deskripsi:** Uji 360, 768, 1440 px pada minimal 2 perangkat/browser, termasuk admin.
- **Prioritas:** P1 · **Status:** TODO
- **Acceptance criteria:** Tidak ada overflow, tombol tertutup, atau teks terpotong; form dapat diselesaikan; tabel admin tetap dapat dipakai.
- **Berkas:** `app.css`, `admin.css`, catatan uji
- **Dependensi:** T2.4.

#### T9.5 — Audit aksesibilitas
- **Deskripsi:** Keyboard, fokus, label, kontras, zoom 200%, reduced motion, dan pembaca layar dasar.
- **Prioritas:** P1 · **Status:** TODO
- **Acceptance criteria:** Semua kontrol dapat dijangkau keyboard; label terhubung; kontras AA; status tidak hanya warna; tidak ada perangkap fokus.
- **Berkas:** seluruh view, CSS, catatan audit
- **Dependensi:** T2.5, T2.6.

#### T9.6 — Pengukuran performa
- **Deskripsi:** Mengukur LCP/ukuran halaman katalog pada 4G dan mengoptimasi gambar/font.
- **Prioritas:** P1 · **Status:** TODO
- **Acceptance criteria:** LCP < 2,5 s target; katalog < 500 KB tanpa foto; hasil sebelum/sesudah dicatat; tidak mengorbankan kualitas.
- **Berkas:** `ImageUploader.php`, CSS/JS, catatan performa
- **Dependensi:** T6.14.

#### T9.7 — Uji konkurensi stok
- **Deskripsi:** 50 checkout bersamaan pada stok terbatas; pastikan tidak oversell dan tidak deadlock permanen.
- **Prioritas:** P1 · **Status:** TODO
- **Acceptance criteria:** Jumlah pesanan sukses ≤ stok; sisanya gagal dengan pesan; stok akhir 0; log bersih.
- **Berkas:** skrip uji beban baru
- **Dependensi:** T1.4.

#### T9.8 — Uji otomatis dan CI
- **Deskripsi:** Menambahkan unit/integration test untuk aturan harga, stok, status, dan auth.
- **Prioritas:** P2 · **Status:** TODO
- **Acceptance criteria:** Test berjalan satu perintah; CI gagal saat regresi; cakupan aturan kritis memadai.
- **Berkas:** `tests/` (baru), konfigurasi CI
- **Dependensi:** Stabilitas P0.

#### T9.9 — Uji backup dan restore
- **Deskripsi:** Menguji dump database + upload ke lingkungan bersih.
- **Prioritas:** P1 · **Status:** TODO
- **Acceptance criteria:** Restore menghasilkan data dan file identik; langkah terdokumentasi; waktu pemulihan dicatat.
- **Berkas:** `docs/BACKUP.md` (baru), skrip backup
- **Dependensi:** T10.6.

#### T9.10 — UAT dengan data bisnis asli
- **Deskripsi:** Pemilik usaha menguji alur dengan TBD terisi dan memberikan sign-off.
- **Prioritas:** P1 · **Status:** TODO
- **Acceptance criteria:** Checklist UAT ditandatangani; tidak ada data karangan; catatan masalah ditutup atau dijadwalkan.
- **Berkas:** `docs/UAT.md` (baru)
- **Dependensi:** TBD-01–TBD-13, seluruh P0.

---

### Milestone 10 — Build, Deployment & Production Readiness

#### T10.1 — Konfigurasi environment produksi
- **Deskripsi:** `APP_ENV=production`, `APP_DEBUG=false`, URL, timezone, kredensial DB produksi.
- **Prioritas:** P0 · **Status:** TODO
- **Acceptance criteria:** Tidak ada debug di produksi; URL benar; .env tidak dapat diakses publik; kredensial terpisah dari dev.
- **Berkas:** `.env` produksi (tidak di repo), `config/config.php`
- **Dependensi:** T10.2.

#### T10.2 — Database hosting dan migrasi
- **Deskripsi:** Impor skema, buat user least-privilege, terapkan migrasi kolom baru (bukti bayar, expiry, login attempts).
- **Prioritas:** P0 · **Status:** TODO
- **Acceptance criteria:** Koneksi sukses; charset `utf8mb4`; semua migrasi tercatat; rollback diuji; user tidak memakai `root`.
- **Berkas:** `database/schema.sql`, `database/migrations/*` (baru), konfigurasi hosting
- **Dependensi:** T5.5, T5.7, T8.9.

#### T10.3 — Verifikasi HTTPS dan header
- **Deskripsi:** Memasang sertifikat, redirect, HSTS, dan memeriksa seluruh header keamanan.
- **Prioritas:** P0 · **Status:** TODO
- **Acceptance criteria:** Semua halaman HTTPS; redirect benar; tidak ada mixed content; header sesuai T8.10; uji eksternal lulus.
- **Berkas:** konfigurasi server, `.htaccess`
- **Dependensi:** T8.10, T8.14.

#### T10.4 — Ganti kredensial admin default
- **Deskripsi:** Mengganti email/password admin hasil seeder sebelum go-live.
- **Prioritas:** P0 · **Status:** TODO
- **Acceptance criteria:** Login default gagal; password kuat; kredensial disimpan aman; tidak ada di dokumentasi publik.
- **Berkas:** database produksi, `README.md`
- **Dependensi:** T10.1.

#### T10.5 — Izin penyimpanan dan pemisahan file sensitif
- **Deskripsi:** Memastikan `storage/` tidak dapat diakses web, upload publik hanya gambar produk, dan bukti bayar disimpan privat. Hapus/legacy `storage/uploads` bila tidak dipakai.
- **Prioritas:** P1 · **Status:** TODO
- **Acceptance criteria:** URL langsung ke storage ditolak; bukti bayar hanya lewat endpoint terautentikasi; permission benar; folder legacy dihapus/didokumentasikan.
- **Berkas:** `storage/`, `public/uploads/`, konfigurasi server
- **Dependensi:** T5.5.

#### T10.6 — Skrip backup terjadwal
- **Deskripsi:** Backup database + upload secara berkala, retensi, dan notifikasi kegagalan.
- **Prioritas:** P1 · **Status:** TODO
- **Acceptance criteria:** Backup otomatis berjalan; file dapat direstore; retensi sesuai; kegagalan terdeteksi.
- **Berkas:** `bin/backup.sh` (baru), dokumentasi
- **Dependensi:** T10.5.

#### T10.7 — Optimasi aset produksi
- **Deskripsi:** Minifikasi CSS/JS, cache header, self-host font, dan gambar teroptimasi.
- **Prioritas:** P2 · **Status:** TODO
- **Acceptance criteria:** Ukuran turun terukur; tidak ada 404 font; cache tidak menyajikan versi lama setelah update (versioning).
- **Berkas:** `public/assets/`, `layouts/*`
- **Dependensi:** T9.6.

#### T10.8 — Deployment dan smoke test pascadeploy
- **Deskripsi:** Deploy ke hosting, jalankan checklist route, login, katalog, checkout uji, lalu batalkan.
- **Prioritas:** P0 · **Status:** TODO
- **Acceptance criteria:** Semua route utama 200; login admin; pesanan uji berhasil dan stok kembali setelah batal; log bersih; data uji dihapus.
- **Berkas:** checklist deploy baru
- **Dependensi:** Seluruh P0 dan T10.1–T10.6.

#### T10.9 — Monitoring dan rotasi log
- **Deskripsi:** Uptime check, notifikasi error, dan rotasi `app.log`.
- **Prioritas:** P2 · **Status:** TODO
- **Acceptance criteria:** Downtime terdeteksi; log tidak tumbuh tanpa batas; alert sampai ke penanggung jawab.
- **Berkas:** konfigurasi monitoring, `storage/logs`
- **Dependensi:** T10.8.

#### T10.10 — Validasi subfolder/base path (usulan opsional)
- **Deskripsi:** Menguji instalasi di subfolder (bila diperlukan) dan memastikan `base_path`/URL aset benar. Hanya relevan bila hosting tidak memakai document root `public/`.
- **Prioritas:** P2 · **Status:** TODO
- **Acceptance criteria:** Semua link/aset bekerja di subfolder; redirect login benar; tidak ada URL hardcoded ke root domain.
- **Berkas:** `config/config.php`, `helpers.php`, layout
- **Dependensi:** T10.8.

---

## 7. Rekap Status dan Prioritas

### 7.1 Berdasarkan status

| Status | Jumlah | Persentase |
|---|---:|---:|
| DONE | 68 | 68% |
| IN PROGRESS | 0 | 0% |
| TODO | 28 | 28% |
| NEEDS REVIEW | 4 | 4% |
| **Total** | **100** | **100%** |

### 7.2 Berdasarkan prioritas

| Prioritas | Jumlah |
|---|---:|
| P0 | 40 |
| P1 | 39 |
| P2 | 21 |
| **Total** | **100** |

### 7.3 Milestone yang sudah 100% selesai

**M4 Customer Interactions, M5 Order & Payment, M8 Security & Validation.**

Milestone terdekat berikutnya: M2 UI Foundation (7/8, 88%) tertahan oleh `UI-SPEC.md`; M3 Product Catalog (6/7, 86%) tertahan oleh keputusan filter; M1 Audit (6/8, 75%) tertahan oleh `UI-SPEC.md` dan baseline commit.

Sisa pekerjaan terkonsentrasi pada M9 (pengujian lapangan) dan M10 (deployment), yang hanya dapat diselesaikan di lingkungan hosting dan dengan data bisnis asli.

---

## 8. Rekomendasi Urutan Pengerjaan

### Gelombang 0 — Prasyarat dokumen (sebelum coding)
1. **T1.2** — buat & setujui `UI-SPEC.md` (P0).
2. **T1.7** — baseline commit setelah persetujuan.
3. Selesaikan keputusan bisnis: TBD-01–TBD-13, kebijakan refund (T5.8), batas bayar (T5.7), pilihan verifikasi akses pesanan (T5.6).

### Gelombang 1 — P0 uang, privasi, stok, keamanan
4. **T5.5** bukti pembayaran → **T5.6** verifikasi akses → **T5.7** expiry stok → **T5.8** refund/pembatalan.
5. **T8.9** throttle login berbasis DB.
6. **T4.8** anti-spam checkout.
7. **T5.9** matriks transisi.
8. **T2.7** perbaikan harga beranda (defect yang terlihat pelanggan).

### Gelombang 2 — P1 operasional & kualitas
9. **T4.4** ubah jumlah keranjang.
10. **T2.6** kontras + **T2.5** fokus/skip-link.
11. **T2.8** copy dinamis; **T6.13** konfirmasi aksi; **T6.14** kompresi gambar.
12. **T8.10/T8.11/T8.14** header, session timeout, HTTPS.
13. **T9.3–T9.7, T9.9, T9.10** pengujian.

### Gelombang 3 — P2 pertumbuhan
14. Kategori/varian CRUD, laporan, audit log, multi-admin, konten beranda (T6.10–T6.19).
15. WhatsApp API hanya bila disetujui (T7.4); normalisasi nomor (T7.5).
16. Deployment hardening lanjutan (T10.7, T10.9, T10.10).

**Alasan urutan:** jangan menambah fitur di atas kebijakan uang yang belum ada. Bukti bayar dan expiry saling bergantung; verifikasi akses harus selesai sebelum bukti bayar dipublikasikan. Perbaikan copy/harga dikerjakan lebih awal karena pelanggan dapat melihatnya hari ini.

---

## 9. Risiko dan Asumsi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| `UI-SPEC.md` tidak ada | Desain tidak dapat diverifikasi; pekerjaan M2 berisiko diulang | T1.2 sebelum perubahan visual besar |
| Data bisnis TBD belum diisi | Paket tidak dapat dipesan; alamat/pembayaran tidak tampil | Checklist TBD; UAT T9.10 |
| Kebijakan refund/batas bayar belum diputuskan | Fitur P0 tidak dapat diselesaikan dengan benar | Keputusan pemilik usaha lebih dulu |
| Throttle/anti-spam salah konfigurasi | Pelanggan sah terblokir | Batas konservatif + monitoring |
| Bukti bayar berisi data sensitif | Kebocoran jika disimpan publik | Penyimpanan privat + akses terverifikasi |
| Cron tidak tersedia di shared hosting | Stok tidak dilepas otomatis | Sediakan command manual + dokumentasi |
| Tidak ada uji otomatis | Regresi saat perubahan P0 | T9.8 setelah fitur stabil |

**Asumsi audit:** kode yang dibaca adalah versi terbaru di working tree; 23 skenario README tidak dijalankan ulang pada audit ini; tidak ada `UI-SPEC.md`; database dev tidak diubah.

---

## 10. Checklist Data TBD yang Menghambat

| ID | Data | Menghambat |
|---|---|---|
| TBD-01/02 | Harga Paket Spesial & Premium | T5.4, UAT |
| TBD-03 | Minimal hari pre-order | Checkout hampers |
| TBD-04 | Nomor WhatsApp | T7.1–T7.3 | — **terisi 19 Nov 2026:** 0896-8806-7113 |
| TBD-05/06 | Alamat & jam operasional | Halaman pesanan/kontak |
| TBD-07 | Logo & foto asli | Tampilan produksi |
| TBD-08/09/10 | Rekening, QRIS, e-wallet | Instruksi pembayaran |
| TBD-11 | Kebijakan refund | T5.8 |
| TBD-12 | Area & tarif kirim | Ongkir manual |
| TBD-13 | Stok awal | Katalog dapat dipesan |
| TBD-14 | Cerita bisnis | Halaman Tentang |
| TBD-15/16 | Kemasan & pajak | Harga final (bila relevan) |

---

## 11. Catatan Penutup

- Status DONE hanya diberikan pada pekerjaan yang diverifikasi langsung di kode/perilaku, bukan karena berkasnya ada.
- Item **NEEDS REVIEW** membutuhkan keputusan pemilik usaha atau pembanding `UI-SPEC.md`; jangan dianggap selesai.
- Pekerjaan yang tersisa (Bagian 12.4) memerlukan data bisnis, keputusan pemilik usaha, lingkungan hosting, atau pengujian di perangkat nyata.

---

## 12. Log Implementasi — 19 November 2026

### 12.1 Berkas dan skema baru

| Area | Perubahan |
|---|---|
| Migrasi | `database/migrations/20261119_p0_security.sql` + rollback, `database/migrations/20261119_p1_settings.sql`, runner `database/migrate.php` (ledger `migrations`) |
| Skema `orders` | `access_token`, `payment_due_at`, `payment_proof`, `payment_proof_at`, `cancel_reason` |
| Tabel baru | `login_attempts` (throttle per email+IP), `rate_limits` (pembatas laju generik) |
| Core | `Core/RateLimiter.php`, `Models/LoginAttempt.php`, header keamanan + paksa HTTPS di `Core/App.php`, `use_strict_mode` di `Core/Session.php`, sinkronisasi zona waktu MySQL di `Core/Database.php` |
| Command | `bin/release-expired.php` (pembatalan otomatis + pengembalian stok) |
| Halaman | `app/Views/order-gate.php`; bukti bayar & tenggat di `app/Views/order.php` |

### 12.2 Tugas yang diselesaikan pada blok ini

- **P0:** T4.8, T4.9, T5.5, T5.6, T5.7, T5.8, T5.9, T8.9, T8.14
- **P1:** T2.5, T2.6, T2.7, T2.8, T4.4, T6.13, T6.14, T8.10, T8.11
- **P2 kecil:** T6.15 (harga 0 ditolak), T7.5 (normalisasi nomor)
- **Register ketidaksesuaian yang tertutup:** D-04, D-05, D-07, D-08, D-09, D-10, D-11, D-12, D-13, D-14, D-15, D-18

### 12.3 Bukti pengujian (lingkungan lokal, PHP 8.4 + MySQL 8.4)

1. `php -l` pada seluruh berkas PHP: lolos.
2. Migrasi dijalankan dua kali: tidak ada penerapan ganda (ledger bekerja).
3. Semua halaman customer + `/admin/login` merespons 200.
4. Checkout satuan: pesanan dibuat, `access_token` 64 karakter, `payment_due_at` = dibuat + 24 jam, nomor WhatsApp tersimpan ternormalisasi (`62…`).
5. Halaman pesanan di sesi baru menampilkan gate verifikasi tanpa membocorkan nama pelanggan; 4 digit salah ditolak; 4 digit benar membuka pesanan.
6. Unggah bukti (PNG) tersimpan di `storage/proofs` (di luar web root) dan status berubah `unpaid` → `awaiting_verification`; admin dapat menampilkan berkas (`Content-Type: image/png`).
7. Transisi status: `new → completed` ditolak, `new → confirmed` diterima, `confirmed → completed` ditolak, `confirmed → cancelled` mengembalikan stok tepat (46 → 50) dan menyimpan alasan serta `payment_status = cancelled`.
8. Throttle login: 5 percobaan gagal mengunci percobaan berikutnya dengan pesan generik dan sisa waktu ±10 menit (setelah bug timezone diperbaiki).
9. Ubah jumlah keranjang: 3 → 4 buah memperbarui subtotal menjadi Rp20.000.
10. Header keamanan muncul pada respons HTML.
11. Data uji (pesanan, pergerakan stok, bukti, rate limit, login attempt) dibersihkan setelah pengujian.

### 12.4 Sisa pekerjaan

| Kelompok | Tugas | Alasan belum selesai |
|---|---|---|
| Dokumen desain | T1.2, T2.3, T3.7, T7.4 | Perlu persetujuan pemilik usaha; `UI-SPEC.md` belum ada |
| Baseline | T1.7 | Perlu keputusan pemilik untuk commit baseline |
| Fitur P2 | T6.10, T6.11, T6.12, T6.16, T6.17, T6.18, T6.19, T7.6 | Opsional/pertumbuhan; sebagian butuh keputusan peran admin dan angka batas |
| Pengujian lapangan | T9.2–T9.10 | Butuh perangkat nyata, data bisnis asli, dan UAT pemilik usaha |
| Deployment | T10.1–T10.10 | Butuh hosting, domain, sertifikat HTTPS, dan kredensial produksi |

> Catatan: kebijakan pembatalan/refund sekarang punya **field** dan alur di sistem, tetapi **teks kebijakannya** tetap harus diisi pemilik usaha (TBD-11). Tidak ada kebijakan atau nominal yang dikarang.
