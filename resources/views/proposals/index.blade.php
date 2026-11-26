@extends('layouts.app')

@section('content')
@php
    $isApprovalTab = ($tab === 'approvals');
    $isHistoryTab = ($tab === 'history');
    $isPenyaluranView = ($isApprovalTab && request()->query('section') === 'penyaluran');
    $showRequesterCol = ($isApprovalTab || $isHistoryTab);
    $currentUser = Auth::user();
    $saldoKasSekarang = $currentSaldoKas ?? 0;
@endphp

<style>
    .method-radio-card {
        border: 1.5px solid #E2E8F0;
        border-radius: 10px;
        padding: 12px 16px;
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        font-size: 13.5px;
        font-weight: 500;
        color: #94A3B8;
        background-color: #FFFFFF;
        transition: all 0.15s ease;
    }
    .method-radio-card.selected {
        border-color: #2F4156;
        color: #1E293B;
        font-weight: 600;
    }
    .method-radio-dot {
        width: 14px;
        height: 14px;
        border-radius: 50%;
        border: 1.5px solid #CBD5E1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .method-radio-card.selected .method-radio-dot {
        border-color: #2F4156;
    }
    .method-radio-card.selected .method-radio-dot::after {
        content: "";
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background-color: #2F4156;
    }
    .disburse-dropzone {
        border: 1.5px dashed #C7B2A3;
        background-color: #FAF6F3;
        border-radius: 10px;
        padding: 24px 16px;
        text-align: center;
        cursor: pointer;
        transition: background-color 0.15s ease;
    }
    .disburse-dropzone:hover {
        background-color: #F4ECE6;
    }
</style>

<!-- Header Halaman -->
<div class="mb-4">
    @if(!$showRequesterCol)
        <div class="text-muted mb-1" style="font-size: 12px;">
            Pengajuan Dana <span class="mx-1">/</span> <span class="text-dark fw-medium">Riwayat Pengajuanku</span>
        </div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h3 class="fw-bold mb-1" style="color: #1E293B;">
                @if($isPenyaluranView) Antrean Pencairan
                @elseif($isApprovalTab) Persetujuan Dana
                @elseif($isHistoryTab) Riwayat Persetujuan
                @else Riwayat Pengajuanku
                @endif
            </h3>
            <p class="text-muted mb-0" style="font-size: 13px;">
                @if($isPenyaluranView) Pengajuan yang sudah disetujui dan menunggu anda cairkan.
                @elseif($isApprovalTab) Daftar pengajuan anggaran yang memerlukan validasi Anda.
                @elseif($isHistoryTab) Rekam jejak pengajuan dana yang telah Anda validasi.
                @else Pantau status dan riwayat permohonan anggaran yang telah Anda ajukan.
                @endif
            </p>
        </div>
        @if(!$showRequesterCol)
            <a href="{{ route('proposals.create') }}" class="btn-nexvo-dark">+ Buat Pengajuan Baru</a>
        @endif
    </div>
</div>

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

<!-- Card Utama -->
<div class="nexvo-card p-4 mb-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start mb-3 gap-2">
        <div>
            <h5 class="fw-bold mb-1" style="font-size: 16px; color: #1E293B;">
                @if($isPenyaluranView) Daftar Antrean Pencairan
                @elseif($isApprovalTab) Daftar Menunggu Validasi
                @elseif($isHistoryTab) Daftar Riwayat Keputusan
                @else Daftar Pengajuan Dana
                @endif
            </h5>
            <p class="text-muted mb-0" style="font-size: 12px;">
                @if($isPenyaluranView) Lihat perkembangan dan detail permohonan anggaran yang siap dicairkan.
                @elseif($isApprovalTab) Pengajuan dana yang membutuhkan keputusan Anda.
                @elseif($isHistoryTab) Riwayat pengajuan dana yang telah disetujui atau ditolak.
                @else Lihat perkembangan dan detail permohonan anggaran Anda.
                @endif
            </p>
        </div>
        @if(!$isPenyaluranView)
            <span class="badge rounded-3 px-4 py-2 fw-semibold" style="background-color: #C5D3DF; color: #243242; font-size: 12px;">
                {{ $proposals->count() }} {{ $isHistoryTab ? 'Riwayat Pengajuan' : 'Pengajuan' }}
            </span>
        @endif
    </div>

    <!-- Bar Pencarian & Filter -->
    <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
        <div class="position-relative" style="width: 100%; max-width: 320px;">
            <i class="bi bi-search text-muted position-absolute" style="top: 50%; left: 14px; transform: translateY(-50%); font-size: 13px;"></i>
            <input type="text" id="searchProposal" class="form-control ps-5 rounded-3" placeholder="{{ ($showRequesterCol && !$isPenyaluranView) ? 'Cari pengaju atau keperluan...' : 'Cari keperluan atau ID pengajuan...' }}" style="font-size: 13px; border-color: #E2E8F0; height: 40px;">
        </div>

        @if($isApprovalTab && !$isPenyaluranView)
            <select id="divisionFilter" class="form-select rounded-3" style="width: 190px; font-size: 13px; border-color: #E2E8F0; height: 40px;">
                <option value="all">Semua Divisi</option>
                @foreach($proposals->pluck('user.division')->filter()->unique() as $div)
                    <option value="{{ strtolower($div) }}">{{ $div }}</option>
                @endforeach
            </select>
        @elseif($isHistoryTab)
            <select id="statusFilter" class="form-select rounded-3" style="width: 175px; font-size: 13px; border-color: #E2E8F0; height: 40px;">
                <option value="all">Semua Status</option>
                <option value="approved">Disetujui</option>
                <option value="rejected">Ditolak</option>
            </select>

            <select id="monthFilter" class="form-select rounded-3" style="width: 175px; font-size: 13px; border-color: #E2E8F0; height: 40px;">
                <option value="all">Semua Bulan</option>
                @foreach($proposals->toBase()->map(fn($p) => $p->created_at->format('Y-m'))->unique() as $ym)
                    <option value="{{ $ym }}">{{ \Carbon\Carbon::parse($ym . '-01')->locale('id')->translatedFormat('F Y') }}</option>
                @endforeach
            </select>

            <select id="divisionFilter" class="form-select rounded-3" style="width: 175px; font-size: 13px; border-color: #E2E8F0; height: 40px;">
                <option value="all">Semua Divisi</option>
                @foreach($proposals->pluck('user.division')->filter()->unique() as $div)
                    <option value="{{ strtolower($div) }}">{{ $div }}</option>
                @endforeach
            </select>
        @elseif(!$isPenyaluranView)
            <select id="statusFilter" class="form-select rounded-3" style="width: 210px; font-size: 13px; border-color: #E2E8F0; height: 40px;">
                <option value="all">Semua Status</option>
                <option value="pending">Menunggu Validasi</option>
                <option value="approved">Disetujui</option>
                <option value="rejected">Ditolak</option>
            </select>
        @endif
    </div>

    <!-- Tabel Pengajuan -->
    <div class="table-responsive">
        <table class="table align-middle mb-0" id="proposalTable" style="font-size: 13px;">
            <thead style="background-color: #F4F7FA; color: #64748B;">
                @if($isPenyaluranView)
                    <tr>
                        <th class="fw-medium py-3 ps-3 rounded-start-3">Pengaju</th>
                        <th class="fw-medium py-3">ID / Keperluan</th>
                        <th class="fw-medium py-3">Nominal</th>
                        <th class="fw-medium py-3">Disetujui</th>
                        <th class="fw-medium py-3 text-end pe-3 rounded-end-3">Aksi</th>
                    </tr>
                @else
                    <tr>
                        <th class="fw-medium py-3 ps-3 rounded-start-3">Tanggal</th>
                        @if($showRequesterCol)
                            <th class="fw-medium py-3">Pengaju &amp; Divisi</th>
                        @endif
                        <th class="fw-medium py-3">{{ $showRequesterCol ? 'Keperluan' : 'ID / Keperluan' }}</th>
                        <th class="fw-medium py-3 {{ $isApprovalTab ? 'text-end pe-4' : '' }}">Nominal</th>
                        @if(!$isApprovalTab)
                            <th class="fw-medium py-3">Status</th>
                        @endif
                        <th class="fw-medium py-3 text-end pe-3 rounded-end-3">Aksi</th>
                    </tr>
                @endif
            </thead>
            <tbody>
                @forelse($proposals as $prop)
                @php
                    $pgjCode = 'PGJ-' . $prop->created_at->format('Y') . '-' . str_pad($prop->id, 3, '0', STR_PAD_LEFT);
                    $code = ($showRequesterCol && !$isPenyaluranView ? 'REQ-' . $prop->created_at->format('Ym') . '-' . str_pad($prop->id, 3, '0', STR_PAD_LEFT) : $pgjCode);
                    $isPendingState = str_starts_with($prop->status, 'pending');
                    $historyDecision = ($prop->status === 'rejected') ? 'rejected' : 'approved';
                    $filterGroup = $isHistoryTab ? $historyDecision : ($isPendingState ? 'pending' : $prop->status);

                    $statusLabel = match ($prop->status) {
                        'pending_koordiv' => 'Menunggu Koordiv',
                        'pending_ketua' => 'Menunggu Ketua',
                        'pending_bendahara' => 'Menunggu Bendahara',
                        'pending_disbursement' => 'Menunggu Pencairan',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        default => 'Menunggu Validasi',
                    };
                @endphp

                @if($isPenyaluranView)
                <tr class="proposal-row"
                    data-status="{{ $filterGroup }}"
                    data-month="{{ $prop->created_at->format('Y-m') }}"
                    data-division="{{ strtolower($prop->user->division ?? 'umum') }}"
                    data-search="{{ strtolower($pgjCode . ' ' . $prop->title . ' ' . $prop->user->name . ' ' . ($prop->user->division ?? '')) }}">
                    <td class="py-3 ps-3">
                        <div class="fw-semibold" style="color: #1E293B;">{{ $prop->user->name }}</div>
                        <div class="text-muted text-uppercase" style="font-size: 10.5px; letter-spacing: 0.4px;">
                            {{ $prop->user->division ?? ucfirst($prop->user->role) }}
                        </div>
                    </td>
                    <td class="py-3">
                        <div class="text-muted" style="font-size: 11px;">{{ $pgjCode }}</div>
                        <div class="fw-medium" style="color: #1E293B;">{{ $prop->title }}</div>
                    </td>
                    <td class="py-3 fw-bold" style="color: #1E293B;">
                        Rp {{ number_format($prop->amount, 0, ',', '.') }}
                    </td>
                    <td class="py-3 text-muted">
                        {{ $prop->updated_at->locale('id')->translatedFormat('d F Y') }}
                    </td>
                    <td class="py-3 text-end pe-3">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-3 px-3 py-1 bg-white" data-bs-toggle="modal" data-bs-target="#detailModal{{ $prop->id }}" style="font-size: 12.5px; border-color: #CBD5E1;">
                            <i class="bi bi-eye me-1"></i> Detail
                        </button>
                    </td>
                </tr>
                @else
                <tr class="proposal-row"
                    data-status="{{ $filterGroup }}"
                    data-month="{{ $prop->created_at->format('Y-m') }}"
                    data-division="{{ strtolower($prop->user->division ?? 'umum') }}"
                    data-search="{{ strtolower($code . ' ' . $prop->title . ' ' . $prop->user->name . ' ' . ($prop->user->division ?? '')) }}">
                    <td class="py-3 ps-3 text-muted">{{ $prop->created_at->locale('id')->translatedFormat('d M Y') }}</td>

                    @if($showRequesterCol)
                    <td class="py-3">
                        <div class="fw-semibold" style="color: #1E293B;">{{ $prop->user->name }}</div>
                        <div class="text-muted" style="font-size: 11.5px;">{{ $prop->user->division ?? ucfirst($prop->user->role) }}</div>
                    </td>
                    @endif

                    <td class="py-3">
                        @if(!$showRequesterCol)
                            <div class="text-muted" style="font-size: 11px;">{{ $code }}</div>
                        @endif
                        <div class="fw-medium" style="color: #1E293B;">{{ $prop->title }}</div>
                        @if(!$showRequesterCol && $prop->status === 'approved')
                            @if(($prop->realization_status ?? 'unsubmitted') === 'approved')
                                <span class="badge rounded-2 mt-1" style="background-color: #DCFCE7; color: #15803D; font-size: 10px;">Bukti Realisasi Terverifikasi</span>
                            @elseif($prop->realization_status === 'pending')
                                <span class="badge rounded-2 mt-1" style="background-color: #FEF3C7; color: #B45309; font-size: 10px;">Bukti Menunggu Verifikasi</span>
                            @elseif($prop->realization_status === 'rejected')
                                <span class="badge rounded-2 mt-1" style="background-color: #FEE2E2; color: #B91C1C; font-size: 10px;">Bukti Ditolak &bull; Unggah Ulang</span>
                            @else
                                <span class="badge rounded-2 mt-1" style="background-color: #E0F2FE; color: #0369A1; font-size: 10px;">Perlu Unggah Struk Realisasi</span>
                            @endif
                        @endif
                    </td>

                    <td class="py-3 fw-bold {{ $isApprovalTab ? 'text-end pe-4' : '' }}" style="color: #1E293B;">
                        Rp {{ number_format($prop->amount, 0, ',', '.') }}
                    </td>

                    @if(!$isApprovalTab)
                    <td class="py-3">
                        @if($isHistoryTab)
                            @if($historyDecision === 'approved')
                                <span class="badge rounded-2 px-3 py-2 fw-medium" style="background-color: #DCFCE7; color: #15803D;">Disetujui</span>
                            @else
                                <span class="badge rounded-2 px-3 py-2 fw-medium" style="background-color: #FEE2E2; color: #B91C1C;">Ditolak</span>
                            @endif
                        @else
                            @if($isPendingState)
                                <span class="badge rounded-2 px-3 py-2 fw-medium" style="background-color: #FEF3C7; color: #B45309;">{{ $statusLabel }}</span>
                            @elseif($prop->status === 'approved')
                                <span class="badge rounded-2 px-3 py-2 fw-medium" style="background-color: #DCFCE7; color: #15803D;">Disetujui</span>
                            @else
                                <span class="badge rounded-2 px-3 py-2 fw-medium" style="background-color: #FEE2E2; color: #B91C1C;">Ditolak</span>
                            @endif
                        @endif
                    </td>
                    @endif

                    <td class="py-3 text-end pe-3">
                        @if($isApprovalTab)
                            <button type="button" class="btn-nexvo-dark px-4 py-2" data-bs-toggle="modal" data-bs-target="#detailModal{{ $prop->id }}">
                                Review
                            </button>
                        @else
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-3 px-3 py-1" data-bs-toggle="modal" data-bs-target="#detailModal{{ $prop->id }}" style="font-size: 12.5px;">
                                <i class="bi bi-eye me-1"></i> Detail
                            </button>
                        @endif
                    </td>
                </tr>
                @endif
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-5">
                        @if($isPenyaluranView) Tidak ada antrean pencairan dana saat ini.
                        @elseif($isApprovalTab) Tidak ada pengajuan dana yang menunggu validasi Anda saat ini.
                        @elseif($isHistoryTab) Belum ada riwayat persetujuan pengajuan.
                        @else Anda belum pernah membuat pengajuan anggaran.
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Footer Tabel -->
    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top" style="font-size: 12px; color: #64748B;">
        <div>
            Menampilkan <span id="visibleCount">{{ $proposals->count() }}</span>
            @if($isPenyaluranView) dari {{ $proposals->count() }} pengajuan
            @elseif($isApprovalTab) pengajuan menunggu validasi
            @elseif($isHistoryTab) riwayat pengajuan
            @else dari {{ $proposals->count() }} pengajuan
            @endif
        </div>
        @if($isHistoryTab)
            <span class="text-muted">Arsip keputusan &bull; Hanya baca</span>
        @elseif($isPenyaluranView || !$isApprovalTab)
            <span class="badge rounded-2 px-3 py-2" style="background-color: #607990; color: white;">1</span>
        @endif
    </div>
</div>

<!-- Banner Notifikasi Sukses di Bagian Bawah -->
@if(session('success_title'))
<div class="rounded-3 p-3 px-4 mt-3 d-flex align-items-center gap-3" style="background-color: #ECFDF5; border: 1px solid #A7F3D0; color: #065F46;">
    <i class="bi bi-shield-check fs-4 text-success"></i>
    <div>
        <div class="fw-bold" style="font-size: 13.5px;">{{ session('success_title') }}</div>
        <div style="font-size: 12px; color: #047857;">{{ session('success') }}</div>
    </div>
</div>
@elseif(session('success'))
<div class="rounded-3 p-3 px-4 mt-3 d-flex align-items-center gap-3" style="background-color: #ECFDF5; border: 1px solid #A7F3D0; color: #065F46;">
    <i class="bi bi-check-circle fs-4 text-success"></i>
    <div class="fw-medium" style="font-size: 13px;">{{ session('success') }}</div>
</div>
@endif

<!-- ===================================================================== -->
<!-- SEMUA MODAL DILETAKKAN DI LUAR TABEL AGAR RENDER BOOTSTRAP SEMPURNA   -->
<!-- ===================================================================== -->
@foreach($proposals as $prop)
@php
    $code = ($showRequesterCol && !$isPenyaluranView ? 'REQ-' . $prop->created_at->format('Ym') : 'PGJ-' . $prop->created_at->format('Y')) . '-' . str_pad($prop->id, 3, '0', STR_PAD_LEFT);
    $isPendingState = str_starts_with($prop->status, 'pending');
    $historyDecision = ($prop->status === 'rejected') ? 'rejected' : 'approved';

    $canApproveNow = ($prop->user_id !== $currentUser->id) && (
        ($prop->status === 'pending_koordiv' && $currentUser->role === 'koordiv') ||
        ($prop->status === 'pending_ketua' && $currentUser->role === 'ketua') ||
        ($prop->status === 'pending_bendahara' && $currentUser->role === 'bendahara')
    );

    $statusLabel = match ($prop->status) {
        'pending_koordiv' => 'Menunggu Koordiv',
        'pending_ketua' => 'Menunggu Ketua',
        'pending_bendahara' => 'Menunggu Bendahara',
        'pending_disbursement' => 'Menunggu Pencairan',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
        default => 'Menunggu Validasi',
    };

    $ext = $prop->document_path ? strtolower(pathinfo($prop->document_path, PATHINFO_EXTENSION)) : null;
    $isImage = in_array($ext, ['jpg', 'jpeg', 'png']);

    $accountNumber = $prop->account_number ?? '-';
    $bankName = $prop->bank_name ?? '-';
    $accountHolderName = $prop->account_holder_name ?? '-';

    preg_match('/(BCA|BRI|BNI|Mandiri|BSI|DANA|OVO|GoPay)\s*[-:]?\s*\d{6,}/i', $prop->reason, $rekMatch);
    $rekeningTujuan = $rekMatch[0] ?? 'BCA 0123456789';
    $saldoSetelahCair = $saldoKasSekarang - $prop->amount;
@endphp

@if($isPenyaluranView)
<!-- ===================================================================== -->
<!-- MODAL FRAME 125: RINCIAN DAN PENCAIRAN (ANTREAN PENCAIRAN BENDAHARA)  -->
<!-- ===================================================================== -->
<div class="modal fade" id="detailModal{{ $prop->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <div class="modal-content border-0 rounded-4 shadow bg-white p-3">
            <form action="{{ route('proposals.updateStatus', $prop->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PATCH')
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="is_disbursement" value="1">
                <input type="hidden" name="disbursement_method" id="disburseMethodInput{{ $prop->id }}" value="Transfer">

                <div class="modal-body p-3">
                    <h4 class="fw-bold mb-1" style="color: #1E293B; font-size: 20px;">Rincian dan Pencairan</h4>
                    <div class="mb-3" style="font-size: 12.5px; color: #64748B;">
                        Pengajuan dipilih:
                        <span class="fw-semibold" style="color: #2F4156;">
                            {{ $prop->user->name }} - {{ strtoupper($prop->user->division ?? $prop->user->role) }}
                        </span>
                    </div>

                    <h2 class="fw-bold mb-4" style="color: #1E293B; font-size: 32px; letter-spacing: -0.5px;">
                        Rp {{ number_format($prop->amount, 0, ',', '.') }}
                    </h2>

                    <div class="d-flex flex-column gap-2.5 mb-4" style="font-size: 13px;">
                        <div class="d-flex justify-content-between align-items-center py-1">
                            <span style="color: #64748B;">Keperluan</span>
                            <span class="fw-semibold text-end" style="color: #1E293B;">{{ $prop->title }}</span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center py-1">
                            <span style="color: #64748B;">Pemilik Rekening</span>
                            <span class="fw-semibold text-end" style="color: #1E293B;">{{ $accountHolderName }}</span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center py-1">
                            <span style="color: #64748B;">Bank</span>
                            <span class="fw-semibold text-end" style="color: #1E293B;">{{ $bankName }}</span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center py-1">
                            <span style="color: #64748B;">Nomor Rekening</span>
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-semibold" id="rekText{{ $prop->id }}" style="color: #1E293B;">{{ $accountNumber }}</span>
                                <button type="button" class="btn btn-link p-0 text-muted" title="Salin Nomor Rekening" onclick="copyRekening('{{ $accountNumber }}', this)">
                                    <i class="bi bi-copy"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center py-1">
                            <span style="color: #64748B;">Disetujui pada</span>
                            <span class="fw-semibold" style="color: #1E293B;">{{ $prop->updated_at->locale('id')->translatedFormat('d M Y, H:i') }} WIB</span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center py-1">
                            <span style="color: #64748B;">Saldo kas</span>
                            <span style="color: #475569;">
                                Rp {{ number_format($saldoKasSekarang, 0, ',', '.') }}
                                <span class="mx-1">&rarr;</span>
                                <strong style="color: #1E293B;">Rp {{ number_format($saldoSetelahCair, 0, ',', '.') }}</strong>
                            </span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="fw-bold mb-2 d-block" style="font-size: 14px; color: #1E293B;">Metode Pencairan</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="method-radio-card selected" id="methodTransferCard{{ $prop->id }}" onclick="selectDisburseMethod({{ $prop->id }}, 'Transfer')">
                                    <span class="method-radio-dot"></span>
                                    <span>Transfer</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="method-radio-card" id="methodTunaiCard{{ $prop->id }}" onclick="selectDisburseMethod({{ $prop->id }}, 'Tunai')">
                                    <span class="method-radio-dot"></span>
                                    <span>Tunai</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="fw-bold mb-2 d-block" style="font-size: 14px; color: #1E293B;">Bukti pencairan (wajib)</label>
                        <input type="file" name="proof_file" id="proofFileInput{{ $prop->id }}" class="d-none" accept=".png,.jpg,.jpeg,image/png,image/jpeg" required onchange="previewProofFile({{ $prop->id }}, this)">
                        <div class="disburse-dropzone" onclick="document.getElementById('proofFileInput{{ $prop->id }}').click()">
                            <div id="proofPlaceholder{{ $prop->id }}" style="font-size: 12px; color: #64748B; line-height: 1.5;">
                                Unggah foto bukti transfer atau serah terima<br>
                                <span class="text-muted">png atau JPEG, maksimal 2MB</span>
                            </div>
                            <div id="proofSelectedInfo{{ $prop->id }}" class="d-none">
                                <i class="bi bi-check-circle-fill text-success fs-5 d-block mb-1"></i>
                                <span class="fw-semibold text-dark small" id="proofFileName{{ $prop->id }}"></span>
                                <div class="text-muted" style="font-size: 11px;">Klik untuk mengganti file</div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-8">
                            <button type="submit" class="btn w-100 rounded-3 py-2.5 fw-semibold text-white" style="background-color: #2F4156; font-size: 14px; height: 44px;">
                                Cairkan Dana
                            </button>
                        </div>
                        <div class="col-4">
                            <button type="button" class="btn w-100 rounded-3 py-2.5 fw-semibold" data-bs-dismiss="modal" style="background-color: #C8D9E6; color: #2F4156; font-size: 14px; height: 44px;">
                                Batal
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@elseif($isApprovalTab)
<!-- ===================================================================== -->
<!-- MODAL 1A: REVIEW (TAB MENUNGGU VALIDASI - KOORDIV / KETUA / BENDAHARA)-->
<!-- ===================================================================== -->
<div class="modal fade" id="detailModal{{ $prop->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow bg-white p-2">
            <div class="modal-header border-bottom-0 pt-3 px-4 pb-2 d-flex align-items-start justify-content-between">
                <div>
                    <h4 class="fw-bold mb-2" style="color: #1E293B;">Detail Pengajuan Dana</h4>
                    <span class="badge rounded-2 px-3 py-1 fw-medium" style="background-color: #FEF3C7; color: #B45309; font-size: 11.5px;">Menunggu Validasi</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body px-4 py-3">
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <div class="text-muted" style="font-size: 11.5px;">ID Pengajuan</div>
                        <div class="fw-semibold" style="font-size: 13.5px;">{{ $code }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted" style="font-size: 11.5px;">Diminta Oleh</div>
                        <div class="fw-semibold" style="font-size: 13.5px;">{{ $prop->user->name }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted" style="font-size: 11.5px;">Divisi</div>
                        <div class="fw-semibold" style="font-size: 13.5px;">{{ $prop->user->division ?? ucfirst($prop->user->role) }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted" style="font-size: 11.5px;">Tanggal Pengajuan</div>
                        <div class="fw-semibold" style="font-size: 13.5px;">{{ $prop->created_at->locale('id')->translatedFormat('d M Y') }}</div>
                    </div>
                </div>

                <div class="p-3 rounded-3 mb-3" style="background-color: #F4F7FA;">
                    <div class="text-muted" style="font-size: 11.5px;">Nominal Pengajuan</div>
                    <h3 class="fw-bold mb-0 mt-1" style="color: #1E293B;">Rp {{ number_format($prop->amount, 0, ',', '.') }}</h3>
                </div>

                <div class="mb-3">
                    <div class="fw-semibold mb-1" style="font-size: 13px; color: #1E293B;">Keterangan / Rincian Pengajuan</div>
                    <div class="p-3 rounded-3" style="background-color: #F8FAFC; font-size: 13px; color: #334155;">
                        <div class="fw-semibold mb-1">{{ $prop->title }}</div>
                        <div>{{ $prop->reason }}</div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="fw-semibold mb-2" style="font-size: 13px; color: #1E293B;">Dokumen Pendukung</div>
                    @if(!empty($prop->document_path))
                        <div class="border rounded-3 p-3 d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background-color: #F1F5F9; color: #64748B;">
                                    <i class="bi bi-file-earmark-text fs-5"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold" style="font-size: 13px;">{{ basename($prop->document_path) }}</div>
                                    <div class="text-muted" style="font-size: 11px;">Dokumen pendukung pengajuan dana</div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-3 px-3" data-bs-toggle="modal" data-bs-target="#previewDocModal{{ $prop->id }}">
                                <i class="bi bi-eye me-1"></i> Lihat
                            </button>
                        </div>
                    @else
                        <div class="border rounded-3 p-3 text-muted" style="background-color: #F8FAFC; border-style: dashed !important; font-size: 12.5px;">
                            <i class="bi bi-file-earmark-x me-1"></i> Tidak ada dokumen pendukung yang dilampirkan.
                        </div>
                    @endif
                </div>

                <div class="mb-2">
                    <label class="fw-semibold mb-1" style="font-size: 13px; color: #1E293B;">Catatan Review (Opsional)</label>
                    <textarea id="reviewNoteInput{{ $prop->id }}" class="form-control rounded-3" rows="3" placeholder="Tambahkan catatan atau alasan penolakan..." style="font-size: 13px; border-color: #E2E8F0;" oninput="syncReviewNote({{ $prop->id }})"></textarea>
                </div>
            </div>

            <div class="modal-footer border-top-0 px-4 pb-4 pt-2">
                @if($canApproveNow)
                    <div class="row g-2 w-100 m-0">
                        <div class="col-6 ps-0">
                            <button type="button" class="btn w-100 rounded-3 py-2 fw-medium" style="background-color: #FEF2F2; border: 1px solid #FECACA; color: #DC2626; font-size: 13px;" data-bs-toggle="modal" data-bs-target="#rejectConfirmModal{{ $prop->id }}">
                                <i class="bi bi-x-circle me-1"></i> Tolak Pengajuan
                            </button>
                        </div>
                        <div class="col-6 pe-0">
                            <button type="button" class="btn-nexvo-dark w-100 py-2" style="font-size: 13px;" data-bs-toggle="modal" data-bs-target="#approveConfirmModal{{ $prop->id }}">
                                <i class="bi bi-check-circle me-1"></i> Setujui Pengajuan
                            </button>
                        </div>
                    </div>
                @else
                    <button type="button" class="btn btn-outline-secondary rounded-3 px-4 ms-auto" data-bs-dismiss="modal" style="font-size: 13px;">Tutup</button>
                @endif
            </div>
        </div>
    </div>
</div>

@elseif($isHistoryTab)
<!-- ===================================================================== -->
<!-- MODAL 1B: DETAIL RIWAYAT PENGAJUAN (TAB RIWAYAT PERSETUJUAN)          -->
<!-- ===================================================================== -->
<div class="modal fade" id="detailModal{{ $prop->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow bg-white p-2">
            <div class="modal-header border-bottom-0 pt-3 px-4 pb-2 d-flex align-items-start justify-content-between">
                <div>
                    <h4 class="fw-bold mb-2" style="color: #1E293B;">Detail Riwayat Pengajuan</h4>
                    @if($historyDecision === 'approved')
                        <span class="badge rounded-2 px-3 py-1 fw-medium" style="background-color: #DCFCE7; color: #15803D; font-size: 11.5px;">Disetujui</span>
                    @else
                        <span class="badge rounded-2 px-3 py-1 fw-medium" style="background-color: #FEE2E2; color: #B91C1C; font-size: 11.5px;">Ditolak</span>
                    @endif
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body px-4 py-3">
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <div class="text-muted" style="font-size: 11.5px;">ID Pengajuan</div>
                        <div class="fw-semibold" style="font-size: 13.5px;">{{ $code }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted" style="font-size: 11.5px;">Diminta Oleh</div>
                        <div class="fw-semibold" style="font-size: 13.5px;">{{ $prop->user->name }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted" style="font-size: 11.5px;">Divisi</div>
                        <div class="fw-semibold" style="font-size: 13.5px;">{{ $prop->user->division ?? ucfirst($prop->user->role) }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted" style="font-size: 11.5px;">Tanggal Pengajuan</div>
                        <div class="fw-semibold" style="font-size: 13.5px;">{{ $prop->created_at->locale('id')->translatedFormat('d M Y') }}</div>
                    </div>
                </div>

                <div class="p-3 rounded-3 mb-3" style="background-color: #F4F7FA;">
                    <div class="text-muted" style="font-size: 11.5px;">Nominal Pengajuan</div>
                    <h3 class="fw-bold mb-0 mt-1" style="color: #1E293B;">Rp {{ number_format($prop->amount, 0, ',', '.') }}</h3>
                </div>

                <div class="mb-3">
                    <div class="fw-semibold mb-1" style="font-size: 13px; color: #1E293B;">Keterangan / Rincian Pengajuan</div>
                    <div class="p-3 rounded-3" style="background-color: #F8FAFC; font-size: 13px; color: #334155;">
                        <div class="fw-semibold mb-1">{{ $prop->title }}</div>
                        <div>{{ $prop->reason }}</div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="fw-semibold mb-2" style="font-size: 13px; color: #1E293B;">Dokumen Pendukung</div>
                    @if(!empty($prop->document_path))
                        <div class="border rounded-3 p-3 d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background-color: #F1F5F9; color: #64748B;">
                                    <i class="bi bi-file-earmark-text fs-5"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold" style="font-size: 13px;">{{ basename($prop->document_path) }}</div>
                                    <div class="text-muted" style="font-size: 11px;">Dokumen pendukung pengajuan dana</div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-3 px-3" data-bs-toggle="modal" data-bs-target="#previewDocModal{{ $prop->id }}">
                                <i class="bi bi-eye me-1"></i> Lihat
                            </button>
                        </div>
                    @else
                        <div class="border rounded-3 p-3 text-muted" style="background-color: #F8FAFC; border-style: dashed !important; font-size: 12.5px;">
                            <i class="bi bi-file-earmark-x me-1"></i> Tidak ada dokumen pendukung yang dilampirkan.
                        </div>
                    @endif
                </div>

                <div class="p-3 rounded-3 mb-3" style="background-color: {{ $historyDecision === 'rejected' ? '#FEF2F2' : '#F4F7FA' }}; border: 1px solid {{ $historyDecision === 'rejected' ? '#FECACA' : '#E2E8F0' }};">
                    <div class="fw-bold mb-2" style="font-size: 13px; color: #1E293B;">Informasi Validasi</div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <div class="text-muted" style="font-size: 11px;">Divalidasi Oleh</div>
                            <div class="fw-semibold" style="font-size: 12.5px;">{{ ucfirst($currentUser->role) }} NEXVO Studio</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted" style="font-size: 11px;">Divalidasi Pada</div>
                            <div class="fw-semibold" style="font-size: 12.5px;">{{ $prop->updated_at->locale('id')->translatedFormat('d M Y, H:i') }} WIB</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3 pt-1">
                        <span class="text-muted" style="font-size: 11.5px;">Status Keputusan</span>
                        <span class="fw-bold" style="font-size: 12.5px; color: {{ $historyDecision === 'rejected' ? '#B91C1C' : '#15803D' }};">
                            {{ $historyDecision === 'rejected' ? 'Ditolak' : 'Disetujui' }}
                        </span>
                    </div>
                </div>

                <div>
                    <div class="fw-semibold mb-1" style="font-size: 13px; color: #1E293B;">Catatan {{ ucfirst($currentUser->role) }}</div>
                    <div class="p-3 rounded-3" style="background-color: #F8FAFC; font-size: 12.5px; color: #334155;">
                        @if($historyDecision === 'rejected')
                            {{ $prop->rejection_reason ?? ($prop->review_note ?? 'Pengajuan ditolak karena rincian kebutuhan belum sesuai.') }}
                        @else
                            {{ $prop->review_note ?? 'Pengajuan disetujui karena kebutuhan tersebut sesuai dengan anggaran operasional organisasi.' }}
                        @endif
                    </div>
                </div>
            </div>

            <div class="modal-footer border-top-0 px-4 pb-4 pt-2 justify-content-end">
                <button type="button" class="btn-nexvo-dark px-5 py-2" data-bs-dismiss="modal" style="font-size: 13px;">Tutup</button>
            </div>
        </div>
    </div>
</div>

@else
<!-- ===================================================================== -->
<!-- MODAL 1C: DETAIL PENGAJUAN (TAB RIWAYAT PENGAJUANKU - 2 KOLOM UI)     -->
<!-- ===================================================================== -->
<div class="modal fade" id="detailModal{{ $prop->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow bg-white">
            <div class="modal-header border-bottom-0 pt-4 px-4 pb-2 d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="fw-bold mb-0" style="color: #1E293B;">Detail Pengajuan Dana</h4>
                    <span class="text-muted" style="font-size: 12px;">{{ $code }}</span>
                </div>
                <div class="d-flex align-items-center gap-3 ms-auto">
                    @if($isPendingState)
                        <span class="badge rounded-2 px-4 py-2 fw-medium" style="background-color: #FEF3C7; color: #B45309;">{{ $statusLabel }}</span>
                    @elseif($prop->status === 'approved')
                        <span class="badge rounded-2 px-4 py-2 fw-medium" style="background-color: #DCFCE7; color: #15803D;">Disetujui</span>
                    @else
                        <span class="badge rounded-2 px-4 py-2 fw-medium" style="background-color: #FEE2E2; color: #B91C1C;">Ditolak</span>
                    @endif
                    <button type="button" class="btn-close ms-0" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <div class="modal-body px-4 py-3">
                <div class="row g-4">
                    <div class="col-lg-7">
                        <h6 class="fw-bold mb-3" style="font-size: 15px; color: #1E293B;">Informasi Pengajuan</h6>

                        <div class="mb-3">
                            <div class="text-muted" style="font-size: 11.5px;">Judul Keperluan</div>
                            <div class="fw-semibold" style="font-size: 15px; color: #1E293B;">{{ $prop->title }}</div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-6">
                                <div class="text-muted" style="font-size: 11.5px;">Kategori / Divisi</div>
                                <div class="fw-semibold" style="font-size: 13.5px;">{{ $prop->user->division ?? ucfirst($prop->user->role) }}</div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted" style="font-size: 11.5px;">Tanggal Pengajuan</div>
                                <div class="fw-semibold" style="font-size: 13.5px;">{{ $prop->created_at->locale('id')->translatedFormat('d F Y, H.i') }} WIB</div>
                            </div>
                        </div>

                        <div class="p-3 rounded-3 mb-3" style="background-color: #F4F7FA;">
                            <div class="text-muted" style="font-size: 11.5px;">Nominal Pengajuan</div>
                            <h3 class="fw-bold mb-0 mt-1" style="color: #1E293B;">Rp {{ number_format($prop->amount, 0, ',', '.') }}</h3>
                        </div>

                        <div class="mb-4">
                            <div class="text-muted mb-1" style="font-size: 11.5px;">Keterangan</div>
                            <p class="mb-0" style="font-size: 13px; line-height: 1.6; color: #334155;">{{ $prop->reason }}</p>
                        </div>

                        <h6 class="fw-bold mb-2" style="font-size: 14px; color: #1E293B;">Dokumen Pendukung</h6>
                        @if(!empty($prop->document_path))
                            <div class="border rounded-3 p-3 d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-2 d-flex align-items-center justify-content-center fw-bold text-uppercase" style="width: 42px; height: 42px; background-color: {{ $isImage ? '#DBEAFE' : '#FEE2E2' }}; color: {{ $isImage ? '#1D4ED8' : '#DC2626' }}; font-size: 11px;">
                                        {{ $ext }}
                                    </div>
                                    <div>
                                        <div class="fw-semibold" style="font-size: 13px;">{{ basename($prop->document_path) }}</div>
                                        <div class="text-muted" style="font-size: 11px;">Dokumen Pendukung</div>
                                        <div class="d-flex gap-3 mt-1" style="font-size: 12px;">
                                            <a href="#" class="text-decoration-none fw-medium" style="color: #243242;" data-bs-toggle="modal" data-bs-target="#previewDocModal{{ $prop->id }}">
                                                <i class="bi bi-eye me-1"></i> Lihat Dokumen
                                            </a>
                                            <a href="{{ asset('storage/' . $prop->document_path) }}" download class="text-decoration-none fw-medium text-muted">
                                                <i class="bi bi-download me-1"></i> Unduh
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="border rounded-3 p-3 d-flex align-items-center gap-3" style="background-color: #F8FAFC; border-style: dashed !important;">
                                <div class="rounded-2 d-flex align-items-center justify-content-center text-muted" style="width: 38px; height: 38px; background-color: #E2E8F0;">
                                    <i class="bi bi-file-earmark-x"></i>
                                </div>
                                <div>
                                    <div class="fw-medium text-muted" style="font-size: 13px;">Tidak ada dokumen pendukung</div>
                                    <div class="text-muted" style="font-size: 11px;">Pengajuan ini dikirim tanpa lampiran file tambahan.</div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Kolom Kanan: Jalur Persetujuan + Unggah Bukti Realisasi -->
                    <div class="col-lg-5">
                        <div class="border rounded-4 p-4 h-100 d-flex flex-column justify-content-between" style="background-color: #FAFAFA;">
                            <div>
                                @php
                                    $totalSteps = match ($prop->user->role) {
                                        'anggota' => 4,
                                        'koordiv', 'ketua' => 3,
                                        default => 2,
                                    };
                                    $completedSteps = match ($prop->status) {
                                        'pending_koordiv' => 1,
                                        'pending_ketua' => ($prop->user->role === 'anggota' ? 2 : 1),
                                        'pending_bendahara' => 1,
                                        'pending_disbursement' => ($prop->user->role === 'anggota' ? 3 : 2),
                                        'approved', 'rejected' => $totalSteps,
                                        default => 1,
                                    };
                                @endphp
                                <h6 class="fw-bold mb-0" style="font-size: 15px; color: #1E293B;">Jalur Persetujuan</h6>
                                <div class="text-muted mb-4" style="font-size: 11.5px;">{{ $completedSteps }} dari {{ $totalSteps }} tahap diproses</div>

                                <!-- Tahap 1: Pengajuan Dibuat -->
                                <div class="d-flex gap-3 mb-4">
                                    <div>
                                        <span class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 30px; height: 30px; background-color: #243242;">
                                            <i class="bi bi-check-lg small"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fw-bold" style="font-size: 13px;">Pengajuan Dibuat</div>
                                        <div class="text-muted" style="font-size: 12px;">Pengajuan berhasil dikirim oleh {{ $prop->user->name }}.</div>
                                        <div class="text-muted mt-1" style="font-size: 11px;">{{ $prop->created_at->locale('id')->translatedFormat('d M Y, H.i') }} WIB</div>
                                        <div class="fw-semibold" style="font-size: 11px; color: #243242;">Selesai</div>
                                    </div>
                                </div>

                                <!-- Tahap Koordiv (Khusus Anggota) -->
                                @if($prop->user->role === 'anggota')
                                @php $koordivWaiting = ($prop->status === 'pending_koordiv'); @endphp
                                <div class="d-flex gap-3 mb-4">
                                    <div>
                                        <span class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 30px; height: 30px; background-color: {{ $koordivWaiting ? '#D97706' : '#16A34A' }};">
                                            <i class="bi {{ $koordivWaiting ? 'bi-clock' : 'bi-check-lg' }} small"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fw-bold" style="font-size: 13px;">Validasi Koordinator Divisi</div>
                                        <div class="text-muted" style="font-size: 12px;">
                                            {{ $koordivWaiting ? 'Menunggu pemeriksaan Koordinator Divisi.' : 'Telah divalidasi oleh Koordinator Divisi.' }}
                                        </div>
                                        <div class="fw-semibold mt-1" style="font-size: 11px; color: {{ $koordivWaiting ? '#D97706' : '#16A34A' }};">
                                            {{ $koordivWaiting ? 'Menunggu' : 'Selesai' }}
                                        </div>
                                    </div>
                                </div>
                                @endif

                                <!-- Tahap Ketua (Untuk Anggota, Koordiv, Bendahara) -->
                                @if(in_array($prop->user->role, ['anggota', 'koordiv', 'bendahara']))
                                @php
                                    $isRejectedAtKetua = ($prop->status === 'rejected' && $prop->user->role === 'bendahara');
                                    $ketuaDone = in_array($prop->status, ['pending_disbursement', 'approved']);
                                    $ketuaWaiting = ($prop->status === 'pending_ketua');
                                @endphp
                                <div class="d-flex gap-3 mb-4">
                                    <div>
                                        <span class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 30px; height: 30px; background-color: {{ $isRejectedAtKetua ? '#B91C1C' : ($ketuaDone ? '#16A34A' : ($ketuaWaiting ? '#D97706' : '#CBD5E1')) }};">
                                            <i class="bi {{ $isRejectedAtKetua ? 'bi-x-lg' : ($ketuaDone ? 'bi-check-lg' : 'bi-clock') }} small"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fw-bold" style="font-size: 13px;">
                                            {{ $isRejectedAtKetua ? 'Ditolak oleh Ketua' : 'Validasi Ketua' }}
                                        </div>
                                        <div class="text-muted" style="font-size: 12px;">
                                            @if($isRejectedAtKetua) Pengajuan tidak disetujui oleh Ketua.
                                            @elseif($ketuaDone) Pengajuan telah disetujui oleh Ketua.
                                            @elseif($ketuaWaiting) Menunggu pemeriksaan dan keputusan Ketua.
                                            @else Menunggu tahap sebelumnya.
                                            @endif
                                        </div>
                                        @if($isRejectedAtKetua || $ketuaDone)
                                            <div class="text-muted mt-1" style="font-size: 11px;">{{ $prop->updated_at->locale('id')->translatedFormat('d M Y, H.i') }} WIB</div>
                                            <div class="fw-semibold" style="font-size: 11px; color: {{ $isRejectedAtKetua ? '#B91C1C' : '#16A34A' }};">Selesai</div>
                                        @endif
                                    </div>
                                </div>
                                @endif

                                <!-- Tahap Persetujuan Bendahara (Khusus Pengajuan dari Ketua) -->
                                @if($prop->user->role === 'ketua')
                                @php
                                    $isRejectedAtBendahara = ($prop->status === 'rejected');
                                    $bendaharaApproveDone = in_array($prop->status, ['pending_disbursement', 'approved']);
                                    $bendaharaApproveWaiting = ($prop->status === 'pending_bendahara');
                                @endphp
                                <div class="d-flex gap-3 mb-4">
                                    <div>
                                        <span class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 30px; height: 30px; background-color: {{ $isRejectedAtBendahara ? '#B91C1C' : ($bendaharaApproveDone ? '#16A34A' : ($bendaharaApproveWaiting ? '#D97706' : '#CBD5E1')) }};">
                                            <i class="bi {{ $isRejectedAtBendahara ? 'bi-x-lg' : ($bendaharaApproveDone ? 'bi-check-lg' : 'bi-clock') }} small"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fw-bold" style="font-size: 13px;">
                                            {{ $isRejectedAtBendahara ? 'Ditolak oleh Bendahara' : 'Validasi Bendahara' }}
                                        </div>
                                        <div class="text-muted" style="font-size: 12px;">
                                            @if($isRejectedAtBendahara) Pengajuan tidak disetujui oleh Bendahara.
                                            @elseif($bendaharaApproveDone) Pengajuan Ketua telah disetujui oleh Bendahara.
                                            @elseif($bendaharaApproveWaiting) Menunggu validasi persetujuan dari Bendahara.
                                            @else Menunggu tahap sebelumnya.
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endif

                                <!-- Tahap Akhir: Pencairan Dana Bendahara (Untuk Semua Role) -->
                                @php
                                    $disburseDone = ($prop->status === 'approved');
                                    $disburseWaiting = ($prop->status === 'pending_disbursement');
                                    $isRejectedFinal = ($prop->status === 'rejected' && $prop->user->role !== 'ketua');
                                @endphp
                                <div class="d-flex gap-3 mb-3">
                                    <div>
                                        <span class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 30px; height: 30px; background-color: {{ $isRejectedFinal ? '#B91C1C' : ($disburseDone ? '#16A34A' : ($disburseWaiting ? '#D97706' : '#CBD5E1')) }};">
                                            <i class="bi {{ $isRejectedFinal ? 'bi-x-lg' : ($disburseDone ? 'bi-check-lg' : 'bi-clock') }} small"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fw-bold" style="font-size: 13px;">
                                            {{ $isRejectedFinal ? 'Pengajuan Ditolak' : 'Pencairan Dana Bendahara' }}
                                        </div>
                                        <div class="text-muted" style="font-size: 12px;">
                                            @if($isRejectedFinal) Pengajuan tidak disetujui pada tahap validasi.
                                            @elseif($disburseDone) Dana telah dicairkan dan tercatat di Buku Kas.
                                            @elseif($disburseWaiting) Berada di Antrean Pencairan Bendahara.
                                            @else Menunggu persetujuan tahap sebelumnya.
                                            @endif
                                        </div>
                                        @if($disburseDone)
                                            <div class="text-muted mt-1" style="font-size: 11px;">{{ $prop->updated_at->locale('id')->translatedFormat('d M Y, H.i') }} WIB</div>
                                            <div class="fw-semibold" style="font-size: 11px; color: #16A34A;">Selesai</div>
                                        @endif
                                    </div>
                                </div>

                                @if($prop->status === 'rejected')
                                <div class="p-3 rounded-3 mt-3 d-flex align-items-start gap-2" style="background-color: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; font-size: 12px;">
                                    <i class="bi bi-exclamation-triangle mt-1"></i>
                                    <div>
                                        Catatan Penolakan: {{ $prop->rejection_reason ?? 'Pengajuan tidak disetujui pada tahap validasi.' }}
                                    </div>
                                </div>
                                @elseif(!empty($prop->review_note))
                                <div class="p-3 rounded-3 mt-3" style="background-color: #F1F5F9; color: #334155; font-size: 12px;">
                                    <strong>Catatan Validator:</strong> {{ $prop->review_note }}
                                </div>
                                @endif

                                <!-- FORM UNGGAH BUKTI REALISASI / STRUK MILESTONE OLEH PENGAJU -->
                                @if($prop->status === 'approved' && $prop->user_id === $currentUser->id)
                                <div class="p-3 rounded-3 mt-3 bg-white border">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-bold" style="font-size: 13px; color: #1E293B;">Bukti Realisasi / Struk Milestone</span>
                                        @if($prop->realization_status === 'approved')
                                            <span class="badge rounded-2 px-2 py-1" style="background-color: #DCFCE7; color: #15803D; font-size: 10.5px;">Terverifikasi</span>
                                        @elseif($prop->realization_status === 'pending')
                                            <span class="badge rounded-2 px-2 py-1" style="background-color: #FEF3C7; color: #B45309; font-size: 10.5px;">Menunggu Verifikasi</span>
                                        @elseif($prop->realization_status === 'rejected')
                                            <span class="badge rounded-2 px-2 py-1" style="background-color: #FEE2E2; color: #B91C1C; font-size: 10.5px;">Ditolak Bendahara</span>
                                        @else
                                            <span class="badge rounded-2 px-2 py-1" style="background-color: #F1F5F9; color: #475569; font-size: 10.5px;">Belum Unggah</span>
                                        @endif
                                    </div>

                                    @if($prop->realization_status === 'rejected' && $prop->realization_rejection_reason)
                                        <div class="p-2 rounded-2 mb-2" style="background-color: #FEF2F2; color: #B91C1C; font-size: 11.5px;">
                                            <strong>Alasan ditolak:</strong> {{ $prop->realization_rejection_reason }}
                                        </div>
                                    @endif

                                    @if(in_array($prop->realization_status, ['unsubmitted', 'rejected']) || empty($prop->realization_status))
                                        <p class="text-muted mb-2" style="font-size: 11.5px;">
                                            Unggah foto struk/nota pembelian dan rincian item agar penggunaan dana terverifikasi sesuai milestone.
                                        </p>
                                        <form action="{{ route('proposals.submitRealization', $prop->id) }}" method="POST" enctype="multipart/form-data">
                                            @csrf
                                            <div id="realizationItemsContainer{{ $prop->id }}" class="d-flex flex-column gap-2 mb-2">
                                                <div class="row g-1">
                                                    <div class="col-7">
                                                        <input type="text" name="items[0][description]" class="form-control form-control-sm rounded-2" placeholder="Nama item / keperluan" required>
                                                    </div>
                                                    <div class="col-5">
                                                        <input type="number" name="items[0][amount]" class="form-control form-control-sm rounded-2" placeholder="Nominal (Rp)" min="1" required>
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-link p-0 text-decoration-none fw-semibold mb-2" style="font-size: 11.5px; color: #2F4156;" onclick="addRealizationItemRow({{ $prop->id }})">
                                                + Tambah Baris Item
                                            </button>

                                            <div class="mb-2">
                                                <label class="form-label mb-1 fw-medium" style="font-size: 11.5px;">Foto Struk / Nota (Bisa pilih lebih dari 1)</label>
                                                <input type="file" name="proof_photos[]" class="form-control form-control-sm rounded-2" accept=".jpg,.jpeg,.png,.pdf" multiple required>
                                            </div>

                                            <button type="submit" class="btn-nexvo-dark w-100 py-2 mt-1" style="font-size: 12px;">
                                                <i class="bi bi-upload me-1"></i> Kirim Bukti Realisasi
                                            </button>
                                        </form>
                                    @else
                                        <div class="text-muted" style="font-size: 11.5px;">
                                            Total Realisasi: <strong class="text-dark">Rp {{ number_format(collect($prop->realization_items)->sum('amount'), 0, ',', '.') }}</strong>
                                            ({{ count($prop->realization_proofs ?? []) }} foto bukti terlampir)
                                        </div>
                                    @endif
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-top px-4 py-3 justify-content-between">
                @if($isPendingState && $prop->user_id === $currentUser->id)
                    <button type="button" class="btn rounded-3 px-4 py-2 text-white fw-medium" style="background-color: #B4433B; font-size: 13px;" data-bs-toggle="modal" data-bs-target="#cancelConfirmModal{{ $prop->id }}">
                        <i class="bi bi-x-lg me-2"></i> Batalkan Pengajuan
                    </button>
                @else
                    <span class="text-muted" style="font-size: 12px;">Informasi pengajuan bersifat hanya baca.</span>
                @endif

                <button type="button" class="btn btn-outline-secondary rounded-3 px-4" data-bs-dismiss="modal" style="font-size: 13px;">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endif

<!-- ===================================================================== -->
<!-- MODAL KONFIRMASI PEMBATALAN PENGAJUAN (KHUSUS PEMILIK SAAT PENDING)   -->
<!-- ===================================================================== -->
@if($isPendingState && $prop->user_id === $currentUser->id && !$showRequesterCol)
<div class="modal fade" id="cancelConfirmModal{{ $prop->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow bg-white p-2">
            <form action="{{ route('proposals.destroy', $prop->id) }}" method="POST">
                @csrf
                @method('DELETE')

                <div class="modal-header border-bottom-0 pt-3 px-4 pb-0">
                    <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background-color: #FEF2F2; color: #DC2626;">
                        <i class="bi bi-trash3 fs-5"></i>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body px-4 pt-3">
                    <h5 class="fw-bold mb-1" style="color: #1E293B;">Batalkan Pengajuan Dana?</h5>
                    <p class="text-muted mb-3" style="font-size: 13px;">Pengajuan yang dibatalkan akan dihapus permanen dari daftar antrean validasi.</p>

                    <div class="p-3 rounded-3 mb-2" style="background-color: #F4F7FA;">
                        <div class="fw-medium" style="font-size: 12.5px; color: #334155;">{{ $prop->title }}</div>
                        <div class="fw-bold mt-1" style="font-size: 16px; color: #1E293B;">Rp {{ number_format($prop->amount, 0, ',', '.') }}</div>
                    </div>
                </div>

                <div class="modal-footer border-top-0 px-4 pb-4 pt-2">
                    <div class="row g-2 w-100 m-0">
                        <div class="col-5 ps-0">
                            <button type="button" class="btn btn-outline-secondary w-100 rounded-3 py-2" style="font-size: 13px;" data-bs-toggle="modal" data-bs-target="#detailModal{{ $prop->id }}">
                                Kembali
                            </button>
                        </div>
                        <div class="col-7 pe-0">
                            <button type="submit" class="btn w-100 rounded-3 py-2 text-white fw-medium" style="background-color: #B4433B; font-size: 13px;">
                                Ya, Batalkan Pengajuan
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- ===================================================================== -->
<!-- MODAL 2: Pratinjau Dokumen                                            -->
<!-- ===================================================================== -->
@if(!empty($prop->document_path))
<div class="modal fade" id="previewDocModal{{ $prop->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow bg-white p-2">
            <div class="modal-header border-bottom-0 pt-3 px-4 pb-2 d-flex align-items-start justify-content-between">
                <div>
                    <h4 class="fw-bold mb-1" style="color: #1E293B;">Pratinjau Dokumen Pendukung</h4>
                    <span class="text-muted" style="font-size: 12px;">{{ basename($prop->document_path) }}</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4">
                <div class="p-3 rounded-4 border text-center mb-3" style="background-color: #FAFAFA;">
                    @if($isImage)
                        <img src="{{ asset('storage/' . $prop->document_path) }}" alt="Dokumen Pendukung" class="img-fluid rounded-3" style="max-height: 420px;">
                    @else
                        <iframe src="{{ asset('storage/' . $prop->document_path) }}" width="100%" height="420px" class="rounded-3 border-0"></iframe>
                    @endif
                </div>

                <button type="button" class="btn btn-outline-secondary w-100 rounded-3 py-2 fw-medium" style="font-size: 13px;" data-bs-toggle="modal" data-bs-target="#detailModal{{ $prop->id }}">
                    Kembali ke Detail
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@if($canApproveNow && !$isPenyaluranView)
<!-- MODAL 3: Konfirmasi Penolakan -->
<div class="modal fade" id="rejectConfirmModal{{ $prop->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow bg-white p-2">
            <form action="{{ route('proposals.updateStatus', $prop->id) }}" method="POST">
                @csrf
                @method('PATCH')
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="review_note" id="hiddenReviewNoteReject{{ $prop->id }}">

                <div class="modal-header border-bottom-0 pt-3 px-4 pb-0">
                    <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background-color: #FEF2F2; color: #DC2626;">
                        <i class="bi bi-x-circle fs-5"></i>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body px-4 pt-3">
                    <h5 class="fw-bold mb-1" style="color: #1E293B;">Konfirmasi Penolakan</h5>
                    <p class="text-muted mb-3" style="font-size: 13px;">Apakah Anda yakin ingin menolak pengajuan dana ini?</p>

                    <div class="p-3 rounded-3 mb-3" style="background-color: #F4F7FA;">
                        <div class="fw-medium" style="font-size: 12.5px; color: #334155;">{{ $prop->title }}</div>
                        <div class="fw-bold mt-1" style="font-size: 16px; color: #1E293B;">Rp {{ number_format($prop->amount, 0, ',', '.') }}</div>
                    </div>

                    <div class="mb-2">
                        <label class="fw-semibold mb-1" style="font-size: 13px; color: #1E293B;">Alasan Penolakan <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" id="rejectReasonTextarea{{ $prop->id }}" class="form-control rounded-3" rows="3" placeholder="Tuliskan alasan penolakan..." style="font-size: 13px; border-color: #E2E8F0;" required></textarea>
                    </div>
                </div>

                <div class="modal-footer border-top-0 px-4 pb-4 pt-2">
                    <div class="row g-2 w-100 m-0">
                        <div class="col-5 ps-0">
                            <button type="button" class="btn btn-outline-secondary w-100 rounded-3 py-2" style="font-size: 13px;" data-bs-toggle="modal" data-bs-target="#detailModal{{ $prop->id }}">
                                Batal
                            </button>
                        </div>
                        <div class="col-7 pe-0">
                            <button type="submit" class="btn w-100 rounded-3 py-2 text-white fw-medium" style="background-color: #B91C1C; font-size: 13px;">
                                Ya, Tolak Pengajuan
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 4: Konfirmasi Persetujuan -->
<div class="modal fade" id="approveConfirmModal{{ $prop->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow bg-white p-2">
            <form action="{{ route('proposals.updateStatus', $prop->id) }}" method="POST">
                @csrf
                @method('PATCH')
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="review_note" id="hiddenReviewNoteApprove{{ $prop->id }}">

                <div class="modal-header border-bottom-0 pt-3 px-4 pb-0">
                    <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background-color: #F1F5F9; color: #243242;">
                        <i class="bi bi-check-circle fs-5"></i>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body px-4 pt-3">
                    <h5 class="fw-bold mb-1" style="color: #1E293B;">Konfirmasi Persetujuan</h5>
                    <p class="text-muted mb-3" style="font-size: 13px;">Apakah Anda yakin ingin menyetujui pengajuan dana ini?</p>

                    <div class="p-3 rounded-3 mb-2" style="background-color: #F4F7FA;">
                        <div class="fw-medium" style="font-size: 12.5px; color: #334155;">{{ $prop->title }}</div>
                        <div class="fw-bold mt-1" style="font-size: 16px; color: #1E293B;">Rp {{ number_format($prop->amount, 0, ',', '.') }}</div>
                    </div>
                </div>

                <div class="modal-footer border-top-0 px-4 pb-4 pt-2">
                    <div class="row g-2 w-100 m-0">
                        <div class="col-5 ps-0">
                            <button type="button" class="btn btn-outline-secondary w-100 rounded-3 py-2" style="font-size: 13px;" data-bs-toggle="modal" data-bs-target="#detailModal{{ $prop->id }}">
                                Batal
                            </button>
                        </div>
                        <div class="col-7 pe-0">
                            <button type="submit" class="btn-nexvo-dark w-100 py-2" style="font-size: 13px;">
                                Ya, Setujui Pengajuan
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
@endsection

@push('scripts')
<script>
    const searchInput = document.getElementById('searchProposal');
    const statusFilter = document.getElementById('statusFilter');
    const monthFilter = document.getElementById('monthFilter');
    const divisionFilter = document.getElementById('divisionFilter');
    const rows = document.querySelectorAll('.proposal-row');
    const visibleCountSpan = document.getElementById('visibleCount');

    function filterProposals() {
        const query = searchInput ? searchInput.value.toLowerCase() : '';
        const status = statusFilter ? statusFilter.value : 'all';
        const month = monthFilter ? monthFilter.value : 'all';
        const division = divisionFilter ? divisionFilter.value : 'all';
        let count = 0;

        rows.forEach(row => {
            const matchText = row.dataset.search.includes(query);
            const matchStatus = (status === 'all') || (row.dataset.status === status);
            const matchMonth = (month === 'all') || (row.dataset.month === month);
            const matchDivision = (division === 'all') || (row.dataset.division === division);

            if (matchText && matchStatus && matchMonth && matchDivision) {
                row.style.display = '';
                count++;
            } else {
                row.style.display = 'none';
            }
        });

        if (visibleCountSpan) visibleCountSpan.textContent = count;
    }

    if (searchInput) searchInput.addEventListener('input', filterProposals);
    if (statusFilter) statusFilter.addEventListener('change', filterProposals);
    if (monthFilter) monthFilter.addEventListener('change', filterProposals);
    if (divisionFilter) divisionFilter.addEventListener('change', filterProposals);

    function syncReviewNote(id) {
        const val = document.getElementById('reviewNoteInput' + id).value;
        const rejectHidden = document.getElementById('hiddenReviewNoteReject' + id);
        const approveHidden = document.getElementById('hiddenReviewNoteApprove' + id);
        const rejectReasonBox = document.getElementById('rejectReasonTextarea' + id);

        if (rejectHidden) rejectHidden.value = val;
        if (approveHidden) approveHidden.value = val;
        if (rejectReasonBox && !rejectReasonBox.dataset.edited) {
            rejectReasonBox.value = val;
        }
    }

    function selectDisburseMethod(id, method) {
        document.getElementById('disburseMethodInput' + id).value = method;
        const transferCard = document.getElementById('methodTransferCard' + id);
        const tunaiCard = document.getElementById('methodTunaiCard' + id);

        if (method === 'Transfer') {
            transferCard.classList.add('selected');
            tunaiCard.classList.remove('selected');
        } else {
            tunaiCard.classList.add('selected');
            transferCard.classList.remove('selected');
        }
    }

    function previewProofFile(id, input) {
        if (input.files && input.files[0]) {
            document.getElementById('proofPlaceholder' + id).classList.add('d-none');
            document.getElementById('proofSelectedInfo' + id).classList.remove('d-none');
            document.getElementById('proofFileName' + id).textContent = input.files[0].name;
        }
    }

    function copyRekening(text, btn) {
        navigator.clipboard.writeText(text);
        const icon = btn.querySelector('i');
        icon.classList.remove('bi-copy');
        icon.classList.add('bi-check-lg', 'text-success');
        setTimeout(() => {
            icon.classList.remove('bi-check-lg', 'text-success');
            icon.classList.add('bi-copy');
        }, 1500);
    }

    const itemRowCounters = {};
    function addRealizationItemRow(propId) {
        if (!itemRowCounters[propId]) itemRowCounters[propId] = 1;
        const idx = itemRowCounters[propId]++;
        const container = document.getElementById('realizationItemsContainer' + propId);
        const row = document.createElement('div');
        row.className = 'row g-1 align-items-center realization-item-row';
        row.innerHTML = `
            <div class="col-6">
                <input type="text" name="items[${idx}][description]" class="form-control form-control-sm rounded-2" placeholder="Nama item / keperluan" required>
            </div>
            <div class="col-4">
                <input type="number" name="items[${idx}][amount]" class="form-control form-control-sm rounded-2" placeholder="Nominal (Rp)" min="1" required>
            </div>
            <div class="col-2 text-end">
                <button type="button" class="btn btn-sm btn-outline-danger w-100 rounded-2 px-0" title="Hapus baris" onclick="this.closest('.realization-item-row').remove()">
                    <i class="bi bi-trash3"></i>
                </button>
            </div>
        `;
        container.appendChild(row);
    }
</script>
@endpush