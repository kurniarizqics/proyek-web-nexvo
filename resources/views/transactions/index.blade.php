@extends('layouts.app')

@section('content')
@php
    $currentUser = Auth::user();
    $isBendahara = ($currentUser->role === 'bendahara');
    $periodLabel = $selectedPeriod === 'all'
        ? 'Semua Periode'
        : \Carbon\Carbon::parse($selectedPeriod . '-01')->locale('id')->translatedFormat('F Y');
@endphp

<!-- Header Halaman -->
<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
    <div>
        <h3 class="fw-bold mb-1" style="color: #1E293B;">Buku Kas Organisasi</h3>
        <p class="text-muted mb-0" style="font-size: 13px;">Pantau seluruh riwayat transaksi kas masuk dan pengeluaran organisasi.</p>
    </div>

    @if($isBendahara)
        <button type="button" class="btn-nexvo-dark" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
            + Catat Transaksi Baru
        </button>
    @else
        <span class="badge rounded-3 px-3 py-2 fw-medium d-inline-flex align-items-center gap-2" style="background-color: #E2E8F0; color: #475569; font-size: 12px;">
            <i class="bi bi-lock"></i> Hanya Baca
        </span>
    @endif
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<!-- 3 Kartu Ringkasan Kas -->
<div class="row g-3 mb-4">
    <!-- Total Kas Masuk -->
    <div class="col-md-4">
        <div class="nexvo-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-medium" style="font-size: 13px; color: #475569;">Total Kas Masuk</span>
                <span class="rounded-2 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; background-color: #ECFDF5; color: #16A34A;">
                    <i class="bi bi-arrow-down-left small"></i>
                </span>
            </div>
            <h3 class="fw-bold mb-1" style="color: #15803D;">Rp {{ number_format($totalIncome, 0, ',', '.') }}</h3>
            <div class="text-muted" style="font-size: 11.5px;">Akumulasi pemasukan {{ strtolower($periodLabel) }}</div>
        </div>
    </div>

    <!-- Total Kas Keluar -->
    <div class="col-md-4">
        <div class="nexvo-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-medium" style="font-size: 13px; color: #475569;">Total Kas Keluar</span>
                <span class="rounded-2 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; background-color: #FEF2F2; color: #DC2626;">
                    <i class="bi bi-arrow-up-right small"></i>
                </span>
            </div>
            <h3 class="fw-bold mb-1" style="color: #B91C1C;">Rp {{ number_format($totalExpense, 0, ',', '.') }}</h3>
            <div class="text-muted" style="font-size: 11.5px;">Akumulasi pengeluaran {{ strtolower($periodLabel) }}</div>
        </div>
    </div>

    <!-- Saldo Akhir -->
    <div class="col-md-4">
        <div class="nexvo-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-medium" style="font-size: 13px; color: #475569;">Saldo Akhir</span>
                <span class="rounded-2 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; background-color: #F1F5F9; color: #243242;">
                    <i class="bi bi-wallet2 small"></i>
                </span>
            </div>
            <h3 class="fw-bold mb-1" style="color: #1E293B;">Rp {{ number_format($endingBalance, 0, ',', '.') }}</h3>
            <div class="text-muted" style="font-size: 11.5px;">Posisi saldo organisasi</div>
        </div>
    </div>
</div>

<!-- Bar Pencarian, Periode, Jenis Kas, & Reset Filter -->
<div class="row g-3 align-items-end mb-4">
    <div class="col-lg-5">
        <label class="form-label text-muted mb-1" style="font-size: 12px;">Pencarian transaksi</label>
        <div class="position-relative">
            <i class="bi bi-search text-muted position-absolute" style="top: 50%; left: 14px; transform: translateY(-50%); font-size: 13px;"></i>
            <input type="text" id="searchTransaction" class="form-control ps-5 rounded-3 bg-white" placeholder="Cari keterangan transaksi..." style="font-size: 13px; border-color: #E2E8F0; height: 42px;">
        </div>
    </div>

    <div class="col-md-4 col-lg-3">
        <label class="form-label text-muted mb-1" style="font-size: 12px;">Periode</label>
        <form method="GET" action="{{ route('transactions.index') }}" id="periodForm">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0 rounded-start-3 text-muted" style="border-color: #E2E8F0;">
                    <i class="bi bi-calendar3"></i>
                </span>
                <select name="period" class="form-select border-start-0 rounded-end-3 bg-white" style="font-size: 13px; border-color: #E2E8F0; height: 42px;" onchange="document.getElementById('periodForm').submit()">
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
        <label class="form-label text-muted mb-1" style="font-size: 12px;">Jenis kas</label>
        <select id="typeFilter" class="form-select rounded-3 bg-white" style="font-size: 13px; border-color: #E2E8F0; height: 42px;">
            <option value="all">Semua Jenis</option>
            <option value="income">Kas Masuk</option>
            <option value="expense">Kas Keluar</option>
        </select>
    </div>

    <div class="col-md-4 col-lg-2">
        <button type="button" id="resetFilterBtn" class="btn btn-outline-secondary w-100 rounded-3 bg-white d-flex align-items-center justify-content-center gap-2" style="font-size: 13px; border-color: #E2E8F0; height: 42px; color: #334155;">
            <i class="bi bi-arrow-counterclockwise"></i> Reset Filter
        </button>
    </div>
</div>

<!-- Card Tabel Riwayat Transaksi Kas -->
<div class="nexvo-card p-4 mb-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start mb-3 gap-2">
        <div>
            <h5 class="fw-bold mb-1" style="font-size: 16px; color: #1E293B;">Riwayat Transaksi Kas</h5>
            <p class="text-muted mb-0" style="font-size: 12px;">Daftar seluruh transaksi pemasukan dan pengeluaran organisasi</p>
        </div>
        <span class="badge rounded-3 px-3 py-2 fw-medium" style="background-color: #F1F5F9; color: #475569; font-size: 12px;">
            Menampilkan <span id="topVisibleCount">{{ $transactions->count() }}</span> transaksi
        </span>
    </div>

    <div class="table-responsive">
        <table class="table align-middle mb-0" style="font-size: 13px;">
            <thead style="background-color: #F4F7FA; color: #64748B;">
                <tr>
                    <th class="fw-medium py-3 ps-3 rounded-start-3">Tanggal</th>
                    <th class="fw-medium py-3">Kategori</th>
                    <th class="fw-medium py-3">Keterangan</th>
                    <th class="fw-medium py-3">Jenis Kas</th>
                    <th class="fw-medium py-3">Nominal</th>
                    <th class="fw-medium py-3">Saldo Berjalan</th>
                    <th class="fw-medium py-3 text-end pe-3 rounded-end-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $trx)
                @php
                    $trxDate = \Carbon\Carbon::parse($trx->transaction_date);
                    $trxCode = 'TRX-' . $trxDate->format('Y') . '-' . str_pad($trx->id, 3, '0', STR_PAD_LEFT);
                    $isIncome = ($trx->type === 'income');
                    $catName = $trx->category->name ?? 'Operasional';
                @endphp
                <tr class="trx-row"
                    data-type="{{ $trx->type }}"
                    data-search="{{ strtolower($trxCode . ' ' . $catName . ' ' . $trx->description) }}">
                    <td class="py-3 ps-3 text-muted">{{ $trxDate->locale('id')->translatedFormat('d M Y') }}</td>
                    <td class="py-3" style="color: #475569;">{{ $catName }}</td>
                    <td class="py-3 fw-medium" style="color: #1E293B;">{{ $trx->description }}</td>
                    <td class="py-3">
                        @if($isIncome)
                            <span class="badge rounded-2 px-3 py-2 fw-medium d-inline-flex align-items-center gap-1" style="background-color: #ECFDF5; color: #15803D;">
                                <i class="bi bi-arrow-down-left-square"></i> Kas Masuk
                            </span>
                        @else
                            <span class="badge rounded-2 px-3 py-2 fw-medium d-inline-flex align-items-center gap-1" style="background-color: #FEF2F2; color: #B91C1C;">
                                <i class="bi bi-arrow-up-right-square"></i> Kas Keluar
                            </span>
                        @endif
                    </td>
                    <td class="py-3 fw-bold" style="color: {{ $isIncome ? '#15803D' : '#B91C1C' }};">
                        {{ $isIncome ? '+' : '-' }} Rp {{ number_format($trx->amount, 0, ',', '.') }}
                    </td>
                    <td class="py-3 fw-semibold" style="color: #1E293B;">
                        Rp {{ number_format($trx->running_balance, 0, ',', '.') }}
                    </td>
                    <td class="py-3 text-end pe-3">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-3 px-3 py-1" style="font-size: 12px;" data-bs-toggle="modal" data-bs-target="#trxDetailModal{{ $trx->id }}">
                            <i class="bi bi-eye me-1"></i> Lihat Bukti
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-5">
                        Belum ada transaksi kas yang tercatat pada periode ini.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Footer Tabel -->
    <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top" style="font-size: 12px; color: #64748B;">
        <div>
            Menampilkan 1&ndash;<span id="bottomVisibleCount">{{ $transactions->count() }}</span> dari {{ $transactions->count() }} transaksi
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted px-2">&lsaquo;</span>
            <span class="badge rounded-2 px-3 py-2" style="background-color: #243242; color: white;">1</span>
            <span class="text-muted px-2">&rsaquo;</span>
        </div>
    </div>
</div>

<!-- Banner Informasi Hak Akses di Bagian Bawah -->
@if(!$isBendahara)
<div class="rounded-3 p-3 px-4 mt-3 d-flex align-items-start gap-3" style="background-color: #EBF1F6; border: 1px solid #D5E1EC; color: #334155;">
    <i class="bi bi-lock mt-1" style="color: #475569;"></i>
    <div style="font-size: 12px;">
        <div class="fw-bold mb-1" style="color: #1E293B;">
            Akses {{ ucfirst($currentUser->role) }} hanya untuk memantau. Seluruh pencatatan transaksi dilakukan oleh Bendahara.
        </div>
        <div class="text-muted">
            Ringkasan {{ strtolower($periodLabel) }} &bull; Tabel menampilkan riwayat transaksi terbaru beserta saldo berjalan secara otomatis.
        </div>
    </div>
</div>
@endif

<!-- ===================================================================== -->
<!-- SEMUA MODAL DILETAKKAN DI LUAR TABEL AGAR RENDER BOOTSTRAP SEMPURNA   -->
<!-- ===================================================================== -->
@foreach($transactions as $trx)
@php
    $trxDate = \Carbon\Carbon::parse($trx->transaction_date);
    $trxCode = 'TRX-' . $trxDate->format('Y') . '-' . str_pad($trx->id, 3, '0', STR_PAD_LEFT);
    $isIncome = ($trx->type === 'income');
    $catName = $trx->category->name ?? 'Operasional';
    $ext = $trx->proof_path ? strtolower(pathinfo($trx->proof_path, PATHINFO_EXTENSION)) : null;
    $isImage = in_array($ext, ['jpg', 'jpeg', 'png']);
    $proofFileName = $trx->proof_path ? basename($trx->proof_path) : ('Bukti_Transaksi_' . $trxDate->format('dMY') . '.jpg');
@endphp

<!-- MODAL 1: Detail Transaksi (2 Kolom Sesuai UI) -->
<div class="modal fade" id="trxDetailModal{{ $trx->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow bg-white">
            <div class="modal-header border-bottom-0 pt-4 px-4 pb-2 d-flex align-items-start justify-content-between">
                <div>
                    <h4 class="fw-bold mb-1" style="color: #1E293B;">Detail Transaksi</h4>
                    <p class="text-muted mb-0" style="font-size: 12.5px;">Informasi pencatatan dan dokumen bukti kas organisasi.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body px-4 py-3">
                <div class="row g-4">
                    <!-- Kolom Kiri: Info Transaksi -->
                    <div class="col-lg-5">
                        <div class="mb-3">
                            @if($isIncome)
                                <span class="badge rounded-2 px-3 py-2 fw-medium d-inline-flex align-items-center gap-1" style="background-color: #ECFDF5; color: #15803D;">
                                    <i class="bi bi-arrow-down-left-square"></i> Kas Masuk
                                </span>
                            @else
                                <span class="badge rounded-2 px-3 py-2 fw-medium d-inline-flex align-items-center gap-1" style="background-color: #FEF2F2; color: #B91C1C;">
                                    <i class="bi bi-arrow-up-right-square"></i> Kas Keluar
                                </span>
                            @endif
                        </div>

                        <!-- Box Nominal -->
                        <div class="p-3 rounded-3 mb-4" style="background-color: {{ $isIncome ? '#ECFDF5' : '#FEF2F2' }};">
                            <div class="text-muted" style="font-size: 11.5px;">Nominal transaksi</div>
                            <h3 class="fw-bold mb-0 mt-1" style="color: {{ $isIncome ? '#15803D' : '#B91C1C' }};">
                                {{ $isIncome ? '+' : '-' }} Rp {{ number_format($trx->amount, 0, ',', '.') }}
                            </h3>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <div class="text-muted" style="font-size: 11.5px;">ID Transaksi</div>
                                <div class="fw-semibold" style="font-size: 13.5px;">{{ $trxCode }}</div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted" style="font-size: 11.5px;">Kategori</div>
                                <div class="fw-semibold" style="font-size: 13.5px;">{{ $catName }}</div>
                            </div>
                            <div class="col-12">
                                <div class="text-muted" style="font-size: 11.5px;">Tanggal Transaksi</div>
                                <div class="fw-semibold" style="font-size: 13.5px;">{{ $trxDate->locale('id')->translatedFormat('d F Y') }}, {{ $trx->created_at->format('H.i') }} WIB</div>
                            </div>
                            <div class="col-12">
                                <div class="text-muted" style="font-size: 11.5px;">Keterangan</div>
                                <div class="fw-medium" style="font-size: 13px; color: #334155;">{{ $trx->description }}</div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted" style="font-size: 11.5px;">Saldo Setelah Transaksi</div>
                                <div class="fw-bold" style="font-size: 13.5px;">Rp {{ number_format($trx->running_balance, 0, ',', '.') }}</div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted" style="font-size: 11.5px;">Dicatat Oleh</div>
                                <div class="fw-semibold" style="font-size: 13.5px;">{{ ucfirst($trx->user->role ?? 'Bendahara') }}</div>
                            </div>
                            <div class="col-12">
                                <div class="text-muted" style="font-size: 11.5px;">Sumber Transaksi</div>
                                <div class="fw-semibold" style="font-size: 13.5px;">
                                    {{ $trx->proposal_id ? 'Pengajuan dana disetujui' : ($isIncome ? 'Pemasukan Kas Organisasi' : 'Pencatatan Pengeluaran Langsung') }}
                                </div>
                            </div>
                        </div>

                        @if($trx->proposal)
                        <div class="p-3 rounded-3 mt-3" style="background-color: #F4F7FA;">
                            <div class="fw-bold mb-2" style="font-size: 12.5px; color: #1E293B;">Pengajuan dana terkait</div>
                            <div class="d-flex justify-content-between align-items-center" style="font-size: 12px;">
                                <div>
                                    <div class="text-muted" style="font-size: 11px;">ID Pengajuan</div>
                                    <div class="fw-semibold">PGJ-{{ $trx->proposal->created_at->format('Y') }}-{{ str_pad($trx->proposal->id, 3, '0', STR_PAD_LEFT) }}</div>
                                </div>
                                <div class="text-end">
                                    <span class="badge rounded-2 px-2 py-1" style="background-color: #DCFCE7; color: #15803D;">Disetujui</span>
                                    <div class="text-muted mt-1" style="font-size: 11px;">Pengaju: {{ $trx->proposal->user->name ?? '-' }}</div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Kolom Kanan: Bukti Transaksi -->
                    <div class="col-lg-7">
                        <h6 class="fw-bold mb-1" style="font-size: 14px; color: #1E293B;">Bukti Transaksi</h6>
                        <p class="text-muted mb-3" style="font-size: 12px;">Dokumen pendukung pencatatan oleh Bendahara.</p>

                        <!-- Box Pratinjau Struk / File -->
                        <div class="rounded-4 p-4 mb-3 position-relative text-center" style="background-color: #F1F5F9; border: 1px solid #E2E8F0;">
                            <button type="button" class="btn btn-sm btn-light bg-white shadow-sm rounded-3 position-absolute" style="top: 14px; right: 14px;" data-bs-toggle="modal" data-bs-target="#zoomProofModal{{ $trx->id }}" title="Perbesar Bukti">
                                <i class="bi bi-zoom-in"></i>
                            </button>

                            @if(!empty($trx->proof_path))
                                @if($isImage)
                                    <img src="{{ asset('storage/' . $trx->proof_path) }}" alt="Bukti Transaksi" class="img-fluid rounded-3 shadow-sm" style="max-height: 310px;">
                                @else
                                    <iframe src="{{ asset('storage/' . $trx->proof_path) }}" width="100%" height="310px" class="rounded-3 border-0 bg-white"></iframe>
                                @endif
                            @else
                                <!-- Struk Bukti Elektronik Otomatis (jika tidak ada file upload) -->
                                <div class="bg-white rounded-3 shadow-sm p-4 mx-auto text-start" style="max-width: 340px; border-top: 4px solid #243242;">
                                    <div class="text-muted fw-bold mb-2" style="font-size: 11px; letter-spacing: 0.5px;">
                                        {{ $isIncome ? 'BUKTI TRANSFER' : 'BUKTI PEMBAYARAN / KUITANSI' }}
                                    </div>
                                    <div class="text-center my-3">
                                        <span class="rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 32px; height: 32px; background-color: #ECFDF5; color: #16A34A;">
                                            <i class="bi bi-check-lg"></i>
                                        </span>
                                        <div class="fw-bold" style="font-size: 13px;">{{ $isIncome ? 'Transfer Berhasil' : 'Pembayaran Lunas' }}</div>
                                        <h4 class="fw-bold my-1" style="color: #1E293B;">Rp {{ number_format($trx->amount, 0, ',', '.') }}</h4>
                                        <div class="text-muted" style="font-size: 10.5px;">{{ $trxDate->locale('id')->translatedFormat('d F Y') }} &bull; {{ $trx->created_at->format('H.i') }} WIB</div>
                                    </div>
                                    <hr class="my-2">
                                    <div class="mb-2" style="font-size: 11px;">
                                        <div class="text-muted">{{ $isIncome ? 'Penerima' : 'Keterangan Transaksi' }}</div>
                                        <div class="fw-bold">{{ $isIncome ? 'NEXVO FINANCE' : $trx->description }}</div>
                                    </div>
                                    <div class="mb-2" style="font-size: 11px;">
                                        <div class="text-muted">Nomor referensi</div>
                                        <div class="fw-semibold">{{ $trxCode }} / {{ str_pad($trx->id * 82601, 8, '0', STR_PAD_LEFT) }}</div>
                                    </div>
                                    <div class="text-muted mt-3" style="font-size: 10px;">Bukti transaksi tersimpan secara elektronik.</div>
                                </div>
                            @endif
                        </div>

                        <!-- Info Nama File -->
                        <div class="d-flex align-items-center gap-3 mb-3 px-1">
                            <div class="rounded-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background-color: #F1F5F9; color: #64748B;">
                                <i class="bi bi-file-earmark-text fs-5"></i>
                            </div>
                            <div>
                                <div class="fw-semibold" style="font-size: 13px;">{{ $proofFileName }}</div>
                                <div class="text-muted" style="font-size: 11px;">{{ $trx->proof_path ? strtoupper($ext) : 'Dokumen Elektronik &bull; JPG' }}</div>
                            </div>
                        </div>

                        <!-- 2 Tombol Bawah: Perbesar Bukti & Unduh Bukti -->
                        <div class="row g-2">
                            <div class="col-6">
                                <button type="button" class="btn btn-outline-secondary w-100 rounded-3 py-2 fw-medium" style="font-size: 13px;" data-bs-toggle="modal" data-bs-target="#zoomProofModal{{ $trx->id }}">
                                    <i class="bi bi-zoom-in me-1"></i> Perbesar Bukti
                                </button>
                            </div>
                            <div class="col-6">
                                @if(!empty($trx->proof_path))
                                    <a href="{{ asset('storage/' . $trx->proof_path) }}" download class="btn-nexvo-dark w-100 py-2 text-center d-inline-block text-decoration-none" style="font-size: 13px;">
                                        <i class="bi bi-download me-1"></i> Unduh Bukti
                                    </a>
                                @else
                                    <button type="button" class="btn-nexvo-dark w-100 py-2" style="font-size: 13px;" data-bs-toggle="modal" data-bs-target="#noFileModal{{ $trx->id }}">
                                        <i class="bi bi-download me-1"></i> Unduh Bukti
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer border-top px-4 py-3 justify-content-between">
                <span class="text-muted" style="font-size: 12px;">Hanya baca &bull; Pencatatan dikelola oleh Bendahara.</span>
                <button type="button" class="btn btn-outline-secondary rounded-3 px-4" data-bs-dismiss="modal" style="font-size: 13px;">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 2: Perbesar Bukti (Sesuai Gambar 4) -->
<div class="modal fade" id="zoomProofModal{{ $trx->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow bg-white p-3">
            <div class="modal-body text-center p-3">
                @if(!empty($trx->proof_path))
                    @if($isImage)
                        <img src="{{ asset('storage/' . $trx->proof_path) }}" alt="Bukti Transaksi" class="img-fluid rounded-3" style="max-height: 520px;">
                    @else
                        <iframe src="{{ asset('storage/' . $trx->proof_path) }}" width="100%" height="520px" class="rounded-3 border-0"></iframe>
                    @endif
                @else
                    <div class="bg-white rounded-3 border shadow-sm p-5 mx-auto text-start my-2" style="max-width: 460px; border-top: 5px solid #243242 !important;">
                        <div class="text-muted fw-bold mb-3" style="font-size: 13px; letter-spacing: 0.5px;">
                            {{ $isIncome ? 'BUKTI TRANSFER' : 'BUKTI PEMBAYARAN / KUITANSI' }}
                        </div>
                        <div class="text-center my-4">
                            <span class="rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 44px; height: 44px; background-color: #ECFDF5; color: #16A34A;">
                                <i class="bi bi-check-lg fs-4"></i>
                            </span>
                            <div class="fw-bold fs-6">{{ $isIncome ? 'Transfer Berhasil' : 'Pembayaran Lunas' }}</div>
                            <h2 class="fw-bold my-2" style="color: #1E293B;">Rp {{ number_format($trx->amount, 0, ',', '.') }}</h2>
                            <div class="text-muted small">{{ $trxDate->locale('id')->translatedFormat('d F Y') }} &bull; {{ $trx->created_at->format('H.i') }} WIB</div>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <div class="text-muted small">{{ $isIncome ? 'Penerima' : 'Uraian Transaksi' }}</div>
                            <div class="fw-bold">{{ $isIncome ? 'NEXVO FINANCE' : $trx->description }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Nomor referensi</div>
                            <div class="fw-semibold">{{ $trxCode }} / {{ str_pad($trx->id * 82601, 8, '0', STR_PAD_LEFT) }}</div>
                        </div>
                        <div class="text-muted small mt-4">Bukti transaksi tersimpan secara elektronik.</div>
                    </div>
                @endif
            </div>
            <div class="modal-footer border-top pt-3 justify-content-between">
                <span class="text-muted" style="font-size: 12px;">{{ $proofFileName }}</span>
                <button type="button" class="btn btn-outline-secondary rounded-3 px-4" style="font-size: 13px;" data-bs-toggle="modal" data-bs-target="#trxDetailModal{{ $trx->id }}">
                    Kembali ke Detail
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 3: Pemberitahuan Bukti Elektronik (Sesuai Gambar 5) -->
<div class="modal fade" id="noFileModal{{ $trx->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 rounded-4 shadow bg-white p-3">
            <div class="modal-body">
                <h6 class="fw-bold mb-2" style="color: #1E293B;">Bukti siap diunduh</h6>
                <p class="text-muted mb-3" style="font-size: 12.5px;">
                    Transaksi ini menggunakan struk bukti digital otomatis tanpa lampiran file fisik tambahan.
                </p>
                <div class="text-end">
                    <button type="button" class="btn-nexvo-dark px-4 py-2" style="font-size: 12.5px;" data-bs-toggle="modal" data-bs-target="#trxDetailModal{{ $trx->id }}">
                        Mengerti
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endforeach

<!-- ===================================================================== -->
<!-- MODAL TAMBAH TRANSAKSI BARU (KHUSUS ROLE BENDAHARA)                   -->
<!-- ===================================================================== -->
@if($isBendahara)
<div class="modal fade" id="addTransactionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow bg-white p-2">
            <form action="{{ route('transactions.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header border-bottom-0 pt-3 px-4 pb-2">
                    <div>
                        <h4 class="fw-bold mb-1" style="color: #1E293B;">Catat Transaksi Kas Baru</h4>
                        <p class="text-muted mb-0" style="font-size: 12.5px;">Input transaksi kas masuk atau kas keluar organisasi beserta bukti transaksi.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body px-4 py-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 13px;">Tanggal Transaksi <span class="text-danger">*</span></label>
                            <input type="date" name="transaction_date" class="form-control rounded-3" value="{{ date('Y-m-d') }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 13px;">Jenis Kas <span class="text-danger">*</span></label>
                            <select name="type" class="form-select rounded-3" required>
                                <option value="income">Kas Masuk (Pemasukan)</option>
                                <option value="expense">Kas Keluar (Pengeluaran)</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 13px;">Kategori Anggaran <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select rounded-3" required>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 13px;">Nominal (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="amount" class="form-control rounded-3" placeholder="Contoh: 500000" min="1" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size: 13px;">Keterangan Transaksi <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control rounded-3" rows="2" placeholder="Tuliskan rincian transaksi kas..." required></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size: 13px;">Tautkan Pengajuan Dana Disetujui (Opsional)</label>
                            <select name="proposal_id" class="form-select rounded-3">
                                <option value="">-- Tidak ditautkan ke pengajuan --</option>
                                @foreach($approvedProposals as $ap)
                                    <option value="{{ $ap->id }}">PGJ-{{ $ap->created_at->format('Y') }}-{{ str_pad($ap->id, 3, '0', STR_PAD_LEFT) }} - {{ $ap->title }} (Rp {{ number_format($ap->amount, 0, ',', '.') }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size: 13px;">Unggah Bukti Transaksi (JPG, PNG, PDF - Maks. 5MB)</label>
                            <input type="file" name="proof" class="form-control rounded-3" accept=".jpg,.jpeg,.png,.pdf">
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top-0 px-4 pb-4 pt-2">
                    <button type="button" class="btn btn-outline-secondary rounded-3 px-4" data-bs-dismiss="modal" style="font-size: 13px;">Batal</button>
                    <button type="submit" class="btn-nexvo-dark px-4 py-2" style="font-size: 13px;">Simpan Transaksi</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
    const searchTrxInput = document.getElementById('searchTransaction');
    const typeFilter = document.getElementById('typeFilter');
    const resetFilterBtn = document.getElementById('resetFilterBtn');
    const trxRows = document.querySelectorAll('.trx-row');
    const topVisibleCount = document.getElementById('topVisibleCount');
    const bottomVisibleCount = document.getElementById('bottomVisibleCount');

    function filterTransactions() {
        const query = searchTrxInput ? searchTrxInput.value.toLowerCase() : '';
        const type = typeFilter ? typeFilter.value : 'all';
        let count = 0;

        trxRows.forEach(row => {
            const matchText = row.dataset.search.includes(query);
            const matchType = (type === 'all') || (row.dataset.type === type);

            if (matchText && matchType) {
                row.style.display = '';
                count++;
            } else {
                row.style.display = 'none';
            }
        });

        if (topVisibleCount) topVisibleCount.textContent = count;
        if (bottomVisibleCount) bottomVisibleCount.textContent = count;
    }

    if (searchTrxInput) searchTrxInput.addEventListener('input', filterTransactions);
    if (typeFilter) typeFilter.addEventListener('change', filterTransactions);
    if (resetFilterBtn) {
        resetFilterBtn.addEventListener('click', () => {
            if (searchTrxInput) searchTrxInput.value = '';
            if (typeFilter) typeFilter.value = 'all';
            filterTransactions();
        });
    }
</script>
@endpush