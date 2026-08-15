<?php

use App\Models\User;

/**
 * Smoke test: seluruh halaman utama harus render tanpa error setelah
 * konversi tombol ke komponen <x-button> dan penambahan validasi periode.
 */
it('renders every main page for an admin', function (string $url) {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get($url)->assertOk();
})->with([
    '/dashboard',
    '/products',
    '/stocks',
    '/income',
    '/sales-returns',
    '/expenses',
    '/capital',
    '/users',
    '/reports',
]);

it('renders pages available to pegawai', function (string $url) {
    $pegawai = User::factory()->pegawai()->create();

    $this->actingAs($pegawai)->get($url)->assertOk();
})->with([
    '/products',
    '/stocks',
    '/income',
    '/sales-returns',
    '/expenses',
]);

it('renders buttons through the x-button component', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/expenses')
        ->assertOk()
        ->assertSee('btn-icon-label', false)
        ->assertSee('btn-icon', false);
});

it('renders capital nominal keyboard sign control', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/capital')
        ->assertOk()
        ->assertSee('updateRupiahSign($event, form.nominal)', false);
});

it('renders profile before logout in the account dropdown', function () {
    $admin = User::factory()->admin()->create();

    $html = $this->actingAs($admin)->get('/expenses')->assertOk()->getContent();

    $profilePosition = strpos($html, '>Profil</span>');
    $logoutPosition = strpos($html, '>Keluar</span>');

    expect($html)->toContain('ld-account-dropdown')
        ->and($profilePosition)->toBeLessThan($logoutPosition);
});

it('never renders a themed button variant without the base btn class', function (string $url) {
    $admin = User::factory()->admin()->create();

    $html = $this->actingAs($admin)->get($url)->assertOk()->getContent();

    preg_match_all('/class="([^"]*)"/', $html, $matches);

    $variants = ['btn-app', 'btn-brand', 'btn-app-secondary', 'btn-app-ghost', 'btn-app-success', 'btn-pill-primary', 'btn-pill-brand', 'btn-pill-secondary'];

    $offenders = [];
    foreach ($matches[1] as $classList) {
        $classes = preg_split('/\s+/', trim($classList));

        if (in_array('btn', $classes, true)) {
            continue;
        }

        foreach ($variants as $variant) {
            if (in_array($variant, $classes, true)) {
                $offenders[] = $classList;
                break;
            }
        }
    }

    // Varian LeatherDash hanya mendefinisikan variabel --bs-btn-*; tanpa kelas
    // `btn` seluruh tema tidak diterapkan dan tombol tampil polos.
    expect($offenders)->toBe([]);
})->with([
    '/dashboard',
    '/products',
    '/stocks',
    '/income',
    '/sales-returns',
    '/expenses',
    '/capital',
    '/users',
    '/reports',
]);
