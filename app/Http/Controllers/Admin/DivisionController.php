<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Division;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class DivisionController extends Controller
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
        $query = Division::with(['coordinator', 'members'])->orderBy('name');

        if ($search) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
        }

        $divisions = $query->get();

        // Daftar kandidat koordinator (user aktif dengan role koordiv atau anggota)
        $coordinators = User::where('is_active', true)
            ->whereIn('role', ['koordiv', 'anggota'])
            ->orderBy('name')
            ->get();

        // Daftar semua anggota/koordiv aktif untuk pemetaan ke divisi
        $allMembers = User::where('is_active', true)
            ->whereIn('role', ['anggota', 'koordiv'])
            ->orderBy('name')
            ->get();

        return view('admin.divisions.index', compact('divisions', 'coordinators', 'allMembers'));
    }

    public function store(Request $request)
    {
        $this->ensureAdmin();

        $request->validate([
            'name' => 'required|string|max:100|unique:divisions,name',
            'description' => 'nullable|string|max:500',
            'coordinator_id' => 'nullable|exists:users,id',
        ]);

        $division = Division::create([
            'name' => $request->name,
            'description' => $request->description,
            'coordinator_id' => $request->coordinator_id,
            'is_active' => true,
        ]);

        // Jika koordinator ditunjuk, sinkronkan divisi & perannya
        if ($division->coordinator_id) {
            User::where('id', $division->coordinator_id)->update([
                'role' => 'koordiv',
                'division_id' => $division->id,
                'division' => $division->name,
            ]);
        }

        ActivityLog::record("Admin menambahkan divisi baru: {$division->name}", 'success');

        return redirect()->route('admin.divisions.index')->with([
            'success_title' => 'Divisi baru berhasil ditambahkan.',
            'success' => "Divisi {$division->name} siap digunakan dalam struktur organisasi.",
        ]);
    }

    public function update(Request $request, Division $division)
    {
        $this->ensureAdmin();

        $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('divisions', 'name')->ignore($division->id)],
            'description' => 'nullable|string|max:500',
            'coordinator_id' => 'nullable|exists:users,id',
        ]);

        $division->update([
            'name' => $request->name,
            'description' => $request->description,
            'coordinator_id' => $request->coordinator_id,
        ]);

        // Sinkronkan nama divisi string pada seluruh anggotanya
        User::where('division_id', $division->id)->update([
            'division' => $division->name,
        ]);

        // Pastikan user yang ditunjuk sebagai koordinator memiliki role koordiv & terpetakan ke divisi ini
        if ($division->coordinator_id) {
            User::where('id', $division->coordinator_id)->update([
                'role' => 'koordiv',
                'division_id' => $division->id,
                'division' => $division->name,
            ]);
        }

        ActivityLog::record("Admin memperbarui informasi & koordinator divisi: {$division->name}", 'success');

        return redirect()->route('admin.divisions.index')->with([
            'success_title' => 'Data divisi berhasil diperbarui.',
            'success' => "Perubahan pada divisi {$division->name} telah disimpan.",
        ]);
    }

    public function toggleStatus(Division $division)
    {
        $this->ensureAdmin();

        $newStatus = !$division->is_active;
        $division->update(['is_active' => $newStatus]);

        $label = $newStatus ? 'mengaktifkan kembali' : 'menonaktifkan';
        ActivityLog::record("Admin {$label} divisi: {$division->name}", 'success');

        return back()->with([
            'success_title' => $newStatus ? 'Divisi diaktifkan kembali.' : 'Divisi dinonaktifkan.',
            'success' => $newStatus
                ? "Divisi {$division->name} kini aktif kembali."
                : "Divisi {$division->name} telah dinonaktifkan.",
        ]);
    }

    public function assignMembers(Request $request, Division $division)
    {
        $this->ensureAdmin();

        $request->validate([
            'member_ids' => 'nullable|array',
            'member_ids.*' => 'exists:users,id',
        ]);

        $selectedIds = $request->input('member_ids', []);

        // Pertahankan koordinator agar tidak terlepas dari divisi
        if ($division->coordinator_id && !in_array($division->coordinator_id, $selectedIds)) {
            $selectedIds[] = $division->coordinator_id;
        }

        // Lepaskan anggota lama yang tidak lagi dipilih pada divisi ini
        User::where('division_id', $division->id)
            ->whereNotIn('id', $selectedIds)
            ->update([
                'division_id' => null,
                'division' => null,
            ]);

        // Tetapkan anggota terpilih ke divisi ini
        if (!empty($selectedIds)) {
            User::whereIn('id', $selectedIds)->update([
                'division_id' => $division->id,
                'division' => $division->name,
            ]);
        }

        ActivityLog::record("Admin memperbarui pemetaan anggota untuk divisi: {$division->name}", 'success');

        return redirect()->route('admin.divisions.index')->with([
            'success_title' => 'Pemetaan anggota berhasil disimpan.',
            'success' => count($selectedIds) . " pengguna kini terpetakan ke dalam divisi {$division->name}.",
        ]);
    }
}