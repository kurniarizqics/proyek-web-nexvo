<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\DivisionController as AdminDivisionController;
use App\Models\Proposal;
use App\Models\Transaction;

Route::get('/', function () {
    return redirect()->route('login');
});

// Rute Autentikasi
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.process');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// Rute Terproteksi Login
Route::middleware('auth')->group(function () {
    // Dashboard per Role
    Route::get('/dashboard/{role?}', function (?string $role = null) {
        $user = Auth::user();
        $targetRole = $role ?? $user->role;

        if ($user->role !== $targetRole) {
            return redirect()->route('dashboard', $user->role);
        }

        // Khusus Administrator -> gunakan view dashboard/admin.blade.php
        if ($targetRole === 'admin') {
            return view('dashboard.admin');
        }

        // Hitung data keuangan real-time untuk dashboard operasional (Ketua, Bendahara, Koordiv, Anggota)
        $allTransactions = Transaction::with('category')->whereNull('voided_at')->get();
        $totalIncome = $allTransactions->filter(fn($t) => ($t->category?->type ?? 'expense') === 'income')->sum('amount');
        $totalExpense = $allTransactions->filter(fn($t) => ($t->category?->type ?? 'expense') === 'expense')->sum('amount');
        $balance = $totalIncome - $totalExpense;

        // Hitung pengajuan pending sesuai wewenang role yang login
        $pendingQuery = Proposal::with('user')->where('user_id', '!=', $user->id);

        if ($user->role === 'koordiv') {
            $pendingQuery->where('status', 'pending_koordiv')
                ->whereHas('user', fn($q) => $q->where('division', $user->division)->where('role', 'anggota'));
        } elseif ($user->role === 'ketua') {
            $pendingQuery->where('status', 'pending_ketua');
        } elseif ($user->role === 'bendahara') {
            $pendingQuery->whereIn('status', ['pending_bendahara', 'pending_disbursement']);
        } else {
            // Untuk Anggota: tampilkan pengajuannya sendiri yang masih menunggu persetujuan
            $pendingQuery = Proposal::with('user')
                ->where('user_id', $user->id)
                ->whereIn('status', ['pending_koordiv', 'pending_ketua', 'pending_bendahara', 'pending_disbursement']);
        }

        $pendingProposals = (clone $pendingQuery)->count();
        $recentPendingProposals = $pendingQuery->latest()->take(4)->get();

        $recentTransactions = Transaction::with('category')
            ->whereNull('voided_at')
            ->latest('transaction_date')
            ->latest('id')
            ->take(5)
            ->get();

        // Gunakan view spesifik role jika file ada dan tidak kosong, jika kosong fallback ke dashboard.index
        $specificViewPath = resource_path('views/dashboard/' . $targetRole . '.blade.php');
        $viewName = (file_exists($specificViewPath) && filesize($specificViewPath) > 0)
            ? 'dashboard.' . $targetRole
            : 'dashboard.index';

        return view($viewName, compact(
            'balance',
            'totalIncome',
            'totalExpense',
            'pendingProposals',
            'recentPendingProposals',
            'recentTransactions'
        ));
    })->name('dashboard');

    // Pengajuan & Persetujuan Dana (Anggota, Koordiv, Ketua, Bendahara)
    Route::get('/proposals', [ProposalController::class, 'index'])->name('proposals.index');
    Route::get('/proposals/create', [ProposalController::class, 'create'])->name('proposals.create');
    Route::post('/proposals', [ProposalController::class, 'store'])->name('proposals.store');
    Route::patch('/proposals/{proposal}/status', [ProposalController::class, 'updateStatus'])->name('proposals.updateStatus');
    Route::post('/proposals/{proposal}/realization', [ProposalController::class, 'submitRealization'])->name('proposals.submitRealization');
    Route::delete('/proposals/{proposal}', [ProposalController::class, 'destroy'])->name('proposals.destroy');

    // Transaksi & Bukti Kas
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::put('/transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
    Route::patch('/transactions/{transaction}/void', [TransactionController::class, 'void'])->name('transactions.void');
    Route::patch('/transactions/{proposal}/verify-realization', [TransactionController::class, 'verifyRealization'])->name('transactions.verifyRealization');

    // Laporan Keuangan
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/preview-pdf', [ReportController::class, 'previewPdf'])->name('reports.previewPdf');
    Route::get('/reports/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.exportPdf');
    Route::get('/reports/export/excel', [ReportController::class, 'exportExcel'])->name('reports.exportExcel');

    // Audit Log
    Route::get('/audit-logs', [ActivityLogController::class, 'index'])->name('audit-logs.index');

    // Profil Saya
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.updatePassword');

    // =========================================================================
    // RUTE KHUSUS ADMINISTRATOR (DATA MASTER PENGGUNA & DIVISI)
    // =========================================================================
    Route::prefix('admin')->name('admin.')->group(function () {
        // Manajemen Pengguna
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::patch('/users/{user}/toggle-status', [AdminUserController::class, 'toggleStatus'])->name('users.toggleStatus');
        Route::patch('/users/{user}/reset-password', [AdminUserController::class, 'resetPassword'])->name('users.resetPassword');

        // Manajemen Divisi
        Route::get('/divisions', [AdminDivisionController::class, 'index'])->name('divisions.index');
        Route::post('/divisions', [AdminDivisionController::class, 'store'])->name('divisions.store');
        Route::put('/divisions/{division}', [AdminDivisionController::class, 'update'])->name('divisions.update');
        Route::patch('/divisions/{division}/toggle-status', [AdminDivisionController::class, 'toggleStatus'])->name('divisions.toggleStatus');
        Route::patch('/divisions/{division}/assign-members', [AdminDivisionController::class, 'assignMembers'])->name('divisions.assignMembers');
    });
}); 