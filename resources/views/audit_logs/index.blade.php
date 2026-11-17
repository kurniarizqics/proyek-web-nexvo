@extends('layouts.app')

@section('content')
@php
    $periodLabel = $selectedPeriod === 'all'
        ? 'semua periode'
        : strtolower(\Carbon\Carbon::parse($selectedPeriod . '-01')->locale('id')->translatedFormat('F Y'));
@endphp

<!-- Breadcrumb & Header Halaman -->
<div class="mb-4">
    <div class="text-muted mb-1" style="font-size: 12px;">
        Audit Log <span class="mx-1">/</span> <span class="text-dark fw-medium">Aktivitas Pengguna</span>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h3 class="fw-bold mb-1" style="color: #1E293B;">Aktivitas Pengguna</h3>
            <p class="text-muted mb-0" style="font-size: 13px;">Pantau seluruh riwayat aktivitas dan akses pengguna dalam sistem keuangan organisasi.</p>
        </div>

        <span class="badge rounded-3 px-3 py-2 fw-medium d-inline-flex align-items-center gap-2" style="background-color: #E2E8F0; color: #475569; font-size: 12px;">
            <i class="bi bi-lock"></i> Hanya Baca
        </span>
    </div>
</div>

<!-- 3 Kartu Ringkasan Aktivitas -->
<div class="row g-3 mb-4">
    <!-- Total Aktivitas -->
    <div class="col-md-4">
        <div class="nexvo-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-medium" style="font-size: 13px; color: #475569;">Total Aktivitas</span>
                <span class="rounded-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px; background-color: #F1F5F9; color: #334155;">
                    <i class="bi bi-person fs-6"></i>
                </span>
            </div>
            <h3 class="fw-bold mb-1" style="color: #2E6B62;">{{ $totalActivities }}</h3>
            <div class="text-muted" style="font-size: 11.5px;">Akumulasi aktivitas tercatat pada {{ $periodLabel }}.</div>
        </div>
    </div>

    <!-- Pengguna Aktif -->
    <div class="col-md-4">
        <div class="nexvo-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-medium" style="font-size: 13px; color: #475569;">Pengguna Aktif</span>
                <span class="rounded-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px; background-color: #F1F5F9; color: #334155;">
                    <i class="bi bi-person-check fs-6"></i>
                </span>
            </div>
            <h3 class="fw-bold mb-1" style="color: #36A142;">{{ $activeUsersCount }}</h3>
            <div class="text-muted" style="font-size: 11.5px;">Total pengguna unik beraktivitas pada {{ $periodLabel }}.</div>
        </div>
    </div>

    <!-- Aktivitas Gagal -->
    <div class="col-md-4">
        <div class="nexvo-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-medium" style="font-size: 13px; color: #475569;">Aktivitas Gagal</span>
                <span class="rounded-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px; background-color: #FEF2F2; color: #B91C1C;">
                    <i class="bi bi-clock-history fs-6"></i>
                </span>
            </div>
            <h3 class="fw-bold mb-1" style="color: #B4433B;">{{ $failedActivities }}</h3>
            <div class="text-muted" style="font-size: 11.5px;">Percobaan akses atau aksi gagal pada {{ $periodLabel }}.</div>
        </div>
    </div>
</div>

<!-- Bar Filter Aktivitas, Periode, Jenis Aktivitas, & Reset Filter -->
<div class="row g-3 align-items-end mb-4">
    <div class="col-lg-5">
        <label class="form-label text-muted mb-1" style="font-size: 12px;">Filter Aktivitas</label>
        <div class="position-relative">
            <i class="bi bi-search text-muted position-absolute" style="top: 50%; left: 14px; transform: translateY(-50%); font-size: 13px;"></i>
            <input type="text" id="searchActivity" class="form-control ps-5 rounded-3 bg-white" placeholder="Cari nama pengguna atau aktivitas..." style="font-size: 13px; border-color: #E2E8F0; height: 42px;">
        </div>
    </div>

    <div class="col-md-4 col-lg-3">
        <label class="form-label text-muted mb-1" style="font-size: 12px;">Periode</label>
        <form method="GET" action="{{ route('audit-logs.index') }}" id="auditPeriodForm">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0 rounded-start-3 text-muted" style="border-color: #E2E8F0;">
                    <i class="bi bi-calendar3"></i>
                </span>
                <select name="period" class="form-select border-start-0 rounded-end-3 bg-white" style="font-size: 13px; border-color: #E2E8F0; height: 42px;" onchange="document.getElementById('auditPeriodForm').submit()">
                    <option value="all" {{ $selectedPeriod === 'all' ? 'selected' : '' }}>Semua Periode</option>
                    @foreach($availablePeriods as $per)
                        <option value="{{ $per }}" {{ $selectedPeriod === $per ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::parse($per . '-01')->locale('id')->translatedFormat('F Y') }}
                        </option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    <div class="col-md-4 col-lg-2">
        <label class="form-label text-muted mb-1" style="font-size: 12px;">Jenis aktivitas</label>
        <select id="statusActivityFilter" class="form-select rounded-3 bg-white" style="font-size: 13px; border-color: #E2E8F0; height: 42px;">
            <option value="all">Semua Jenis</option>
            <option value="success">Berhasil</option>
            <option value="failed">Gagal</option>
        </select>
    </div>

    <div class="col-md-4 col-lg-2">
        <button type="button" id="resetActivityFilterBtn" class="btn btn-outline-secondary w-100 rounded-3 bg-white d-flex align-items-center justify-content-center gap-2" style="font-size: 13px; border-color: #E2E8F0; height: 42px; color: #334155;">
            <i class="bi bi-arrow-counterclockwise"></i> Reset Filter
        </button>
    </div>
</div>

<!-- Card Tabel Riwayat Aktivitas Pengguna -->
<div class="nexvo-card p-4 mb-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start mb-3 gap-2">
        <div>
            <h5 class="fw-bold mb-1" style="font-size: 16px; color: #1E293B;">Riwayat Aktivitas Pengguna</h5>
            <p class="text-muted mb-0" style="font-size: 12px;">Daftar seluruh aktivitas pengguna</p>
        </div>
        <span class="badge rounded-3 px-3 py-2 fw-medium" style="background-color: #F1F5F9; color: #475569; font-size: 12px;">
            Menampilkan <span id="topActivityCount">{{ $logs->count() }}</span> aktivitas
        </span>
    </div>

    <div class="table-responsive">
        <table class="table align-middle mb-0" style="font-size: 13px;">
            <thead style="background-color: #F4F7FA; color: #64748B;">
                <tr>
                    <th class="fw-medium py-3 ps-3 rounded-start-3">Tanggal</th>
                    <th class="fw-medium py-3">Waktu</th>
                    <th class="fw-medium py-3">Nama Pengguna</th>
                    <th class="fw-medium py-3">Keterangan</th>
                    <th class="fw-medium py-3">Peran</th>
                    <th class="fw-medium py-3 text-center pe-3 rounded-end-3">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                @php
                    $isSuccess = ($log->status === 'success');
                @endphp
                <tr class="activity-row"
                    data-status="{{ $log->status }}"
                    data-search="{{ strtolower($log->user_name . ' ' . $log->description . ' ' . $log->user_role) }}">
                    <td class="py-3 ps-3 text-muted">{{ $log->created_at->locale('id')->translatedFormat('d M Y') }}</td>
                    <td class="py-3 text-muted">{{ $log->created_at->format('H:i') }}</td>
                    <td class="py-3 fw-medium" style="color: #1E293B;">{{ $log->user_name }}</td>
                    <td class="py-3" style="color: #334155;">{{ $log->description }}</td>
                    <td class="py-3" style="color: #475569;">{{ $log->user_role }}</td>
                    <td class="py-3 text-center pe-3">
                        @if($isSuccess)
                            <span class="d-inline-block rounded-2 px-4 py-1 fw-medium" style="border: 1px solid #CBD5E1; color: #36A142; font-size: 12px; min-width: 105px;">
                                Berhasil
                            </span>
                        @else
                            <span class="d-inline-block rounded-2 px-4 py-1 fw-medium" style="border: 1px solid #CBD5E1; color: #B4433B; font-size: 12px; min-width: 105px;">
                                Gagal
                            </span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-5">
                        Belum ada aktivitas pengguna yang tercatat pada periode ini.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Footer Tabel -->
    <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top" style="font-size: 12px; color: #64748B;">
        <div>
            Menampilkan {{ $logs->count() > 0 ? '1–' : '' }}<span id="bottomActivityCount">{{ $logs->count() }}</span> dari {{ $logs->count() }} aktivitas
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted px-2">&lsaquo;</span>
            <span class="badge rounded-2 px-3 py-2" style="background-color: #607990; color: white;">1</span>
            <span class="text-muted px-2">&rsaquo;</span>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const searchActivity = document.getElementById('searchActivity');
    const statusActivityFilter = document.getElementById('statusActivityFilter');
    const resetActivityFilterBtn = document.getElementById('resetActivityFilterBtn');
    const activityRows = document.querySelectorAll('.activity-row');
    const topActivityCount = document.getElementById('topActivityCount');
    const bottomActivityCount = document.getElementById('bottomActivityCount');

    function filterActivities() {
        const query = searchActivity ? searchActivity.value.toLowerCase() : '';
        const status = statusActivityFilter ? statusActivityFilter.value : 'all';
        let count = 0;

        activityRows.forEach(row => {
            const matchText = row.dataset.search.includes(query);
            const matchStatus = (status === 'all') || (row.dataset.status === status);

            if (matchText && matchStatus) {
                row.style.display = '';
                count++;
            } else {
                row.style.display = 'none';
            }
        });

        if (topActivityCount) topActivityCount.textContent = count;
        if (bottomActivityCount) bottomActivityCount.textContent = count;
    }

    if (searchActivity) searchActivity.addEventListener('input', filterActivities);
    if (statusActivityFilter) statusActivityFilter.addEventListener('change', filterActivities);
    if (resetActivityFilterBtn) {
        resetActivityFilterBtn.addEventListener('click', () => {
            if (searchActivity) searchActivity.value = '';
            if (statusActivityFilter) statusActivityFilter.value = 'all';
            filterActivities();
        });
    }
</script>
@endpush