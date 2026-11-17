<?php

namespace App\Http\Controllers;

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
            if ($trx->type === 'income') {
                $runningBalance += $trx->amount;
            } else {
                $runningBalance -= $trx->amount;
            }
            $trx->running_balance = $runningBalance;
        }

        // Daftar periode unik (YYYY-MM) dari data yang ada + bulan saat ini
        $availablePeriods = $allTransactions
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

        $totalIncome = $filtered->where('type', 'income')->sum('amount');
        $totalExpense = $filtered->where('type', 'expense')->sum('amount');
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
            return back()->with('error', 'Hanya Bendahara yang memiliki akses mencatat transaksi kas.');
        }

        $request->validate([
            'transaction_date' => 'required|date',
            'type' => 'required|in:income,expense',
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:500',
            'proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $proofPath = null;
        if ($request->hasFile('proof')) {
            $proofPath = $request->file('proof')->store('proofs', 'public');
        }

        Transaction::create([
            'user_id' => Auth::id(),
            'category_id' => $request->category_id,
            'proposal_id' => $request->proposal_id ?: null,
            'type' => $request->type,
            'amount' => $request->amount,
            'transaction_date' => $request->transaction_date,
            'description' => $request->description,
            'proof_path' => $proofPath,
        ]);

        return redirect()->route('transactions.index')
            ->with('success', 'Transaksi kas baru berhasil dicatat ke dalam Buku Kas Organisasi.');
    }
}