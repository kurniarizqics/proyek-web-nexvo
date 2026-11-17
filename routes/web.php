<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ProposalController;
use App\Models\Transaction;
use App\Models\Proposal;

Route::get('/', fn() => redirect()->route('login'));

// Autentikasi
Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Halaman terproteksi Login
Route::middleware('auth')->group(function () {
    
    // Dashboard dinamis berdasarkan role (Parameter opsional agar tidak error)
    Route::get('/dashboard/{role?}', function ($role = null) {
        $role = $role ?? Auth::user()->role;

        $totalIncome = Transaction::whereHas('category', fn($q) => $q->where('type', 'income'))->sum('amount');
        $totalExpense = Transaction::whereHas('category', fn($q) => $q->where('type', 'expense'))->sum('amount');
        $balance = $totalIncome - $totalExpense;
        $pendingProposals = Proposal::where('status', 'pending')->count();
        
        // Data dinamis untuk widget kanan & bawah di Dashboard
        $recentPendingProposals = Proposal::with('user')->where('status', 'pending')->latest()->take(4)->get();
        $recentTransactions = Transaction::with('category')->latest()->take(4)->get();

        return view('dashboard.index', compact(
            'role', 
            'totalIncome', 
            'totalExpense', 
            'balance', 
            'pendingProposals',
            'recentPendingProposals',
            'recentTransactions'
        ));
    })->name('dashboard');

    // CRUD Transaksi Kas
    Route::resource('transactions', TransactionController::class);

    // Pengajuan & Persetujuan Dana (Proposals)
    Route::get('/proposals', [ProposalController::class, 'index'])->name('proposals.index');
    Route::get('/proposals/create', [ProposalController::class, 'create'])->name('proposals.create');
    Route::post('/proposals', [ProposalController::class, 'store'])->name('proposals.store');
    Route::patch('/proposals/{proposal}/status', [ProposalController::class, 'updateStatus'])->name('proposals.updateStatus');
    Route::delete('/proposals/{proposal}', [ProposalController::class, 'destroy'])->name('proposals.destroy');
});