@extends('layouts.app')

@section('content')
@php
    $startCarbon = \Carbon\Carbon::parse($startDate);
    $endCarbon = \Carbon\Carbon::parse($endDate);
    $periodText = $startCarbon->locale('id')->translatedFormat('d F Y') . ' - ' . $endCarbon->locale('id')->translatedFormat('d F Y');
    $beforeStartText = $startCarbon->locale('id')->translatedFormat('d F Y');
@endphp

<!-- Breadcrumb & Header Halaman -->
<div class="mb-4">
    <div class="text-muted mb-1" style="font-size: 12px;">
        Transaksi &amp; Bukti Kas <span class="mx-1">/</span> <span class="text-dark fw-medium">Laporan Keuangan</span>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h3 class="fw-bold mb-1" style="color: #1E293B;">Laporan Keuangan</h3>
            <p class="text-muted mb-0" style="font-size: 13px;">Buat, tinjau, dan unduh rekapitulasi arus kas organisasi berdasarkan periode.</p>
        </div>

        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn rounded-3 px-4 py-2 text-white fw-medium d-inline-flex align-items-center gap-2" style="background-color: #547285; font-size: 13px;" data-bs-toggle="modal" data-bs-target="#confirmPdfModal">
                <i class="bi bi-file-earmark-text"></i> Unduh PDF
            </button>
            <button type="button" class="btn btn-outline-secondary rounded-3 px-4 py-2 bg-white fw-medium d-inline-flex align-items-center gap-2" style="font-size: 13px; border-color: #CBD5E1; color: #1E293B;" data-bs-toggle="modal" data-bs-target="#confirmExcelModal">
                <i class="bi bi-table"></i> Unduh Excel
            </button>
        </div>
    </div>
</div>

<!-- Card Filter Periode Laporan -->
<div class="nexvo-card p-4 mb-4">
    <h6 class="fw-bold mb-3" style="font-size: 15px; color: #1E293B;">Filter Periode Laporan</h6>

    <form method="GET" action="{{ route('reports.index') }}">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label text-muted mb-1" style="font-size: 12px;">Dari Tanggal</label>
                <input type="date" name="start_date" value="{{ $startDate }}" class="form-control rounded-3" style="font-size: 13px; border-color: #E2E8F0; height: 42px;">
            </div>

            <div class="col-md-3">
                <label class="form-label text-muted mb-1" style="font-size: 12px;">Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ $endDate }}" class="form-control rounded-3" style="font-size: 13px; border-color: #E2E8F0; height: 42px;">
            </div>

            <div class="col-md-3">
                <label class="form-label text-muted mb-1" style="font-size: 12px;">Kategori Transaksi</label>
                <select name="type" class="form-select rounded-3" style="font-size: 13px; border-color: #E2E8F0; height: 42px;">
                    <option value="all" {{ $typeFilter === 'all' ? 'selected' : '' }}>Semua Kategori</option>
                    <option value="income" {{ $typeFilter === 'income' ? 'selected' : '' }}>Pemasukan Saja</option>
                    <option value="expense" {{ $typeFilter === 'expense' ? 'selected' : '' }}>Pengeluaran Saja</option>
                </select>
            </div>

            <div class="col-md-3">
                <button type="submit" class="btn-nexvo-dark w-100 d-flex align-items-center justify-content-center gap-2" style="height: 42px; font-size: 13px;">
                    <i class="bi bi-filter"></i> Terapkan Filter
                </button>
            </div>
        </div>
    </form>

    <div class="text-muted mt-3" style="font-size: 11.5px;">
        Periode laporan: {{ $periodText }} &bull; Filter diterapkan
    </div>
</div>

<!-- 3 Kartu Ringkasan -->
<div class="row g-3 mb-4">
    <!-- Total Pemasukan -->
    <div class="col-md-4">
        <div class="nexvo-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-medium" style="font-size: 13px; color: #475569;">Total Pemasukan</span>
                <span class="rounded-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px; background-color: #F4F1EA; color: #64748B;">
                    <i class="bi bi-graph-up-arrow small"></i>
                </span>
            </div>
            <h3 class="fw-bold mb-1" style="color: #46657A;">Rp {{ number_format($totalIncome, 0, ',', '.') }}</h3>
            <div class="text-muted" style="font-size: 11.5px;">Total kas masuk pada periode ini.</div>
        </div>
    </div>

    <!-- Total Pengeluaran -->
    <div class="col-md-4">
        <div class="nexvo-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-medium" style="font-size: 13px; color: #475569;">Total Pengeluaran</span>
                <span class="rounded-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px; background-color: #F4F1EA; color: #64748B;">
                    <i class="bi bi-graph-down-arrow small"></i>
                </span>
            </div>
            <h3 class="fw-bold mb-1" style="color: #1E293B;">Rp {{ number_format($totalExpense, 0, ',', '.') }}</h3>
            <div class="text-muted" style="font-size: 11.5px;">Total kas keluar pada periode ini.</div>
        </div>
    </div>

    <!-- Saldo Akhir -->
    <div class="col-md-4">
        <div class="nexvo-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-medium" style="font-size: 13px; color: #475569;">Saldo Akhir</span>
                <span class="rounded-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px; background-color: #F4F1EA; color: #64748B;">
                    <i class="bi bi-wallet2 small"></i>
                </span>
            </div>
            <h3 class="fw-bold mb-1" style="color: #1E293B;">Rp {{ number_format($endingBalance, 0, ',', '.') }}</h3>
            <div class="text-muted" style="font-size: 11.5px;">Saldo akhir setelah seluruh transaksi periode dihitung.</div>
            <div class="text-muted mt-2" style="font-size: 11px;">Saldo Awal: Rp {{ number_format($openingBalance, 0, ',', '.') }}</div>
        </div>
    </div>
</div>

<!-- Card Pratinjau Laporan Transaksi -->
<div class="nexvo-card p-4 mb-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start mb-3 gap-2">
        <div>
            <h5 class="fw-bold mb-1" style="font-size: 16px; color: #1E293B;">Pratinjau Laporan Transaksi</h5>
            <p class="text-muted mb-0" style="font-size: 12px;">Rincian transaksi kas organisasi berdasarkan periode yang dipilih.</p>
        </div>
        <span class="text-muted" style="font-size: 12px;">Menampilkan {{ $transactions->count() }} transaksi.</span>
    </div>

    <div class="table-responsive">
        <table class="table align-middle mb-0" style="font-size: 13px;">
            <thead style="background-color: #F6EFE6; color: #334155;">
                <tr>
                    <th class="fw-semibold py-3 ps-3 rounded-start-3">Tanggal</th>
                    <th class="fw-semibold py-3">Kategori</th>
                    <th class="fw-semibold py-3">Keterangan</th>
                    <th class="fw-semibold py-3 text-end">Kas Masuk</th>
                    <th class="fw-semibold py-3 text-end">Kas Keluar</th>
                    <th class="fw-semibold py-3 text-end pe-3 rounded-end-3">Saldo Berjalan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $trx)
                @php
                    $isIncome = (($trx->resolved_type ?? $trx->category?->type) === 'income');
                @endphp
                <tr>
                    <td class="py-3 ps-3 text-muted">{{ \Carbon\Carbon::parse($trx->transaction_date)->locale('id')->translatedFormat('d M Y') }}</td>
                    <td class="py-3" style="color: #334155;">{{ $trx->category->name ?? 'Operasional' }}</td>
                    <td class="py-3" style="color: #1E293B;">{{ $trx->description }}</td>
                    <td class="py-3 text-end fw-medium" style="color: {{ $isIncome ? '#46657A' : '#94A3B8' }};">
                        {{ $isIncome ? 'Rp ' . number_format($trx->amount, 0, ',', '.') : '-' }}
                    </td>
                    <td class="py-3 text-end fw-medium" style="color: {{ !$isIncome ? '#1E293B' : '#94A3B8' }};">
                        {{ !$isIncome ? 'Rp ' . number_format($trx->amount, 0, ',', '.') : '-' }}
                    </td>
                    <td class="py-3 text-end pe-3 fw-semibold" style="color: #1E293B;">
                        Rp {{ number_format($trx->running_balance, 0, ',', '.') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-5">
                        Tidak ada transaksi kas pada rentang tanggal dan kategori yang dipilih.
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if($transactions->isNotEmpty())
            <tfoot>
                <tr style="background-color: #F6EFE6;">
                    <td colspan="3" class="py-3 ps-3 fw-bold rounded-start-3" style="color: #1E293B;">TOTAL KESELURUHAN</td>
                    <td class="py-3 text-end fw-bold" style="color: #46657A;">Rp {{ number_format($totalIncome, 0, ',', '.') }}</td>
                    <td class="py-3 text-end fw-bold" style="color: #1E293B;">Rp {{ number_format($totalExpense, 0, ',', '.') }}</td>
                    <td class="py-3 text-end pe-3 fw-bold rounded-end-3" style="color: #1E293B;">Rp {{ number_format($endingBalance, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>

    <!-- Footer Tabel -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 pt-3 border-top gap-2" style="font-size: 11.5px; color: #64748B;">
        <div>
            <div>Menampilkan {{ $transactions->count() > 0 ? '1–' . $transactions->count() : '0' }} dari {{ $transactions->count() }} transaksi</div>
            <div class="mt-1">Saldo awal periode terfilter: Rp {{ number_format($openingBalance, 0, ',', '.') }} &bull; Saldo sebelum transaksi {{ $beforeStartText }}.</div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="px-2">&lsaquo;</span>
            <span class="fw-bold text-dark px-2">1</span>
            <span class="px-2">&rsaquo;</span>
        </div>
    </div>
</div>

<!-- Keterangan Bawah -->
<div class="text-muted mt-3" style="font-size: 11.5px;">
    Hanya baca &bull; Data bersumber dari transaksi yang dicatat Bendahara. Pencatatan dan perubahan dilakukan oleh Bendahara.
</div>

<!-- ===================================================================== -->
<!-- MODAL 1: KONFIRMASI UNDUH PDF                                         -->
<!-- ===================================================================== -->
<div class="modal fade" id="confirmPdfModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow bg-white p-3">
            <div class="modal-body p-3">
                <div class="text-muted fw-semibold mb-1" style="font-size: 11px; letter-spacing: 0.4px;">EKSPOR LAPORAN &bull; .pdf</div>
                <h4 class="fw-bold mb-2" style="color: #1E293B;">Unduh Laporan PDF?</h4>
                <p class="text-muted mb-4" style="font-size: 13px;">
                    Laporan keuangan periode {{ $periodText }} siap ditinjau dan diunduh dalam format PDF resmi.
                </p>

                <div class="row g-2">
                    <div class="col-4">
                        <button type="button" class="btn btn-outline-secondary w-100 rounded-3 py-2 fw-medium" data-bs-dismiss="modal" style="font-size: 13px;">
                            Batal
                        </button>
                    </div>
                    <div class="col-8">
                        <a href="{{ route('reports.previewPdf', ['start_date' => $startDate, 'end_date' => $endDate, 'type' => $typeFilter]) }}" class="btn w-100 rounded-3 py-2 text-white fw-medium text-center d-inline-block text-decoration-none" style="background-color: #547285; font-size: 13px;">
                            Unduh PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===================================================================== -->
<!-- MODAL 2: KONFIRMASI UNDUH EXCEL                                       -->
<!-- ===================================================================== -->
<div class="modal fade" id="confirmExcelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow bg-white p-3">
            <div class="modal-body p-3">
                <div class="text-muted fw-semibold mb-1" style="font-size: 11px; letter-spacing: 0.4px;">EKSPOR LAPORAN &bull; .xlsx</div>
                <h4 class="fw-bold mb-2" style="color: #1E293B;">Unduh Laporan Excel?</h4>
                <p class="text-muted mb-4" style="font-size: 13px;">
                    File Excel berisi rincian transaksi dan rekapitulasi keuangan periode yang dipilih.
                </p>

                <div class="row g-2">
                    <div class="col-4">
                        <button type="button" class="btn btn-outline-secondary w-100 rounded-3 py-2 fw-medium" data-bs-dismiss="modal" style="font-size: 13px;">
                            Batal
                        </button>
                    </div>
                    <div class="col-8">
                        <a href="{{ route('reports.exportExcel', ['start_date' => $startDate, 'end_date' => $endDate, 'type' => $typeFilter]) }}" onclick="bootstrap.Modal.getInstance(document.getElementById('confirmExcelModal')).hide()" class="btn w-100 rounded-3 py-2 text-white fw-medium text-center d-inline-block text-decoration-none" style="background-color: #547285; font-size: 13px;">
                            Unduh Excel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection