<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            // Cek apakah akun dinonaktifkan oleh Admin
            if (isset($user->is_active) && !$user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'Akun Anda telah dinonaktifkan oleh Administrator. Hubungi Admin untuk pemulihan akses.',
                ])->onlyInput('email');
            }

            $request->session()->regenerate();
            ActivityLog::record('Login ke sistem sebagai ' . ucfirst($user->role), 'success');

            return redirect()->intended(route('dashboard', $user->role));
        }

        return back()->withErrors([
            'email' => 'Email atau kata sandi yang Anda masukkan tidak sesuai.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            ActivityLog::record('Keluar (Logout) dari sistem', 'success');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}