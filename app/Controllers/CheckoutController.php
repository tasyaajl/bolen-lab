<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\Controller;
use App\Core\RateLimiter;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use DomainException;

final class CheckoutController extends Controller
{
    public function index(): string
    {
        $lines = Cart::detailed();

        if ($lines === []) {
            $this->flash('info', 'Keranjang masih kosong. Pilih bolen favorit Anda terlebih dahulu.');
            return $this->redirect('/menu');
        }

        $hasHampers = Cart::hasHampers();
        $preorderDays = setting('hampers_preorder_days');

        if ($hasHampers && $preorderDays === null) {
            $this->flash('error', 'Ketentuan pre-order hampers belum diatur admin. Silakan hubungi kami untuk pemesanan paket.');
            return $this->redirect('/keranjang');
        }

        $minDate = $this->minimumDate($hasHampers, $preorderDays);

        // Waktu mulai formulir dipakai untuk menolak submit yang terlalu cepat.
        Session::put('checkout_started_at', time());

        return $this->view('checkout', [
            'title'        => 'Checkout — BaleBolen',
            'lines'        => $lines,
            'subtotal'     => Cart::subtotal(),
            'hasHampers'   => $hasHampers,
            'preorderDays' => $preorderDays,
            'minDate'      => $minDate,
            'errors'       => Session::pull('_errors', []),
            'old'          => Session::pull('_old', []),
        ]);
    }

    public function store(): string
    {
        $this->checkCsrf();

        if (Cart::raw() === []) {
            $this->flash('info', 'Keranjang masih kosong.');
            return $this->redirect('/menu');
        }

        // Honeypot: field ini tidak terlihat oleh pengguna sungguhan.
        if (trim((string) ($this->rawInput('website') ?? '')) !== '') {
            $this->flash('error', 'Pesanan tidak dapat diproses. Silakan coba lagi.');
            return $this->redirect('/checkout');
        }

        $startedAt = (int) Session::get('checkout_started_at', 0);

        if ($startedAt > 0 && (time() - $startedAt) < 3) {
            $this->flash('error', 'Formulir terkirim terlalu cepat. Periksa kembali data pesanan Anda lalu kirim ulang.');
            return $this->redirect('/checkout');
        }

        $lines = Cart::detailed();
        $hasHampers = Cart::hasHampers();
        $preorderDays = setting('hampers_preorder_days');

        $data = [
            'customer_name'    => (string) ($this->input('customer_name') ?? ''),
            'whatsapp'         => (string) ($this->input('whatsapp') ?? ''),
            'fulfillment'      => (string) ($this->input('fulfillment') ?? ''),
            'delivery_method'  => (string) ($this->input('delivery_method') ?? ''),
            'delivery_address' => (string) ($this->input('delivery_address') ?? ''),
            'delivery_date'    => (string) ($this->input('delivery_date') ?? ''),
            'payment_method'   => (string) ($this->input('payment_method') ?? ''),
            'notes'            => (string) ($this->input('notes') ?? ''),
            'gift_recipient'   => (string) ($this->input('gift_recipient') ?? ''),
            'gift_message'     => (string) ($this->input('gift_message') ?? ''),
        ];

        $errors = Validator::validate($data, [
            'customer_name'    => 'required|max:100',
            'whatsapp'         => 'required|phone',
            'fulfillment'      => 'required|in:pickup,delivery',
            'delivery_method'  => 'nullable|in:own,gojek,other',
            'delivery_address' => 'nullable|max:500',
            'delivery_date'    => 'required|date',
            'payment_method'   => 'required|in:transfer,qris,cod,cash,gopay',
            'notes'            => 'nullable|max:1000',
            'gift_recipient'   => 'nullable|max:100',
            'gift_message'     => 'nullable|max:300',
        ], [
            'customer_name'    => 'Nama pelanggan',
            'whatsapp'         => 'Nomor WhatsApp',
            'fulfillment'      => 'Metode penerimaan',
            'delivery_method'  => 'Jasa pengiriman',
            'delivery_address' => 'Alamat pengiriman',
            'delivery_date'    => 'Tanggal penerimaan',
            'payment_method'   => 'Metode pembayaran',
            'notes'            => 'Catatan',
            'gift_recipient'   => 'Nama penerima hadiah',
            'gift_message'     => 'Pesan kartu ucapan',
        ]);

        if ($data['fulfillment'] === 'delivery') {
            if ($data['delivery_method'] === '') {
                $errors['delivery_method'] = 'Jasa pengiriman wajib dipilih untuk pesanan delivery.';
            }

            if (mb_strlen(trim($data['delivery_address'])) < 10) {
                $errors['delivery_address'] = 'Alamat pengiriman wajib diisi lengkap (minimal 10 karakter).';
            }
        }

        if ($data['payment_method'] === 'cod' && $data['fulfillment'] !== 'delivery') {
            $errors['payment_method'] = 'COD hanya tersedia untuk pesanan delivery.';
        }

        if ($data['payment_method'] === 'cash' && $data['fulfillment'] !== 'pickup') {
            $errors['payment_method'] = 'Pembayaran tunai hanya tersedia untuk pickup.';
        }

        if (!isset($errors['delivery_date']) && $data['delivery_date'] !== '') {
            $timestamp = strtotime($data['delivery_date']);

            if ($timestamp === false) {
                $errors['delivery_date'] = 'Tanggal penerimaan tidak valid.';
            } else {
                $selected = date('Y-m-d', $timestamp);
                $today = date('Y-m-d');

                if ($selected < $today) {
                    $errors['delivery_date'] = 'Tanggal penerimaan tidak boleh di masa lalu.';
                } elseif ($hasHampers) {
                    if ($preorderDays === null) {
                        $errors['delivery_date'] = 'Ketentuan pre-order hampers belum diatur admin.';
                    } else {
                        $minDate = $this->minimumDate(true, $preorderDays);

                        if ($selected < $minDate) {
                            $errors['delivery_date'] = 'Paket hampers perlu dipesan minimal ' . (int) $preorderDays . ' hari sebelumnya (paling cepat ' . tanggal_id($minDate) . ').';
                        }
                    }
                }
            }
        }

        $maxQty = max(1, (int) App::configValue('limits.max_item_qty', 50));

        // Validasi ulang isi keranjang terhadap data terbaru di database.
        foreach ($lines as $line) {
            $product = $line['product'];

            if ((int) $line['quantity'] > $maxQty) {
                $errors['cart'] = 'Jumlah ' . $product['name'] . ' melebihi batas ' . $maxQty . ' per baris. Silakan sesuaikan jumlahnya.';
                break;
            }

            if ((int) $product['is_active'] !== 1) {
                $errors['cart'] = 'Produk ' . $product['name'] . ' sudah tidak tersedia. Hapus dari keranjang.';
                break;
            }

            if ($line['unit_price'] === null) {
                $errors['cart'] = 'Harga ' . $product['name'] . ' belum diatur admin.';
                break;
            }

            if ((string) $product['type'] === 'hampers') {
                $total = 0;
                foreach ($line['composition'] as $row) {
                    $total += (int) $row['quantity'];
                }

                if ($total !== (int) $product['package_size']) {
                    $errors['cart'] = 'Komposisi ' . $product['name'] . ' tidak sesuai. Perbarui keranjang.';
                    break;
                }
            }

            $available = Product::availableStock($product);
            $needed = (string) $product['type'] === 'hampers'
                ? (int) $product['package_size']
                : (int) $line['quantity'];

            if ($available < $needed) {
                $errors['cart'] = 'Stok ' . $product['name'] . ' tidak mencukupi. Silakan sesuaikan jumlah.';
                break;
            }
        }

        if ($errors !== []) {
            $this->withErrors($errors, $data);
            $this->flash('error', 'Periksa kembali data pemesanan Anda.');
            return $this->redirect('/checkout');
        }

        // Batasi frekuensi pembuatan pesanan per IP untuk mencegah spam.
        if (!RateLimiter::attempt(
            'checkout',
            RateLimiter::clientIp(),
            (int) App::configValue('limits.checkout_per_window', 5),
            (int) App::configValue('limits.checkout_window', 600)
        )) {
            $minutes = max(1, (int) ceil(RateLimiter::availableIn(
                'checkout',
                RateLimiter::clientIp(),
                (int) App::configValue('limits.checkout_per_window', 5),
                (int) App::configValue('limits.checkout_window', 600)
            ) / 60));
            $this->flash('error', 'Terlalu banyak pesanan dibuat dari perangkat ini. Coba lagi dalam ' . $minutes . ' menit atau hubungi kami via WhatsApp.');
            return $this->redirect('/checkout');
        }

        try {
            $order = Order::create([
                'customer_name'    => $data['customer_name'],
                'whatsapp'         => normalize_phone($data['whatsapp']),
                'fulfillment'      => $data['fulfillment'],
                'delivery_method'  => $data['fulfillment'] === 'delivery' ? $data['delivery_method'] : null,
                'delivery_address' => $data['fulfillment'] === 'delivery' ? $data['delivery_address'] : null,
                'delivery_date'    => $data['delivery_date'],
                'notes'            => $data['notes'] !== '' ? $data['notes'] : null,
                'gift_recipient'   => $data['gift_recipient'] !== '' ? $data['gift_recipient'] : null,
                'gift_message'     => $data['gift_message'] !== '' ? $data['gift_message'] : null,
                'payment_method'   => $data['payment_method'],
            ], $lines);
        } catch (DomainException $e) {
            $this->withErrors(['cart' => $e->getMessage()], $data);
            $this->flash('error', $e->getMessage());
            return $this->redirect('/checkout');
        }

        Cart::clear();
        Session::forget('_errors');
        Session::forget('_old');
        Session::forget('checkout_started_at');

        // Pemesan langsung mendapat akses ke halaman pesanannya.
        Order::grantSessionAccess($order);

        $this->flash('success', 'Pesanan ' . $order['code'] . ' berhasil dibuat.');

        return $this->redirect('/pesanan/' . $order['code']);
    }

    private function minimumDate(bool $hasHampers, ?string $preorderDays): string
    {
        $days = $hasHampers ? (int) $preorderDays : 0;

        return date('Y-m-d', strtotime('+' . max(0, $days) . ' days'));
    }
}
