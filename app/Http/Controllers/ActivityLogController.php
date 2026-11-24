<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        if (!in_array(Auth::user()->role, ['admin', 'ketua', 'bendahara'])) {
            ActivityLog::record('Percobaan mengakses Audit Log tanpa wewenang', 'failed');
            abort(403, 'Akses ditolak. Audit Log hanya dapat diakses oleh Administrator, Ketua, dan Bendahara.');
        }

        $selectedPeriod = $request->query('period', 'all');

        $allLogs = ActivityLog::latest()->get();

        // Gunakan toBase() agar menjadi Support Collection standar sebelum map/unique
        $availablePeriods = $allLogs
            ->toBase()
            ->map(fn($log) => $log->created_at->format('Y-m'))
            ->push(now()->format('Y-m'))
            ->unique()
            ->sortDesc()
            ->values();

        $logs = $allLogs->filter(function ($log) use ($selectedPeriod) {
            if ($selectedPeriod === 'all') {
                return true;
            }
            return $log->created_at->format('Y-m') === $selectedPeriod;
        })->values();

        $totalActivities = $logs->count();
        $activeUsersCount = $logs->pluck('user_name')->unique()->count();
        $failedActivities = $logs->where('status', 'failed')->count();

        return view('audit_logs.index', compact(
            'logs',
            'availablePeriods',
            'selectedPeriod',
            'totalActivities',
            'activeUsersCount',
            'failedActivities'
        ));
    }
}