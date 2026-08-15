<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\patch;

it('requires authentication to access profile settings', function () {
    get('/profile')->assertRedirect('/login');
    patch('/profile', [])->assertRedirect('/login');
    patch('/profile/password', [])->assertRedirect('/login');
});

it('shows the profile page for admin and pegawai', function (string $role) {
    $user = User::factory()->{$role}()->create([
        'nama' => 'Budi Santoso',
        'email' => 'budi@example.test',
    ]);

    actingAs($user)
        ->get('/profile')
        ->assertOk()
        ->assertSee('Profil Saya')
        ->assertSee('Budi Santoso')
        ->assertSee('budi@example.test');
})->with(['admin', 'pegawai']);

it('updates the authenticated users name and email only', function () {
    $user = User::factory()->pegawai()->create([
        'nama' => 'Nama Lama',
        'email' => 'lama@example.test',
        'username' => 'tetap',
        'peran' => 'pegawai',
    ]);

    actingAs($user)
        ->patch('/profile', [
            'nama' => 'Nama Baru',
            'email' => 'baru@example.test',
            'username' => 'berubah',
            'peran' => 'admin',
        ])
        ->assertRedirect('/profile')
        ->assertSessionHas('status', 'profile-updated');

    $user->refresh();

    expect($user->nama)->toBe('Nama Baru')
        ->and($user->email)->toBe('baru@example.test')
        ->and($user->username)->toBe('tetap')
        ->and($user->peran)->toBe('pegawai');
});

it('allows the authenticated user to keep their current email', function () {
    $user = User::factory()->admin()->create();

    actingAs($user)
        ->patch('/profile', [
            'nama' => $user->nama,
            'email' => $user->email,
        ])
        ->assertRedirect('/profile')
        ->assertSessionHasNoErrors();
});

it('rejects an email used by another active user', function () {
    $user = User::factory()->pegawai()->create();
    $otherUser = User::factory()->create(['email' => 'taken@example.test']);

    actingAs($user)
        ->from('/profile')
        ->patch('/profile', [
            'nama' => $user->nama,
            'email' => $otherUser->email,
        ])
        ->assertRedirect('/profile')
        ->assertSessionHasErrors(['email']);
});

it('updates the authenticated users password', function () {
    $user = User::factory()->admin()->create(['password' => 'old-password']);

    actingAs($user)
        ->patch('/profile/password', [
            'current_password' => 'old-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertRedirect('/profile')
        ->assertSessionHas('status', 'password-updated');

    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue();
});

it('rejects an incorrect current password', function () {
    $user = User::factory()->pegawai()->create(['password' => 'old-password']);

    actingAs($user)
        ->patch('/profile/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertSessionHasErrors(['current_password']);

    expect(Hash::check('old-password', $user->fresh()->password))->toBeTrue();
});

it('renders the profile dropdown without the sidebar footer', function () {
    $user = User::factory()->admin()->create(['nama' => 'Admin Kulit']);

    $response = actingAs($user)->get('/dashboard')->assertOk();

    expect($response->getContent())
        ->toContain('ld-account-dropdown')
        ->toContain('@click="toggleAccountMenu()"')
        ->toContain('x-show="accountMenuOpen"')
        ->toContain('href="'.route('profile.edit').'"')
        ->toContain('Admin Kulit')
        ->not->toContain('ld-sidebar__footer');
});
