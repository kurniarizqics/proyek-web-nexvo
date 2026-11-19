<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    private function getReportData(Request $request)
    {
        // Hanya ambil transaksi aktif (bukan yang sudah di-Void)
        $allTransactions = Transaction::with(['category', 'user'])
            ->whereNull('voided_at')
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // Tentukan tanggal default: awal & akhir bulan dari transaksi terbaru (atau bulan ini)
        $latestDate = $allTransactions->isNotEmpty()
            ? Carbon::parse($allTransactions->last()->transaction_date)
            : now();

        $startDate = $request->query('start_date', $latestDate->copy()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->query('end_date', $latestDate->copy()->endOfMonth()->format('Y-m-d'));
        $typeFilter = $request->query('type', 'all'); // all | income | expense

        // Hitung saldo berjalan kronologis untuk seluruh transaksi aktif
        $runningBalance = 0;
        $openingBalance = 0;

        foreach ($allTransactions as $trx) {
            $trxDateStr = Carbon::parse($trx->transaction_date)->format('Y-m-d');
            $trxType = $trx->category?->type ?? 'expense';
            $trx->resolved_type = $trxType;

            // Akumulasi saldo sebelum startDate sebagai Saldo Awal periode
            if ($trxDateStr < $startDate) {
                $openingBalance += ($trxType === 'income') ? $trx->amount : -$trx->amount;
            }

            $runningBalance += ($trxType === 'income') ? $trx->amount : -$trx->amount;
            $trx->running_balance = $runningBalance;
        }

        // Filter transaksi dalam rentang tanggal & kategori jenis kas
        $transactions = $allTransactions->filter(function ($trx) use ($startDate, $endDate, $typeFilter) {
            $trxDateStr = Carbon::parse($trx->transaction_date)->format('Y-m-d');
            $inRange = ($trxDateStr >= $startDate && $trxDateStr <= $endDate);
            $matchType = ($typeFilter === 'all') || ($trx->resolved_type === $typeFilter);
            return $inRange && $matchType;
        })->values();

        $totalIncome = $transactions->where('resolved_type', 'income')->sum('amount');
        $totalExpense = $transactions->where('resolved_type', 'expense')->sum('amount');
        $endingBalance = $transactions->isNotEmpty()
            ? $transactions->last()->running_balance
            : $openingBalance;

        return compact(
            'transactions',
            'startDate',
            'endDate',
            'typeFilter',
            'openingBalance',
            'totalIncome',
            'totalExpense',
            'endingBalance'
        );
    }

    public function index(Request $request)
    {
        $data = $this->getReportData($request);
        return view('reports.index', $data);
    }

    public function previewPdf(Request $request)
    {
        $data = $this->getReportData($request);
        $data['bendaharaName'] = User::where('role', 'bendahara')->value('name') ?? 'Bendahara NEXVO';
        $data['ketuaName'] = User::where('role', 'ketua')->value('name') ?? 'Ketua NEXVO';

        return view('reports.pdf_preview', $data);
    }

    public function exportExcel(Request $request)
    {
        $data = $this->getReportData($request);
        $fileName = 'Laporan_Keuangan_' . $data['startDate'] . '_sd_' . $data['endDate'] . '.xls';

        return response()
            ->view('reports.excel_export', $data)
            ->header('Content-Type', 'application/vnd.ms-excel; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }
}