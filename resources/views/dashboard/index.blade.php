@extends('layouts.app')

@section('content')
@php
    $user = Auth::user();
    $role = $user->role;

    $balance = $balance ?? 0;
    $totalIncome = $totalIncome ?? 0;
    $totalExpense = $totalExpense ?? 0;
    $pendingProposals = $pendingProposals ?? 0;
    $recentPendingProposals = $recentPendingProposals ?? collect();
    $recentTransactions = $recentTransactions ?? collect();
    $latestLogs = $latestLogs ?? collect();
    $personalStats = $personalStats ?? ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'total_amount' => 0];
    $periodDate = $periodDate ?? now();
    $selectedMonth = $selectedMonth ?? now()->format('Y-m');
    $monthOptions = $monthOptions ?? [now()->format('Y-m') => now()->locale('id')->translatedFormat('F Y')];
    $monthlyIncomeChart = $monthlyIncomeChart ?? array_fill(0, 12, 0);
    $monthlyExpenseChart = $monthlyExpenseChart ?? array_fill(0, 12, 0);

    // Kustomisasi teks & tautan per Role
    $roleMeta = match($role) {
        'ketua' => [
            'subtitle' => 'Ringkasan arus kas organisasi dan daftar pengajuan dana yang menunggu otorisasi Ketua.',
            'kpi4_title' => 'Menunggu Otorisasi Ketua',
            'kpi4_unit' => 'Pengajuan',
            'kpi4_sub' => 'Perlu keputusan persetujuan Anda',
            'kpi4_url' => route('proposals.index', ['tab' => 'approvals']),
            'widget_title' => 'Pending Approvals (Ketua)',
            'widget_sub' => 'Pengajuan yang telah lolos Koordiv dan menunggu otorisasi Anda.',
            'widget_btn' => 'Review',
        ],
        'bendahara' => [
            'subtitle' => 'Pantau saldo kas, pencairan dana disetujui, dan verifikasi bukti realisasi anggota.',
            'kpi4_title' => 'Tugas Bendahara',
            'kpi4_unit' => 'Antrean',
            'kpi4_sub' => 'Validasi, pencairan, & cek bukti nota',
            'kpi4_url' => route('proposals.index', ['tab' => 'approvals']),
            'widget_title' => 'Antrean Keuangan Bendahara',
            'widget_sub' => 'Pengajuan menunggu validasi, pencairan, atau verifikasi realisasi.',
            'widget_btn' => 'Proses',
        ],
        'koordiv' => [
            'subtitle' => 'Pantau saldo kas tersedia dan tinjau pengajuan dana anggota ' . ($user->division ?? 'Divisi Anda') . '.',
            'kpi4_title' => 'Persetujuan Divisi',
            'kpi4_unit' => 'Pengajuan',
            'kpi4_sub' => 'Menunggu tinjauan Koordinator Divisi',
            'kpi4_url' => route('proposals.index', ['tab' => 'approvals']),
            'widget_title' => 'Antrean Persetujuan Anggota Divisi',
            'widget_sub' => 'Pengajuan anggota ' . ($user->division ?? 'divisi') . ' yang perlu disetujui Tingkat 1.',
            'widget_btn' => 'Tinjau',
        ],
        default => [
            'subtitle' => 'Pantau saldo kas NEXVO Studio serta status pengajuan & realisasi dana pribadi Anda.',
            'kpi4_title' => 'Pengajuan Aktif Saya',
            'kpi4_unit' => 'Pengajuan',
            'kpi4_sub' => 'Sedang diproses / perlu bukti realisasi',
            'kpi4_url' => route('proposals.index', ['tab' => 'my']),
            'widget_title' => 'Riwayat Pengajuan Dana Saya',
            'widget_sub' => 'Pengajuan Anda yang sedang berjalan atau menunggu unggah nota.',
            'widget_btn' => 'Detail',
        ],
    };

    $statusLabels = [
        'pending_koordiv' => ['label' => 'Menunggu Koordiv', 'bg' => '#FEF3C7', 'color' => '#B45309'],
        'pending_ketua' => ['label' => 'Menunggu Ketua', 'bg' => '#FEF3C7', 'color' => '#B45309'],
        'pending_bendahara' => ['label' => 'Validasi Bendahara', 'bg' => '#E0F2FE', 'color' => '#0369A1'],
        'approved' => ['label' => 'Disetujui (Siap Cair)', 'bg' => '#DCFCE7', 'color' => '#15803D'],
        'pending_disbursement' => ['label' => 'Siap Dicairkan', 'bg' => '#DCFCE7', 'color' => '#15803D'],
        'disbursed' => ['label' => 'Perlu Bukti Nota', 'bg' => '#E0E7FF', 'color' => '#4338CA'],
        'waiting_verification' => ['label' => 'Verifikasi Bukti', 'bg' => '#F3E8FF', 'color' => '#7E22CE'],
        'completed' => ['label' => 'Selesai', 'bg' => '#DCFCE7', 'color' => '#15803D'],
        'rejected' => ['label' => 'Ditolak', 'bg' => '#FEE2E2', 'color' => '#B91C1C'],
    ];

    $isFinanceViewer = in_array($role, ['ketua', 'bendahara']);
@endphp

<style>
    .kpi-clickable {
        transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
        cursor: pointer;
    }
    .kpi-clickable:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(36, 50, 66, 0.08) !important;
        border-color: #94A3B8 !important;
    }
    .row-clickable {
        transition: background-color 0.15s ease;
        cursor: pointer;
    }
    .row-clickable:hover {
        background-color: #F8FAFC !important;
    }
</style>

<!-- Header Dashboard & Filter -->
<div class="d-flex flex-wrap flex-lg-nowrap justify-content-between align-items-start align-items-lg-center mb-4 gap-3">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h3 class="fw-bold mb-0" style="color: #1E293B;">Dashboard</h3>
            <span class="badge rounded-2 px-2.5 py-1 fw-medium" style="background-color: #E8EEF3; color: #2F4156; font-size: 11px;">
                {{ $role === 'koordiv' ? 'Koordinator Divisi' . ($user->division ? ' • ' . $user->division : '') : ucfirst($role) . ($user->division && $role === 'anggota' ? ' • ' . $user->division : '') }}
            </span>
        </div>
        <p class="text-muted mb-0" style="font-size: 13px;">
            Selamat datang kembali, <strong>{{ $user->name }}</strong>. {{ $roleMeta['subtitle'] }}
        </p>
    </div>

    <div class="d-flex flex-wrap flex-xl-nowrap align-items-center gap-2 flex-shrink-0">
        <div class="bg-white border rounded-3 px-3 py-2 d-flex align-items-center gap-2 text-nowrap" style="font-size: 12.5px;">
            <i class="bi bi-calendar3 text-muted"></i>
            <span>{{ \Carbon\Carbon::now()->locale('id')->translatedFormat('l, d F Y') }}</span>
        </div>

        @if($isFinanceViewer)
            <form method="GET" action="{{ route('dashboard', $role) }}" class="m-0">
                <select name="month" onchange="this.form.submit()" class="form-select form-select-sm bg-white border rounded-3 py-2" style="width: auto; font-size: 12.5px; cursor: pointer;">
                    @foreach($monthOptions as $val => $label)
                        <option value="{{ $val }}" {{ $selectedMonth === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        @endif

        @if($role === 'bendahara')
            <a href="{{ route('reports.index') }}" class="btn-nexvo-soft text-nowrap text-decoration-none">Export Laporan</a>
            <a href="{{ route('transactions.index') }}" class="btn-nexvo-dark text-nowrap text-decoration-none">+ Catat Kas</a>
        @elseif($role === 'ketua')
            <a href="{{ route('reports.index') }}" class="btn-nexvo-soft text-nowrap text-decoration-none">Laporan Keuangan</a>
            <a href="{{ route('proposals.create') }}" class="btn-nexvo-dark text-nowrap text-decoration-none">+ Buat Permintaan</a>
        @else
            <a href="{{ route('proposals.index') }}" class="btn-nexvo-soft text-nowrap text-decoration-none">Riwayat Pengajuan</a>
            <a href="{{ route('proposals.create') }}" class="btn-nexvo-dark text-nowrap text-decoration-none">+ Buat Permintaan</a>
        @endif
    </div>
</div>

<!-- 4 Kartu Metrik Atas -->
<div class="row g-3 mb-4">
    <!-- Kartu 1: Total Saldo Kas (Transparan untuk semua aktor) -->
    <div class="col-md-6 col-xl-3">
        <a href="{{ $isFinanceViewer ? route('transactions.index') : route('proposals.create') }}" class="text-decoration-none text-reset d-block h-100">
            <div class="nexvo-card kpi-clickable h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted" style="font-size: 12.5px;">Total Saldo Kas</span>
                    <span class="badge rounded-2 p-2" style="background-color: #2F4156; color: #FFFFFF;"><i class="bi bi-wallet2"></i></span>
                </div>
                <h4 class="fw-bold mb-1" style="color: #1E293B;">Rp {{ number_format($balance, 0, ',', '.') }}</h4>
                <div class="d-flex justify-content-between align-items-center text-muted" style="font-size: 11.5px;">
                    <span>{{ $isFinanceViewer ? 'Saldo aktif keseluruhan' : 'Transparansi kas tersedia' }}</span>
                    <i class="bi bi-arrow-up-right"></i>
                </div>
            </div>
        </a>
    </div>

    @if($isFinanceViewer)
        <!-- Kartu 2 (Ketua & Bendahara): Total Pemasukan -->
        <div class="col-md-6 col-xl-3">
            <a href="{{ route('transactions.index', ['type' => 'income']) }}" class="text-decoration-none text-reset d-block h-100">
                <div class="nexvo-card kpi-clickable h-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted" style="font-size: 12.5px;">Total Pemasukan</span>
                        <span class="badge rounded-2 p-2" style="background-color: #DCE5EE; color: #47637E;"><i class="bi bi-graph-up-arrow"></i></span>
                    </div>
                    <h4 class="fw-bold mb-1" style="color: #1E293B;">Rp {{ number_format($totalIncome, 0, ',', '.') }}</h4>
                    <div class="d-flex justify-content-between align-items-center text-muted" style="font-size: 11.5px;">
                        <span>Periode {{ $periodDate->locale('id')->translatedFormat('F Y') }}</span>
                        <i class="bi bi-arrow-up-right"></i>
                    </div>
                </div>
            </a>
        </div>

        <!-- Kartu 3 (Ketua & Bendahara): Total Pengeluaran -->
        <div class="col-md-6 col-xl-3">
            <a href="{{ route('reports.index') }}" class="text-decoration-none text-reset d-block h-100">
                <div class="nexvo-card kpi-clickable h-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted" style="font-size: 12.5px;">Total Pengeluaran</span>
                        <span class="badge rounded-2 p-2" style="background-color: #DCE5EE; color: #243242;"><i class="bi bi-graph-down-arrow"></i></span>
                    </div>
                    <h4 class="fw-bold mb-1" style="color: #1E293B;">Rp {{ number_format($totalExpense, 0, ',', '.') }}</h4>
                    <div class="d-flex justify-content-between align-items-center text-muted" style="font-size: 11.5px;">
                        <span>Periode {{ $periodDate->locale('id')->translatedFormat('F Y') }}</span>
                        <i class="bi bi-arrow-up-right"></i>
                    </div>
                </div>
            </a>
        </div>
    @else
        <!-- Kartu 2 (Anggota & Koordiv): Pengajuan Disetujui -->
        <div class="col-md-6 col-xl-3">
            <a href="{{ route('proposals.index') }}" class="text-decoration-none text-reset d-block h-100">
                <div class="nexvo-card kpi-clickable h-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted" style="font-size: 12.5px;">Pengajuan Disetujui</span>
                        <span class="badge rounded-2 p-2" style="background-color: #DCFCE7; color: #15803D;"><i class="bi bi-check-circle"></i></span>
                    </div>
                    <h4 class="fw-bold mb-1" style="color: #1E293B;">{{ str_pad($personalStats['approved'], 2, '0', STR_PAD_LEFT) }} Pengajuan</h4>
                    <div class="d-flex justify-content-between align-items-center text-muted" style="font-size: 11.5px;">
                        <span>{{ $role === 'koordiv' ? 'Lolos validasi di divisi' : 'Disetujui / dalam pencairan' }}</span>
                        <i class="bi bi-arrow-up-right"></i>
                    </div>
                </div>
            </a>
        </div>

        <!-- Kartu 3 (Anggota & Koordiv): Total Dana Disetujui -->
        <div class="col-md-6 col-xl-3">
            <a href="{{ route('proposals.index') }}" class="text-decoration-none text-reset d-block h-100">
                <div class="nexvo-card kpi-clickable h-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted" style="font-size: 12.5px;">{{ $role === 'koordiv' ? 'Dana Disetujui Divisi' : 'Total Dana Disetujui' }}</span>
                        <span class="badge rounded-2 p-2" style="background-color: #DCE5EE; color: #47637E;"><i class="bi bi-cash-stack"></i></span>
                    </div>
                    <h4 class="fw-bold mb-1" style="color: #1E293B;">Rp {{ number_format($personalStats['total_amount'], 0, ',', '.') }}</h4>
                    <div class="d-flex justify-content-between align-items-center text-muted" style="font-size: 11.5px;">
                        <span>Ditolak: {{ $personalStats['rejected'] }} pengajuan</span>
                        <i class="bi bi-arrow-up-right"></i>
                    </div>
                </div>
            </a>
        </div>
    @endif

    <!-- Kartu 4: Antrean / Status Tindakan per Role -->
    <div class="col-md-6 col-xl-3">
        <a href="{{ $roleMeta['kpi4_url'] }}" class="text-decoration-none text-reset d-block h-100">
            <div class="nexvo-card kpi-clickable h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted" style="font-size: 12.5px;">{{ $roleMeta['kpi4_title'] }}</span>
                    <span class="badge rounded-2 p-2" style="background-color: #EFECE8; color: #243242;"><i class="bi bi-clock-history"></i></span>
                </div>
                <h4 class="fw-bold mb-1" style="color: #1E293B;">{{ str_pad($pendingProposals, 2, '0', STR_PAD_LEFT) }} {{ $roleMeta['kpi4_unit'] }}</h4>
                <div class="d-flex justify-content-between align-items-center text-muted" style="font-size: 11.5px;">
                    <span>{{ $roleMeta['kpi4_sub'] }}</span>
                    <i class="bi bi-arrow-up-right"></i>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- =================================================================== -->
<!-- DIAGRAM BATANG SERAGAM UNTUK SEMUA ROLE (ANGGOTA, KOORDIV, KETUA, BENDAHARA) -->
<!-- =================================================================== -->
<div class="row g-3 mb-4">
    <div class="{{ $isFinanceViewer ? 'col-lg-8' : 'col-12' }}">
        <div class="nexvo-card h-100 d-flex flex-column justify-content-between">
            <div class="d-flex flex-wrap justify-content-between align-items-start mb-3 gap-2">
                <div>
                    <h5 class="fw-bold mb-0" style="font-size: 16px;">Financial Overview ({{ $periodDate->year }})</h5>
                    <p class="text-muted mb-2" style="font-size: 12px;">Ringkasan arus pemasukan dan pengeluaran organisasi (dalam Juta Rupiah).</p>
                    <div class="d-flex align-items-center gap-3" style="font-size: 11.5px;">
                        <span><i class="bi bi-circle-fill me-1" style="color: #607990;"></i> Pemasukan</span>
                        <span><i class="bi bi-circle-fill me-1" style="color: #243242;"></i> Pengeluaran</span>
                    </div>
                </div>
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" id="btnChartSemester1" class="btn btn-light border" style="font-size: 11.5px;" onclick="switchChartRange('h1')">Jan - Jun</button>
                    <button type="button" id="btnChartSemester2" class="btn btn-light border" style="font-size: 11.5px;" onclick="switchChartRange('h2')">Jul - Des</button>
                    <button type="button" id="btnChartFullYear" class="btn border fw-semibold" style="background-color: #C5D3DF; font-size: 11.5px;" onclick="switchChartRange('all')">Tahunan</button>
                </div>
            </div>

            <div style="height: 225px;">
                <canvas id="financialChart"></canvas>
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center text-muted mt-2" style="font-size: 11.5px;">
                <span>
                    {{ $periodDate->locale('id')->translatedFormat('F Y') }}:
                    <strong>Rp {{ number_format($totalIncome, 0, ',', '.') }}</strong> masuk &bull;
                    <strong>Rp {{ number_format($totalExpense, 0, ',', '.') }}</strong> keluar
                </span>
                @if($isFinanceViewer)
                    <a href="{{ route('reports.index') }}" class="text-decoration-none fw-semibold" style="color: #47637E;">
                        Detail Laporan &rarr;
                    </a>
                @else
                    <span class="text-muted">Transparansi arus kas organisasi</span>
                @endif
            </div>
        </div>
    </div>

    @if($isFinanceViewer)
    <div class="col-lg-4">
        <div class="nexvo-card h-100 d-flex flex-column justify-content-between">
            <div>
                <h5 class="fw-bold mb-0" style="font-size: 16px;">{{ $roleMeta['widget_title'] }}</h5>
                <p class="text-muted mb-3" style="font-size: 12px;">{{ $roleMeta['widget_sub'] }}</p>

                <div class="d-flex flex-column gap-2">
                    @forelse($recentPendingProposals as $prop)
                    @php
                        $targetActionUrl = ($role === 'bendahara' && $prop->status === 'waiting_verification')
                            ? route('transactions.index', ['tab' => 'verification'])
                            : route('proposals.index', ['tab' => 'approvals']);
                    @endphp
                    <div class="d-flex justify-content-between align-items-center p-2.5 px-3 rounded-3" style="background-color: #EFECE8;">
                        <div class="me-2" style="min-width: 0;">
                            <div class="fw-bold text-truncate" style="font-size: 13px;">{{ $prop->title ?? ($prop->user?->name ?? 'Pengajuan') }}</div>
                            <div class="text-muted text-truncate" style="font-size: 11px;">
                                {{ $prop->user?->name ?? '-' }} &bull; {{ $statusLabels[$prop->status]['label'] ?? ucfirst($prop->status) }}
                            </div>
                        </div>
                        <div class="text-end me-2 flex-shrink-0">
                            <div class="fw-bold" style="font-size: 12.5px;">Rp {{ number_format($prop->amount, 0, ',', '.') }}</div>
                            <div class="text-muted" style="font-size: 10.5px;">{{ $prop->created_at->format('d M Y') }}</div>
                        </div>
                        <a href="{{ $targetActionUrl }}" class="btn-nexvo-dark py-1 px-3 text-decoration-none flex-shrink-0" style="font-size: 11.5px;">
                            {{ $roleMeta['widget_btn'] }}
                        </a>
                    </div>
                    @empty
                    <div class="text-center text-muted py-4 rounded-3" style="font-size: 12.5px; background-color: #F8FAFC;">
                        <i class="bi bi-check2-circle d-block fs-4 mb-1 text-success"></i>
                        Tidak ada antrean yang perlu ditindaklanjuti saat ini.
                    </div>
                    @endforelse
                </div>
            </div>

            <a href="{{ $roleMeta['kpi4_url'] }}" class="btn-nexvo-soft w-100 text-center mt-3 py-2 text-decoration-none">
                Lihat Semua Pengajuan
            </a>
        </div>
    </div>
    @endif
</div>

@if($isFinanceViewer)
    <!-- =================================================================== -->
    <!-- BARIS BAWAH KEUANGAN (KHUSUS KETUA & BENDAHARA)                     -->
    <!-- =================================================================== -->
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="nexvo-card h-100 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="fw-bold mb-0" style="font-size: 16px;">Recent Transactions</h5>
                            <p class="text-muted mb-0" style="font-size: 12px;">Aktivitas transaksi keuangan terbaru pada buku kas.</p>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0" style="font-size: 12.5px;">
                            <thead class="text-muted">
                                <tr>
                                    <th class="fw-normal border-bottom pb-2">Tanggal</th>
                                    <th class="fw-normal border-bottom pb-2">Keterangan</th>
                                    <th class="fw-normal border-bottom pb-2">Kategori</th>
                                    <th class="fw-normal border-bottom pb-2 text-end">Nominal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentTransactions as $trx)
                                <tr class="row-clickable" onclick="window.location='{{ route('transactions.index') }}'">
                                    <td class="py-2.5 text-muted">{{ \Carbon\Carbon::parse($trx->transaction_date)->format('d M Y') }}</td>
                                    <td class="py-2.5 fw-medium">{{ $trx->description ?? ($trx->category?->name ?? '-') }}</td>
                                    <td class="py-2.5" style="color: #607990;">{{ $trx->category?->name ?? '-' }}</td>
                                    <td class="py-2.5 text-end fw-bold {{ ($trx->category?->type ?? 'expense') === 'income' ? 'text-success' : '' }}">
                                        @if(($trx->category?->type ?? 'expense') === 'income')
                                            +Rp {{ number_format($trx->amount, 0, ',', '.') }}
                                        @else
                                            -Rp {{ number_format($trx->amount, 0, ',', '.') }}
                                        @endif
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

                <a href="{{ route('transactions.index') }}" class="btn-nexvo-soft w-100 text-center mt-3 py-2 text-decoration-none">
                    Lihat Semua Transaksi
                </a>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="nexvo-card h-100 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="fw-bold mb-0" style="font-size: 16px;">Recent Activities</h5>
                            <p class="text-muted mb-0" style="font-size: 12px;">Jejak aktivitas sistem &amp; persetujuan terkini.</p>
                        </div>
                        <a href="{{ route('audit-logs.index') }}" class="text-decoration-none fw-semibold" style="font-size: 11.5px; color: #47637E;">
                            Audit Log
                        </a>
                    </div>

                    <div class="d-flex flex-column gap-3">
                        @forelse($latestLogs as $log)
                        <a href="{{ route('audit-logs.index') }}" class="text-decoration-none text-reset d-flex align-items-start gap-2.5">
                            <span class="badge rounded-circle p-2 flex-shrink-0" style="background-color: #DCE5EE; color: #243242;">
                                <i class="bi bi-check2-circle"></i>
                            </span>
                            <div>
                                <div class="fw-semibold" style="font-size: 12px; line-height: 1.35;">{{ $log->action }}</div>
                                <div class="text-muted" style="font-size: 10.5px;">{{ $log->created_at->locale('id')->diffForHumans() }} &bull; {{ $log->created_at->format('H:i') }} WIB</div>
                            </div>
                        </a>
                        @empty
                        <div class="text-center text-muted py-4" style="font-size: 12.5px;">
                            Belum ada aktivitas terbaru tercatat.
                        </div>
                        @endforelse
                    </div>
                </div>

                <a href="{{ route('audit-logs.index') }}" class="btn-nexvo-soft w-100 text-center mt-3 py-2 text-decoration-none">
                    Buka Riwayat Aktivitas
                </a>
            </div>
        </div>
    </div>
@else
    <!-- =================================================================== -->
    <!-- KONTEN OPERASIONAL PENGAJUAN (KHUSUS ANGGOTA & KOORDINATOR DIVISI)  -->
    <!-- =================================================================== -->
    <div class="nexvo-card p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h5 class="fw-bold mb-0" style="font-size: 16px;">{{ $roleMeta['widget_title'] }}</h5>
                <p class="text-muted mb-0" style="font-size: 12px;">{{ $roleMeta['widget_sub'] }}</p>
            </div>
            <a href="{{ $roleMeta['kpi4_url'] }}" class="btn-nexvo-soft text-decoration-none">
                Lihat Semua Pengajuan
            </a>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0" style="font-size: 13px;">
                <thead>
                    <tr style="color: #64748B; border-bottom: 1px solid #E2E8F0;">
                        <th class="fw-semibold py-2">Tanggal</th>
                        @if($role === 'koordiv')
                            <th class="fw-semibold py-2">Pemohon</th>
                        @endif
                        <th class="fw-semibold py-2">Keperluan / Judul</th>
                        <th class="fw-semibold py-2">Nominal</th>
                        <th class="fw-semibold py-2">Status</th>
                        <th class="fw-semibold py-2 text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentPendingProposals as $prop)
                    @php
                        $badge = $statusLabels[$prop->status] ?? ['label' => ucfirst($prop->status), 'bg' => '#E2E8F0', 'color' => '#334155'];
                    @endphp
                    <tr class="row-clickable" onclick="window.location='{{ $roleMeta['kpi4_url'] }}'">
                        <td class="py-3 text-muted">{{ $prop->created_at->format('d M Y') }}</td>
                        @if($role === 'koordiv')
                            <td class="py-3 fw-medium">{{ $prop->user?->name ?? '-' }}</td>
                        @endif
                        <td class="py-3">
                            <div class="fw-bold" style="color: #1E293B;">{{ $prop->title ?? 'Pengajuan Dana' }}</div>
                            <div class="text-muted text-truncate" style="font-size: 11.5px; max-width: 360px;">{{ $prop->description }}</div>
                        </td>
                        <td class="py-3 fw-bold">Rp {{ number_format($prop->amount, 0, ',', '.') }}</td>
                        <td class="py-3">
                            <span class="badge rounded-2 px-2.5 py-1 fw-medium" style="background-color: {{ $badge['bg'] }}; color: {{ $badge['color'] }}; font-size: 11px;">
                                {{ $badge['label'] }}
                            </span>
                        </td>
                        <td class="py-3 text-end">
                            <a href="{{ $roleMeta['kpi4_url'] }}" class="btn-nexvo-dark py-1 px-3 text-decoration-none" style="font-size: 11.5px;">
                                {{ $roleMeta['widget_btn'] }}
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $role === 'koordiv' ? 6 : 5 }}" class="text-center text-muted py-4">
                            {{ $role === 'koordiv' ? 'Tidak ada permohonan anggota divisi yang menunggu persetujuan.' : 'Belum ada pengajuan dana yang Anda buat.' }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const allLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    const allIncome = @json($monthlyIncomeChart);
    const allExpense = @json($monthlyExpenseChart);

    let financialChartInstance = null;
    const ctx = document.getElementById('financialChart');

    if (ctx) {
        financialChartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: allLabels,
                datasets: [
                    {
                        label: 'Pemasukan (Jt)',
                        data: allIncome,
                        backgroundColor: '#607990',
                        borderRadius: 4,
                        barPercentage: 0.65
                    },
                    {
                        label: 'Pengeluaran (Jt)',
                        data: allExpense,
                        backgroundColor: '#243242',
                        borderRadius: 4,
                        barPercentage: 0.65
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
                                return context.dataset.label + ': Rp ' + context.parsed.y.toLocaleString('id-ID') + ' Jt';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            font: { size: 11 },
                            callback: value => 'Rp ' + value + ' Jt'
                        },
                        grid: { color: '#F1F5F9' },
                        border: { display: false }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11 } },
                        border: { display: false }
                    }
                }
            }
        });
    }

    function switchChartRange(range) {
        if (!financialChartInstance) return;

        const btnH1 = document.getElementById('btnChartSemester1');
        const btnH2 = document.getElementById('btnChartSemester2');
        const btnAll = document.getElementById('btnChartFullYear');

        [btnH1, btnH2, btnAll].forEach(b => {
            b.style.backgroundColor = '#FFFFFF';
            b.classList.remove('fw-semibold');
        });

        if (range === 'h1') {
            financialChartInstance.data.labels = allLabels.slice(0, 6);
            financialChartInstance.data.datasets[0].data = allIncome.slice(0, 6);
            financialChartInstance.data.datasets[1].data = allExpense.slice(0, 6);
            btnH1.style.backgroundColor = '#C5D3DF';
            btnH1.classList.add('fw-semibold');
        } else if (range === 'h2') {
            financialChartInstance.data.labels = allLabels.slice(6, 12);
            financialChartInstance.data.datasets[0].data = allIncome.slice(6, 12);
            financialChartInstance.data.datasets[1].data = allExpense.slice(6, 12);
            btnH2.style.backgroundColor = '#C5D3DF';
            btnH2.classList.add('fw-semibold');
        } else {
            financialChartInstance.data.labels = allLabels;
            financialChartInstance.data.datasets[0].data = allIncome;
            financialChartInstance.data.datasets[1].data = allExpense;
            btnAll.style.backgroundColor = '#C5D3DF';
            btnAll.classList.add('fw-semibold');
        }

        financialChartInstance.update();
    }
</script>
@endpush