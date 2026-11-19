@extends('layouts.app')

@section('content')
@php
    $balance = $balance ?? 0;
    $totalIncome = $totalIncome ?? 0;
    $totalExpense = $totalExpense ?? 0;
    $pendingProposals = $pendingProposals ?? 0;
    $recentPendingProposals = $recentPendingProposals ?? collect();
    $recentTransactions = $recentTransactions ?? collect();
@endphp

<!-- Header Dashboard & Filter -->
<div class="d-flex flex-wrap flex-lg-nowrap justify-content-between align-items-start align-items-lg-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold mb-1" style="color: #1E293B;">Dashboard</h3>
        <p class="text-muted mb-0" style="font-size: 13px;">
            Selamat datang kembali, {{ Auth::user()->name }}. Berikut ringkasan keuangan NEXVO Studio.
        </p>
    </div>

    <div class="d-flex flex-wrap flex-xl-nowrap align-items-center gap-2 flex-shrink-0">
        <div class="bg-white border rounded-3 px-3 py-2 d-flex align-items-center gap-2 text-nowrap" style="font-size: 12.5px;">
            <i class="bi bi-calendar3 text-muted"></i>
            <span>{{ \Carbon\Carbon::now()->locale('id')->translatedFormat('l, d F Y') }}</span>
        </div>
        <select class="form-select form-select-sm bg-white border rounded-3 py-2" style="width: auto; font-size: 12.5px;">
            <option>November 2026</option>
            <option>Oktober 2026</option>
        </select>
        <a href="{{ route('transactions.index') }}" class="btn-nexvo-soft text-nowrap">Export Laporan</a>
        <a href="{{ route('proposals.create') }}" class="btn-nexvo-dark text-nowrap">Buat Permintaan</a>
    </div>
</div>

<!-- 4 Kartu Metrik Atas -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="nexvo-card h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted" style="font-size: 12.5px;">Total Saldo Kas</span>
                <span class="badge rounded-2 p-2" style="background-color: #E2E8F0; color: #243242;"><i class="bi bi-wallet2"></i></span>
            </div>
            <h4 class="fw-bold mb-1">Rp {{ number_format($balance, 0, ',', '.') }}</h4>
            <div class="text-muted" style="font-size: 11.5px;">Saldo tercatat saat ini</div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="nexvo-card h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted" style="font-size: 12.5px;">Total Pemasukan</span>
                <span class="badge rounded-2 p-2" style="background-color: #DCE5EE; color: #47637E;"><i class="bi bi-graph-up-arrow"></i></span>
            </div>
            <h4 class="fw-bold mb-1">Rp {{ number_format($totalIncome, 0, ',', '.') }}</h4>
            <div class="text-muted" style="font-size: 11.5px;">Periode November 2026</div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="nexvo-card h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted" style="font-size: 12.5px;">Total Pengeluaran</span>
                <span class="badge rounded-2 p-2" style="background-color: #DCE5EE; color: #243242;"><i class="bi bi-graph-down-arrow"></i></span>
            </div>
            <h4 class="fw-bold mb-1">Rp {{ number_format($totalExpense, 0, ',', '.') }}</h4>
            <div class="text-muted" style="font-size: 11.5px;">Periode November 2026</div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="nexvo-card h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted" style="font-size: 12.5px;">Menunggu Persetujuan</span>
                <span class="badge rounded-2 p-2" style="background-color: #EFECE8; color: #243242;"><i class="bi bi-clock"></i></span>
            </div>
            <h4 class="fw-bold mb-1">{{ str_pad($pendingProposals, 2, '0', STR_PAD_LEFT) }} Pengajuan</h4>
            <div class="text-muted" style="font-size: 11.5px;">Memerlukan tindakan Anda</div>
        </div>
    </div>
</div>

<!-- Baris Tengah: Financial Overview (Kiri) & Pending Approvals (Kanan) -->
<div class="row g-3 mb-4">
    <!-- Grafik Financial Overview -->
    <div class="col-lg-8">
        <div class="nexvo-card h-100 d-flex flex-column justify-content-between">
            <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
                <div>
                    <h5 class="fw-bold mb-0" style="font-size: 16px;">Financial Overview</h5>
                    <p class="text-muted mb-2" style="font-size: 12px;">Ringkasan pemasukan dan pengeluaran organisasi.</p>
                    <div class="d-flex align-items-center gap-3" style="font-size: 11.5px;">
                        <span><i class="bi bi-circle-fill me-1" style="color: #607990;"></i> Pemasukan</span>
                        <span><i class="bi bi-circle-fill me-1" style="color: #243242;"></i> Pengeluaran</span>
                    </div>
                </div>
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-light border" style="font-size: 11.5px;">Mingguan</button>
                    <button type="button" class="btn btn-light border" style="font-size: 11.5px;">Bulanan</button>
                    <button type="button" class="btn border fw-semibold" style="background-color: #C5D3DF; font-size: 11.5px;">Tahunan</button>
                </div>
            </div>

            <div style="height: 210px;">
                <canvas id="financialChart"></canvas>
            </div>

            <div class="text-muted mt-2" style="font-size: 11px;">
                November: Rp {{ number_format($totalIncome / 1000000, 1, ',', '.') }} jt masuk / Rp {{ number_format($totalExpense / 1000000, 1, ',', '.') }} jt keluar • Desember belum ada data
            </div>
        </div>
    </div>

    <!-- Widget Pending Approvals -->
    <div class="col-lg-4">
        <div class="nexvo-card h-100 d-flex flex-column justify-content-between">
            <div>
                <h5 class="fw-bold mb-0" style="font-size: 16px;">Pending Approvals</h5>
                <p class="text-muted mb-3" style="font-size: 12px;">Pengajuan dana yang membutuhkan persetujuan Anda.</p>

                <div class="d-flex flex-column gap-2">
                    @forelse($recentPendingProposals as $prop)
                    <div class="d-flex justify-content-between align-items-center p-2 px-3 rounded-3" style="background-color: #EFECE8;">
                        <div>
                            <div class="fw-bold" style="font-size: 13px;">{{ $prop->user?->name ?? 'Pengguna' }}</div>
                            <div class="text-muted" style="font-size: 11px;">{{ $prop->user?->division ?? 'Divisi' }}</div>
                        </div>
                        <div class="text-end me-2">
                            <div class="fw-bold" style="font-size: 12.5px;">Rp {{ number_format($prop->amount, 0, ',', '.') }}</div>
                            <div class="text-muted" style="font-size: 10.5px;">{{ $prop->created_at->format('d M Y') }}</div>
                        </div>
                        <a href="{{ route('proposals.index', ['tab' => Auth::user()->role === 'anggota' ? 'my' : 'approvals']) }}" class="btn-nexvo-dark py-1 px-3" style="font-size: 11.5px;">Review</a>
                    </div>
                    @empty
                    <div class="text-center text-muted py-4" style="font-size: 12.5px;">
                        Tidak ada pengajuan pending saat ini.
                    </div>
                    @endforelse
                </div>
            </div>

            <a href="{{ route('proposals.index', ['tab' => Auth::user()->role === 'anggota' ? 'my' : 'approvals']) }}" class="btn-nexvo-soft w-100 text-center mt-3 py-2">
                Lihat Semua Pengajuan
            </a>
        </div>
    </div>
</div>

<!-- Baris Bawah: Recent Transactions (Kiri) & Recent Activities (Kanan) -->
<div class="row g-3">
    <!-- Recent Transactions -->
    <div class="col-lg-8">
        <div class="nexvo-card h-100 d-flex flex-column justify-content-between">
            <div>
                <h5 class="fw-bold mb-0" style="font-size: 16px;">Recent Transactions</h5>
                <p class="text-muted mb-3" style="font-size: 12px;">Aktivitas transaksi keuangan terbaru.</p>

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
                            <tr>
                                <td class="py-2 text-muted">{{ \Carbon\Carbon::parse($trx->transaction_date)->format('d M Y') }}</td>
                                <td class="py-2 fw-medium">{{ $trx->description ?? ($trx->category?->name ?? '-') }}</td>
                                <td class="py-2" style="color: #607990;">{{ $trx->category?->name ?? '-' }}</td>
                                <td class="py-2 text-end fw-bold">
                                    @if(($trx->category?->type ?? 'expense') === 'income')
                                        +Rp {{ number_format($trx->amount, 0, ',', '.') }}
                                    @else
                                        -Rp {{ number_format($trx->amount, 0, ',', '.') }}
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">Belum ada transaksi tercatat.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <a href="{{ route('transactions.index') }}" class="btn-nexvo-soft w-100 text-center mt-3 py-2">
                Lihat Semua Transaksi
            </a>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="col-lg-4">
        <div class="nexvo-card h-100">
            <h5 class="fw-bold mb-0" style="font-size: 16px;">Recent Activities</h5>
            <p class="text-muted mb-3" style="font-size: 12px;">Aktivitas terbaru organisasi.</p>

            <div class="d-flex flex-column gap-3">
                @php
                    $latestLogs = \App\Models\ActivityLog::latest()->take(4)->get();
                @endphp
                @forelse($latestLogs as $log)
                <div class="d-flex align-items-start gap-2">
                    <span class="badge rounded-circle p-2" style="background-color: #DCE5EE; color: #243242;"><i class="bi bi-check2-circle"></i></span>
                    <div>
                        <div class="fw-semibold" style="font-size: 12px;">{{ $log->action }}</div>
                        <div class="text-muted" style="font-size: 10.5px;">{{ $log->created_at->locale('id')->diffForHumans() }}, {{ $log->created_at->format('H:i') }} WIB</div>
                    </div>
                </div>
                @empty
                <div class="text-center text-muted py-4" style="font-size: 12.5px;">
                    Belum ada aktivitas terbaru tercatat.
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('financialChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
                datasets: [
                    {
                        label: 'Pemasukan',
                        data: [4, 6, 5, 7, 8, 9, 8.5, 9.5, 10, 11, 8.5, 0],
                        backgroundColor: '#607990',
                        borderRadius: 4,
                        barPercentage: 0.65
                    },
                    {
                        label: 'Pengeluaran',
                        data: [2, 3, 4, 3.5, 4.5, 5, 5.5, 6, 6.5, 7, 3.25, 0],
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
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 12,
                        ticks: { stepSize: 4, font: { size: 11 } },
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
</script>
@endpush