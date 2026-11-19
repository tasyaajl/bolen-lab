<?php

declare(strict_types=1);

use App\Controllers\CartController;
use App\Controllers\CatalogController;
use App\Controllers\CheckoutController;
use App\Controllers\HomeController;
use App\Controllers\OrderController;
use App\Controllers\PageController;
use App\Core\App;

$router = App::router();

$router->get('/', [HomeController::class, 'index']);
$router->get('/menu', [CatalogController::class, 'index']);
$router->get('/menu/{slug}', [CatalogController::class, 'show']);

$router->get('/keranjang', [CartController::class, 'index']);
$router->post('/keranjang/tambah', [CartController::class, 'add']);
$router->post('/keranjang/hapus', [CartController::class, 'remove']);
$router->post('/keranjang/ubah', [CartController::class, 'update']);

$router->get('/checkout', [CheckoutController::class, 'index']);
$router->post('/checkout', [CheckoutController::class, 'store']);

$router->get('/pesanan/{code}', [OrderController::class, 'show']);
$router->post('/pesanan/{code}/akses', [OrderController::class, 'verifyAccess']);
$router->get('/pesanan/{code}/bukti', [OrderController::class, 'proof']);
$router->post('/pesanan/{code}/bukti', [OrderController::class, 'uploadProof']);

$router->get('/tentang', [PageController::class, 'about']);
$router->get('/kontak', [PageController::class, 'contact']);
