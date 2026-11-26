@extends('layouts.app')

@section('content')
@php
    // Ambil data transaksi aktif (bukan Void) & hitung saldo real-time
    $allTrx = \App\Models\Transaction::with(['category', 'user', 'proposal'])
        ->whereNull('voided_at')
        ->orderBy('transaction_date', 'desc')
        ->orderBy('id', 'desc')
        ->get();

    $selectedMonth = request('month', now()->format('Y-m'));

    $totalIncomeAll = $allTrx->filter(fn($t) => ($t->category?->type ?? 'expense') === 'income')->sum('amount');
    $totalExpenseAll = $allTrx->filter(fn($t) => ($t->category?->type ?? 'expense') === 'expense')->sum('amount');
    $totalSaldoKas = $totalIncomeAll - $totalExpenseAll;

    // Filter transaksi bulan terpilih untuk kartu Pemasukan & Pengeluaran
    $monthlyTrx = $allTrx->filter(function ($t) use ($selectedMonth) {
        if ($selectedMonth === 'all') return true;
        return \Carbon\Carbon::parse($t->transaction_date)->format('Y-m') === $selectedMonth;
    });

    $monthlyIncome = $monthlyTrx->filter(fn($t) => ($t->category?->type ?? 'expense') === 'income')->sum('amount');
    $monthlyExpense = $monthlyTrx->filter(fn($t) => ($t->category?->type ?? 'expense') === 'expense')->sum('amount');

    $periodText = $selectedMonth === 'all'
        ? 'Semua Periode'
        : 'Periode ' . \Carbon\Carbon::parse($selectedMonth . '-01')->locale('id')->translatedFormat('F Y');

    // Daftar bulan untuk dropdown filter
    $monthOptions = $allTrx
        ->toBase()
        ->map(fn($t) => \Carbon\Carbon::parse($t->transaction_date)->format('Y-m'))
        ->push(now()->format('Y-m'))
        ->unique()
        ->sortDesc()
        ->values();

    // Antrean pengajuan menunggu validasi, pencairan, atau verifikasi realisasi Bendahara
    $pendingDisbursements = \App\Models\Proposal::with('user')
        ->whereIn('status', ['pending_disbursement', 'pending_bendahara', 'pending_ketua'])
        ->where('user_id', '!=', Auth::id())
        ->latest()
        ->get();

    $readyToDisburseCount = $pendingDisbursements->where('status', 'pending_disbursement')->count();

    // Transaksi terbaru (4 baris)
    $recentTransactions = $allTrx->take(4);

    // Pemeriksaan Pembukuan otomatis
    $expenseWithoutProofCount = $allTrx->filter(function ($t) {
        return ($t->category?->type ?? 'expense') === 'expense' && empty($t->proof_path);
    })->count();

    $pendingProposalsCount = \App\Models\Proposal::whereIn('status', ['pending_koordiv', 'pending_ketua', 'pending_bendahara', 'pending_disbursement'])->count();

    // Data Grafik 12 Bulan (Jan - Des tahun berjalan)
    $currentYear = now()->year;
    $chartIncomeData = [];
    $chartExpenseData = [];
    for ($m = 1; $m <= 12; $m++) {
        $ym = sprintf('%04d-%02d', $currentYear, $m);
        $inc = $allTrx->filter(fn($t) => ($t->category?->type ?? 'expense') === 'income' && \Carbon\Carbon::parse($t->transaction_date)->format('Y-m') === $ym)->sum('amount');
        $exp = $allTrx->filter(fn($t) => ($t->category?->type ?? 'expense') === 'expense' && \Carbon\Carbon::parse($t->transaction_date)->format('Y-m') === $ym)->sum('amount');
        $chartIncomeData[] = round($inc / 1000000, 2);
        $chartExpenseData[] = round($exp / 1000000, 2);
    }

    // Aktivitas terbaru
    $recentActivities = \App\Models\ActivityLog::latest()->take(4)->get();
@endphp

<style>
    .bendahara-kpi-dark {
        background-color: #2F4156;
        color: #FFFFFF;
        border-radius: 14px;
        border: none;
    }
    .bendahara-kpi-light {
        background-color: #FFFFFF;
        color: #1E293B;
        border-radius: 14px;
        border: 1px solid #E5E7EB;
    }
    .queue-item-box {
        background-color: #F4EFEA;
        border-radius: 12px;
        padding: 12px 14px;
        transition: background-color 0.15s ease;
    }
    .queue-item-box:hover {
        background-color: #ECE5DE;
    }
    .btn-proses-dark {
        background-color: #2F4156;
        color: #FFFFFF;
        border: none;
        border-radius: 8px;
        padding: 6px 14px;
        font-size: 11.5px;
        font-weight: 500;
        text-decoration: none;
        display: inline-block;
        transition: background-color 0.15s ease;
    }
    .btn-proses-dark:hover {
        background-color: #223040;
        color: #FFFFFF;
    }
    .btn-cairkan {
        background-color: #2F4156;
        color: #FFFFFF;
        border: none;
        border-radius: 8px;
        padding: 5px 16px;
        font-size: 11.5px;
        font-weight: 500;
        text-decoration: none;
        display: inline-block;
        transition: background-color 0.15s ease;
    }
    .btn-cairkan:hover {
        background-color: #223040;
        color: #FFFFFF;
    }
    .btn-tinjau-soft {
        background-color: #E2E8F0;
        color: #334155;
        border: none;
        border-radius: 8px;
        padding: 5px 18px;
        font-size: 11.5px;
        font-weight: 500;
        text-decoration: none;
        display: inline-block;
    }
    .btn-tinjau-soft:hover {
        background-color: #CBD5E1;
        color: #1E293B;
    }
    .chart-tab-btn {
        border: none;
        background: transparent;
        font-size: 11.5px;
        font-weight: 500;
        color: #64748B;
        padding: 4px 12px;
        border-radius: 7px;
    }
    .chart-tab-btn.active {
        background-color: #C8D9E6;
        color: #2F4156;
        font-weight: 600;
    }
</style>

<!-- 1. HEADER DASHBOARD & FILTER BAR -->
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h3 class="fw-bold mb-0" style="color: #2F4156;">Dashboard</h3>
            <span class="badge rounded-2 px-2 py-1 fw-medium" style="background-color: #E8EEF3; color: #567C8D; font-size: 11px;">Bendahara</span>
        </div>
        <p class="text-muted mb-0" style="font-size: 12.5px;">
            Selamat datang kembali, {{ Auth::user()->name }}. Pantau saldo kas, pencairan dana disetujui, dan verifikasi bukti realisasi anggota.
        </p>
    </div>

    <div class="d-flex flex-wrap align-items-center gap-2">
        <!-- Tanggal Hari Ini -->
        <div class="bg-white border rounded-3 px-3 py-2 d-flex align-items-center gap-2" style="font-size: 12px; color: #334155; border-color: #E2E8F0 !important;">
            <i class="bi bi-calendar3 text-muted"></i>
            <span>{{ now()->locale('id')->translatedFormat('l, d F Y') }}</span>
        </div>

        <!-- Filter Bulan -->
        <form method="GET" action="{{ route('dashboard', 'bendahara') }}" id="bendaharaMonthForm">
            <select name="month" class="form-select bg-white rounded-3" style="font-size: 12px; border-color: #E2E8F0; height: 36px; min-width: 145px;" onchange="document.getElementById('bendaharaMonthForm').submit()">
                @foreach($monthOptions as $mOpt)
                    <option value="{{ $mOpt }}" {{ $selectedMonth === $mOpt ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::parse($mOpt . '-01')->locale('id')->translatedFormat('F Y') }}
                    </option>
                @endforeach
                <option value="all" {{ $selectedMonth === 'all' ? 'selected' : '' }}>Semua Periode</option>
            </select>
        </form>

        <!-- Export Laporan -->
        <a href="{{ route('reports.index') }}" class="btn rounded-3 px-3 py-2 fw-medium" style="background-color: #C8D9E6; color: #2F4156; font-size: 12px;">
            Export Laporan
        </a>

        <!-- Catat Kas -->
        <a href="{{ route('transactions.index') }}" class="btn rounded-3 px-3 py-2 fw-medium text-white" style="background-color: #2F4156; font-size: 12px;">
            + Catat Kas
        </a>
    </div>
</div>

<!-- 2. EMPAT KARTU RINGKASAN (KPI CARDS) -->
<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="bendahara-kpi-light p-4 h-100 shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="fw-medium" style="font-size: 13px; color: #475569;">Total Saldo Kas</span>
                <span class="rounded-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px; background-color: #2F4156; color: #FFFFFF;">
                    <i class="bi bi-wallet2 small"></i>
                </span>
            </div>
            <h4 class="fw-bold mb-2" style="font-size: 22px; color: #2F4156;">Rp {{ number_format($totalSaldoKas, 0, ',', '.') }}</h4>
            <div class="text-muted" style="font-size: 11.5px;">Saldo aktif keseluruhan</div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="bendahara-kpi-light p-4 h-100 shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="fw-medium" style="font-size: 13px; color: #475569;">Total Pemasukan</span>
                <span class="rounded-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px; background-color: #E8EEF3; color: #567C8D;">
                    <i class="bi bi-graph-up-arrow small"></i>
                </span>
            </div>
            <h4 class="fw-bold mb-2" style="font-size: 22px; color: #2F4156;">Rp {{ number_format($monthlyIncome, 0, ',', '.') }}</h4>
            <div class="text-muted" style="font-size: 11.5px;">{{ $periodText }}</div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="bendahara-kpi-light p-4 h-100 shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="fw-medium" style="font-size: 13px; color: #475569;">Total Pengeluaran</span>
                <span class="rounded-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px; background-color: #E8EEF3; color: #567C8D;">
                    <i class="bi bi-graph-down-arrow small"></i>
                </span>
            </div>
            <h4 class="fw-bold mb-2" style="font-size: 22px; color: #2F4156;">Rp {{ number_format($monthlyExpense, 0, ',', '.') }}</h4>
            <div class="text-muted" style="font-size: 11.5px;">{{ $periodText }}</div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="bendahara-kpi-light p-4 h-100 shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="fw-medium" style="font-size: 13px; color: #475569;">Tugas Bendahara</span>
                <span class="rounded-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px; background-color: #E8EEF3; color: #567C8D;">
                    <i class="bi bi-clock small"></i>
                </span>
            </div>
            <h4 class="fw-bold mb-2" style="font-size: 22px; color: #2F4156;">{{ str_pad($pendingDisbursements->count(), 2, '0', STR_PAD_LEFT) }} Antrean</h4>
            <div class="text-muted" style="font-size: 11.5px;">Validasi, pencairan, &amp; cek bukti nota</div>
        </div>
    </div>
</div>

<!-- 3. BARIS TENGAH: FINANCIAL OVERVIEW & ANTREAN KEUANGAN BENDAHARA -->
<div class="row g-3 mb-4">
    <!-- Kiri: Financial Overview (Chart) -->
    <div class="col-lg-8">
        <div class="nexvo-card p-4 h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                    <div>
                        <h5 class="fw-bold mb-1" style="font-size: 16px; color: #2F4156;">Financial Overview ({{ $currentYear }})</h5>
                        <p class="text-muted mb-2" style="font-size: 12px;">Ringkasan arus pemasukan dan pengeluaran organisasi (dalam Juta Rupiah).</p>
                        <div class="d-flex align-items-center gap-3" style="font-size: 11.5px;">
                            <span class="d-inline-flex align-items-center gap-1">
                                <span class="rounded-circle d-inline-block" style="width: 8px; height: 8px; background-color: #567C8D;"></span>
                                Pemasukan
                            </span>
                            <span class="d-inline-flex align-items-center gap-1">
                                <span class="rounded-circle d-inline-block" style="width: 8px; height: 8px; background-color: #2F4156;"></span>
                                Pengeluaran
                            </span>
                        </div>
                    </div>

                    <div class="p-1 rounded-3" style="background-color: #F4EFEA;">
                        <button type="button" class="chart-tab-btn" onclick="switchChartMode('jan-jun', this)">Jan - Jun</button>
                        <button type="button" class="chart-tab-btn" onclick="switchChartMode('jul-des', this)">Jul - Des</button>
                        <button type="button" class="chart-tab-btn active" onclick="switchChartMode('tahunan', this)">Tahunan</button>
                    </div>
                </div>

                <div style="height: 240px;" class="mt-3">
                    <canvas id="bendaharaOverviewChart"></canvas>
                </div>
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center text-muted mt-3 pt-2 border-top" style="font-size: 11.5px;">
                <span>
                    {{ \Carbon\Carbon::parse(($selectedMonth === 'all' ? now()->format('Y-m') : $selectedMonth) . '-01')->locale('id')->translatedFormat('F Y') }}:
                    <strong style="color: #1E293B;">Rp {{ number_format($monthlyIncome, 0, ',', '.') }}</strong> masuk &bull;
                    <strong style="color: #1E293B;">Rp {{ number_format($monthlyExpense, 0, ',', '.') }}</strong> keluar
                </span>
                <a href="{{ route('reports.index') }}" class="fw-semibold text-decoration-none" style="color: #2F4156;">
                    Detail Laporan &rarr;
                </a>
            </div>
        </div>
    </div>

    <!-- Kanan: Antrean Keuangan Bendahara (Gaya Kotak Krem Figma - Maks 3 Item) -->
    <div class="col-lg-4">
        <div class="nexvo-card p-4 h-100 d-flex flex-column justify-content-between">
            <div>
                <h5 class="fw-bold mb-1" style="font-size: 16px; color: #2F4156;">Antrean Keuangan Bendahara</h5>
                <p class="text-muted mb-3" style="font-size: 12px;">Pengajuan menunggu validasi, pencairan, atau verifikasi realisasi.</p>

                <div class="d-flex flex-column gap-2.5">
                    @forelse($pendingDisbursements->take(3) as $item)
                    @php
                        $statusLabel = match($item->status) {
                            'pending_disbursement' => 'Siap Dicairkan',
                            'pending_bendahara'    => 'Validasi Bendahara',
                            'pending_ketua'        => 'Menunggu Ketua',
                            default                => ucfirst(str_replace('_', ' ', $item->status)),
                        };
                    @endphp
                    <div class="queue-item-box d-flex justify-content-between align-items-center gap-2">
                        <div style="min-width: 0;">
                            <div class="fw-bold text-truncate mb-0.5" style="font-size: 13px; color: #1E293B;">
                                {{ $item->title }}
                            </div>
                            <div class="text-muted text-truncate mb-2" style="font-size: 11px;">
                                {{ $item->user->name ?? 'Pengaju' }} &bull; {{ $statusLabel }}
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <span class="fw-bold" style="font-size: 12.5px; color: #1E293B;">
                                    Rp {{ number_format($item->amount, 0, ',', '.') }}
                                </span>
                                <span class="text-muted" style="font-size: 11px;">
                                    {{ $item->created_at->locale('id')->translatedFormat('d M Y') }}
                                </span>
                            </div>
                        </div>

                        <a href="{{ route('proposals.index', ['tab' => 'approvals', 'section' => 'penyaluran']) }}" class="btn-proses-dark flex-shrink-0">
                            Proses
                        </a>
                    </div>
                    @empty
                    <div class="text-center text-muted py-5" style="font-size: 12.5px;">
                        <i class="bi bi-check2-circle fs-4 d-block mb-1 text-success"></i>
                        Tidak ada antrean keuangan saat ini.
                    </div>
                    @endforelse
                </div>
            </div>

            <a href="{{ route('proposals.index', ['tab' => 'approvals', 'section' => 'penyaluran']) }}" class="btn w-100 rounded-3 py-2 fw-semibold text-center mt-3" style="background-color: #C8D9E6; color: #2F4156; font-size: 12.5px;">
                Lihat Semua Pengajuan
            </a>
        </div>
    </div>
</div>

<!-- 4. BARIS BAWAH: TRANSAKSI TERBARU & AKTIVITAS TERBARU -->
<div class="row g-3">
    <!-- Kiri: Transaksi Terbaru -->
    <div class="col-lg-8">
        <div class="nexvo-card p-4 h-100 d-flex flex-column justify-content-between">
            <div>
                <h5 class="fw-bold mb-1" style="font-size: 16px; color: #2F4156;">Transaksi Terbaru</h5>
                <p class="text-muted mb-3" style="font-size: 12px;">Aktivitas transaksi keuangan terbaru pada buku kas.</p>

                <div class="table-responsive">
                    <table class="table align-middle mb-3" style="font-size: 12.5px;">
                        <thead>
                            <tr style="color: #64748B; border-bottom: 1px solid #E2E8F0;">
                                <th class="fw-medium py-2">Tanggal</th>
                                <th class="fw-medium py-2">Keterangan</th>
                                <th class="fw-medium py-2">Kategori</th>
                                <th class="fw-medium py-2 text-end">Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentTransactions as $trx)
                            @php
                                $isIncome = ($trx->category?->type ?? 'expense') === 'income';
                            @endphp
                            <tr>
                                <td class="py-2.5 text-muted">{{ \Carbon\Carbon::parse($trx->transaction_date)->locale('id')->translatedFormat('d M Y') }}</td>
                                <td class="py-2.5 fw-medium" style="color: #1E293B;">{{ \Illuminate\Support\Str::limit($trx->description, 38) }}</td>
                                <td class="py-2.5 text-muted">{{ $trx->category->name ?? 'Operasional' }}</td>
                                <td class="py-2.5 text-end fw-bold" style="color: {{ $isIncome ? '#16A34A' : '#1E293B' }};">
                                    {{ $isIncome ? '+' : '-' }}Rp {{ number_format($trx->amount, 0, ',', '.') }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">Belum ada transaksi tercatat.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <a href="{{ route('transactions.index') }}" class="btn w-100 rounded-3 py-2 fw-semibold text-center" style="background-color: #C8D9E6; color: #2F4156; font-size: 12.5px;">
                Lihat Semua Transaksi
            </a>
        </div>
    </div>

    <!-- Kanan: Aktivitas Terbaru -->
    <div class="col-lg-4">
        <div class="nexvo-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <h5 class="fw-bold mb-0" style="font-size: 16px; color: #2F4156;">Aktivitas Terbaru</h5>
                <a href="{{ route('audit-logs.index') }}" class="text-muted text-decoration-none" style="font-size: 11px;">Audit Log</a>
            </div>
            <p class="text-muted mb-3" style="font-size: 12px;">Jejak aktivitas sistem &amp; persetujuan terkini.</p>

            <div class="d-flex flex-column gap-3">
                @forelse($recentActivities as $act)
                <div class="d-flex align-items-start gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 mt-1" style="width: 30px; height: 30px; background-color: #E8EEF3; color: #2F4156;">
                        <i class="bi bi-check-circle small"></i>
                    </div>
                    <div>
                        <div class="fw-semibold" style="font-size: 12.5px; color: #1E293B; line-height: 1.35;">
                            {{ $act->action }}
                        </div>
                        <div class="text-muted" style="font-size: 11px;">
                            {{ $act->created_at->locale('id')->diffForHumans() }} &bull; {{ $act->created_at->format('H:i') }} WIB
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center text-muted py-4" style="font-size: 12.5px;">
                    Belum ada aktivitas tercatat.
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    const ctxBendahara = document.getElementById('bendaharaOverviewChart');
    const yearlyLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    const yearlyIncome = @json($chartIncomeData);
    const yearlyExpense = @json($chartExpenseData);

    let bendaharaChart = null;
    if (ctxBendahara) {
        bendaharaChart = new Chart(ctxBendahara, {
            type: 'bar',
            data: {
                labels: yearlyLabels,
                datasets: [
                    {
                        label: 'Pemasukan',
                        data: yearlyIncome,
                        backgroundColor: '#567C8D',
                        borderRadius: 4,
                        barPercentage: 0.65,
                        categoryPercentage: 0.6
                    },
                    {
                        label: 'Pengeluaran',
                        data: yearlyExpense,
                        backgroundColor: '#2F4156',
                        borderRadius: 4,
                        barPercentage: 0.65,
                        categoryPercentage: 0.6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': Rp ' + context.parsed.y + ' jt';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11, family: 'Poppins' }, color: '#64748B' }
                    },
                    y: {
                        beginAtZero: true,
                        border: { display: false },
                        grid: { color: '#F1F5F9' },
                        ticks: {
                            font: { size: 11, family: 'Poppins' },
                            color: '#64748B',
                            callback: function(value) {
                                return 'Rp ' + value + ' Jt';
                            }
                        }
                    }
                }
            }
        });
    }

    function switchChartMode(mode, btn) {
        document.querySelectorAll('.chart-tab-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        if (!bendaharaChart) return;

        if (mode === 'jan-jun') {
            bendaharaChart.data.labels = yearlyLabels.slice(0, 6);
            bendaharaChart.data.datasets[0].data = yearlyIncome.slice(0, 6);
            bendaharaChart.data.datasets[1].data = yearlyExpense.slice(0, 6);
        } else if (mode === 'jul-des') {
            bendaharaChart.data.labels = yearlyLabels.slice(6, 12);
            bendaharaChart.data.datasets[0].data = yearlyIncome.slice(6, 12);
            bendaharaChart.data.datasets[1].data = yearlyExpense.slice(6, 12);
        } else {
            bendaharaChart.data.labels = yearlyLabels;
            bendaharaChart.data.datasets[0].data = yearlyIncome;
            bendaharaChart.data.datasets[1].data = yearlyExpense;
        }
        bendaharaChart.update();
    }
</script>
@endpush