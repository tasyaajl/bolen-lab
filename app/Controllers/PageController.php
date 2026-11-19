<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

final class PageController extends Controller
{
    public function about(): string
    {
        return $this->view('about', [
            'title' => 'Tentang BaleBolen',
        ]);
    }

    public function contact(): string
    {
        return $this->view('contact', [
            'title' => 'Hubungi BaleBolen',
        ]);
    }
}
