<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Product;
use App\Models\Variant;

final class HomeController extends Controller
{
    public function index(): string
    {
        $units = Product::active('single');
        $hampers = Product::active('hampers');

        return $this->view('home', [
            'title'       => 'BaleBolen — Bolen Artisan, Pesan Online',
            'units'       => $units,
            'hampers'     => $hampers,
            'variants'    => Variant::active(),
            'preorderDays' => setting('hampers_preorder_days'),
        ]);
    }
}
