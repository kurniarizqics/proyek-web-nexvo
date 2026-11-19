@extends('layouts.app')

@section('content')
@php
    $currentUser = Auth::user();
    $isBendahara = ($currentUser->role === 'bendahara');
    $isValidasiBuktiView = ($isBendahara && request()->query('section') === 'penyaluran');
@endphp

@if($isValidasiBuktiView)
<!-- ===================================================================== -->
<!-- TAMPILAN KHUSUS: PENYALURAN DANA -> VALIDASI BUKTI (SESUAI FIGMA)     -->
<!-- ===================================================================== -->
@php
    $proofTab = request()->query('proof_tab', 'pending'); // 'pending' atau 'unsubmitted'
    $activeList = $proofTab === 'unsubmitted' ? $unsubmittedProofProposals : $pendingProofProposals;
    $selectedId = (int) request()->query('selected', $activeList->first()?->id ?? 0);
    $selectedProposal = $activeList->firstWhere('id', $selectedId) ?? $activeList->first();
    $expenseAccounts = ['Beban Layanan', 'Beban Operasional', 'Beban Perlengkapan', 'Beban Acara', 'Beban Konsumsi', 'Beban Transportasi'];
@endphp

<style>
    .proof-pill-tab {
        border-radius: 20px;
        padding: 7px 16px;
        font-size: 12.5px;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
        transition: all 0.15s ease;
    }
    .proof-pill-tab.active {
        background-color: #2F4156;
        color: #FFFFFF;
    }
    .proof-pill-tab.inactive {
        background-color: transparent;
        color: #64748B;
    }
    .proof-pill-tab.inactive:hover {
        color: #1E293B;
        background-color: #F1F5F9;
    }
    .proof-item-card {
        display: block;
        text-decoration: none;
        background-color: #DDE6EE;
        border: 1.5px solid transparent;
        border-radius: 10px;
        padding: 14px 16px;
        transition: all 0.15s ease;
    }
    .proof-item-card:hover {
        background-color: #D2DFEA;
    }
    .proof-item-card.selected {
        border-color: #2F4156;
        background-color: #DCE6EF;
    }
</style>

<!-- Header Halaman -->
<div class="mb-4">
    <h3 class="fw-bold mb-1" style="color: #1E293B;">Validasi Bukti</h3>
    <p class="text-muted mb-0" style="font-size: 13px;">Periksa bukti penggunaan dana, pilih akun beban untuk setiap item lalu setujui atau tolak</p>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        <ul class="mb-0 ps-3 small">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row g-4 align-items-start">
    <!-- PANEL KIRI: Daftar Pengajuan Menunggu Verifikasi / Belum Unggah -->
    <div class="col-lg-5">
        <div class="nexvo-card p-4" style="min-height: 460px;">
            <div class="d-flex align-items-center gap-2 mb-4">
                <a href="{{ route('transactions.index', ['section' => 'penyaluran', 'proof_tab' => 'pending']) }}"
                   class="proof-pill-tab {{ $proofTab === 'pending' ? 'active' : 'inactive' }}">
                    Menunggu Verifikasi
                </a>
                <a href="{{ route('transactions.index', ['section' => 'penyaluran', 'proof_tab' => 'unsubmitted']) }}"
                   class="proof-pill-tab {{ $proofTab === 'unsubmitted' ? 'active' : 'inactive' }}">
                    Belum Unggah
                </a>
            </div>

            <div class="d-flex flex-column gap-3">
                @forelse($activeList as $itemProp)
                @php
                    $itemRealizedTotal = collect($itemProp->realization_items ?? [])->sum('amount');
                    $isCurrentSelected = ($selectedProposal && $selectedProposal->id === $itemProp->id);
                @endphp
                <a href="{{ route('transactions.index', ['section' => 'penyaluran', 'proof_tab' => $proofTab, 'selected' => $itemProp->id]) }}"
                   class="proof-item-card {{ $isCurrentSelected ? 'selected' : '' }}">
                    <div class="fw-bold" style="font-size: 14px; color: #1E293B;">
                        {{ $itemProp->user->name }} - {{ $itemProp->user->division ?? ucfirst($itemProp->user->role) }}
                    </div>
                    <div style="font-size: 12px; color: #475569; margin-top: 2px;">
                        {{ $itemProp->title }}
                    </div>
                    <div style="font-size: 11px; color: #64748B; margin-top: 6px;">
                        @if($proofTab === 'pending')
                            Realisasi Rp {{ number_format($itemRealizedTotal, 0, ',', '.') }} dari Rp {{ number_format($itemProp->amount, 0, ',', '.') }}
                            @if($itemProp->realization_submitted_at)
- diunggah {{ $itemProp->realization_submitted_at->locale('id')->translatedFormat('d M') }}
                            @endif
                        @else
                            Dicairkan Rp {{ number_format($itemProp->amount, 0, ',', '.') }} pada {{ $itemProp->updated_at->locale('id')->translatedFormat('d M Y') }}
                            @if($itemProp->realization_status === 'rejected')
                                <span class="text-danger fw-semibold">&bull; Perlu Unggah Ulang</span>
                            @endif
                        @endif
                    </div>
                </a>
                @empty
                <div class="text-center text-muted py-5" style="font-size: 13px;">
                    @if($proofTab === 'pending')
                        Belum ada bukti realisasi yang menunggu verifikasi.
                    @else
                        Semua pengajuan yang dicairkan sudah mengunggah bukti realisasi.
                    @endif
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- PANEL KANAN: Rincian Realisasi -->
    <div class="col-lg-7">
        <div class="nexvo-card p-4" style="min-height: 460px;">
            @if($selectedProposal)
                @php
                    $realizationItems = $selectedProposal->realization_items ?? [];
                    $realizationProofs = $selectedProposal->realization_proofs ?? [];
                    $totalRealization = collect($realizationItems)->sum('amount');
                @endphp

                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h5 class="fw-bold mb-1" style="font-size: 16px; color: #1E293B;">Rincian Realisasi</h5>
                        <div class="text-muted" style="font-size: 12px;">
                            {{ $selectedProposal->user->name }} - {{ $selectedProposal->user->division ?? ucfirst($selectedProposal->user->role) }} - {{ $selectedProposal->title }}
                        </div>
                    </div>

                    @if($selectedProposal->realization_status === 'pending')
                        <span class="badge rounded-2 px-3 py-2 fw-semibold" style="background-color: #C8D9E6; color: #2F4156; font-size: 11px;">
                            Menunggu Verifikasi
                        </span>
                    @elseif($selectedProposal->realization_status === 'rejected')
                        <span class="badge rounded-2 px-3 py-2 fw-semibold" style="background-color: #FEE2E2; color: #B91C1C; font-size: 11px;">
                            Bukti Ditolak
                        </span>
                    @else
                        <span class="badge rounded-2 px-3 py-2 fw-semibold" style="background-color: #FEF3C7; color: #B45309; font-size: 11px;">
                            Belum Unggah Bukti
                        </span>
                    @endif
                </div>

                <!-- Info Tanggal Pencairan & Tanggal Bukti -->
                <div class="d-flex flex-column gap-2 mb-4" style="font-size: 12.5px;">
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Dana dicairkan</span>
                        <span class="fw-semibold" style="color: #1E293B;">
                            Rp {{ number_format($selectedProposal->amount, 0, ',', '.') }} - {{ $selectedProposal->updated_at->locale('id')->translatedFormat('d M Y') }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Tanggal Bukti</span>
                        <span class="fw-semibold" style="color: #1E293B;">
                            {{ $selectedProposal->realization_submitted_at ? $selectedProposal->realization_submitted_at->locale('id')->translatedFormat('d M Y') : '-' }}
                        </span>
                    </div>
                </div>

                @if($proofTab === 'pending')
                <form action="{{ route('transactions.validateProof', $selectedProposal->id) }}" method="POST">
                    @csrf

                    <!-- Foto Bukti -->
                    <div class="mb-4">
                        <div class="fw-bold mb-2" style="font-size: 13px; color: #1E293B;">Foto Bukti</div>
                        <div class="d-flex flex-wrap gap-3">
                            @forelse($realizationProofs as $idx => $proofFile)
                                <a href="{{ asset('storage/' . $proofFile) }}" target="_blank" class="border rounded-3 overflow-hidden d-block bg-light" style="width: 150px; height: 95px;" title="Klik untuk memperbesar foto struk">
                                    <img src="{{ asset('storage/' . $proofFile) }}" alt="Struk {{ $idx + 1 }}" class="w-100 h-100 object-fit-cover">
                                </a>
                            @empty
                                <div class="text-muted small">Tidak ada foto struk terlampir.</div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Rincian Item dan Akun Beban -->
                    <div class="mb-3">
                        <div class="fw-bold mb-2" style="font-size: 13px; color: #1E293B;">Rincian item dan akun beban</div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-2" style="font-size: 12.5px;">
                                <thead class="text-muted border-bottom">
                                    <tr>
                                        <th class="fw-medium pb-2">Keterangan</th>
                                        <th class="fw-medium pb-2">Nominal</th>
                                        <th class="fw-medium pb-2 text-end">Akun Beban</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($realizationItems as $i => $rItem)
                                    <tr>
                                        <td class="py-2 text-muted">{{ $rItem['description'] }}</td>
                                        <td class="py-2 fw-bold" style="color: #1E293B;">Rp {{ number_format($rItem['amount'], 0, ',', '.') }}</td>
                                        <td class="py-2 text-end">
                                            <select name="item_accounts[{{ $i }}]" class="form-select form-select-sm d-inline-block border-0 fw-semibold text-end pe-4" style="width: auto; background-color: transparent; color: #1E293B;">
                                                @foreach($expenseAccounts as $acc)
                                                    <option value="{{ $acc }}" {{ ($rItem['account'] ?? 'Beban Layanan') === $acc ? 'selected' : '' }}>
                                                        {{ $acc }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center pt-2 border-top" style="font-size: 13px;">
                            <span class="text-muted">Total realisasi</span>
                            <span class="fw-bold" style="color: #1E293B;">Rp {{ number_format($totalRealization, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <!-- Alasan Penolakan -->
                    <div class="mb-4">
                        <label class="fw-bold mb-2 d-block" style="font-size: 13px; color: #1E293B;">
                            Alasan penolakan <span class="fw-normal text-muted">(wajib jika menolak)</span>
                        </label>
                        <textarea name="rejection_reason" class="form-control rounded-3" rows="2" placeholder="Contoh: foto nota buram atau tidak sesuai rincian" style="font-size: 12.5px; border-color: #E2E8F0;"></textarea>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="row g-2">
                        <div class="col-8">
                            <button type="submit" name="decision" value="approve" class="btn w-100 rounded-3 fw-semibold text-white" style="background-color: #2F4156; height: 42px; font-size: 13.5px;">
                                Setujui dan Catat Beban
                            </button>
                        </div>
                        <div class="col-4">
                            <button type="submit" name="decision" value="reject" class="btn w-100 rounded-3 fw-semibold" style="background-color: #C8D9E6; color: #2F4156; height: 42px; font-size: 13.5px;">
                                Tolak Bukti
                            </button>
                        </div>
                    </div>
                </form>
                @else
                <div class="p-4 rounded-3 text-center my-4" style="background-color: #F8FAFC; border: 1px dashed #CBD5E1;">
                    <i class="bi bi-hourglass-split fs-3 text-muted d-block mb-2"></i>
                    <div class="fw-semibold text-dark mb-1" style="font-size: 13.5px;">Menunggu Pengaju Mengunggah Bukti Penggunaan Dana</div>
                    <p class="text-muted mb-0" style="font-size: 12px;">
                        Dana sebesar <strong>Rp {{ number_format($selectedProposal->amount, 0, ',', '.') }}</strong> telah dicairkan kepada <strong>{{ $selectedProposal->user->name }}</strong>. Pengaju dapat mengunggah foto struk/nota melalui menu <em>Riwayat Pengajuanku</em>.
                    </p>
                </div>
                @endif
            @else
                <div class="d-flex flex-column align-items-center justify-content-center text-center text-muted" style="min-height: 380px;">
                    <i class="bi bi-receipt fs-1 mb-2"></i>
                    <div class="fw-semibold text-dark" style="font-size: 14px;">Tidak ada data realisasi pada tab ini</div>
                    <div style="font-size: 12px;">Pilih tab lain di sebelah kiri atau tunggu pengaju mengunggah bukti penggunaan dana.</div>
                </div>
            @endif
        </div>
    </div>
</div>

@else
<!-- ===================================================================== -->
<!-- TAMPILAN NORMAL: TRANSAKSI KAS / BUKU KAS ORGANISASI                  -->
<!-- ===================================================================== -->
@php
    $periodLabel = $selectedPeriod === 'all'
        ? 'Semua Periode'
        : \Carbon\Carbon::parse($selectedPeriod . '-01')->locale('id')->translatedFormat('F Y');
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
    <div>
        <div class="text-muted mb-1" style="font-size: 11.5px;">
            Transaksi &amp; Bukti Kas <span class="mx-1">/</span> <span class="text-dark fw-medium">Buku Kas</span>
        </div>
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

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        <ul class="mb-0 ps-3 small">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<!-- 3 Kartu Ringkasan Kas -->
<div class="row g-3 mb-4">
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

<!-- Bar Pencarian & Filter -->
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
            <option value="void">Void (Dibatalkan)</option>
        </select>
    </div>

    <div class="col-md-4 col-lg-2">
        <button type="button" id="resetFilterBtn" class="btn btn-outline-secondary w-100 rounded-3 bg-white d-flex align-items-center justify-content-center gap-2" style="font-size: 13px; border-color: #E2E8F0; height: 42px; color: #334155;">
            <i class="bi bi-arrow-counterclockwise"></i> Reset Filter
        </button>
    </div>
</div>

<!-- Tabel Riwayat Transaksi Kas -->
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
                    $trxType = $trx->resolved_type ?? ($trx->category?->type ?? 'expense');
                    $isIncome = ($trxType === 'income');
                    $isVoid = !is_null($trx->voided_at);
                    $catName = $trx->category->name ?? 'Operasional';
                @endphp
                <tr class="trx-row"
                    style="{{ $isVoid ? 'opacity: 0.72; background-color: #FAFAFA;' : '' }}"
                    data-type="{{ $isVoid ? 'void' : $trxType }}"
                    data-search="{{ strtolower($trxCode . ' ' . $catName . ' ' . $trx->description . ($isVoid ? ' void dibatalkan' : '')) }}">
                    <td class="py-3 ps-3 text-muted">{{ $trxDate->locale('id')->translatedFormat('d M Y') }}</td>
                    <td class="py-3" style="color: #475569;">{{ $catName }}</td>
                    <td class="py-3 fw-medium" style="color: #1E293B;">
                        <span class="{{ $isVoid ? 'text-decoration-line-through text-muted' : '' }}">{{ $trx->description }}</span>
                        @if($isVoid)
                            <div class="text-danger mt-1" style="font-size: 11px;">
                                <i class="bi bi-slash-circle me-1"></i>Void: {{ $trx->void_reason }}
                            </div>
                        @endif
                    </td>
                    <td class="py-3">
                        @if($isVoid)
                            <span class="badge rounded-2 px-3 py-2 fw-medium d-inline-flex align-items-center gap-1" style="background-color: #F1F5F9; color: #64748B;">
                                <i class="bi bi-slash-circle"></i> Void
                            </span>
                        @elseif($isIncome)
                            <span class="badge rounded-2 px-3 py-2 fw-medium d-inline-flex align-items-center gap-1" style="background-color: #ECFDF5; color: #15803D;">
                                <i class="bi bi-arrow-down-left-square"></i> Kas Masuk
                            </span>
                        @else
                            <span class="badge rounded-2 px-3 py-2 fw-medium d-inline-flex align-items-center gap-1" style="background-color: #FEF2F2; color: #B91C1C;">
                                <i class="bi bi-arrow-up-right-square"></i> Kas Keluar
                            </span>
                        @endif
                    </td>
                    <td class="py-3 fw-bold {{ $isVoid ? 'text-decoration-line-through text-muted' : '' }}" style="color: {{ $isVoid ? '#94A3B8' : ($isIncome ? '#15803D' : '#B91C1C') }};">
                        {{ $isIncome ? '+' : '-' }} Rp {{ number_format($trx->amount, 0, ',', '.') }}
                    </td>
                    <td class="py-3 fw-semibold" style="color: #1E293B;">
                        Rp {{ number_format($trx->running_balance, 0, ',', '.') }}
                    </td>
                    <td class="py-3 text-end pe-3">
                        <div class="d-inline-flex align-items-center gap-1">
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-3 px-2 py-1" style="font-size: 11.5px;" data-bs-toggle="modal" data-bs-target="#trxDetailModal{{ $trx->id }}">
                                <i class="bi bi-eye me-1"></i> Detail
                            </button>

                            @if($isBendahara)
                                @if(!$isVoid)
                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-3 px-2 py-1" style="font-size: 11.5px;" data-bs-toggle="modal" data-bs-target="#editTransactionModal{{ $trx->id }}">
                                        <i class="bi bi-pencil-square me-1"></i> Edit
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-warning rounded-3 px-2 py-1 text-dark" style="font-size: 11.5px; border-color: #CBD5E1;" data-bs-toggle="modal" data-bs-target="#voidTransactionModal{{ $trx->id }}">
                                        <i class="bi bi-slash-circle me-1"></i> Void
                                    </button>
                                @else
                                    <span class="badge rounded-2 px-2 py-1" style="background-color: #F1F5F9; color: #94A3B8; font-size: 11px;">Voided</span>
                                @endif
                            @endif
                        </div>
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

<!-- MODAL DETAIL, EDIT, VOID, & TAMBAH TRANSAKSI -->
@foreach($transactions as $trx)
@php
    $trxDate = \Carbon\Carbon::parse($trx->transaction_date);
    $trxCode = 'TRX-' . $trxDate->format('Y') . '-' . str_pad($trx->id, 3, '0', STR_PAD_LEFT);
    $trxType = $trx->resolved_type ?? ($trx->category?->type ?? 'expense');
    $isIncome = ($trxType === 'income');
    $isVoid = !is_null($trx->voided_at);
    $catName = $trx->category->name ?? 'Operasional';
    $ext = $trx->proof_path ? strtolower(pathinfo($trx->proof_path, PATHINFO_EXTENSION)) : null;
    $isImage = in_array($ext, ['jpg', 'jpeg', 'png']);
@endphp

<!-- Modal Detail Transaksi -->
<div class="modal fade" id="trxDetailModal{{ $trx->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow bg-white">
            <div class="modal-header border-bottom-0 pt-4 px-4 pb-2 d-flex align-items-start justify-content-between">
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <h4 class="fw-bold mb-0" style="color: #1E293B;">Detail Transaksi</h4>
                        @if($isVoid)
                            <span class="badge rounded-2 px-3 py-1" style="background-color: #FEE2E2; color: #B91C1C; font-size: 11px;">Status: VOID</span>
                        @endif
                    </div>
                    <p class="text-muted mb-0 mt-1" style="font-size: 12.5px;">Informasi pencatatan dan dokumen bukti kas organisasi.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <div class="row g-4">
                    <div class="col-lg-5">
                        <div class="p-3 rounded-3 mb-4" style="background-color: {{ $isVoid ? '#F1F5F9' : ($isIncome ? '#ECFDF5' : '#FEF2F2') }};">
                            <div class="text-muted" style="font-size: 11.5px;">Nominal transaksi</div>
                            <h3 class="fw-bold mb-0 mt-1 {{ $isVoid ? 'text-decoration-line-through text-muted' : '' }}" style="color: {{ $isVoid ? '#64748B' : ($isIncome ? '#15803D' : '#B91C1C') }};">
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
                                <div class="text-muted" style="font-size: 11.5px;">Keterangan</div>
                                <div class="fw-medium" style="font-size: 13px; color: #334155;">{{ $trx->description }}</div>
                            </div>
                            @if($isVoid)
                            <div class="col-12">
                                <div class="p-3 rounded-3" style="background-color: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; font-size: 12.5px;">
                                    <div class="fw-bold mb-1"><i class="bi bi-slash-circle me-1"></i> Transaksi Dibatalkan (Void)</div>
                                    <div>Waktu Void: {{ $trx->voided_at->locale('id')->translatedFormat('d M Y, H:i') }} WIB</div>
                                    <div>Alasan: {{ $trx->void_reason }}</div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <h6 class="fw-bold mb-2" style="font-size: 14px; color: #1E293B;">Bukti Transaksi</h6>
                        <div class="rounded-4 p-4 mb-3 text-center" style="background-color: #F1F5F9; border: 1px solid #E2E8F0;">
                            @if(!empty($trx->proof_path))
                                @if($isImage)
                                    <img src="{{ asset('storage/' . $trx->proof_path) }}" alt="Bukti Transaksi" class="img-fluid rounded-3 shadow-sm" style="max-height: 310px;">
                                @else
                                    <iframe src="{{ asset('storage/' . $trx->proof_path) }}" width="100%" height="310px" class="rounded-3 border-0 bg-white"></iframe>
                                @endif
                            @else
                                <div class="text-muted small py-5">Bukti transaksi tercatat secara elektronik.</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top px-4 py-3 justify-content-end">
                <button type="button" class="btn btn-outline-secondary rounded-3 px-4" data-bs-dismiss="modal" style="font-size: 13px;">Tutup</button>
            </div>
        </div>
    </div>
</div>

@if($isBendahara && !$isVoid)
<!-- Modal Edit Transaksi -->
<div class="modal fade" id="editTransactionModal{{ $trx->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow bg-white p-2">
            <form action="{{ route('transactions.update', $trx->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-header border-bottom-0 pt-3 px-4 pb-2">
                    <h4 class="fw-bold mb-0" style="color: #1E293B;">Edit Transaksi ({{ $trxCode }})</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 13px;">Tanggal Transaksi</label>
                            <input type="date" name="transaction_date" class="form-control rounded-3" value="{{ $trxDate->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 13px;">Kategori Anggaran</label>
                            <select name="category_id" class="form-select rounded-3" required>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ $trx->category_id == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 13px;">Nominal (Rp)</label>
                            <input type="number" name="amount" class="form-control rounded-3" value="{{ (int) $trx->amount }}" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 13px;">Ganti Bukti (Opsional)</label>
                            <input type="file" name="proof" class="form-control rounded-3" accept=".jpg,.jpeg,.png,.pdf">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size: 13px;">Keterangan Transaksi</label>
                            <textarea name="description" class="form-control rounded-3" rows="2" required>{{ $trx->description }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 px-4 pb-4 pt-2">
                    <button type="button" class="btn btn-outline-secondary rounded-3 px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-nexvo-dark px-4 py-2">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Void Transaksi -->
<div class="modal fade" id="voidTransactionModal{{ $trx->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow bg-white p-2">
            <form action="{{ route('transactions.void', $trx->id) }}" method="POST">
                @csrf
                @method('PATCH')

                <div class="modal-header border-bottom-0 pt-3 px-4 pb-0">
                    <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background-color: #FEF2F2; color: #DC2626;">
                        <i class="bi bi-slash-circle fs-5"></i>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body px-4 pt-3">
                    <h5 class="fw-bold mb-1" style="color: #1E293B;">Void Transaksi Kas?</h5>
                    <p class="text-muted mb-3" style="font-size: 13px;">
                        Transaksi <strong>{{ $trxCode }}</strong> akan ditandai sebagai <strong>Void (Dibatalkan)</strong>. Riwayat tetap tercatat di audit log, namun nominalnya tidak lagi dihitung ke dalam saldo kas.
                    </p>

                    <div class="p-3 rounded-3 mb-3" style="background-color: #F4F7FA;">
                        <div class="fw-medium" style="font-size: 12.5px; color: #334155;">{{ $trx->description }}</div>
                        <div class="fw-bold mt-1" style="font-size: 16px; color: #1E293B;">Rp {{ number_format($trx->amount, 0, ',', '.') }}</div>
                    </div>

                    <div class="mb-2">
                        <label class="fw-semibold mb-1" style="font-size: 13px; color: #1E293B;">Alasan Void <span class="text-danger">*</span></label>
                        <textarea name="void_reason" class="form-control rounded-3" rows="2" placeholder="Contoh: Salah input nominal atau duplikasi pencatatan..." style="font-size: 13px; border-color: #E2E8F0;" required></textarea>
                    </div>
                </div>

                <div class="modal-footer border-top-0 px-4 pb-4 pt-2">
                    <div class="row g-2 w-100 m-0">
                        <div class="col-5 ps-0">
                            <button type="button" class="btn btn-outline-secondary w-100 rounded-3 py-2" style="font-size: 13px;" data-bs-dismiss="modal">
                                Batal
                            </button>
                        </div>
                        <div class="col-7 pe-0">
                            <button type="submit" class="btn w-100 rounded-3 py-2 text-white fw-medium" style="background-color: #B91C1C; font-size: 13px;">
                                Ya, Void Transaksi
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endforeach

@if($isBendahara)
<div class="modal fade" id="addTransactionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow bg-white p-2">
            <form action="{{ route('transactions.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header border-bottom-0 pt-3 px-4 pb-2">
                    <h4 class="fw-bold mb-0" style="color: #1E293B;">Catat Transaksi Kas Baru</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 13px;">Tanggal Transaksi</label>
                            <input type="date" name="transaction_date" class="form-control rounded-3" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 13px;">Jenis Kas</label>
                            <select id="modalTypeSelect" class="form-select rounded-3" required>
                                <option value="income">Kas Masuk (Pemasukan)</option>
                                <option value="expense">Kas Keluar (Pengeluaran)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 13px;">Kategori Anggaran</label>
                            <select name="category_id" id="modalCategorySelect" class="form-select rounded-3" required>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" data-type="{{ $cat->type }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 13px;">Nominal (Rp)</label>
                            <input type="number" name="amount" class="form-control rounded-3" min="1" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size: 13px;">Keterangan Transaksi</label>
                            <textarea name="description" class="form-control rounded-3" rows="2" required></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size: 13px;">Unggah Bukti Transaksi</label>
                            <input type="file" name="proof" class="form-control rounded-3" accept=".jpg,.jpeg,.png,.pdf">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 px-4 pb-4 pt-2">
                    <button type="button" class="btn btn-outline-secondary rounded-3 px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-nexvo-dark px-4 py-2">Simpan Transaksi</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
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

    const modalTypeSelect = document.getElementById('modalTypeSelect');
    const modalCategorySelect = document.getElementById('modalCategorySelect');
    if (modalTypeSelect && modalCategorySelect) {
        function syncModalCategories() {
            const selectedType = modalTypeSelect.value;
            let firstVisible = null;
            Array.from(modalCategorySelect.options).forEach(opt => {
                const match = opt.dataset.type === selectedType;
                opt.hidden = !match;
                if (match && !firstVisible) firstVisible = opt;
            });
            if (firstVisible && modalCategorySelect.selectedOptions[0]?.hidden) {
                modalCategorySelect.value = firstVisible.value;
            }
        }
        modalTypeSelect.addEventListener('change', syncModalCategories);
        syncModalCategories();
    }
</script>
@endpush