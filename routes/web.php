<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CapitalController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Reports\ExportController;
use App\Http\Controllers\SalesReceiptController;
use App\Http\Controllers\SalesReturnController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'store'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('dashboard');
    Route::get('/api/dashboard', [DashboardController::class, 'data'])->middleware('dashboard');
    Route::get('/api/dashboard/compare', [DashboardController::class, 'compare'])->middleware('dashboard');

    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store'])->middleware('role:admin,pegawai');
    Route::match(['put', 'patch'], '/products/{product}', [ProductController::class, 'update'])->middleware('role:admin');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->middleware('role:admin');
    Route::post('/products/{product}/stock', [ProductController::class, 'adjustStock'])->middleware('role:admin');
    Route::get('/products/{product}/movements', [ProductController::class, 'movements']);

    // Kelola Stok — semua peran dapat melihat & mencatat retur/produksi;
    // mutasi stok masuk (restok) butuh Admin.
    Route::get('/stocks', [StockController::class, 'index']);
    Route::post('/stocks', [StockController::class, 'store'])->middleware('role:admin');
    Route::get('/stocks/movements', [StockController::class, 'movements']);

    Route::get('/income', [IncomeController::class, 'index']);
    Route::post('/income', [IncomeController::class, 'store']);
    Route::match(['put', 'patch'], '/income/{income}', [IncomeController::class, 'update']);
    Route::delete('/income/{income}', [IncomeController::class, 'destroy']);

    // Nota penjualan — bukti transaksi per nomor transaksi kasir
    Route::get('/income/nota/{nomorTransaksi}', [SalesReceiptController::class, 'show'])->name('income.nota');
    Route::get('/income/nota/{nomorTransaksi}/pdf', [SalesReceiptController::class, 'pdf'])->name('income.nota.pdf');

    // Retur penjualan (lihat Bagian 2.4 & 4 dokumen acuan)
    Route::get('/sales-returns', [SalesReturnController::class, 'index']);
    Route::post('/sales-returns', [SalesReturnController::class, 'store']);
    Route::delete('/sales-returns/{salesReturn}', [SalesReturnController::class, 'destroy'])->middleware('role:admin');

    Route::get('/expenses', [ExpenseController::class, 'index']);
    Route::post('/expenses', [ExpenseController::class, 'store']);
    Route::match(['put', 'patch'], '/expenses/{expense}', [ExpenseController::class, 'update']);
    Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy']);

    // Modal / setoran pemilik — admin only (lihat Bagian 2.1)
    Route::get('/capital', [CapitalController::class, 'index'])->middleware('role:admin');
    Route::post('/capital', [CapitalController::class, 'store'])->middleware('role:admin');
    Route::delete('/capital/{capitalInjection}', [CapitalController::class, 'destroy'])->middleware('role:admin');

    Route::get('/users', [UserController::class, 'index'])->middleware('role:admin');
    Route::post('/users', [UserController::class, 'store'])->middleware('role:admin');
    Route::match(['put', 'patch'], '/users/{user}', [UserController::class, 'update'])->middleware('role:admin');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('role:admin');

    Route::get('/reports', [ReportController::class, 'index'])->middleware('role:admin');
    Route::post('/reports/hpp-adjustments', [ReportController::class, 'storeHppAdjustment'])->middleware('role:admin');
    Route::delete('/reports/hpp-adjustments/{hppAdjustment}', [ReportController::class, 'destroyHppAdjustment'])->middleware('role:admin');
    Route::get('/reports/export/pdf', [ExportController::class, 'pdf'])->name('reports.export.pdf')->middleware('role:admin');
    Route::get('/reports/export/excel', [ExportController::class, 'excel'])->name('reports.export.excel')->middleware('role:admin');
});
