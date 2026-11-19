<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Proposal;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProposalController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $tab = $request->query('tab', 'my'); // 'my', 'approvals', 'history'
        $isPenyaluranSection = ($request->query('section') === 'penyaluran');

        if ($tab === 'approvals') {
            $query = Proposal::with('user')->where('user_id', '!=', $user->id);

            if ($user->role === 'koordiv') {
                $query->where('status', 'pending_koordiv')
                      ->whereHas('user', fn($q) => $q->where('division', $user->division)->where('role', 'anggota'));
            } elseif ($user->role === 'ketua') {
                $query->where('status', 'pending_ketua');
            } elseif ($user->role === 'bendahara') {
                if ($isPenyaluranSection) {
                    // Menu Penyaluran Dana -> Antrean Pencairan:
                    // Semua yang berstatus 'pending_disbursement' ATAU data lama 'pending_bendahara' dari Anggota/Koordiv
                    $query->where(function ($q) {
                        $q->where('status', 'pending_disbursement')
                          ->orWhere(function ($sub) {
                              $sub->where('status', 'pending_bendahara')
                                  ->whereHas('user', fn($u) => $u->whereRaw('LOWER(role) != ?', ['ketua']));
                          });
                    });
                } else {
                    // Menu Persetujuan Dana -> Menunggu Validasi (khusus pengajuan dari Ketua)
                    $query->where('status', 'pending_bendahara')
                          ->whereHas('user', fn($q) => $q->whereRaw('LOWER(role) = ?', ['ketua']));
                }
            } else {
                $query->where('status', 'none');
            }

            $proposals = $query->latest()->get();
        } elseif ($tab === 'history') {
            $query = Proposal::with('user')->where('user_id', '!=', $user->id);

            if ($user->role === 'koordiv') {
                $query->whereHas('user', fn($q) => $q->where('division', $user->division)->where('role', 'anggota'))
                      ->whereIn('status', ['pending_ketua', 'pending_bendahara', 'pending_disbursement', 'approved', 'rejected']);
            } elseif ($user->role === 'ketua') {
                $query->whereIn('status', ['pending_bendahara', 'pending_disbursement', 'approved', 'rejected']);
            } elseif ($user->role === 'bendahara') {
                // Riwayat persetujuan Bendahara (pengajuan Ketua yang sudah disetujui/ditolak)
                $query->whereHas('user', fn($q) => $q->whereRaw('LOWER(role) = ?', ['ketua']))
                      ->whereIn('status', ['pending_disbursement', 'approved', 'rejected']);
            } else {
                $query->whereIn('status', ['pending_ketua', 'pending_bendahara', 'pending_disbursement', 'approved', 'rejected']);
            }

            $proposals = $query->latest('updated_at')->get();
        } else {
            $proposals = Proposal::with('user')->where('user_id', $user->id)->latest()->get();
        }

        // Hitung saldo kas real-time (mengecualikan transaksi Void)
        $allTrx = Transaction::with('category')->whereNull('voided_at')->get();
        $totalIncome = $allTrx->filter(fn($t) => ($t->category?->type ?? 'expense') === 'income')->sum('amount');
        $totalExpense = $allTrx->filter(fn($t) => ($t->category?->type ?? 'expense') === 'expense')->sum('amount');
        $currentSaldoKas = $totalIncome - $totalExpense;

        return view('proposals.index', compact('proposals', 'tab', 'currentSaldoKas'));
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

        // Validasi Bukti Pencairan Wajib jika dikirim dari modal Rincian dan Pencairan
        $isDisbursementForm = $request->boolean('is_disbursement');
        if ($isDisbursementForm) {
            $request->validate([
                'disbursement_method' => 'required|in:Transfer,Tunai',
                'proof_file' => 'required|file|mimes:jpg,jpeg,png|max:2048',
            ], [
                'proof_file.required' => 'Bukti pencairan (foto bukti transfer atau serah terima) wajib diunggah.',
                'proof_file.mimes' => 'Format bukti pencairan harus berupa PNG atau JPEG.',
                'proof_file.max' => 'Ukuran file bukti pencairan maksimal 2MB.',
            ]);
        }

        $nextStatus = $proposal->status;

        if ($proposal->status === 'pending_koordiv' && $user->role === 'koordiv') {
            // Koordiv setujui -> lanjut ke Ketua
            $nextStatus = 'pending_ketua';
        } elseif ($proposal->status === 'pending_ketua' && $user->role === 'ketua') {
            // Ketua setujui -> langsung masuk ke Antrean Pencairan Bendahara (pending_disbursement)
            $nextStatus = 'pending_disbursement';
        } elseif ($proposal->status === 'pending_bendahara' && $user->role === 'bendahara' && !$isDisbursementForm) {
            // Bendahara menyetujui pengajuan Ketua -> masuk ke Antrean Pencairan (pending_disbursement)
            $nextStatus = 'pending_disbursement';
        } elseif (in_array($proposal->status, ['pending_disbursement', 'pending_bendahara']) && $user->role === 'bendahara' && $isDisbursementForm) {
            // Bendahara mencairkan dana di menu Antrean Pencairan -> selesai (approved) & tercatat ke Buku Kas
            $nextStatus = 'approved';
        } else {
            ActivityLog::record('Percobaan menyetujui/mencairkan pengajuan dana tanpa wewenang', 'failed');
            return back()->with('error', 'Anda tidak memiliki wewenang pada tahap validasi saat ini.');
        }

        $proofPath = null;
        if ($request->hasFile('proof_file')) {
            $proofPath = $request->file('proof_file')->store('proofs', 'public');
        }

        $methodLabel = $request->input('disbursement_method');
        $noteToSave = $request->review_note;
        if ($isDisbursementForm && empty($noteToSave)) {
            $noteToSave = "Dana telah dicairkan melalui metode {$methodLabel}.";
        }

        DB::transaction(function () use ($proposal, $nextStatus, $noteToSave, $user, $proofPath, $methodLabel) {
            $proposal->update([
                'status' => $nextStatus,
                'review_note' => $noteToSave,
            ]);

            if ($nextStatus === 'approved' && !Transaction::where('proposal_id', $proposal->id)->exists()) {
                $defaultExpenseCategory = Category::where('type', 'expense')->first();

                if ($defaultExpenseCategory) {
                    $pgjCode = 'PGJ-' . $proposal->created_at->format('Y') . '-' . str_pad($proposal->id, 3, '0', STR_PAD_LEFT);
                    $methodSuffix = $methodLabel ? " [{$methodLabel}]" : '';

                    Transaction::create([
                        'user_id'          => $user->id,
                        'category_id'      => $proposal->category_id ?? $defaultExpenseCategory->id,
                        'proposal_id'      => $proposal->id,
                        'amount'           => $proposal->amount,
                        'transaction_date' => now()->toDateString(),
                        'description'      => "Pencairan Dana ({$pgjCode}){$methodSuffix}: {$proposal->title}",
                        'proof_path'       => $proofPath,
                    ]);

                    ActivityLog::record("Mencatat pencairan kas otomatis untuk pengajuan: {$proposal->title}", 'success');
                }
            }
        });

        ActivityLog::record(
            $isDisbursementForm ? 'Mencairkan dana pengajuan: ' . $proposal->title : 'Menyetujui pengajuan dana: ' . $proposal->title,
            'success'
        );

        $successTitle = $isDisbursementForm
            ? 'Dana berhasil dicairkan.'
            : 'Pengajuan dana berhasil disetujui.';

        $successMessage = $nextStatus === 'approved'
            ? 'Pencairan dana dan bukti transaksi telah otomatis tercatat sebagai Kas Keluar di Buku Kas.'
            : ($nextStatus === 'pending_disbursement'
                ? 'Pengajuan telah masuk ke daftar Antrean Pencairan Bendahara.'
                : 'Daftar menunggu validasi telah diperbarui sesuai alur persetujuan.');

        return back()->with([
            'success_title' => $successTitle,
            'success' => $successMessage,
        ]);
    }

    public function submitRealization(Request $request, Proposal $proposal)
    {
        $user = Auth::user();

        if ($proposal->user_id !== $user->id) {
            return back()->with('error', 'Anda tidak memiliki akses untuk mengunggah bukti realisasi pengajuan ini.');
        }

        if ($proposal->status !== 'approved') {
            return back()->with('error', 'Bukti realisasi hanya dapat diunggah untuk pengajuan yang sudah dicairkan.');
        }

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.amount' => 'required|numeric|min:1',
            'proof_photos' => 'required|array|min:1',
            'proof_photos.*' => 'file|mimes:jpg,jpeg,png,pdf|max:5120',
        ], [
            'items.required' => 'Minimal isi 1 rincian item penggunaan dana.',
            'proof_photos.required' => 'Unggah minimal 1 foto struk / nota bukti penggunaan dana.',
        ]);

        // Hapus foto lama jika mengunggah ulang setelah ditolak
        if (!empty($proposal->realization_proofs)) {
            foreach ($proposal->realization_proofs as $oldPath) {
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
        }

        $storedProofs = [];
        foreach ($request->file('proof_photos') as $file) {
            $storedProofs[] = $file->store('realizations', 'public');
        }

        $cleanItems = collect($request->input('items'))->map(fn($item) => [
            'description' => $item['description'],
            'amount' => (int) $item['amount'],
            'account' => 'Beban Operasional',
        ])->values()->all();

        $proposal->update([
            'realization_status' => 'pending',
            'realization_items' => $cleanItems,
            'realization_proofs' => $storedProofs,
            'realization_submitted_at' => now(),
            'realization_rejection_reason' => null,
        ]);

        ActivityLog::record('Mengunggah bukti realisasi & struk penggunaan dana: ' . $proposal->title, 'success');

        return redirect()->route('proposals.index', ['tab' => 'my'])
            ->with([
                'success_title' => 'Bukti realisasi berhasil diunggah.',
                'success' => 'Foto struk dan rincian item penggunaan dana telah dikirim ke Bendahara untuk diverifikasi.',
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