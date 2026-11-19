<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\DivisionController as AdminDivisionController;

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
    // Dashboard per Role (Mendukung route('dashboard') dengan atau tanpa parameter role)
    Route::get('/dashboard/{role?}', function (?string $role = null) {
        $user = Auth::user();
        $targetRole = $role ?? $user->role;

        if ($user->role !== $targetRole) {
            return redirect()->route('dashboard', $user->role);
        }

        if (!view()->exists('dashboard.' . $targetRole)) {
            abort(404);
        }

        return view('dashboard.' . $targetRole);
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
    Route::patch('/transactions/{transaction}/void', [TransactionController::class, 'void'])->name('transactions.void');
    Route::patch('/transactions/{proposal}/verify-realization', [TransactionController::class, 'verifyRealization'])->name('transactions.verifyRealization');

    // Laporan Keuangan
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    // Audit Log
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

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