@extends('layouts.app')

@section('content')
@php
    $activeUsersCount = \App\Models\User::where('is_active', true)->count();
    $inactiveUsersCount = \App\Models\User::where('is_active', false)->count();
    $activeDivisionsCount = \App\Models\Division::where('is_active', true)->count();
    $todayLogsCount = \App\Models\ActivityLog::whereDate('created_at', today())->count();

    // Distribusi pengguna per divisi
    $divisions = \App\Models\Division::withCount('members')->orderBy('name')->get();
    $divisionLabels = $divisions->pluck('name')->values()->all();
    $divisionCounts = $divisions->pluck('members_count')->values()->all();

    // Fallback jika tabel divisions masih kosong (ambil dari kolom string division di users)
    if (empty($divisionLabels)) {
        $grouped = \App\Models\User::whereNotNull('division')
            ->where('division', '!=', '')
            ->selectRaw('division, count(*) as total')
            ->groupBy('division')
            ->get();
        $divisionLabels = $grouped->pluck('division')->values()->all();
        $divisionCounts = $grouped->pluck('total')->values()->all();
    }

    $recentUsers = \App\Models\User::latest('updated_at')->take(5)->get();
    $recentSystemLogs = \App\Models\ActivityLog::latest()->take(5)->get();
@endphp

<style>
    .admin-kpi-dark {
        background-color: #2F4156;
        color: #FFFFFF;
        border-radius: 14px;
        border: none;
    }
    .admin-kpi-light {
        background-color: #FFFFFF;
        color: #1E293B;
        border-radius: 14px;
        border: 1px solid #E5E7EB;
    }
    .quick-action-btn {
        border: 1.5px solid #E2E8F0;
        background-color: #FAFAFA;
        color: #2F4156;
        border-radius: 12px;
        padding: 14px 16px;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: all 0.15s ease;
    }
    .quick-action-btn:hover {
        background-color: #C8D9E6;
        border-color: #C8D9E6;
        color: #1E293B;
    }
</style>

<!-- 1. HEADER DASHBOARD ADMIN -->
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h3 class="fw-bold mb-1" style="color: #2F4156;">Dashboard Administrator</h3>
        <p class="text-muted mb-0" style="font-size: 12.5px;">
            Ringkasan tata kelola pengguna, struktur divisi, dan pemantauan aktivitas sistem Nexvo Finance.
        </p>
    </div>

    <div class="d-flex flex-wrap align-items-center gap-2">
        <div class="bg-white border rounded-3 px-3 py-2 d-flex align-items-center gap-2" style="font-size: 12px; color: #334155; border-color: #E2E8F0 !important;">
            <i class="bi bi-calendar3 text-muted"></i>
            <span>{{ now()->locale('id')->translatedFormat('l, d F Y') }}</span>
        </div>

        <a href="{{ route('admin.users.index', ['modal' => 'create']) }}" class="btn rounded-3 px-3 py-2 fw-medium text-white" style="background-color: #2F4156; font-size: 12px;">
            <i class="bi bi-person-plus me-1"></i> + Tambah Pengguna
        </a>
    </div>
</div>

<!-- 2. SUMMARY CARDS (4 KARTU ATAS) -->
<div class="row g-3 mb-4">
    <!-- Total Pengguna Aktif -->
    <div class="col-md-6 col-xl-3">
        <div class="admin-kpi-dark p-4 h-100 shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span style="font-size: 13px; color: #E2E8F0; font-weight: 500;">Total Pengguna Aktif</span>
                <span class="rounded-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px; background-color: rgba(255,255,255,0.12); color: #FFFFFF;">
                    <i class="bi bi-people small"></i>
                </span>
            </div>
            <h4 class="fw-bold mb-2" style="font-size: 22px;">{{ $activeUsersCount }} Pengguna</h4>
            <div style="font-size: 11.5px; color: #94A3B8;">Akun memiliki akses aktif</div>
        </div>
    </div>

    <!-- Total Divisi Aktif -->
    <div class="col-md-6 col-xl-3">
        <div class="admin-kpi-light p-4 h-100 shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="fw-medium" style="font-size: 13px; color: #475569;">Total Divisi Aktif</span>
                <span class="rounded-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px; background-color: #E8EEF3; color: #567C8D;">
                    <i class="bi bi-diagram-3 small"></i>
                </span>
            </div>
            <h4 class="fw-bold mb-2" style="font-size: 22px; color: #2F4156;">{{ $activeDivisionsCount }} Divisi</h4>
            <div class="text-muted" style="font-size: 11.5px;">Unit kerja berjalan di studio</div>
        </div>
    </div>

    <!-- Akun Nonaktif / Ditangguhkan -->
    <div class="col-md-6 col-xl-3">
        <div class="admin-kpi-light p-4 h-100 shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="fw-medium" style="font-size: 13px; color: #475569;">Akun Nonaktif</span>
                <span class="rounded-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px; background-color: #FEE2E2; color: #B91C1C;">
                    <i class="bi bi-person-slash small"></i>
                </span>
            </div>
            <h4 class="fw-bold mb-2" style="font-size: 22px; color: #2F4156;">{{ $inactiveUsersCount }} Akun</h4>
            <div class="text-muted" style="font-size: 11.5px;">Akses ditangguhkan sementara</div>
        </div>
    </div>

    <!-- Reset Sandi / Log Hari Ini -->
    <div class="col-md-6 col-xl-3">
        <div class="admin-kpi-light p-4 h-100 shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="fw-medium" style="font-size: 13px; color: #475569;">Log Aktivitas Hari Ini</span>
                <span class="rounded-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px; background-color: #E8EEF3; color: #567C8D;">
                    <i class="bi bi-shield-check small"></i>
                </span>
            </div>
            <h4 class="fw-bold mb-2" style="font-size: 22px; color: #2F4156;">{{ str_pad($todayLogsCount, 2, '0', STR_PAD_LEFT) }} Aktivitas</h4>
            <div class="text-muted" style="font-size: 11.5px;">Tercatat pada {{ now()->locale('id')->translatedFormat('d M Y') }}</div>
        </div>
    </div>
</div>

<!-- 3. BARIS TENGAH: DISTRIBUSI PENGGUNA PER DIVISI & AKSES CEPAT -->
<div class="row g-3 mb-4">
    <!-- Kiri: Chart Distribusi Pengguna per Divisi -->
    <div class="col-lg-8">
        <div class="nexvo-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h5 class="fw-bold mb-1" style="font-size: 16px; color: #2F4156;">Distribusi Pengguna per Divisi</h5>
                    <p class="text-muted mb-0" style="font-size: 12px;">Komposisi jumlah anggota yang terpetakan pada masing-masing divisi.</p>
                </div>
                <a href="{{ route('admin.divisions.index') }}" class="btn btn-sm rounded-3 px-3 py-1 fw-medium" style="background-color: #E8EEF3; color: #2F4156; font-size: 12px;">
                    Kelola Divisi
                </a>
            </div>

            <div style="height: 230px;">
                <canvas id="divisionDistributionChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Kanan: Akses Cepat (Quick Actions) -->
    <div class="col-lg-4">
        <div class="nexvo-card p-4 h-100 d-flex flex-column justify-content-between">
            <div>
                <h5 class="fw-bold mb-1" style="font-size: 16px; color: #2F4156;">Akses Cepat</h5>
                <p class="text-muted mb-3" style="font-size: 12px;">Pintasan tugas administratif data master.</p>

                <div class="d-flex flex-column gap-2.5">
                    <a href="{{ route('admin.users.index', ['modal' => 'create']) }}" class="quick-action-btn">
                        <div class="d-flex align-items-center gap-3">
                            <span class="rounded-3 d-flex align-items-center justify-content-center text-white" style="width: 36px; height: 36px; background-color: #2F4156;">
                                <i class="bi bi-person-plus"></i>
                            </span>
                            <div>
                                <div class="fw-bold" style="font-size: 13px;">+ Tambah Pengguna Baru</div>
                                <div class="text-muted" style="font-size: 11px;">Buat akun &amp; tetapkan peran</div>
                            </div>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </a>

                    <a href="{{ route('admin.divisions.index', ['modal' => 'create']) }}" class="quick-action-btn">
                        <div class="d-flex align-items-center gap-3">
                            <span class="rounded-3 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background-color: #C8D9E6; color: #2F4156;">
                                <i class="bi bi-diagram-3"></i>
                            </span>
                            <div>
                                <div class="fw-bold" style="font-size: 13px;">+ Tambah Divisi Baru</div>
                                <div class="text-muted" style="font-size: 11px;">Buat unit &amp; tunjuk Koordinator</div>
                            </div>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </a>

                    <a href="{{ route('admin.users.index') }}" class="quick-action-btn">
                        <div class="d-flex align-items-center gap-3">
                            <span class="rounded-3 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background-color: #E8EEF3; color: #567C8D;">
                                <i class="bi bi-key"></i>
                            </span>
                            <div>
                                <div class="fw-bold" style="font-size: 13px;">Reset Kata Sandi &amp; Peran</div>
                                <div class="text-muted" style="font-size: 11px;">Pemulihan akses &amp; status akun</div>
                            </div>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </a>
                </div>
            </div>

            <div class="p-3 rounded-3 mt-3" style="background-color: #F4EFEA; font-size: 11.5px; color: #475569;">
                <i class="bi bi-info-circle me-1 fw-bold"></i>
                Admin mengelola data master sistem &amp; struktur organisasi terpisah dari transaksi kas.
            </div>
        </div>
    </div>
</div>

<!-- 4. BARIS BAWAH: PENGGUNA TERBARU & LOG AKTIVITAS SISTEM -->
<div class="row g-3">
    <!-- Kiri: Tabel Pengguna Terbaru -->
    <div class="col-lg-8">
        <div class="nexvo-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h5 class="fw-bold mb-1" style="font-size: 16px; color: #2F4156;">Pengguna Terbaru</h5>
                    <p class="text-muted mb-0" style="font-size: 12px;">Akun yang baru ditambahkan atau diperbarui perannya.</p>
                </div>
                <a href="{{ route('admin.users.index') }}" class="btn btn-sm rounded-3 px-3 py-1 fw-medium" style="background-color: #C8D9E6; color: #2F4156; font-size: 12px;">
                    Lihat Semua
                </a>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0" style="font-size: 12.5px;">
                    <thead>
                        <tr style="color: #64748B; border-bottom: 1px solid #E2E8F0;">
                            <th class="fw-medium py-2">Nama &amp; Email</th>
                            <th class="fw-medium py-2">Peran</th>
                            <th class="fw-medium py-2">Divisi</th>
                            <th class="fw-medium py-2 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentUsers as $u)
                        <tr>
                            <td class="py-2.5">
                                <div class="fw-bold" style="color: #1E293B;">{{ $u->name }}</div>
                                <div class="text-muted" style="font-size: 11px;">{{ $u->email }}</div>
                            </td>
                            <td class="py-2.5">
                                <span class="badge rounded-2 px-2.5 py-1 fw-medium" style="background-color: #E8EEF3; color: #2F4156;">
                                    {{ ucfirst($u->role) }}
                                </span>
                            </td>
                            <td class="py-2.5 text-muted">{{ $u->division ?? '-' }}</td>
                            <td class="py-2.5 text-center">
                                @if($u->is_active ?? true)
                                    <span class="badge rounded-2 px-2.5 py-1 fw-medium" style="background-color: #DCFCE7; color: #15803D;">Aktif</span>
                                @else
                                    <span class="badge rounded-2 px-2.5 py-1 fw-medium" style="background-color: #FEE2E2; color: #B91C1C;">Nonaktif</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">Belum ada data pengguna.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Kanan: Log Aktivitas Sistem -->
    <div class="col-lg-4">
        <div class="nexvo-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h5 class="fw-bold mb-1" style="font-size: 16px; color: #2F4156;">Log Aktivitas Sistem</h5>
                    <p class="text-muted mb-0" style="font-size: 12px;">Riwayat administratif terkini.</p>
                </div>
                <a href="{{ route('audit-logs.index') }}" class="text-decoration-none fw-semibold" style="font-size: 12px; color: #567C8D;">
                    Audit Log
                </a>
            </div>

            <div class="d-flex flex-column gap-3">
                @forelse($recentSystemLogs as $log)
                <div class="d-flex align-items-start gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 mt-1" style="width: 30px; height: 30px; background-color: #E8EEF3; color: #2F4156;">
                        <i class="bi bi-shield-check small"></i>
                    </div>
                    <div>
                        <div class="fw-semibold" style="font-size: 12.5px; color: #1E293B; line-height: 1.35;">
                            {{ $log->action }}
                        </div>
                        <div class="text-muted" style="font-size: 11px;">
                            {{ $log->created_at->locale('id')->diffForHumans() }} &bull; {{ $log->created_at->format('H:i') }} WIB
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center text-muted py-4" style="font-size: 12.5px;">
                    Belum ada aktivitas sistem tercatat.
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
    const ctxDiv = document.getElementById('divisionDistributionChart');
    if (ctxDiv) {
        new Chart(ctxDiv, {
            type: 'bar',
            data: {
                labels: @json($divisionLabels),
                datasets: [{
                    label: 'Jumlah Pengguna',
                    data: @json($divisionCounts),
                    backgroundColor: '#2F4156',
                    hoverBackgroundColor: '#567C8D',
                    borderRadius: 6,
                    barPercentage: 0.45
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => ctx.parsed.y + ' Anggota'
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11.5, family: 'Poppins' }, color: '#475569' }
                    },
                    y: {
                        beginAtZero: true,
                        border: { display: false },
                        grid: { color: '#F1F5F9' },
                        ticks: { stepSize: 1, font: { size: 11, family: 'Poppins' }, color: '#64748B' }
                    }
                }
            }
        });
    }
</script>
@endpush