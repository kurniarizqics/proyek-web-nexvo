<?php

use Illuminate\Http\Request;
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
use App\Models\ActivityLog;
use App\Models\Proposal;
use App\Models\Transaction;
use Carbon\Carbon;

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
// =========================================================================
    // 1. DASHBOARD PER ROLE (TRANSPARANSI SALDO KAS UNTUK SEMUA AKTOR)
    // =========================================================================
    Route::get('/dashboard/{role?}', function (Request $request, ?string $role = null) {
        $user = Auth::user();
        $targetRole = $role ?? $user->role;

        if ($user->role !== $targetRole) {
            return redirect()->route('dashboard', $user->role);
        }

        // Khusus Administrator -> dashboard/admin.blade.php
        if ($targetRole === 'admin') {
            return view('dashboard.admin');
        }

        $selectedMonth = $request->query('month', now()->format('Y-m'));
        try {
            $periodDate = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        } catch (\Exception $e) {
            $selectedMonth = now()->format('Y-m');
            $periodDate = now()->startOfMonth();
        }

        // A. TRANSPARANSI SALDO KAS (Dihitung untuk semua peran: Anggota, Koordiv, Bendahara, Ketua)
        $allTransactions = Transaction::with('category')->whereNull('voided_at')->get();
        $allIncome = $allTransactions->filter(fn($t) => ($t->category?->type ?? 'expense') === 'income')->sum('amount');
        $allExpense = $allTransactions->filter(fn($t) => ($t->category?->type ?? 'expense') === 'expense')->sum('amount');
        $balance = $allIncome - $allExpense;

        $monthTransactions = Transaction::with('category')
            ->whereNull('voided_at')
            ->whereYear('transaction_date', $periodDate->year)
            ->whereMonth('transaction_date', $periodDate->month)
            ->get();

        $totalIncome = $monthTransactions->filter(fn($t) => ($t->category?->type ?? 'expense') === 'income')->sum('amount');
        $totalExpense = $monthTransactions->filter(fn($t) => ($t->category?->type ?? 'expense') === 'expense')->sum('amount');

        $yearlyTransactions = Transaction::with('category')
            ->whereNull('voided_at')
            ->whereYear('transaction_date', $periodDate->year)
            ->get();

        $monthlyIncomeChart = array_fill(0, 12, 0);
        $monthlyExpenseChart = array_fill(0, 12, 0);
        for ($m = 1; $m <= 12; $m++) {
            $trxInMonth = $yearlyTransactions->filter(fn($t) => Carbon::parse($t->transaction_date)->month === $m);
            $inc = $trxInMonth->filter(fn($t) => ($t->category?->type ?? 'expense') === 'income')->sum('amount');
            $exp = $trxInMonth->filter(fn($t) => ($t->category?->type ?? 'expense') === 'expense')->sum('amount');
            $monthlyIncomeChart[$m - 1] = round($inc / 1000000, 2);
            $monthlyExpenseChart[$m - 1] = round($exp / 1000000, 2);
        }

        $recentTransactions = in_array($user->role, ['ketua', 'bendahara'])
            ? Transaction::with('category')->whereNull('voided_at')->latest('transaction_date')->latest('id')->take(5)->get()
            : collect();

        // B. ANTREAN & STATISTIK PENGAJUAN SESUAI ROLE
        $personalStats = [
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
            'total_amount' => 0,
        ];

        if ($user->role === 'anggota') {
            $myProposals = Proposal::where('user_id', $user->id)->get();
            $personalStats = [
                'pending' => $myProposals->whereIn('status', ['pending_koordiv', 'pending_ketua', 'pending_bendahara'])->count(),
                'approved' => $myProposals->whereIn('status', ['approved', 'pending_disbursement', 'disbursed', 'waiting_verification', 'completed'])->count(),
                'rejected' => $myProposals->where('status', 'rejected')->count(),
                'total_amount' => $myProposals->whereIn('status', ['approved', 'pending_disbursement', 'disbursed', 'waiting_verification', 'completed'])->sum('amount'),
            ];

            $pendingQuery = Proposal::with('user')
                ->where('user_id', $user->id)
                ->latest();
        } elseif ($user->role === 'koordiv') {
            $divProposals = Proposal::whereHas('user', fn($q) => $q->where('division', $user->division))->get();
            $personalStats = [
                'pending' => $divProposals->where('status', 'pending_koordiv')->count(),
                'approved' => $divProposals->whereIn('status', ['pending_ketua', 'approved', 'pending_disbursement', 'disbursed', 'waiting_verification', 'completed'])->count(),
                'rejected' => $divProposals->where('status', 'rejected')->count(),
                'total_amount' => $divProposals->whereIn('status', ['approved', 'pending_disbursement', 'disbursed', 'waiting_verification', 'completed'])->sum('amount'),
            ];

            $pendingQuery = Proposal::with('user')
                ->where('user_id', '!=', $user->id)
                ->where('status', 'pending_koordiv')
                ->whereHas('user', fn($q) => $q->where('division', $user->division)->where('role', 'anggota'));
        } elseif ($user->role === 'ketua') {
            $pendingQuery = Proposal::with('user')
                ->where('user_id', '!=', $user->id)
                ->where('status', 'pending_ketua');
        } else {
            // Bendahara
            $pendingQuery = Proposal::with('user')
                ->where('user_id', '!=', $user->id)
                ->whereIn('status', ['pending_bendahara', 'approved', 'pending_disbursement', 'waiting_verification']);
        }

        $pendingProposals = (clone $pendingQuery)->count();
        $recentPendingProposals = $pendingQuery->latest()->take(5)->get();

        $latestLogs = in_array($user->role, ['ketua', 'bendahara'])
            ? ActivityLog::latest()->take(4)->get()
            : collect();

        $monthOptions = [];
        for ($i = 0; $i < 6; $i++) {
            $d = now()->subMonths($i);
            $monthOptions[$d->format('Y-m')] = $d->locale('id')->translatedFormat('F Y');
        }

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
            'recentTransactions',
            'latestLogs',
            'personalStats',
            'selectedMonth',
            'periodDate',
            'monthOptions',
            'monthlyIncomeChart',
            'monthlyExpenseChart'
        ));
    })->name('dashboard');

    // =========================================================================
    // 2. PENGAJUAN & PERSETUJUAN DANA (Anggota, Koordiv, Ketua, Bendahara)
    // =========================================================================
    Route::get('/proposals', [ProposalController::class, 'index'])->name('proposals.index');
    Route::get('/proposals/create', [ProposalController::class, 'create'])->name('proposals.create');
    Route::post('/proposals', [ProposalController::class, 'store'])->name('proposals.store');
    Route::patch('/proposals/{proposal}/status', [ProposalController::class, 'updateStatus'])->name('proposals.updateStatus');
    Route::post('/proposals/{proposal}/realization', [ProposalController::class, 'submitRealization'])->name('proposals.submitRealization');
    Route::delete('/proposals/{proposal}', [ProposalController::class, 'destroy'])->name('proposals.destroy');

    // =========================================================================
    // 3. BUKU KAS & TRANSAKSI (Read: Ketua & Bendahara | Write/CRUD: Khusus Bendahara)
    // =========================================================================
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::put('/transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
    Route::patch('/transactions/{transaction}/void', [TransactionController::class, 'void'])->name('transactions.void');
    Route::patch('/transactions/{proposal}/verify-realization', [TransactionController::class, 'verifyRealization'])->name('transactions.verifyRealization');
    Route::post('/transactions/{proposal}/validate-proof', [TransactionController::class, 'validateProof'])->name('transactions.validateProof');

    // =========================================================================
    // 4. LAPORAN KEUANGAN (Khusus Bendahara & Ketua)
    // =========================================================================
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/preview-pdf', [ReportController::class, 'previewPdf'])->name('reports.previewPdf');
    Route::get('/reports/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.exportPdf');
    Route::get('/reports/export/excel', [ReportController::class, 'exportExcel'])->name('reports.exportExcel');

    // =========================================================================
    // 5. AUDIT LOG (Khusus Ketua, Bendahara, & Admin)
    // =========================================================================
    Route::get('/audit-logs', [ActivityLogController::class, 'index'])->name('audit-logs.index');

    // =========================================================================
    // 6. PROFIL SAYA (Semua Role)
    // =========================================================================
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.updatePassword');

    // =========================================================================
    // 7. RUTE KHUSUS ADMINISTRATOR (DATA MASTER PENGGUNA & DIVISI)
    // =========================================================================
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::patch('/users/{user}/toggle-status', [AdminUserController::class, 'toggleStatus'])->name('users.toggleStatus');
        Route::patch('/users/{user}/reset-password', [AdminUserController::class, 'resetPassword'])->name('users.resetPassword');

        Route::get('/divisions', [AdminDivisionController::class, 'index'])->name('divisions.index');
        Route::post('/divisions', [AdminDivisionController::class, 'store'])->name('divisions.store');
        Route::put('/divisions/{division}', [AdminDivisionController::class, 'update'])->name('divisions.update');
        Route::patch('/divisions/{division}/toggle-status', [AdminDivisionController::class, 'toggleStatus'])->name('divisions.toggleStatus');
        Route::patch('/divisions/{division}/assign-members', [AdminDivisionController::class, 'assignMembers'])->name('divisions.assignMembers');
    });
});