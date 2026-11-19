<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Transaction;
use App\Models\Category;
use App\Models\Proposal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $selectedPeriod = $request->query('period', 'all');

        $allTransactions = Transaction::with(['category', 'user', 'proposal'])
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $runningBalance = 0;
        foreach ($allTransactions as $trx) {
            $trxType = $trx->category?->type ?? 'expense';
            $trx->resolved_type = $trxType;
            $trx->is_void = !is_null($trx->voided_at);

            // Transaksi berstatus Void tidak mengubah saldo berjalan
            if (!$trx->is_void) {
                if ($trxType === 'income') {
                    $runningBalance += $trx->amount;
                } else {
                    $runningBalance -= $trx->amount;
                }
            }
            $trx->running_balance = $runningBalance;
        }

        $availablePeriods = $allTransactions
            ->toBase()
            ->map(fn($t) => Carbon::parse($t->transaction_date)->format('Y-m'))
            ->push(now()->format('Y-m'))
            ->unique()
            ->sortDesc()
            ->values();

        $filtered = $allTransactions->filter(function ($trx) use ($selectedPeriod) {
            if ($selectedPeriod === 'all') {
                return true;
            }
            return Carbon::parse($trx->transaction_date)->format('Y-m') === $selectedPeriod;
        });

        // Total Kas Masuk & Kas Keluar hanya menghitung transaksi aktif (bukan Void)
        $activeFiltered = $filtered->where('is_void', false);
        $totalIncome = $activeFiltered->where('resolved_type', 'income')->sum('amount');
        $totalExpense = $activeFiltered->where('resolved_type', 'expense')->sum('amount');
        $endingBalance = $runningBalance;

        $transactions = $filtered->sortByDesc(function ($trx) {
            return $trx->transaction_date . '-' . str_pad($trx->id, 6, '0', STR_PAD_LEFT);
        })->values();

        $categories = Category::orderBy('name')->get();
        $approvedProposals = Proposal::where('status', 'approved')->latest()->get();

        // Data khusus Halaman Validasi Bukti (?section=penyaluran)
        $pendingProofProposals = Proposal::with(['user', 'transaction'])
            ->where('status', 'approved')
            ->where('realization_status', 'pending')
            ->latest('realization_submitted_at')
            ->get();

        $unsubmittedProofProposals = Proposal::with(['user', 'transaction'])
            ->where('status', 'approved')
            ->whereIn('realization_status', ['unsubmitted', 'rejected'])
            ->latest('updated_at')
            ->get();

        return view('transactions.index', compact(
            'transactions',
            'totalIncome',
            'totalExpense',
            'endingBalance',
            'availablePeriods',
            'selectedPeriod',
            'categories',
            'approvedProposals',
            'pendingProofProposals',
            'unsubmittedProofProposals'
        ));
    }

    public function validateProof(Request $request, Proposal $proposal)
    {
        if (Auth::user()->role !== 'bendahara') {
            return back()->with('error', 'Hanya Bendahara yang berwenang memvalidasi bukti realisasi.');
        }

        $decision = $request->input('decision'); // 'approve' atau 'reject'

        if ($decision === 'reject') {
            $request->validate([
                'rejection_reason' => 'required|string|max:1000',
            ], [
                'rejection_reason.required' => 'Alasan penolakan wajib diisi jika menolak bukti realisasi.',
            ]);

            $proposal->update([
                'realization_status' => 'rejected',
                'realization_rejection_reason' => $request->rejection_reason,
            ]);

            ActivityLog::record("Menolak bukti realisasi pengajuan: {$proposal->title}", 'success');

            return redirect()->route('transactions.index', ['section' => 'penyaluran'])
                ->with('success', 'Bukti realisasi ditolak dan pengaju diminta mengunggah ulang bukti yang sesuai.');
        }

        // Setujui dan Catat Akun Beban per item
        $items = $proposal->realization_items ?? [];
        $accounts = $request->input('item_accounts', []);

        foreach ($items as $idx => $item) {
            if (isset($accounts[$idx])) {
                $items[$idx]['account'] = $accounts[$idx];
            }
        }

        $proposal->update([
            'realization_status' => 'approved',
            'realization_items' => $items,
            'realization_rejection_reason' => null,
        ]);

        // Perbarui transaksi kas terkait dengan lampiran struk realisasi pertama jika tersedia
        if ($proposal->transaction && !empty($proposal->realization_proofs)) {
            $proposal->transaction->update([
                'proof_path' => $proposal->realization_proofs[0],
            ]);
        }

        ActivityLog::record("Memverifikasi bukti realisasi & mencatat beban untuk: {$proposal->title}", 'success');

        return redirect()->route('transactions.index', ['section' => 'penyaluran'])
            ->with('success', 'Bukti realisasi berhasil diverifikasi dan beban telah dicatat.');
    }

    public function store(Request $request)
    {
        if (Auth::user()->role !== 'bendahara') {
            ActivityLog::record('Percobaan mencatat transaksi kas tanpa wewenang', 'failed');
            return back()->with('error', 'Hanya Bendahara yang memiliki akses mencatat transaksi kas.');
        }

        $request->validate([
            'transaction_date' => 'required|date',
            'category_id'      => 'required|exists:categories,id',
            'proposal_id'      => 'nullable|exists:proposals,id',
            'amount'           => 'required|numeric|min:1',
            'description'      => 'required|string|max:500',
            'proof'            => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $category = Category::findOrFail($request->category_id);

        $proofPath = null;
        if ($request->hasFile('proof')) {
            $proofPath = $request->file('proof')->store('proofs', 'public');
        }

        Transaction::create([
            'user_id'          => Auth::id(),
            'category_id'      => $category->id,
            'proposal_id'      => $request->proposal_id ?: null,
            'amount'           => $request->amount,
            'transaction_date' => $request->transaction_date,
            'description'      => $request->description,
            'proof_path'       => $proofPath,
        ]);

        $jenisLabel = $category->type === 'income' ? 'Kas Masuk' : 'Kas Keluar';
        ActivityLog::record("Mencatat transaksi {$jenisLabel}: {$request->description}", 'success');

        return redirect()->route('transactions.index')
            ->with('success', 'Transaksi kas baru berhasil dicatat ke dalam Buku Kas Organisasi.');
    }

    public function update(Request $request, Transaction $transaction)
    {
        if (Auth::user()->role !== 'bendahara') {
            ActivityLog::record('Percobaan mengubah transaksi kas tanpa wewenang', 'failed');
            return back()->with('error', 'Hanya Bendahara yang memiliki akses mengubah transaksi kas.');
        }

        if (!is_null($transaction->voided_at)) {
            return back()->with('error', 'Transaksi yang sudah di-Void tidak dapat diubah.');
        }

        $request->validate([
            'transaction_date' => 'required|date',
            'category_id'      => 'required|exists:categories,id',
            'proposal_id'      => 'nullable|exists:proposals,id',
            'amount'           => 'required|numeric|min:1',
            'description'      => 'required|string|max:500',
            'proof'            => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        if ($request->hasFile('proof')) {
            if ($transaction->proof_path && Storage::disk('public')->exists($transaction->proof_path)) {
                Storage::disk('public')->delete($transaction->proof_path);
            }
            $transaction->proof_path = $request->file('proof')->store('proofs', 'public');
        }

        $transaction->update([
            'category_id'      => $request->category_id,
            'proposal_id'      => $request->proposal_id ?: null,
            'amount'           => $request->amount,
            'transaction_date' => $request->transaction_date,
            'description'      => $request->description,
            'proof_path'       => $transaction->proof_path,
        ]);

        ActivityLog::record("Memperbarui transaksi kas: {$transaction->description}", 'success');

        return redirect()->route('transactions.index')
            ->with('success', 'Data transaksi kas berhasil diperbarui.');
    }

    public function void(Request $request, Transaction $transaction)
    {
        if (Auth::user()->role !== 'bendahara') {
            ActivityLog::record('Percobaan melakukan void transaksi kas tanpa wewenang', 'failed');
            return back()->with('error', 'Hanya Bendahara yang memiliki akses melakukan Void transaksi kas.');
        }

        if (!is_null($transaction->voided_at)) {
            return back()->with('error', 'Transaksi ini sudah berstatus Void.');
        }

        $request->validate([
            'void_reason' => 'required|string|max:500',
        ], [
            'void_reason.required' => 'Alasan pembatalan (Void) wajib diisi untuk keperluan jejak audit.',
        ]);

        $transaction->update([
            'voided_at'   => now(),
            'void_reason' => $request->void_reason,
        ]);

        ActivityLog::record("Melakukan Void transaksi kas ({$transaction->description}) - Alasan: {$request->void_reason}", 'success');

        return redirect()->route('transactions.index')
            ->with('success', 'Transaksi berhasil di-Void. Saldo kas dan rekapitulasi telah disesuaikan otomatis.');
    }

    public function destroy(Transaction $transaction)
    {
        if (Auth::user()->role !== 'bendahara') {
            ActivityLog::record('Percobaan menghapus transaksi kas tanpa wewenang', 'failed');
            return back()->with('error', 'Hanya Bendahara yang memiliki akses menghapus transaksi kas.');
        }

        if ($transaction->proof_path && Storage::disk('public')->exists($transaction->proof_path)) {
            Storage::disk('public')->delete($transaction->proof_path);
        }

        $desc = $transaction->description;
        $transaction->delete();

        ActivityLog::record("Menghapus transaksi kas: {$desc}", 'success');

        return redirect()->route('transactions.index')
            ->with('success', 'Transaksi kas berhasil dihapus dari Buku Kas.');
    }
}