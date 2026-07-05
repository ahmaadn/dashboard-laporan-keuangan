<?php

namespace App\Providers;

use App\Services\Mock\MockData;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as IlluminateView;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::directive('rupiah', fn ($expression) => "<?php echo \\App\\Services\\Mock\\MockData::rupiah({$expression}); ?>");
        Blade::directive('tanggal', fn ($expression) => "<?php echo \\App\\Services\\Mock\\MockData::tanggal({$expression}); ?>");
        Blade::directive('tanggalLengkap', fn ($expression) => "<?php echo \\App\\Services\\Mock\\MockData::tanggalLengkap({$expression}); ?>");

        View::composer('layouts.app', function (IlluminateView $view) {
            $currentUser = MockData::currentUser(request());
            $isAdmin = $currentUser['peran'] === 'admin';
            $canSeeDashboard = $isAdmin || $currentUser['dapat_melihat_dashboard'];

            $menus = array_filter([
                ['label' => 'Dashboard', 'url' => '/dashboard', 'icon' => 'dashboard', 'show' => $canSeeDashboard],
                ['label' => 'Data Produk', 'url' => '/products', 'icon' => 'products', 'show' => true],
                ['label' => 'Pemasukan', 'url' => '/income', 'icon' => 'income', 'show' => true],
                ['label' => 'Pengeluaran', 'url' => '/expenses', 'icon' => 'expenses', 'show' => true],
                ['label' => 'Data Pengguna', 'url' => '/users', 'icon' => 'users', 'show' => $isAdmin],
                ['label' => 'Laporan Keuangan', 'url' => '/reports', 'icon' => 'reports', 'show' => $isAdmin],
            ], fn ($m) => $m['show']);

            $view
                ->with('currentUser', $currentUser)
                ->with('profiles', MockData::profiles())
                ->with('menus', array_values($menus));
        });
    }
}
