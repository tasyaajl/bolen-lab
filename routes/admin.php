<?php

declare(strict_types=1);

use App\Controllers\Admin\AuthController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\HampersController;
use App\Controllers\Admin\InventoryController;
use App\Controllers\Admin\ProductController;
use App\Controllers\Admin\SettingsController;
use App\Controllers\Admin\OrderController as AdminOrderController;
use App\Core\App;

$router = App::router();

$router->get('/admin/login', [AuthController::class, 'showLogin']);
$router->post('/admin/login', [AuthController::class, 'login']);
$router->post('/admin/logout', [AuthController::class, 'logout']);

$router->get('/admin', [DashboardController::class, 'index']);

$router->get('/admin/pesanan', [AdminOrderController::class, 'index']);
$router->get('/admin/pesanan/{id}', [AdminOrderController::class, 'show']);
$router->post('/admin/pesanan/{id}/status', [AdminOrderController::class, 'updateStatus']);
$router->post('/admin/pesanan/{id}/pembayaran', [AdminOrderController::class, 'updatePayment']);
$router->post('/admin/pesanan/{id}/ongkir', [AdminOrderController::class, 'updateShipping']);
$router->get('/admin/pesanan/{id}/bukti', [AdminOrderController::class, 'proof']);
$router->post('/admin/pesanan/{id}/bukti/hapus', [AdminOrderController::class, 'deleteProof']);

$router->get('/admin/produk', [ProductController::class, 'index']);
$router->get('/admin/produk/baru', [ProductController::class, 'create']);
$router->post('/admin/produk', [ProductController::class, 'store']);
$router->get('/admin/produk/{id}/edit', [ProductController::class, 'edit']);
$router->post('/admin/produk/{id}', [ProductController::class, 'update']);
$router->post('/admin/produk/{id}/hapus', [ProductController::class, 'destroy']);

$router->get('/admin/hampers', [HampersController::class, 'index']);
$router->get('/admin/hampers/{id}/edit', [HampersController::class, 'edit']);
$router->post('/admin/hampers/{id}', [HampersController::class, 'update']);

$router->get('/admin/stok', [InventoryController::class, 'index']);
$router->post('/admin/stok/varian/{id}', [InventoryController::class, 'updateStock']);
$router->post('/admin/stok/varian/{id}/data', [InventoryController::class, 'updateVariant']);

$router->get('/admin/pengaturan', [SettingsController::class, 'index']);
$router->post('/admin/pengaturan', [SettingsController::class, 'save']);
$router->post('/admin/pengaturan/profil', [SettingsController::class, 'updateProfile']);
