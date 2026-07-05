<?php

use App\Http\Controllers\Reports\ExportController;
use App\Services\Mock\MockData;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/login'));

Route::get('/login', function () {
    return view('auth.login', [
        'profiles' => MockData::profiles(),
    ]);
});

Route::get('/logout', function () {
    return Response::redirectTo('/login')->withCookie(cookie()->forget('ld_profile'));
});

Route::get('/dashboard', function () {
    return view('dashboard.index', [
        'pemasukan' => MockData::pemasukanAktif(),
        'pengeluaran' => MockData::pengeluaranAktif(),
        'produk' => MockData::produk(),
        'kategoriProduk' => MockData::kategoriProduk(),
        'kategoriPengeluaran' => MockData::kategoriPengeluaran(),
        'pengguna' => MockData::pengguna(),
    ]);
});

Route::get('/products', function () {
    return view('products.index', [
        'produk' => MockData::produk(),
        'kategoriProduk' => MockData::kategoriProdukById(),
        'currentUser' => MockData::currentUser(request()),
    ]);
});

Route::get('/income', function () {
    return view('income.index', [
        'pemasukan' => MockData::pemasukan(),
        'produkAktif' => MockData::produkAktif(),
        'produkById' => MockData::produkById(),
        'penggunaById' => MockData::penggunaById(),
        'currentUser' => MockData::currentUser(request()),
    ]);
});

Route::get('/expenses', function () {
    return view('expenses.index', [
        'pengeluaran' => MockData::pengeluaran(),
        'kategoriPengeluaran' => MockData::kategoriPengeluaran(),
        'penggunaById' => MockData::penggunaById(),
        'currentUser' => MockData::currentUser(request()),
    ]);
});

Route::get('/users', function () {
    return view('users.index', [
        'pengguna' => MockData::pengguna(),
        'currentUser' => MockData::currentUser(request()),
    ]);
});

Route::get('/reports', function () {
    $period = request()->query('period', 'bulan_ini');
    $report = MockData::reportSummary($period, request()->query('start'), request()->query('end'));

    return view('reports.index', [
        'report' => $report,
        'periodOptions' => MockData::periodOptions(),
        'currentUser' => MockData::currentUser(request()),
    ]);
});

Route::get('/reports/export/pdf', [ExportController::class, 'pdf'])->name('reports.export.pdf');
Route::get('/reports/export/excel', [ExportController::class, 'excel'])->name('reports.export.excel');
