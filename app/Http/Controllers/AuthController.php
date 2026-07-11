<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login', [
            'profiles' => $this->demoProfiles(),
        ]);
    }

    public function login(LoginRequest $request)
    {
        $login = $request->string('login')->trim();
        $password = $request->string('password');

        $user = User::where('username', $login)
            ->orWhere('email', $login)
            ->first();

        if (! $user) {
            return back()->withErrors(['login' => 'Nama pengguna atau kata sandi salah.'])->onlyInput('login');
        }

        if (! $user->is_active || $user->trashed()) {
            return back()->withErrors(['login' => 'Akun tidak aktif, hubungi administrator.'])->onlyInput('login');
        }

        if (! Auth::attempt(['username' => $user->username, 'password' => $password], $request->boolean('remember'))) {
            return back()->withErrors(['login' => 'Nama pengguna atau kata sandi salah.'])->onlyInput('login');
        }

        $request->session()->regenerate();

        return redirect()->intended($user->canSeeDashboard() ? '/dashboard' : '/income');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    /**
     * Demo profiles for the login quick-fill buttons.
     *
     * @return array<int, array<string, mixed>>
     */
    private function demoProfiles(): array
    {
        return User::whereIn('username', ['busari', 'dimas', 'rina'])
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->get()
            ->map(fn (User $u) => [
                'nama' => $u->nama,
                'nama_pengguna' => $u->username,
                'peran' => $u->peran,
                'dapat_melihat_dashboard' => $u->canSeeDashboard(),
            ])
            ->values()
            ->all();
    }
}
