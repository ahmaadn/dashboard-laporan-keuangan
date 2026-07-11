<?php

namespace App\Providers;

use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as IlluminateView;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Blade::directive('rupiah', fn ($expression) => "<?php echo \\App\\Support\\Format::rupiah({$expression}); ?>");
        Blade::directive('tanggal', fn ($expression) => "<?php echo \\App\\Support\\Format::tanggal({$expression}); ?>");
        Blade::directive('tanggalLengkap', fn ($expression) => "<?php echo \\App\\Support\\Format::tanggalLengkap({$expression}); ?>");

        View::composer('layouts.app', function (IlluminateView $view) {
            $user = Auth::user();
            $currentUser = $user ? UserResource::make($user)->resolve() : null;
            $isAdmin = $user?->isAdmin() ?? false;
            $canSeeDashboard = $user?->canSeeDashboard() ?? false;

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
                ->with('menus', array_values($menus));
        });
    }
}
