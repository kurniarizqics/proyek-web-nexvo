@extends('layouts.app')

@section('content')
<!-- Header -->
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h3 class="fw-bold mb-1" style="color: #2F4156;">Manajemen Pengguna</h3>
        <p class="text-muted mb-0" style="font-size: 13px;">
            Kelola akun pengguna, tetapkan peran akses, nonaktifkan akun tidak aktif, atau reset kata sandi.
        </p>
    </div>

    <button type="button" class="btn rounded-3 px-3 py-2 fw-medium text-white" style="background-color: #2F4156; font-size: 13px;" data-bs-toggle="modal" data-bs-target="#createUserModal">
        <i class="bi bi-person-plus me-1"></i> + Tambah Pengguna Baru
    </button>
</div>

<!-- Notifikasi Sukses / Error -->
@if(session('success_title'))
    <div class="alert alert-success border-0 shadow-sm rounded-3 mb-4 d-flex align-items-center justify-content-between">
        <div>
            <div class="fw-bold" style="font-size: 13.5px;">{{ session('success_title') }}</div>
            <div style="font-size: 12.5px;">{{ session('success') }}</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4" style="font-size: 13px;">
        {{ session('error') }}
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

<!-- Datalist Global Pilihan Divisi (Bisa Dipilih atau Diketik Manual) -->
<datalist id="divisionOptionsList">
    @foreach($divisions as $div)
        <option value="{{ $div->name }}">
    @endforeach
</datalist>

<!-- Card Daftar Pengguna -->
<div class="nexvo-card p-4">
    <!-- Filter & Pencarian -->
    <form method="GET" action="{{ route('admin.users.index') }}" class="row g-2 mb-4">
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control border-start-0" placeholder="Cari nama, email, atau divisi..." style="font-size: 13px;">
            </div>
        </div>
        <div class="col-md-3">
            <select name="role" class="form-select" style="font-size: 13px;" onchange="this.form.submit()">
                <option value="">Semua Peran (Role)</option>
                <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                <option value="ketua" {{ request('role') === 'ketua' ? 'selected' : '' }}>Ketua</option>
                <option value="bendahara" {{ request('role') === 'bendahara' ? 'selected' : '' }}>Bendahara</option>
                <option value="koordiv" {{ request('role') === 'koordiv' ? 'selected' : '' }}>Koordinator Divisi</option>
                <option value="anggota" {{ request('role') === 'anggota' ? 'selected' : '' }}>Anggota</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="status" class="form-select" style="font-size: 13px;" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-nexvo-dark w-100">Filter</button>
            @if(request()->hasAny(['search', 'role', 'status']))
                <a href="{{ route('admin.users.index') }}" class="btn btn-light border" title="Reset Filter"><i class="bi bi-arrow-counterclockwise"></i></a>
            @endif
        </div>
    </form>

    <!-- Tabel Pengguna -->
    <div class="table-responsive">
        <table class="table align-middle mb-0" style="font-size: 13px;">
            <thead>
                <tr style="color: #64748B; border-bottom: 1px solid #E2E8F0;">
                    <th class="fw-semibold py-2.5">Pengguna</th>
                    <th class="fw-semibold py-2.5">Peran (Role)</th>
                    <th class="fw-semibold py-2.5">Divisi</th>
                    <th class="fw-semibold py-2.5 text-center">Status Akun</th>
                    <th class="fw-semibold py-2.5 text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $u)
                <tr>
                    <td class="py-3">
                        <div class="d-flex align-items-center gap-2.5">
                            <div>
                                <div class="fw-bold" style="color: #1E293B;">{{ $u->name }}</div>
                                <div class="text-muted" style="font-size: 11.5px;">{{ $u->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="py-3">
                        @php
                            $roleLabels = [
                                'admin' => 'Administrator',
                                'ketua' => 'Ketua',
                                'bendahara' => 'Bendahara',
                                'koordiv' => 'Koordinator Divisi',
                                'anggota' => 'Anggota',
                            ];
                        @endphp
                        <span class="badge rounded-2 px-2.5 py-1 fw-medium" style="background-color: #E8EEF3; color: #2F4156;">
                            {{ $roleLabels[$u->role] ?? ucfirst($u->role) }}
                        </span>
                    </td>
                    <td class="py-3 text-muted">{{ $u->division ?? '-' }}</td>
                    <td class="py-3 text-center">
                        @if($u->is_active)
                            <span class="badge rounded-2 px-2.5 py-1 fw-medium" style="background-color: #DCFCE7; color: #15803D;">Aktif</span>
                        @else
                            <span class="badge rounded-2 px-2.5 py-1 fw-medium" style="background-color: #FEE2E2; color: #B91C1C;">Nonaktif</span>
                        @endif
                    </td>
                    <td class="py-3 text-end">
                        <div class="d-inline-flex gap-1">
                            <!-- Edit Data & Peran -->
                            <button type="button" class="btn btn-sm btn-light border" title="Edit Data & Peran" data-bs-toggle="modal" data-bs-target="#editUserModal{{ $u->id }}">
                                <i class="bi bi-pencil-square text-dark"></i>
                            </button>

                            <!-- Reset Kata Sandi -->
                            <button type="button" class="btn btn-sm btn-light border" title="Reset Kata Sandi" data-bs-toggle="modal" data-bs-target="#resetPassModal{{ $u->id }}">
                                <i class="bi bi-key text-primary"></i>
                            </button>

                            <!-- Aktif / Nonaktifkan Akun -->
                            @if($u->id !== Auth::id())
                            <form action="{{ route('admin.users.toggleStatus', $u->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-light border" title="{{ $u->is_active ? 'Nonaktifkan Akun' : 'Aktifkan Kembali' }}">
                                    <i class="bi {{ $u->is_active ? 'bi-person-slash text-danger' : 'bi-person-check text-success' }}"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>

                <!-- Modal Edit Pengguna -->
                <div class="modal fade" id="editUserModal{{ $u->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 rounded-4 p-2">
                            <div class="modal-header border-0">
                                <h5 class="modal-title fw-bold" style="color: #2F4156;">Edit Pengguna &amp; Peran</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form action="{{ route('admin.users.update', $u->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label fw-medium small">Nama Lengkap</label>
                                        <input type="text" name="name" value="{{ $u->name }}" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-medium small">Alamat Email</label>
                                        <input type="email" name="email" value="{{ $u->email }}" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-medium small">Peran (Role)</label>
                                        <select name="role" class="form-select" required>
                                            <option value="anggota" {{ $u->role === 'anggota' ? 'selected' : '' }}>Anggota</option>
                                            <option value="koordiv" {{ $u->role === 'koordiv' ? 'selected' : '' }}>Koordinator Divisi</option>
                                            <option value="bendahara" {{ $u->role === 'bendahara' ? 'selected' : '' }}>Bendahara</option>
                                            @if(!$hasKetua || $u->role === 'ketua')
                                                <option value="ketua" {{ $u->role === 'ketua' ? 'selected' : '' }}>Ketua</option>
                                            @endif
                                            <option value="admin" {{ $u->role === 'admin' ? 'selected' : '' }}>Administrator</option>
                                        </select>
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

                <!-- Modal Reset Kata Sandi -->
                <div class="modal fade" id="resetPassModal{{ $u->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 rounded-4 p-2">
                            <div class="modal-header border-0">
                                <h5 class="modal-title fw-bold" style="color: #2F4156;">Reset Kata Sandi</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form action="{{ route('admin.users.resetPassword', $u->id) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <div class="modal-body">
                                    <p class="text-muted small mb-3">
                                        Atur ulang kata sandi baru untuk akun <strong>{{ $u->name }}</strong> ({{ $u->email }}).
                                    </p>
                                    <div class="mb-3">
                                        <label class="form-label fw-medium small">Kata Sandi Baru</label>
                                        <input type="password" name="new_password" class="form-control" minlength="6" required placeholder="Minimal 6 karakter">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label fw-medium small">Konfirmasi Kata Sandi Baru</label>
                                        <input type="password" name="new_password_confirmation" class="form-control" minlength="6" required placeholder="Ulangi kata sandi baru">
                                    </div>
                                </div>
                                <div class="modal-footer border-0">
                                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn text-white" style="background-color: #2F4156;">Reset Kata Sandi</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">Tidak ada data pengguna yang sesuai filter.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Pengguna Baru -->
<div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 p-2">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold" style="color: #2F4156;">Tambah Pengguna Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.users.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium small">Nama Lengkap</label>
                        <input type="text" name="name" class="form-control" required placeholder="Contoh: Raka Pratama">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium small">Alamat Email</label>
                        <input type="email" name="email" class="form-control" required placeholder="raka@nexvo.id">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium small">Kata Sandi Awal</label>
                        <input type="password" name="password" class="form-control" minlength="6" required placeholder="Minimal 6 karakter">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium small">Peran (Role)</label>
                        <select name="role" class="form-select" required>
                            <option value="anggota">Anggota</option>
                            <option value="koordiv">Koordinator Divisi</option>
                            <option value="bendahara">Bendahara</option>
                            @if(!$hasKetua)
                                <option value="ketua">Ketua</option>
                            @endif
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn text-white" style="background-color: #2F4156;">Simpan Pengguna</button>
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
            new bootstrap.Modal(document.getElementById('createUserModal')).show();
        });
    @endif
</script>
@endpush