<?php

namespace App\Http\Controllers;

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

            // Arahkan ke dashboard masing-masing sesuai role UI Figma
            return match ($role) {
                'ketua'     => redirect()->intended('/dashboard/ketua'),
                'bendahara' => redirect()->intended('/dashboard/bendahara'),
                'koordiv'   => redirect()->intended('/dashboard/koordiv'),
                'admin'     => redirect()->intended('/dashboard/admin'),
                default     => redirect('/'),
            };
        }

        return back()->withErrors([
            'email' => 'Email atau password tidak sesuai.',
        ])->onlyInput('email');
    }

    // Proses keluar (Logout)
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}