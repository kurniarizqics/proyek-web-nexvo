@extends('layouts.app')

@section('content')
<!-- Header -->
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h3 class="fw-bold mb-1" style="color: #2F4156;">Manajemen Divisi</h3>
        <p class="text-muted mb-0" style="font-size: 13px;">
            Atur struktur divisi organisasi, tetapkan Koordinator Divisi, dan petakan anggota ke masing-masing divisi.
        </p>
    </div>

    <button type="button" class="btn rounded-3 px-3 py-2 fw-medium text-white" style="background-color: #2F4156; font-size: 13px;" data-bs-toggle="modal" data-bs-target="#createDivisionModal">
        <i class="bi bi-diagram-3 me-1"></i> + Tambah Divisi Baru
    </button>
</div>

<!-- Notifikasi -->
@if(session('success_title'))
    <div class="alert alert-success border-0 shadow-sm rounded-3 mb-4 d-flex align-items-center justify-content-between">
        <div>
            <div class="fw-bold" style="font-size: 13.5px;">{{ session('success_title') }}</div>
            <div style="font-size: 12.5px;">{{ session('success') }}</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4" style="font-size: 12.5px;">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

<!-- Daftar Divisi -->
<div class="row g-3">
    @forelse($divisions as $div)
    <div class="col-md-6 col-xl-4">
        <div class="nexvo-card p-4 h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h5 class="fw-bold mb-0" style="color: #2F4156; font-size: 16px;">{{ $div->name }}</h5>
                    @if($div->is_active)
                        <span class="badge rounded-2 px-2.5 py-1 fw-medium" style="background-color: #DCFCE7; color: #15803D; font-size: 11px;">Aktif</span>
                    @else
                        <span class="badge rounded-2 px-2.5 py-1 fw-medium" style="background-color: #FEE2E2; color: #B91C1C; font-size: 11px;">Nonaktif</span>
                    @endif
                </div>

                <p class="text-muted mb-3" style="font-size: 12.5px; min-height: 36px;">
                    {{ $div->description ?: 'Tidak ada deskripsi tambahan untuk divisi ini.' }}
                </p>

                <div class="p-3 rounded-3 mb-3" style="background-color: #F8FAFC; border: 1px solid #E2E8F0; font-size: 12.5px;">
                    <div class="d-flex justify-content-between mb-1.5">
                        <span class="text-muted">Koordinator:</span>
                        <span class="fw-semibold" style="color: #2F4156;">
                            {{ $div->coordinator?->name ?? 'Belum Ditetapkan' }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Total Anggota Terpetakan:</span>
                        <span class="fw-semibold" style="color: #2F4156;">
                            {{ $div->members->count() }} Orang
                        </span>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2 pt-2 border-top">
                <!-- Pemetaan Anggota -->
                <button type="button" class="btn btn-sm flex-grow-1 fw-medium" style="background-color: #C8D9E6; color: #2F4156; font-size: 12px;" data-bs-toggle="modal" data-bs-target="#assignMembersModal{{ $div->id }}">
                    <i class="bi bi-people me-1"></i> Petakan Anggota
                </button>

                <!-- Edit Divisi & Koordinator -->
                <button type="button" class="btn btn-sm btn-light border" title="Edit Divisi & Koordinator" data-bs-toggle="modal" data-bs-target="#editDivisionModal{{ $div->id }}">
                    <i class="bi bi-pencil-square"></i>
                </button>

                <!-- Aktif / Nonaktifkan Divisi -->
                <form action="{{ route('admin.divisions.toggleStatus', $div->id) }}" method="POST" class="d-inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-sm btn-light border" title="{{ $div->is_active ? 'Nonaktifkan Divisi' : 'Aktifkan Divisi' }}">
                        <i class="bi {{ $div->is_active ? 'bi-slash-circle text-danger' : 'bi-check-circle text-success' }}"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit Divisi -->
    <div class="modal fade" id="editDivisionModal{{ $div->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 p-2">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold" style="color: #2F4156;">Edit Divisi &amp; Koordinator</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('admin.divisions.update', $div->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-medium small">Nama Divisi</label>
                            <input type="text" name="name" value="{{ $div->name }}" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium small">Deskripsi Divisi</label>
                            <textarea name="description" class="form-control" rows="2">{{ $div->description }}</textarea>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-medium small">Koordinator Divisi</label>
                            <select name="coordinator_id" class="form-select">
                                <option value="">-- Belum Ditetapkan --</option>
                                @foreach($coordinators as $coord)
                                    <option value="{{ $coord->id }}" {{ $div->coordinator_id == $coord->id ? 'selected' : '' }}>
                                        {{ $coord->name }} ({{ ucfirst($coord->role) }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text" style="font-size: 11px;">Pengguna yang dipilih otomatis ditetapkan perannya sebagai Koordinator Divisi.</div>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn text-white" style="background-color: #2F4156;">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Pemetaan Anggota ke Divisi -->
    <div class="modal fade" id="assignMembersModal{{ $div->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 p-2">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold" style="color: #2F4156;">Pemetaan Anggota: {{ $div->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('admin.divisions.assignMembers', $div->id) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="modal-body">
                        <p class="text-muted small mb-3">
                            Pilih anggota yang tergabung dalam divisi <strong>{{ $div->name }}</strong>.
                        </p>
                        <div class="border rounded-3 p-3" style="max-height: 260px; overflow-y: auto; background-color: #FAFAFA;">
                            @forelse($allMembers as $member)
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="member_ids[]" value="{{ $member->id }}" id="m_{{ $div->id }}_{{ $member->id }}"
                                    {{ $member->division_id == $div->id ? 'checked' : '' }}>
                                <label class="form-check-label small" for="m_{{ $div->id }}_{{ $member->id }}">
                                    <strong>{{ $member->name }}</strong>
                                    <span class="text-muted">({{ ucfirst($member->role) }}{{ $member->division && $member->division_id != $div->id ? ' - saat ini: ' . $member->division : '' }})</span>
                                </label>
                            </div>
                            @empty
                            <div class="text-muted small text-center py-2">Belum ada akun anggota/koordiv yang tersedia.</div>
                            @endforelse
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn text-white" style="background-color: #2F4156;">Simpan Pemetaan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="nexvo-card p-5 text-center text-muted">
            Belum ada divisi yang dibuat. Klik tombol <strong>+ Tambah Divisi Baru</strong> untuk memulai.
        </div>
    </div>
    @endforelse
</div>

<!-- Modal Tambah Divisi Baru -->
<div class="modal fade" id="createDivisionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 p-2">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold" style="color: #2F4156;">Tambah Divisi Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.divisions.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium small">Nama Divisi</label>
                        <input type="text" name="name" class="form-control" required placeholder="Contoh: Divisi Kreatif & Produksi">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium small">Deskripsi (Opsional)</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Tugas pokok dan ruang lingkup divisi..."></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-medium small">Tetapkan Koordinator Divisi (Opsional)</label>
                        <select name="coordinator_id" class="form-select">
                            <option value="">-- Pilih Koordinator --</option>
                            @foreach($coordinators as $coord)
                                <option value="{{ $coord->id }}">{{ $coord->name }} ({{ ucfirst($coord->role) }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn text-white" style="background-color: #2F4156;">Simpan Divisi</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    @if(request()->query('modal') === 'create')
        document.addEventListener('DOMContentLoaded', function () {
            new bootstrap.Modal(document.getElementById('createDivisionModal')).show();
        });
    @endif
</script>
@endpush