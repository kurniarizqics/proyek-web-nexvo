<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Transaction;
use App\Models\Category;
use App\Models\Proposal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $selectedPeriod = $request->query('period', 'all');

        // Ambil semua transaksi urut kronologis untuk menghitung Saldo Berjalan secara akurat
        $allTransactions = Transaction::with(['category', 'user'])
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $runningBalance = 0;
        foreach ($allTransactions as $trx) {
            $trxType = $trx->category?->type ?? 'expense';
            $trx->resolved_type = $trxType;

            if ($trxType === 'income') {
                $runningBalance += $trx->amount;
            } else {
                $runningBalance -= $trx->amount;
            }
            $trx->running_balance = $runningBalance;
        }

        // Daftar periode unik (YYYY-MM) dari data yang ada + bulan saat ini
        $availablePeriods = $allTransactions
            ->toBase()
            ->map(fn($t) => Carbon::parse($t->transaction_date)->format('Y-m'))
            ->push(now()->format('Y-m'))
            ->unique()
            ->sortDesc()
            ->values();

        // Filter berdasarkan periode jika dipilih
        $filtered = $allTransactions->filter(function ($trx) use ($selectedPeriod) {
            if ($selectedPeriod === 'all') {
                return true;
            }
            return Carbon::parse($trx->transaction_date)->format('Y-m') === $selectedPeriod;
        });

        $totalIncome = $filtered->where('resolved_type', 'income')->sum('amount');
        $totalExpense = $filtered->where('resolved_type', 'expense')->sum('amount');
        $endingBalance = $runningBalance;

        // Urutkan terbaru di atas untuk ditampilkan di tabel
        $transactions = $filtered->sortByDesc(function ($trx) {
            return $trx->transaction_date . '-' . str_pad($trx->id, 6, '0', STR_PAD_LEFT);
        })->values();

        $categories = Category::orderBy('name')->get();
        $approvedProposals = Proposal::where('status', 'approved')->latest()->get();

        return view('transactions.index', compact(
            'transactions',
            'totalIncome',
            'totalExpense',
            'endingBalance',
            'availablePeriods',
            'selectedPeriod',
            'categories',
            'approvedProposals'
        ));
    }

    public function store(Request $request)
    {
        if (Auth::user()->role !== 'bendahara') {
            ActivityLog::record('Percobaan mencatat transaksi kas tanpa wewenang', 'failed');
            return back()->with('error', 'Hanya Bendahara yang memiliki akses mencatat transaksi kas.');
        }

        $request->validate([
            'transaction_date' => 'required|date',
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:500',
            'proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $category = Category::findOrFail($request->category_id);

        $proofPath = null;
        if ($request->hasFile('proof')) {
            $proofPath = $request->file('proof')->store('proofs', 'public');
        }

        Transaction::create([
            'user_id' => Auth::id(),
            'category_id' => $category->id,
            'proposal_id' => $request->proposal_id ?: null,
            'amount' => $request->amount,
            'transaction_date' => $request->transaction_date,
            'description' => $request->description,
            'proof_path' => $proofPath,
        ]);

        $jenisLabel = $category->type === 'income' ? 'Kas Masuk' : 'Kas Keluar';
        ActivityLog::record("Mencatat transaksi {$jenisLabel}: {$request->description}", 'success');

        return redirect()->route('transactions.index')
            ->with('success', 'Transaksi kas baru berhasil dicatat ke dalam Buku Kas Organisasi.');
    }
}