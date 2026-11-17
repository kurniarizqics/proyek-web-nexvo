@extends('layouts.app')

@section('content')
@php
    $isApprovalTab = ($tab === 'approvals');
    $isHistoryTab = ($tab === 'history');
    $showRequesterCol = ($isApprovalTab || $isHistoryTab);
    $currentUser = Auth::user();
@endphp

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
                @if($isApprovalTab) Persetujuan Dana
                @elseif($isHistoryTab) Riwayat Persetujuan
                @else Riwayat Pengajuanku
                @endif
            </h3>
            <p class="text-muted mb-0" style="font-size: 13px;">
                @if($isApprovalTab) Daftar pengajuan anggaran yang memerlukan validasi Anda.
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
                @if($isApprovalTab) Daftar Menunggu Validasi
                @elseif($isHistoryTab) Daftar Riwayat Keputusan
                @else Daftar Pengajuan Dana
                @endif
            </h5>
            <p class="text-muted mb-0" style="font-size: 12px;">
                @if($isApprovalTab) Pengajuan dana yang membutuhkan keputusan Anda.
                @elseif($isHistoryTab) Riwayat pengajuan dana yang telah disetujui atau ditolak.
                @else Lihat perkembangan dan detail permohonan anggaran Anda.
                @endif
            </p>
        </div>
        <span class="badge rounded-3 px-4 py-2 fw-semibold" style="background-color: #C5D3DF; color: #243242; font-size: 12px;">
            {{ $proposals->count() }} {{ $isHistoryTab ? 'Riwayat Pengajuan' : 'Pengajuan' }}
        </span>
    </div>

    <!-- Bar Pencarian & Filter -->
    <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
        <div class="position-relative" style="width: 100%; max-width: 320px;">
            <i class="bi bi-search text-muted position-absolute" style="top: 50%; left: 14px; transform: translateY(-50%); font-size: 13px;"></i>
            <input type="text" id="searchProposal" class="form-control ps-5 rounded-3" placeholder="{{ $showRequesterCol ? 'Cari pengaju atau keperluan...' : 'Cari keperluan atau ID pengajuan...' }}" style="font-size: 13px; border-color: #E2E8F0; height: 40px;">
        </div>

        @if($isApprovalTab)
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
                @foreach($proposals->map(fn($p) => $p->created_at->format('Y-m'))->unique() as $ym)
                    <option value="{{ $ym }}">{{ \Carbon\Carbon::parse($ym . '-01')->locale('id')->translatedFormat('F Y') }}</option>
                @endforeach
            </select>

            <select id="divisionFilter" class="form-select rounded-3" style="width: 175px; font-size: 13px; border-color: #E2E8F0; height: 40px;">
                <option value="all">Semua Divisi</option>
                @foreach($proposals->pluck('user.division')->filter()->unique() as $div)
                    <option value="{{ strtolower($div) }}">{{ $div }}</option>
                @endforeach
            </select>
        @else
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
            </thead>
            <tbody>
                @forelse($proposals as $prop)
                @php
                    $code = ($showRequesterCol ? 'REQ-' . $prop->created_at->format('Ym') : 'PGJ-' . $prop->created_at->format('Y')) . '-' . str_pad($prop->id, 3, '0', STR_PAD_LEFT);
                    $isPendingState = str_starts_with($prop->status, 'pending');
                    $historyDecision = ($prop->status === 'rejected') ? 'rejected' : 'approved';
                    $filterGroup = $isHistoryTab ? $historyDecision : ($isPendingState ? 'pending' : $prop->status);

                    $statusLabel = match ($prop->status) {
                        'pending_koordiv' => 'Menunggu Koordiv',
                        'pending_ketua' => 'Menunggu Ketua',
                        'pending_bendahara' => 'Menunggu Bendahara',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        default => 'Menunggu Validasi',
                    };
                @endphp
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
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-5">
                        @if($isApprovalTab) Tidak ada pengajuan dana yang menunggu validasi Anda saat ini.
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
            @if($isApprovalTab) pengajuan menunggu validasi
            @elseif($isHistoryTab) riwayat pengajuan
            @else dari {{ $proposals->count() }} pengajuan
            @endif
        </div>
        @if($isHistoryTab)
            <span class="text-muted">Arsip keputusan &bull; Hanya baca</span>
        @elseif(!$isApprovalTab)
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
    $code = ($showRequesterCol ? 'REQ-' . $prop->created_at->format('Ym') : 'PGJ-' . $prop->created_at->format('Y')) . '-' . str_pad($prop->id, 3, '0', STR_PAD_LEFT);
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
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
        default => 'Menunggu Validasi',
    };

    $ext = $prop->document_path ? strtolower(pathinfo($prop->document_path, PATHINFO_EXTENSION)) : null;
    $isImage = in_array($ext, ['jpg', 'jpeg', 'png']);
@endphp

@if($isApprovalTab)
<!-- ===================================================================== -->
<!-- MODAL 1A: REVIEW (TAB MENUNGGU VALIDASI)                              -->
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

                <!-- Box Nominal -->
                <div class="p-3 rounded-3 mb-3" style="background-color: #F4F7FA;">
                    <div class="text-muted" style="font-size: 11.5px;">Nominal Pengajuan</div>
                    <h3 class="fw-bold mb-0 mt-1" style="color: #1E293B;">Rp {{ number_format($prop->amount, 0, ',', '.') }}</h3>
                </div>

                <!-- Keterangan / Rincian Pengajuan -->
                <div class="mb-3">
                    <div class="fw-semibold mb-1" style="font-size: 13px; color: #1E293B;">Keterangan / Rincian Pengajuan</div>
                    <div class="p-3 rounded-3" style="background-color: #F8FAFC; font-size: 13px; color: #334155;">
                        <div class="fw-semibold mb-1">{{ $prop->title }}</div>
                        <div>{{ $prop->reason }}</div>
                    </div>
                </div>

                <!-- Dokumen Pendukung -->
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

                <!-- Box Informasi Validasi -->
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

                <!-- Catatan Validator -->
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

                    <!-- Kolom Kanan: Jalur Persetujuan -->
                    <div class="col-lg-5">
                        <div class="border rounded-4 p-4 h-100 d-flex flex-column justify-content-between" style="background-color: #FAFAFA;">
                            <div>
                                @php
                                    $totalSteps = match ($prop->user->role) {
                                        'anggota' => 4,
                                        'koordiv' => 3,
                                        default => 2,
                                    };
                                    $completedSteps = match ($prop->status) {
                                        'pending_koordiv' => 1,
                                        'pending_ketua' => ($prop->user->role === 'anggota' ? 2 : 1),
                                        'pending_bendahara' => ($prop->user->role === 'anggota' ? 3 : ($prop->user->role === 'koordiv' ? 2 : 1)),
                                        'approved', 'rejected' => $totalSteps,
                                        default => 1,
                                    };
                                @endphp
                                <h6 class="fw-bold mb-0" style="font-size: 15px; color: #1E293B;">Jalur Persetujuan</h6>
                                <div class="text-muted mb-4" style="font-size: 11.5px;">{{ $completedSteps }} dari {{ $totalSteps }} tahap diproses</div>

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

                                @if(in_array($prop->user->role, ['anggota', 'koordiv', 'bendahara']))
                                @php
                                    $isRejectedAtKetua = ($prop->status === 'rejected' && $prop->user->role === 'bendahara');
                                    $ketuaDone = in_array($prop->status, ['pending_bendahara', 'approved']);
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

                                @if(in_array($prop->user->role, ['anggota', 'koordiv', 'ketua']))
                                @php
                                    $isRejectedAtBendahara = ($prop->status === 'rejected');
                                    $bendaharaDone = ($prop->status === 'approved');
                                    $bendaharaWaiting = ($prop->status === 'pending_bendahara');
                                @endphp
                                <div class="d-flex gap-3 mb-3">
                                    <div>
                                        <span class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 30px; height: 30px; background-color: {{ $isRejectedAtBendahara ? '#B91C1C' : ($bendaharaDone ? '#16A34A' : ($bendaharaWaiting ? '#D97706' : '#CBD5E1')) }};">
                                            <i class="bi {{ $isRejectedAtBendahara ? 'bi-x-lg' : ($bendaharaDone ? 'bi-check-lg' : 'bi-clock') }} small"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fw-bold" style="font-size: 13px;">
                                            {{ $isRejectedAtBendahara ? 'Ditolak oleh Bendahara' : ($prop->user->role === 'ketua' ? 'Validasi & Pencairan Bendahara' : 'Pencairan Dana Bendahara') }}
                                        </div>
                                        <div class="text-muted" style="font-size: 12px;">
                                            @if($isRejectedAtBendahara) Pengajuan tidak disetujui oleh Bendahara.
                                            @elseif($bendaharaDone) Dana telah disetujui dan dicairkan oleh Bendahara.
                                            @elseif($bendaharaWaiting) Menunggu validasi dan pencairan Bendahara.
                                            @else Menunggu persetujuan Ketua.
                                            @endif
                                        </div>
                                        @if($isRejectedAtBendahara || $bendaharaDone)
                                            <div class="text-muted mt-1" style="font-size: 11px;">{{ $prop->updated_at->locale('id')->translatedFormat('d M Y, H.i') }} WIB</div>
                                            <div class="fw-semibold" style="font-size: 11px; color: {{ $isRejectedAtBendahara ? '#B91C1C' : '#16A34A' }};">Selesai</div>
                                        @endif
                                    </div>
                                </div>
                                @endif

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
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer dengan Tombol Batalkan Pengajuan jika Masih Menunggu Validasi -->
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

@if($canApproveNow)
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
</script>
@endpush