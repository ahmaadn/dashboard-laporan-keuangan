<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

describe('user store', function () {
    it('creates a user as admin', function () {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/users', [
            'nama' => 'Pengguna Baru',
            'nama_pengguna' => 'penggunabaru',
            'email' => 'baru@test.id',
            'kata_sandi' => 'password123',
            'peran' => 'pegawai',
            'dapat_melihat_dashboard' => false,
            'aktif' => true,
        ]);

        $response->assertCreated();
        expect(User::where('username', 'penggunabaru')->exists())->toBeTrue();
    });

    it('validates unique username', function () {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['username' => 'taken']);

        $this->actingAs($admin)->postJson('/users', [
            'nama' => 'Test',
            'nama_pengguna' => 'taken',
            'email' => 'new@test.id',
            'kata_sandi' => 'password123',
            'peran' => 'pegawai',
        ])->assertStatus(422);
    });

    it('validates unique email', function () {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['email' => 'taken@test.id']);

        $this->actingAs($admin)->postJson('/users', [
            'nama' => 'Test',
            'nama_pengguna' => 'newuser',
            'email' => 'taken@test.id',
            'kata_sandi' => 'password123',
            'peran' => 'pegawai',
        ])->assertStatus(422);
    });

    it('validates password minimum length on create', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson('/users', [
            'nama' => 'Test',
            'nama_pengguna' => 'newuser',
            'email' => 'new@test.id',
            'kata_sandi' => 'short',
            'peran' => 'pegawai',
        ])->assertStatus(422);
    });

    it('sets dapat_melihat_dashboard true for admin automatically', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson('/users', [
            'nama' => 'Admin Baru',
            'nama_pengguna' => 'adminbaru',
            'email' => 'adminbaru@test.id',
            'kata_sandi' => 'password123',
            'peran' => 'admin',
            'dapat_melihat_dashboard' => false,
            'aktif' => true,
        ])->assertCreated();

        expect(User::where('username', 'adminbaru')->first()->dapat_melihat_dashboard)->toBeTrue();
    });
});

describe('user update', function () {
    it('updates a user without changing password', function () {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->pegawai()->create(['nama' => 'Old Name']);

        $this->actingAs($admin)->putJson("/users/{$user->id}", [
            'nama' => 'New Name',
            'nama_pengguna' => $user->username,
            'email' => $user->email,
            'peran' => 'pegawai',
            'aktif' => true,
        ])->assertOk()->assertJsonPath('resource.nama', 'New Name');
    });

    it('updates password when provided', function () {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['password' => 'oldpassword']);

        $this->actingAs($admin)->putJson("/users/{$user->id}", [
            'nama' => $user->nama,
            'nama_pengguna' => $user->username,
            'email' => $user->email,
            'kata_sandi' => 'newpassword123',
            'peran' => $user->peran,
            'aktif' => true,
        ])->assertOk();

        expect(Hash::check('newpassword123', $user->fresh()->password))->toBeTrue();
    });
});

describe('user destroy', function () {
    it('soft deletes a user', function () {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->pegawai()->create();

        $this->actingAs($admin)->deleteJson("/users/{$target->id}")->assertOk();
        expect(User::find($target->id))->toBeNull();
        expect(User::withTrashed()->find($target->id)->trashed())->toBeTrue();
    });

    it('prevents self-deletion', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->deleteJson("/users/{$admin->id}")->assertForbidden();
    });

    it('prevents deactivating last active admin', function () {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->admin()->create();

        // Deactivate the other admin — OK, $admin still active.
        $this->actingAs($admin)->putJson("/users/{$other->id}", [
            'nama' => $other->nama,
            'nama_pengguna' => $other->username,
            'email' => $other->email,
            'peran' => 'admin',
            'aktif' => false,
        ])->assertOk();

        // Now $admin is the last active admin — cannot deactivate.
        $this->actingAs($admin)->putJson("/users/{$admin->id}", [
            'nama' => $admin->nama,
            'nama_pengguna' => $admin->username,
            'email' => $admin->email,
            'peran' => 'admin',
            'aktif' => false,
        ])->assertStatus(422);
    });
});
