<?php

use App\Models\User;

use function Pest\Laravel\get;

describe('sidebar menu and route access', function () {
    it('admin sees the expected menu entries in order', function () {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/dashboard');
        $response->assertOk();
        $html = $response->getContent();

        $dashboardPos = strpos($html, 'href="/dashboard"');
        $productsPos = strpos($html, 'href="/products"');
        $stocksPos = strpos($html, 'href="/stocks"');
        $incomePos = strpos($html, 'href="/income"');
        $returnsPos = strpos($html, 'href="/sales-returns"');
        $expensesPos = strpos($html, 'href="/expenses"');
        $usersPos = strpos($html, 'href="/users"');
        $reportsPos = strpos($html, 'href="/reports"');
        $capitalPos = strpos($html, 'href="/capital"');

        expect($dashboardPos)->toBeLessThan($productsPos);
        expect($productsPos)->toBeLessThan($stocksPos);
        expect($stocksPos)->toBeLessThan($incomePos);
        expect($incomePos)->toBeLessThan($returnsPos);
        expect($returnsPos)->toBeLessThan($expensesPos);
        expect($expensesPos)->toBeLessThan($usersPos);
        expect($usersPos)->toBeLessThan($reportsPos);
        expect($reportsPos)->toBeLessThan($capitalPos);
        expect($capitalPos)->toBeGreaterThan(0);
    });

    it('pegawai does NOT see admin-only menus', function () {
        $pegawai = User::factory()->pegawai()->withDashboard()->create();

        $response = $this->actingAs($pegawai)->get('/dashboard');
        $response->assertOk();
        $html = $response->getContent();

        expect(str_contains($html, 'href="/users"'))->toBeFalse();
        expect(str_contains($html, 'href="/reports"'))->toBeFalse();
        expect(str_contains($html, 'href="/capital"'))->toBeFalse();
    });

    it('pegawai sees Dashboard, Data Produk, Kelola Stok, Pemasukan, Retur, Pengeluaran', function () {
        $pegawai = User::factory()->pegawai()->withDashboard()->create();

        $response = $this->actingAs($pegawai)->get('/dashboard');
        $response->assertOk();
        $html = $response->getContent();

        expect(str_contains($html, 'href="/dashboard"'))->toBeTrue();
        expect(str_contains($html, 'href="/products"'))->toBeTrue();
        expect(str_contains($html, 'href="/stocks"'))->toBeTrue();
        expect(str_contains($html, 'href="/income"'))->toBeTrue();
        expect(str_contains($html, 'href="/sales-returns"'))->toBeTrue();
        expect(str_contains($html, 'href="/expenses"'))->toBeTrue();
    });

    it('admin can access all menu targets', function () {
        $admin = User::factory()->admin()->create();

        foreach (['/dashboard', '/products', '/stocks', '/income', '/sales-returns', '/expenses', '/users', '/reports', '/capital'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    });

    it('pegawai without dashboard access is blocked from dashboard', function () {
        $pegawai = User::factory()->pegawai()->withoutDashboard()->create();
        $this->actingAs($pegawai)->get('/dashboard')->assertForbidden();
    });

    it('pegawai cannot access admin-only pages', function () {
        $pegawai = User::factory()->pegawai()->withDashboard()->create();
        $this->actingAs($pegawai)->get('/users')->assertForbidden();
        $this->actingAs($pegawai)->get('/reports')->assertForbidden();
        $this->actingAs($pegawai)->get('/capital')->assertForbidden();
    });
});

describe('global auth gating', function () {
    it('requires authentication for protected routes', function () {
        foreach (['/dashboard', '/products', '/stocks', '/income', '/expenses', '/sales-returns'] as $url) {
            get($url)->assertRedirect('/login');
        }
    });
});
