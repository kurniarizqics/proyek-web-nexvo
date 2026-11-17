<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // Menampilkan halaman form login
    public function showLogin()
    {
        return view('auth.login');
    }

    // Memproses login dan mengarahkan sesuai role
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            $role = Auth::user()->role;

            // Catat log login berhasil
            ActivityLog::record('Login ke sistem', 'success');

            // Arahkan ke dashboard masing-masing sesuai role UI Figma
            return match ($role) {
                'ketua'     => redirect()->intended('/dashboard/ketua'),
                'bendahara' => redirect()->intended('/dashboard/bendahara'),
                'koordiv'   => redirect()->intended('/dashboard/koordiv'),
                'anggota'   => redirect()->intended('/dashboard/anggota'),
                'admin'     => redirect()->intended('/dashboard/admin'),
                default     => redirect('/'),
            };
        }

        // Catat log percobaan login gagal
        $attemptedUser = User::where('email', $request->email)->first();
        ActivityLog::create([
            'user_id'     => $attemptedUser?->id,
            'user_name'   => $attemptedUser?->name ?? $request->email,
            'user_role'   => $attemptedUser ? ucfirst($attemptedUser->role) : 'Tamu',
            'description' => 'Percobaan login gagal',
            'status'      => 'failed',
        ]);

        return back()->withErrors([
            'email' => 'Email atau password tidak sesuai.',
        ])->onlyInput('email');
    }

    // Proses keluar (Logout)
    public function logout(Request $request)
    {
        if (Auth::check()) {
            ActivityLog::record('Logout dari sistem', 'success');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}