<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        return view('profile.index', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name'   => 'required|string|max:255',
            'email'  => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ], [
            'name.required'  => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email'    => 'Format alamat email tidak valid.',
            'email.unique'   => 'Alamat email sudah digunakan oleh akun lain.',
            'avatar.image'   => 'File harus berupa gambar.',
            'avatar.mimes'   => 'Format foto harus JPG atau PNG.',
            'avatar.max'     => 'Ukuran maksimal foto adalah 2 MB.',
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $user->avatar = $request->file('avatar')->store('avatars', 'public');
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->save();

        ActivityLog::record('Memperbarui informasi profil akun', 'success');

        return redirect()
            ->route('profile.index', ['tab' => 'profile'])
            ->with('profile_success', 'Informasi profil berhasil diperbarui.');
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|string|min:8|confirmed',
        ], [
            'current_password.required' => 'Kata sandi saat ini wajib diisi.',
            'password.required'         => 'Kata sandi baru wajib diisi.',
            'password.min'              => 'Kata sandi baru minimal 8 karakter.',
            'password.confirmed'        => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            ActivityLog::record('Gagal mengubah kata sandi (kata sandi saat ini salah)', 'failed');
            return redirect()
                ->route('profile.index', ['tab' => 'security'])
                ->withErrors(['current_password' => 'Kata sandi saat ini tidak sesuai.']);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        ActivityLog::record('Memperbarui kata sandi akun', 'success');

        return redirect()
            ->route('profile.index', ['tab' => 'security'])
            ->with('profile_success', 'Kata sandi berhasil diperbarui.');
    }
}