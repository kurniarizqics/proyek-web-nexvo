<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Division;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    private function ensureAdmin(): void
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Akses ditolak. Halaman ini khusus Administrator.');
        }
    }

    public function index(Request $request)
    {
        $this->ensureAdmin();

        $search = $request->query('search');
        $roleFilter = $request->query('role');
        $statusFilter = $request->query('status');

        $query = User::with('divisionRelation')->latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('division', 'like', "%{$search}%");
            });
        }

        if ($roleFilter) {
            $query->where('role', $roleFilter);
        }

        if ($statusFilter === 'active') {
            $query->where('is_active', true);
        } elseif ($statusFilter === 'inactive') {
            $query->where('is_active', false);
        }

        $users = $query->get();
        $divisions = Division::where('is_active', true)->orderBy('name')->get();
        $hasKetua = User::where('role', 'ketua')->exists();

        return view('admin.users.index', compact('users', 'divisions', 'hasKetua'));
    }

    public function store(Request $request)
    {
        $this->ensureAdmin();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,ketua,bendahara,koordiv,anggota',
            'division_name' => 'nullable|string|max:100',
        ]);

        $divisionInput = trim((string) $request->input('division_name'));
        $division = null;

        if ($divisionInput !== '') {
            $division = Division::firstOrCreate(
                ['name' => $divisionInput],
                ['is_active' => true]
            );
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'division_id' => $division?->id,
            'division' => $division?->name,
            'is_active' => true,
        ]);

        if ($user->role === 'koordiv' && $division && !$division->coordinator_id) {
            $division->update(['coordinator_id' => $user->id]);
        }

        ActivityLog::record("Admin menambahkan akun pengguna baru: {$user->name} ({$user->role})", 'success');

        return redirect()->route('admin.users.index')->with([
            'success_title' => 'Pengguna baru berhasil ditambahkan.',
            'success' => "Akun {$user->name} telah terdaftar dengan peran " . ucfirst($user->role) . '.',
        ]);
    }

    public function update(Request $request, User $user)
    {
        $this->ensureAdmin();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => 'required|in:admin,ketua,bendahara,koordiv,anggota',
            'division_name' => 'nullable|string|max:100',
        ]);

        if ($user->id === Auth::id() && $request->role !== 'admin') {
            return back()->with('error', 'Anda tidak dapat menurunkan peran akun Administrator Anda sendiri.');
        }

        $divisionInput = trim((string) $request->input('division_name'));
        $division = null;

        if ($divisionInput !== '') {
            $division = Division::firstOrCreate(
                ['name' => $divisionInput],
                ['is_active' => true]
            );
        }

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'division_id' => $division?->id,
            'division' => $division?->name,
        ]);

        if ($user->role === 'koordiv' && $division && !$division->coordinator_id) {
            $division->update(['coordinator_id' => $user->id]);
        }

        ActivityLog::record("Admin memperbarui data & peran akun: {$user->name} (" . ucfirst($user->role) . ")", 'success');

        return redirect()->route('admin.users.index')->with([
            'success_title' => 'Data pengguna berhasil diperbarui.',
            'success' => "Informasi dan peran untuk {$user->name} telah disimpan.",
        ]);
    }

    public function toggleStatus(User $user)
    {
        $this->ensureAdmin();

        if ($user->id === Auth::id()) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        $newStatus = !$user->is_active;
        $user->update(['is_active' => $newStatus]);

        $statusLabel = $newStatus ? 'mengaktifkan kembali' : 'menonaktifkan';
        ActivityLog::record("Admin {$statusLabel} akun pengguna: {$user->name}", 'success');

        return back()->with([
            'success_title' => $newStatus ? 'Akun pengguna diaktifkan.' : 'Akun pengguna dinonaktifkan.',
            'success' => $newStatus
                ? "Akun {$user->name} kini dapat login kembali ke sistem."
                : "Akses login untuk {$user->name} telah dihentikan tanpa menghapus riwayat data.",
        ]);
    }

    public function resetPassword(Request $request, User $user)
    {
        $this->ensureAdmin();

        $request->validate([
            'new_password' => 'required|string|min:6|confirmed',
        ], [
            'new_password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
            'new_password.min' => 'Kata sandi baru minimal 6 karakter.',
        ]);

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        ActivityLog::record("Admin mereset kata sandi akun: {$user->name}", 'success');

        return back()->with([
            'success_title' => 'Kata sandi berhasil direset.',
            'success' => "Kata sandi baru untuk akun {$user->name} telah diterapkan.",
        ]);
    }
}