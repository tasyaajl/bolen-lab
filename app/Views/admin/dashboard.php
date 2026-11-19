<?php
/**
 * @var array<string, int|float> $stats
 * @var list<array<string, mixed>> $recent
 * @var list<array<string, bool|string>> $checklist
 * @var int $stockTotal
 */
?>
<div class="a-grid a-grid--4">
    <div class="stat stat--gold">
        <span>Total pesanan</span>
        <strong><?= e($stats['total_orders']) ?></strong>
        <small><?= e($stats['today_orders']) ?> pesanan hari ini</small>
    </div>
    <div class="stat">
        <span>Pesanan baru</span>
        <strong><?= e($stats['new']) ?></strong>
        <small><a href="<?= url('/admin/pesanan?status=new') ?>" class="a-muted">Perlu diproses →</a></small>
    </div>
    <div class="stat">
        <span>Menunggu pembayaran</span>
        <strong><?= e($stats['awaiting_payment']) ?></strong>
        <small>Status pesanan menunggu pembayaran</small>
    </div>
    <div class="stat">
        <span>Pendapatan dibayar</span>
        <strong style="font-size: 1.5rem;"><?= e(rupiah($stats['paid_revenue'])) ?></strong>
        <small>Dari pesanan berstatus Dibayar</small>
    </div>
</div>

<div class="order-layout" style="margin-top: 18px;">
    <div>
        <div class="a-card">
            <div class="a-card__head">
                <div>
                    <h2>Pesanan terbaru</h2>
                    <p>Enam pesanan terakhir yang masuk.</p>
                </div>
                <a href="<?= url('/admin/pesanan') ?>" class="a-btn a-btn--ghost a-btn--small">Semua pesanan</a>
            </div>

            <?php if ($recent === []): ?>
                <div class="a-empty">Belum ada pesanan. Pesanan pelanggan akan muncul di sini.</div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Pelanggan</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent as $order): ?>
                                <tr>
                                    <td><strong><?= e($order['order_code']) ?></strong></td>
                                    <td>
                                        <?= e($order['customer_name']) ?>
                                        <div class="a-tiny a-muted"><?= e($order['whatsapp']) ?></div>
                                    </td>
                                    <td class="a-price"><?= e(rupiah($order['total'])) ?></td>
                                    <td><span class="a-badge a-badge--<?= e(str_replace(['badge--'], [''], \App\Models\Order::statusBadgeClass((string) $order['order_status']))) ?>"><?= e(\App\Models\Order::statusLabel((string) $order['order_status'])) ?></span></td>
                                    <td class="a-tiny a-muted"><?= e(tanggal_id((string) $order['created_at'])) ?></td>
                                    <td style="text-align: right;"><a class="a-btn a-btn--ghost a-btn--small" href="<?= url('/admin/pesanan/' . $order['id']) ?>">Detail</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="a-grid a-grid--2">
            <div class="a-card">
                <div class="a-card__head">
                    <div>
                        <h2>Status pesanan</h2>
                        <p>Ringkasan seluruh pesanan.</p>
                    </div>
                </div>
                <dl class="detail-list">
                    <?php foreach (\App\Models\Order::STATUSES as $status => $label): ?>
                        <div>
                            <dt><?= e($label) ?></dt>
                            <dd><?= e($stats[$status]) ?></dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            </div>
            <div class="a-card">
                <div class="a-card__head">
                    <div>
                        <h2>Stok saat ini</h2>
                        <p>Total stok seluruh varian.</p>
                    </div>
                </div>
                <div style="font-family: var(--font-display); font-size: 2.6rem; color: var(--cocoa); line-height: 1.1;"><?= e($stockTotal) ?> <span style="font-size: 1rem; font-family: var(--font-body); color: rgba(45,33,29,0.5);">buah</span></div>
                <p class="a-tiny a-muted" style="margin-top: 8px;">Perbarui stok setiap varian di menu Stok &amp; Varian.</p>
                <a href="<?= url('/admin/stok') ?>" class="a-btn a-btn--primary a-btn--small" style="margin-top: 14px;">Kelola stok</a>
            </div>
        </div>
    </div>

    <div class="a-card">
        <div class="a-card__head">
            <div>
                <h2>Kelengkapan data</h2>
                <p>Informasi yang masih perlu dilengkapi pemilik usaha.</p>
            </div>
        </div>

        <div class="checklist">
            <?php foreach ($checklist as $item): ?>
                <div class="checklist__item">
                    <span class="checklist__icon <?= $item['ok'] ? 'is-ok' : '' ?>"><?= $item['ok'] ? '✓' : '!' ?></span>
                    <div class="checklist__body">
                        <strong><?= e($item['label']) ?></strong>
                        <span><?= e($item['hint']) ?></span>
                    </div>
                    <?php if (!$item['ok']): ?>
                        <a href="<?= e($item['link']) ?>" class="checklist__link"><?= e($item['cta']) ?> →</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="a-note" style="margin-top: 18px;">
            <strong>Catatan:</strong> website tidak menampilkan rekening, QRIS, nomor WhatsApp, alamat, atau
            harga paket sampai data tersebut diisi admin. Tidak ada data bisnis contoh yang dikarang.
        </div>
    </div>
</div>
