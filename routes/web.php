<?php

use Illuminate\Support\Facades\Route;
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
    
    // Dashboard dinamis berdasarkan role
    Route::get('/dashboard/{role}', function ($role) {
        $totalIncome = Transaction::whereHas('category', fn($q) => $q->where('type', 'income'))->sum('amount');
        $totalExpense = Transaction::whereHas('category', fn($q) => $q->where('type', 'expense'))->sum('amount');
        $balance = $totalIncome - $totalExpense;
        $pendingProposals = Proposal::where('status', 'pending')->count();

        return view('dashboard.index', compact('role', 'totalIncome', 'totalExpense', 'balance', 'pendingProposals'));
    })->name('dashboard');

    // CRUD Transaksi Kas
    Route::resource('transactions', TransactionController::class);

    // Pengajuan & Persetujuan Dana (Proposals)
    Route::get('/proposals', [ProposalController::class, 'index'])->name('proposals.index');
    Route::get('/proposals/create', [ProposalController::class, 'create'])->name('proposals.create');
    Route::post('/proposals', [ProposalController::class, 'store'])->name('proposals.store');
    Route::patch('/proposals/{proposal}/status', [ProposalController::class, 'updateStatus'])->name('proposals.updateStatus');
});