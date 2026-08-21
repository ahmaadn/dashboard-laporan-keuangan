<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

describe('user schema', function () {
    it('does not contain the dashboard visibility column', function () {
        expect(Schema::hasColumn('users', 'dapat_melihat_dashboard'))->toBeFalse();
    });
});

describe('user store', function () {
    it('creates a user as admin', function () {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/users', [
            'nama' => 'Pengguna Baru',
            'nama_pengguna' => 'penggunabaru',
            'email' => 'baru@test.id',
            'kata_sandi' => 'password123',
            'peran' => 'pegawai',
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

    it('allows admin to update their own non-role fields', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->putJson("/users/{$admin->id}", [
            'nama' => 'Nama Baru Admin',
            'nama_pengguna' => $admin->username,
            'email' => $admin->email,
            'peran' => 'admin',
            'aktif' => true,
        ])->assertOk()->assertJsonPath('resource.nama', 'Nama Baru Admin');
    });

    it('prevents admin from downgrading their own role', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->putJson("/users/{$admin->id}", [
            'nama' => $admin->nama,
            'nama_pengguna' => $admin->username,
            'email' => $admin->email,
            'peran' => 'pegawai',
            'aktif' => true,
        ])->assertStatus(422);

        expect($admin->fresh()->peran)->toBe('admin');
    });

    it('prevents downgrading the last active admin to pegawai', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->putJson("/users/{$admin->id}", [
            'nama' => $admin->nama,
            'nama_pengguna' => $admin->username,
            'email' => $admin->email,
            'peran' => 'pegawai',
            'aktif' => true,
        ])->assertStatus(422);

        expect($admin->fresh()->peran)->toBe('admin');
    });

    it('allows admin to downgrade another admin when another active admin exists', function () {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->admin()->create();

        $this->actingAs($admin)->putJson("/users/{$other->id}", [
            'nama' => $other->nama,
            'nama_pengguna' => $other->username,
            'email' => $other->email,
            'peran' => 'pegawai',
            'aktif' => true,
        ])->assertOk();

        expect($other->fresh()->peran)->toBe('pegawai');
    });

    it('allows admin to delete another admin', function () {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->admin()->create();

        $this->actingAs($admin)->deleteJson("/users/{$other->id}")->assertOk();
        expect(User::find($other->id))->toBeNull();
        expect(User::withTrashed()->find($other->id)->trashed())->toBeTrue();
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
