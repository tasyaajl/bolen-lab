<?php
/**
 * @var array<string, mixed> $admin
 * @var array<string, string|null> $settings
 */

use App\Core\Session;

$errors = Session::pull('_errors', []);
$oldInput = Session::pull('_old', []);
$value = static function (string $key, string $default = '') use ($oldInput, $settings): string {
    if (array_key_exists($key, $oldInput)) {
        return (string) $oldInput[$key];
    }

    return (string) ($settings[$key] ?? '') !== '' ? (string) $settings[$key] : $default;
};
?>

<?php if ($errors !== []): ?>
    <div class="a-alert a-alert--error"><?= e(reset($errors)) ?></div>
<?php endif; ?>

<form method="post" action="<?= url('/admin/pengaturan') ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="a-card" id="bisnis">
        <div class="a-card__head">
            <div>
                <h2>Profil bisnis</h2>
                <p>Informasi ini tampil di website, footer, dan halaman kontak.</p>
            </div>
        </div>

        <div class="a-form a-form--2">
            <div class="a-field">
                <label for="business_name">Nama bisnis <span class="req">*</span></label>
                <input type="text" id="business_name" name="business_name" value="<?= e($value('business_name', 'BaleBolen')) ?>" maxlength="100" required>
                <?php if (isset($errors['business_name'])): ?><span class="error"><?= e($errors['business_name']) ?></span><?php endif; ?>
            </div>
            <div class="a-field">
                <label for="business_tagline">Tagline / subjudul</label>
                <input type="text" id="business_tagline" name="business_tagline" value="<?= e($value('business_tagline', 'Artisan Bakery')) ?>" maxlength="80" placeholder="Artisan Bakery">
            </div>

            <div class="a-field">
                <label for="whatsapp">Nomor WhatsApp resmi</label>
                <input type="text" id="whatsapp" name="whatsapp" value="<?= e($value('whatsapp')) ?>" maxlength="20" placeholder="081234567890">
                <?php if (isset($errors['whatsapp'])): ?><span class="error"><?= e($errors['whatsapp']) ?></span><?php endif; ?>
                <span class="hint">Kosongkan bila belum tersedia. Tombol WhatsApp otomatis disembunyikan.</span>
            </div>
            <div class="a-field">
                <label for="opening_hours">Jam operasional</label>
                <input type="text" id="opening_hours" name="opening_hours" value="<?= e($value('opening_hours')) ?>" maxlength="255" placeholder="Senin - Sabtu, 08.00 - 20.00">
            </div>

            <div class="a-field full">
                <label for="address">Alamat usaha</label>
                <textarea id="address" name="address" maxlength="500" placeholder="Alamat lengkap untuk pickup dan halaman kontak."><?= e($value('address')) ?></textarea>
            </div>

            <div class="a-field full">
                <label for="maps_url">Google Maps embed URL</label>
                <input type="text" id="maps_url" name="maps_url" value="<?= e($value('maps_url')) ?>" maxlength="800" placeholder="https://www.google.com/maps/embed?pb=...">
                <span class="hint">Gunakan URL dari menu "Share → Embed a map" Google Maps, bukan tautan biasa.</span>
            </div>

            <div class="a-field">
                <label for="about_intro">Ringkasan bisnis (beranda &amp; tentang)</label>
                <textarea id="about_intro" name="about_intro" maxlength="500" placeholder="Dua-tiga kalimat tentang BaleBolen."><?= e($value('about_intro')) ?></textarea>
            </div>
            <div class="a-field">
                <label for="about_story">Cerita bisnis (halaman tentang)</label>
                <textarea id="about_story" name="about_story" maxlength="3000" placeholder="Sejarah dan cerita BaleBolen dari pemilik usaha."><?= e($value('about_story')) ?></textarea>
            </div>

            <div class="a-field">
                <label for="instagram_url">Instagram (URL)</label>
                <input type="text" id="instagram_url" name="instagram_url" value="<?= e($value('instagram_url')) ?>" maxlength="255" placeholder="https://instagram.com/...">
            </div>
            <div class="a-field">
                <label for="tiktok_url">TikTok (URL)</label>
                <input type="text" id="tiktok_url" name="tiktok_url" value="<?= e($value('tiktok_url')) ?>" maxlength="255" placeholder="https://tiktok.com/@...">
            </div>

            <div class="a-field">
                <label for="logo">Logo bisnis</label>
                <input type="file" id="logo" name="logo" accept="image/jpeg,image/png,image/webp">
                <span class="hint">JPG, PNG, atau WEBP. Maksimal 3 MB. Bila kosong, digunakan monogram "B".</span>
                <?php if (!empty($settings['logo'])): ?>
                    <div style="margin-top: 10px; display: flex; align-items: center; gap: 12px;">
                        <img src="<?= url('/uploads/brand/' . $settings['logo']) ?>" alt="Logo" style="width: 56px; height: 56px; object-fit: cover; border-radius: 10px; border: 1px solid var(--border);">
                        <a href="<?= url('/admin/pengaturan') ?>" class="a-tiny a-muted">Logo saat ini</a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="a-field">
                <label for="pickup_instructions">Instruksi pickup</label>
                <textarea id="pickup_instructions" name="pickup_instructions" maxlength="500" placeholder="Contoh: tunjukkan kode pesanan ke kasir."><?= e($value('pickup_instructions')) ?></textarea>
            </div>
        </div>
    </div>

    <div class="a-card" id="pembayaran">
        <div class="a-card__head">
            <div>
                <h2>Informasi pembayaran</h2>
                <p>Ditampilkan pada halaman pesanan setelah pelanggan checkout. Kosongkan yang belum tersedia.</p>
            </div>
        </div>

        <div class="a-form a-form--2">
            <div class="a-field">
                <label for="bank_name">Nama bank</label>
                <input type="text" id="bank_name" name="bank_name" value="<?= e($value('bank_name')) ?>" maxlength="80" placeholder="BCA / BRI / Mandiri">
            </div>
            <div class="a-field">
                <label for="bank_holder">Atas nama</label>
                <input type="text" id="bank_holder" name="bank_holder" value="<?= e($value('bank_holder')) ?>" maxlength="100">
            </div>
            <div class="a-field">
                <label for="bank_number">Nomor rekening</label>
                <input type="text" id="bank_number" name="bank_number" value="<?= e($value('bank_number')) ?>" maxlength="40">
            </div>
            <div class="a-field">
                <label for="ewallet_name">Nama e-wallet</label>
                <input type="text" id="ewallet_name" name="ewallet_name" value="<?= e($value('ewallet_name')) ?>" maxlength="60" placeholder="GoPay / OVO / DANA">
            </div>
            <div class="a-field">
                <label for="ewallet_number">Nomor e-wallet</label>
                <input type="text" id="ewallet_number" name="ewallet_number" value="<?= e($value('ewallet_number')) ?>" maxlength="40">
            </div>
            <div class="a-field">
                <label for="qris_note">Catatan QRIS</label>
                <input type="text" id="qris_note" name="qris_note" value="<?= e($value('qris_note')) ?>" maxlength="255" placeholder="Contoh: QRIS atas nama BaleBolen">
            </div>
            <div class="a-field">
                <label for="qris_image">Gambar QRIS</label>
                <input type="file" id="qris_image" name="qris_image" accept="image/jpeg,image/png,image/webp">
                <span class="hint">Unggah gambar QRIS resmi bila sudah tersedia.</span>
                <?php if (!empty($settings['qris_image'])): ?>
                    <div style="margin-top: 10px;">
                        <img src="<?= url('/uploads/brand/' . $settings['qris_image']) ?>" alt="QRIS" style="width: 110px; height: 110px; object-fit: contain; border-radius: 10px; border: 1px solid var(--border); background: #fff;">
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="a-card" id="pengiriman">
        <div class="a-card__head">
            <div>
                <h2>Pengiriman</h2>
                <p>Ongkos kirim tetap dikonfirmasi manual per pesanan. Tidak ada tarif otomatis.</p>
            </div>
        </div>

        <div class="a-form a-form--2">
            <div class="a-field">
                <label for="shipping_area">Area jangkauan</label>
                <input type="text" id="shipping_area" name="shipping_area" value="<?= e($value('shipping_area')) ?>" maxlength="255" placeholder="Contoh: Kota Bandung">
            </div>
            <div class="a-field">
                <label for="delivery_note">Catatan pengiriman</label>
                <input type="text" id="delivery_note" name="delivery_note" value="<?= e($value('delivery_note')) ?>" maxlength="500" placeholder="Contoh: pesanan dikirim setelah dikonfirmasi.">
            </div>
            <div class="a-field full">
                <label for="shipping_note">Keterangan ongkos kirim</label>
                <textarea id="shipping_note" name="shipping_note" maxlength="500" placeholder="Contoh: biaya menyesuaikan jarak, dikonfirmasi via WhatsApp."><?= e($value('shipping_note')) ?></textarea>
            </div>
        </div>
    </div>

    <div class="a-card" id="preorder">
        <div class="a-card__head">
            <div>
                <h2>Pre-order hampers</h2>
                <p>Waktu minimal pemesanan paket sebelum tanggal penerimaan.</p>
            </div>
            <span class="a-badge a-badge--<?= !empty($settings['hampers_preorder_days']) || $settings['hampers_preorder_days'] === '0' ? 'success' : 'warning' ?>">
                <?= $settings['hampers_preorder_days'] !== null && $settings['hampers_preorder_days'] !== '' ? 'Aktif: ' . e($settings['hampers_preorder_days']) . ' hari' : 'Belum diatur' ?>
            </span>
        </div>

        <div class="a-form a-form--2">
            <div class="a-field">
                <label for="hampers_preorder_days">Minimal hari pre-order</label>
                <input type="number" id="hampers_preorder_days" name="hampers_preorder_days" value="<?= e($value('hampers_preorder_days')) ?>" min="0" max="90" placeholder="Belum ditentukan">
                <?php if (isset($errors['hampers_preorder_days'])): ?><span class="error"><?= e($errors['hampers_preorder_days']) ?></span><?php endif; ?>
                <span class="hint">Kosongkan bila pemilik usaha belum menetapkan. Paket hampers tidak dapat dipesan sampai angka ini diisi.</span>
            </div>
            <div class="a-field">
                <label for="order_note">Catatan tambahan pada halaman pesanan</label>
                <input type="text" id="order_note" name="order_note" value="<?= e($value('order_note')) ?>" maxlength="500" placeholder="Contoh: konfirmasi maksimal 1x24 jam.">
            </div>
            <div class="a-field">
                <label for="payment_due_hours">Batas waktu pembayaran (jam)</label>
                <input type="number" id="payment_due_hours" name="payment_due_hours" value="<?= e($value('payment_due_hours', '24')) ?>" min="0" max="336" placeholder="24">
                <?php if (isset($errors['payment_due_hours'])): ?><span class="error"><?= e($errors['payment_due_hours']) ?></span><?php endif; ?>
                <span class="hint">Berlaku untuk transfer, QRIS, dan e-wallet. <strong>0 = tanpa batas</strong>. Kosongkan untuk memakai bawaan 24 jam. Pesanan yang belum dibayar sampai batas ini dibatalkan otomatis lewat <code>php bin/release-expired.php</code> (jadwalkan via cron).</span>
            </div>
            <div class="a-field full">
                <label for="cancellation_policy">Kebijakan pembatalan &amp; refund</label>
                <textarea id="cancellation_policy" name="cancellation_policy" maxlength="2000" placeholder="Tuliskan kebijakan resmi pemilik usaha. Kosongkan bila belum ditetapkan."><?= e($value('cancellation_policy')) ?></textarea>
                <span class="hint">Ditampilkan di halaman pesanan pelanggan. Tidak ada refund otomatis; proses tetap manual.</span>
            </div>
        </div>

        <div class="a-note" style="margin-top: 6px;">
            <strong>Aturan bisnis:</strong> sistem tidak menetapkan H-1/H-2/H-3 secara otomatis.
            Nilai ini sepenuhnya ditentukan pemilik usaha dan bisa diubah kapan saja.
        </div>
    </div>

    <div class="a-actions" style="margin-top: 18px;">
        <button type="submit" class="a-btn a-btn--primary">Simpan pengaturan</button>
        <a href="<?= url('/') ?>" target="_blank" rel="noopener" class="a-btn a-btn--ghost">Lihat website</a>
    </div>
</form>

<div class="a-card" id="profil" style="margin-top: 18px;">
    <div class="a-card__head">
        <div>
            <h2>Profil admin</h2>
            <p>Ubah nama, email, atau password akun admin.</p>
        </div>
    </div>

    <form method="post" action="<?= url('/admin/pengaturan/profil') ?>" class="a-form a-form--2">
        <?= csrf_field() ?>
        <div class="a-field">
            <label for="admin_name">Nama admin</label>
            <input type="text" id="admin_name" name="name" value="<?= e($admin['name'] ?? '') ?>" maxlength="100" required>
            <?php if (isset($errors['name'])): ?><span class="error"><?= e($errors['name']) ?></span><?php endif; ?>
        </div>
        <div class="a-field">
            <label for="admin_email">Email admin</label>
            <input type="email" id="admin_email" name="email" value="<?= e($admin['email'] ?? '') ?>" maxlength="150" required>
            <?php if (isset($errors['email'])): ?><span class="error"><?= e($errors['email']) ?></span><?php endif; ?>
        </div>
        <div class="a-field">
            <label for="current_password">Password saat ini</label>
            <input type="password" id="current_password" name="current_password" autocomplete="current-password">
            <?php if (isset($errors['current_password'])): ?><span class="error"><?= e($errors['current_password']) ?></span><?php endif; ?>
            <span class="hint">Wajib diisi hanya bila mengganti password.</span>
        </div>
        <div class="a-field">
            <label for="new_password">Password baru</label>
            <input type="password" id="new_password" name="new_password" autocomplete="new-password" minlength="8">
            <?php if (isset($errors['new_password'])): ?><span class="error"><?= e($errors['new_password']) ?></span><?php endif; ?>
            <span class="hint">Minimal 8 karakter.</span>
        </div>
        <div class="a-actions full" style="grid-column: 1 / -1;">
            <button type="submit" class="a-btn a-btn--dark">Perbarui profil</button>
        </div>
    </form>
</div>
