<?php

use App\Services\Mock\MockData;
use Illuminate\Http\Request;

describe('mock routes', function () {
    foreach (['/login', '/dashboard', '/products', '/income', '/expenses', '/users', '/reports'] as $uri) {
        test("admin can access {$uri}", function () use ($uri) {
            $admin = MockData::profiles()[0];

            $this->withUnencryptedCookie('ld_profile', json_encode($admin))
                ->get($uri)
                ->assertStatus(200);
        });

        test("pegawai with dashboard can access {$uri}", function () use ($uri) {
            $pegawai = collect(MockData::profiles())->firstWhere('peran', 'pegawai');

            $this->withUnencryptedCookie('ld_profile', json_encode($pegawai))
                ->get($uri)
                ->assertStatus(200);
        });
    }
});

it('hides admin-only nav for pegawai without dashboard access', function () {
    $pegawaiNoDash = collect(MockData::profiles())->firstWhere('dapat_melihat_dashboard', false);

    $response = $this->withUnencryptedCookie('ld_profile', json_encode($pegawaiNoDash))->get('/income');

    $response->assertStatus(200);
    $response->assertDontSee('href="/users" class="ld-nav-link"', false);
    $response->assertDontSee('href="/reports" class="ld-nav-link"', false);
    $response->assertDontSee('href="/dashboard" class="ld-nav-link"', false);
});

it('shows all nav items for admin', function () {
    $admin = MockData::profiles()[0];

    $response = $this->withUnencryptedCookie('ld_profile', json_encode($admin))->get('/income');

    $response->assertStatus(200);
    $response->assertSee('href="/users" class="ld-nav-link"', false);
    $response->assertSee('href="/reports" class="ld-nav-link"', false);
    $response->assertSee('href="/dashboard" class="ld-nav-link"', false);
});

it('resolves pegawai profile from url-encoded cookie', function () {
    $pegawai = collect(MockData::profiles())->firstWhere('peran', 'pegawai');

    $request = Request::create('http://localhost/income', 'GET', [], ['ld_profile' => urlencode(json_encode($pegawai))]);
    $user = MockData::currentUser($request);

    expect($user['id'])->toBe($pegawai['id']);
    expect($user['peran'])->toBe('pegawai');
});
