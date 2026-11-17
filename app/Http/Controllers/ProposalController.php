<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Proposal;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProposalController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $tab = $request->query('tab', 'my'); // 'my', 'approvals', 'history'

        if ($tab === 'approvals') {
            // Menampilkan HANYA pengajuan orang lain yang sedang menunggu giliran validasi role ini
            $query = Proposal::with('user')->where('user_id', '!=', $user->id);

            if ($user->role === 'koordiv') {
                $query->where('status', 'pending_koordiv')
                      ->whereHas('user', fn($q) => $q->where('division', $user->division)->where('role', 'anggota'));
            } elseif ($user->role === 'ketua') {
                $query->where('status', 'pending_ketua');
            } elseif ($user->role === 'bendahara') {
                $query->where('status', 'pending_bendahara');
            } else {
                $query->where('status', 'none');
            }

            $proposals = $query->latest()->get();
        } elseif ($tab === 'history') {
            // Riwayat Persetujuan: hanya pengajuan yang sudah pernah divalidasi oleh role terkait
            $query = Proposal::with('user')->where('user_id', '!=', $user->id);

            if ($user->role === 'koordiv') {
                $query->whereHas('user', fn($q) => $q->where('division', $user->division)->where('role', 'anggota'))
                      ->whereIn('status', ['pending_ketua', 'pending_bendahara', 'approved', 'rejected']);
            } elseif ($user->role === 'ketua') {
                $query->whereIn('status', ['pending_bendahara', 'approved', 'rejected']);
            } elseif ($user->role === 'bendahara') {
                $query->whereIn('status', ['approved', 'rejected']);
            } else {
                $query->whereIn('status', ['pending_ketua', 'pending_bendahara', 'approved', 'rejected']);
            }

            $proposals = $query->latest('updated_at')->get();
        } else {
            // Riwayat Pengajuanku: HANYA milik user yang sedang login
            $proposals = Proposal::with('user')->where('user_id', $user->id)->latest()->get();
        }

        return view('proposals.index', compact('proposals', 'tab'));
    }

    public function create()
    {
        $categories = Category::where('type', 'expense')->get();
        return view('proposals.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:1',
            'reason' => 'required|string',
            'document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $documentPath = null;
        if ($request->hasFile('document')) {
            $documentPath = $request->file('document')->store('proposals', 'public');
        }

        $role = Auth::user()->role;
        $initialStatus = match ($role) {
            'anggota' => 'pending_koordiv',
            'koordiv', 'bendahara' => 'pending_ketua',
            'ketua' => 'pending_bendahara',
            default => 'pending_ketua',
        };

        $proposal = Proposal::create([
            'user_id' => Auth::id(),
            'title' => $request->title,
            'amount' => $request->amount,
            'reason' => $request->reason,
            'document_path' => $documentPath,
            'status' => $initialStatus,
        ]);

        ActivityLog::record('Membuat pengajuan dana: ' . $proposal->title, 'success');

        return redirect()->route('proposals.index', ['tab' => 'my'])
            ->with('success', 'Pengajuan anggaran berhasil dikirim dan masuk ke jalur persetujuan.');
    }

    public function updateStatus(Request $request, Proposal $proposal)
    {
        $user = Auth::user();

        if ($proposal->user_id === $user->id) {
            ActivityLog::record('Percobaan validasi pengajuan milik sendiri', 'failed');
            return back()->with('error', 'Anda tidak dapat memvalidasi pengajuan Anda sendiri.');
        }

        $action = $request->input('action'); // 'approve' atau 'reject'

        if ($action === 'reject') {
            $request->validate([
                'rejection_reason' => 'required|string|max:1000',
            ]);

            $proposal->update([
                'status' => 'rejected',
                'rejection_reason' => $request->rejection_reason,
                'review_note' => $request->review_note,
            ]);

            ActivityLog::record('Menolak pengajuan dana: ' . $proposal->title, 'success');

            return back()->with([
                'success_title' => 'Pengajuan dana telah ditolak.',
                'success' => 'Daftar menunggu validasi telah diperbarui dan catatan penolakan telah dikirim ke pemohon.',
            ]);
        }

        // Logika transisi berjenjang saat di-approve
        $nextStatus = $proposal->status;

        if ($proposal->status === 'pending_koordiv' && $user->role === 'koordiv') {
            $nextStatus = 'pending_ketua';
        } elseif ($proposal->status === 'pending_ketua' && $user->role === 'ketua') {
            $nextStatus = ($proposal->user->role === 'bendahara') ? 'approved' : 'pending_bendahara';
        } elseif ($proposal->status === 'pending_bendahara' && $user->role === 'bendahara') {
            $nextStatus = 'approved';
        } else {
            ActivityLog::record('Percobaan menyetujui pengajuan dana tanpa wewenang', 'failed');
            return back()->with('error', 'Anda tidak memiliki wewenang pada tahap validasi saat ini.');
        }

        $proposal->update([
            'status' => $nextStatus,
            'review_note' => $request->review_note,
        ]);

        ActivityLog::record('Menyetujui pengajuan dana: ' . $proposal->title, 'success');

        return back()->with([
            'success_title' => 'Pengajuan dana berhasil disetujui.',
            'success' => 'Daftar menunggu validasi telah diperbarui sesuai alur persetujuan.',
        ]);
    }

    public function destroy(Proposal $proposal)
    {
        $user = Auth::user();

        if ($proposal->user_id !== $user->id) {
            ActivityLog::record('Percobaan membatalkan pengajuan milik pengguna lain', 'failed');
            return back()->with('error', 'Anda tidak memiliki akses untuk membatalkan pengajuan ini.');
        }

        if (!str_starts_with($proposal->status, 'pending')) {
            return back()->with('error', 'Pengajuan yang sudah diproses tidak dapat dibatalkan.');
        }

        $title = $proposal->title;

        if (!empty($proposal->document_path) && Storage::disk('public')->exists($proposal->document_path)) {
            Storage::disk('public')->delete($proposal->document_path);
        }

        $proposal->delete();

        ActivityLog::record('Membatalkan pengajuan dana: ' . $title, 'success');

        return redirect()->route('proposals.index', ['tab' => 'my'])
            ->with([
                'success_title' => 'Pengajuan dana berhasil dibatalkan.',
                'success' => 'Permohonan anggaran Anda telah dihapus dari daftar pengajuan.',
            ]);
    }
}