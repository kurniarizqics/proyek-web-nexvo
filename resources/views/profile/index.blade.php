@extends('layouts.app')

@section('content')
@php
    $activeTab = request()->query('tab', 'profile');
    if ($errors->has('current_password') || $errors->has('password')) {
        $activeTab = 'security';
    }
    $roleLabel = ucfirst($user->role);
@endphp

<style>
    .profile-tabs {
        display: flex;
        gap: 28px;
        border-bottom: 1px solid #E2E8F0;
        margin-bottom: 24px;
    }
    .profile-tab-btn {
        background: none;
        border: none;
        padding: 10px 4px 12px 4px;
        font-size: 13.5px;
        font-weight: 500;
        color: #64748B;
        position: relative;
        cursor: pointer;
        transition: color 0.15s ease;
    }
    .profile-tab-btn:hover {
        color: #1E293B;
    }
    .profile-tab-btn.active {
        color: #1E293B;
        font-weight: 600;
    }
    .profile-tab-btn.active::after {
        content: "";
        position: absolute;
        left: 0;
        right: 0;
        bottom: -1px;
        height: 2.5px;
        background-color: #547285;
        border-radius: 2px;
    }
    .avatar-circle-lg {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #F1F5F9;
        background-color: #E2E8F0;
    }
    .avatar-placeholder-lg {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background-color: #607990;
        color: #FFFFFF;
        font-size: 40px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
    }
    .camera-badge-btn {
        position: absolute;
        right: 4px;
        bottom: 4px;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background-color: #547285;
        color: #FFFFFF;
        border: 2px solid #FFFFFF;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        cursor: pointer;
    }
    .locked-field-box {
        background-color: #F4F6F9;
        border: 1px solid #E2E8F0;
        border-radius: 10px;
        padding: 10px 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        color: #475569;
        font-size: 13.5px;
    }
    .role-pill-badge {
        background-color: #DCE5EE;
        color: #243242;
        font-size: 12px;
        font-weight: 600;
        padding: 4px 14px;
        border-radius: 6px;
        display: inline-block;
    }
    .meta-strip {
        background-color: #F8FAFC;
        border: 1px solid #EDF2F7;
        border-radius: 12px;
        padding: 16px 20px;
    }
    .strength-bar {
        height: 4px;
        border-radius: 4px;
        background-color: #E2E8F0;
        transition: background-color 0.2s ease;
    }
    .strength-bar.filled {
        background-color: #2E6F5E;
    }
    .btn-slate-save {
        background-color: #547285;
        color: #FFFFFF;
        border: none;
        border-radius: 9px;
        padding: 9px 22px;
        font-size: 13px;
        font-weight: 500;
        transition: all 0.15s ease;
    }
    .btn-slate-save:hover:not(:disabled) {
        background-color: #435D6E;
        color: #FFFFFF;
    }
    .btn-slate-save:disabled {
        background-color: #CBD5E1;
        color: #FFFFFF;
        cursor: not-allowed;
    }
</style>

<!-- Header + Toast Sukses Kanan Atas -->
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
    <div>
        <h3 class="fw-bold mb-1" style="color: #1E293B;">Pengaturan Akun</h3>
        <p class="text-muted mb-0" style="font-size: 13px;">Kelola informasi profil pribadi, informasi organisasi, dan keamanan kata sandi Anda.</p>
    </div>

    @if(session('profile_success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center justify-content-between mb-0 py-2 px-3 rounded-3"
             role="alert"
             style="background-color: #ECFDF5; border: 1px solid #A7F3D0; color: #15803D; font-size: 12.5px; min-width: 290px;">
            <span>{{ session('profile_success') }}</span>
            <button type="button" class="btn-close btn-close-sm ms-3" data-bs-dismiss="alert" style="font-size: 10px;"></button>
        </div>
    @endif
</div>

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3" role="alert" style="font-size: 13px;">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<!-- Navigasi Tab -->
<div class="profile-tabs">
    <button type="button" class="profile-tab-btn {{ $activeTab === 'profile' ? 'active' : '' }}" data-tab="profile">
        Profil &amp; Informasi
    </button>
    <button type="button" class="profile-tab-btn {{ $activeTab === 'security' ? 'active' : '' }}" data-tab="security">
        Keamanan &amp; Kata Sandi
    </button>
</div>

<!-- ===================================================================== -->
<!-- TAB 1: PROFIL & INFORMASI                                             -->
<!-- ===================================================================== -->
<div id="tab-profile" class="{{ $activeTab === 'profile' ? '' : 'd-none' }}">
    <div class="nexvo-card p-4 p-md-5">
        <div class="mb-4">
            <h5 class="fw-bold mb-1" style="font-size: 16px; color: #1E293B;">Informasi Profil</h5>
            <p class="text-muted mb-0" style="font-size: 12.5px;">Informasi ini digunakan untuk identitas akun Anda di dalam sistem Nexvo Finance.</p>
        </div>

        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" id="profileForm">
            @csrf
            @method('PUT')

            <!-- Hidden input file foto profil -->
            <input type="file" name="avatar" id="avatarInput" class="d-none" accept=".jpg,.jpeg,.png">

            <div class="row g-4 align-items-start">
                <!-- Kolom Kiri: Foto Profil -->
                <div class="col-lg-3 text-center pt-2">
                    <div class="position-relative d-inline-block mb-3">
                        @if($user->avatar_url)
                            <img src="{{ $user->avatar_url }}" alt="Foto Profil" id="avatarMainPreview" class="avatar-circle-lg">
                            <div id="avatarMainInitial" class="avatar-placeholder-lg d-none">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                        @else
                            <img src="" alt="Foto Profil" id="avatarMainPreview" class="avatar-circle-lg d-none">
                            <div id="avatarMainInitial" class="avatar-placeholder-lg">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                        @endif

                        <button type="button" class="camera-badge-btn" data-bs-toggle="modal" data-bs-target="#photoPickerModal" title="Ubah Foto Profil">
                            <i class="bi bi-camera"></i>
                        </button>
                    </div>

                    <div>
                        <button type="button" class="btn btn-outline-secondary rounded-3 px-3 py-2 fw-medium bg-white" style="font-size: 12.5px; border-color: #CBD5E1; color: #1E293B;" data-bs-toggle="modal" data-bs-target="#photoPickerModal">
                            Unggah Foto Baru
                        </button>
                    </div>

                    <div class="text-muted mt-2" style="font-size: 11px; line-height: 1.5;">
                        Format JPG atau PNG.<br>Ukuran maksimal 2 MB.
                    </div>

                    <div id="photoSelectedBadge" class="d-none mt-3 mx-auto px-3 py-1 rounded-2" style="background-color: #ECFDF5; color: #15803D; font-size: 11.5px; font-weight: 500; max-width: 170px;">
                        Foto baru dipilih
                    </div>
                </div>

                <!-- Kolom Kanan: Form Detail Profil -->
                <div class="col-lg-9 border-lg-start ps-lg-4">
                    <!-- Nama Lengkap -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size: 12.5px; color: #1E293B;">Nama Lengkap</label>
                        <input type="text"
                               name="name"
                               id="inputName"
                               value="{{ old('name', $user->name) }}"
                               data-initial="{{ $user->name }}"
                               class="form-control rounded-3"
                               style="font-size: 13.5px; border-color: #E2E8F0; height: 44px;"
                               required>
                    </div>

                    <!-- Nomor Induk / ID Pengguna (Locked) -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size: 12.5px; color: #1E293B;">Nomor Induk / ID Pengguna</label>
                        <div class="locked-field-box">
                            <span>{{ $user->user_code }}</span>
                            <i class="bi bi-lock text-muted"></i>
                        </div>
                        <div class="text-muted mt-1" style="font-size: 11px;">ID pengguna dikelola oleh administrator dan tidak dapat diubah secara mandiri.</div>
                    </div>

                    <!-- Alamat Email -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size: 12.5px; color: #1E293B;">Alamat Email</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 rounded-start-3 text-muted" style="border-color: #E2E8F0;">
                                <i class="bi bi-envelope"></i>
                            </span>
                            <input type="email"
                                   name="email"
                                   id="inputEmail"
                                   value="{{ old('email', $user->email) }}"
                                   data-initial="{{ $user->email }}"
                                   class="form-control border-start-0 rounded-end-3"
                                   style="font-size: 13.5px; border-color: #E2E8F0; height: 44px;"
                                   required>
                        </div>
                    </div>

                    <!-- Peran / Jabatan (Locked - hanya peran saja) -->
                    <div class="mb-2">
                        <label class="form-label fw-semibold" style="font-size: 12.5px; color: #1E293B;">Peran / Jabatan</label>
                        <div class="locked-field-box">
                            <span class="role-pill-badge">{{ $roleLabel }}</span>
                            <i class="bi bi-lock text-muted"></i>
                        </div>
                        <div class="text-muted mt-1" style="font-size: 11px;">Peran akun ditetapkan berdasarkan hak akses organisasi dan tidak dapat diubah melalui halaman ini.</div>
                    </div>
                </div>
            </div>

            <!-- Strip Metadata Akun -->
            <div class="meta-strip mt-4">
                <div class="row g-3" style="font-size: 12.5px;">
                    <div class="col-md-4 d-flex align-items-center gap-2">
                        <i class="bi bi-check-lg text-muted"></i>
                        <div>
                            <div class="text-muted" style="font-size: 11px;">Status Akun</div>
                            <div class="fw-semibold" style="color: #15803D;">Aktif</div>
                        </div>
                    </div>
                    <div class="col-md-4 d-flex align-items-center gap-2">
                        <i class="bi bi-calendar3 text-muted"></i>
                        <div>
                            <div class="text-muted" style="font-size: 11px;">Terdaftar Sejak</div>
                            <div class="fw-semibold" style="color: #1E293B;">{{ $user->created_at->locale('id')->translatedFormat('d F Y') }}</div>
                        </div>
                    </div>
                    <div class="col-md-4 d-flex align-items-center gap-2">
                        <i class="bi bi-clock text-muted"></i>
                        <div>
                            <div class="text-muted" style="font-size: 11px;">Terakhir Diperbarui</div>
                            <div class="fw-semibold" style="color: #1E293B;">{{ $user->updated_at->locale('id')->translatedFormat('d F Y') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Aksi Tab Profil -->
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-4 pt-2">
                <div id="profileStatusNote" class="text-muted" style="font-size: 11.5px;">
                    {{ session('profile_success') ? 'Semua perubahan telah disimpan.' : 'ID pengguna dan peran hanya dapat dilihat.' }}
                </div>

                <div class="d-flex align-items-center gap-2">
                    <button type="button" id="btnResetProfile" class="btn btn-outline-secondary rounded-3 px-4 py-2 bg-white fw-medium" style="font-size: 13px; border-color: #CBD5E1; color: #1E293B;">
                        Batal
                    </button>
                    <button type="button" id="btnOpenConfirmProfile" class="btn-slate-save" disabled data-bs-toggle="modal" data-bs-target="#confirmProfileModal">
                        Simpan Perubahan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ===================================================================== -->
<!-- TAB 2: KEAMANAN & KATA SANDI                                          -->
<!-- ===================================================================== -->
<div id="tab-security" class="{{ $activeTab === 'security' ? '' : 'd-none' }}">
    <div class="nexvo-card p-4 p-md-5 mx-auto" style="max-width: 780px;">
        <div class="d-flex align-items-start gap-3 mb-4">
            <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background-color: #F1F5F9; color: #547285;">
                <i class="bi bi-lock fs-5"></i>
            </div>
            <div>
                <h5 class="fw-bold mb-1" style="font-size: 16px; color: #1E293B;">Keamanan Akun</h5>
                <p class="text-muted mb-0" style="font-size: 12.5px;">Pastikan akun Anda menggunakan kata sandi yang kuat untuk menjaga kerahasiaan data keuangan.</p>
            </div>
        </div>

        <form action="{{ route('profile.updatePassword') }}" method="POST" id="passwordForm" class="mx-auto" style="max-width: 520px;">
            @csrf
            @method('PUT')

            <!-- Kata Sandi Saat Ini -->
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size: 12.5px; color: #1E293B;">Kata Sandi Saat Ini</label>
                <div class="input-group">
                    <input type="password" name="current_password" id="currentPassword" class="form-control border-end-0 rounded-start-3" placeholder="Masukkan kata sandi saat ini" style="font-size: 13px; border-color: #E2E8F0; height: 44px;" required>
                    <button type="button" class="input-group-text bg-white border-start-0 rounded-end-3 text-muted toggle-pw" data-target="currentPassword" style="border-color: #E2E8F0;">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <!-- Kata Sandi Baru -->
            <div class="mb-2">
                <label class="form-label fw-semibold" style="font-size: 12.5px; color: #1E293B;">Kata Sandi Baru</label>
                <div class="input-group">
                    <input type="password" name="password" id="newPassword" class="form-control border-end-0 rounded-start-3" placeholder="Masukkan kata sandi baru" style="font-size: 13px; border-color: #E2E8F0; height: 44px;" required>
                    <button type="button" class="input-group-text bg-white border-start-0 rounded-end-3 text-muted toggle-pw" data-target="newPassword" style="border-color: #E2E8F0;">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <!-- 4 Bar Indikator Kekuatan Sandi -->
            <div class="row g-2 my-2">
                <div class="col-3"><div class="strength-bar" id="bar1"></div></div>
                <div class="col-3"><div class="strength-bar" id="bar2"></div></div>
                <div class="col-3"><div class="strength-bar" id="bar3"></div></div>
                <div class="col-3"><div class="strength-bar" id="bar4"></div></div>
            </div>

            <div id="strengthLabel" class="fw-semibold mb-2" style="font-size: 11.5px; color: #64748B;">Kekuatan kata sandi</div>

            <!-- 4 Checklist Kriteria Sandi -->
            <ul class="list-unstyled mb-3" style="font-size: 11.5px; color: #64748B; line-height: 1.8;">
                <li id="ruleLength"><i class="bi bi-check2 me-2"></i>Minimal 8 karakter.</li>
                <li id="ruleCase"><i class="bi bi-check2 me-2"></i>Mengandung huruf besar dan huruf kecil.</li>
                <li id="ruleNumber"><i class="bi bi-check2 me-2"></i>Mengandung angka.</li>
                <li id="ruleSpecial"><i class="bi bi-check2 me-2"></i>Disarankan menggunakan karakter khusus.</li>
            </ul>

            <!-- Konfirmasi Kata Sandi Baru -->
            <div class="mb-4">
                <label class="form-label fw-semibold" style="font-size: 12.5px; color: #1E293B;">Konfirmasi Kata Sandi Baru</label>
                <div class="input-group">
                    <input type="password" name="password_confirmation" id="confirmPassword" class="form-control border-end-0 rounded-start-3" placeholder="Masukkan kembali kata sandi baru" style="font-size: 13px; border-color: #E2E8F0; height: 44px;" required>
                    <button type="button" class="input-group-text bg-white border-start-0 rounded-end-3 text-muted toggle-pw" data-target="confirmPassword" style="border-color: #E2E8F0;">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <hr class="my-4" style="border-color: #E2E8F0;">

            <div class="d-flex justify-content-end gap-2">
                <button type="button" id="btnResetPassword" class="btn btn-outline-secondary rounded-3 px-4 py-2 bg-white fw-medium" style="font-size: 13px; border-color: #CBD5E1; color: #1E293B;">
                    Batal
                </button>
                <button type="button" class="btn-slate-save" data-bs-toggle="modal" data-bs-target="#confirmPasswordModal">
                    Simpan Perubahan
                </button>
            </div>
        </form>

        <div class="text-muted mt-4 pt-2" style="font-size: 11.5px;">
            <i class="bi bi-lock me-1"></i> Jangan bagikan kata sandi Anda kepada siapa pun.
        </div>
    </div>
</div>

<!-- ===================================================================== -->
<!-- MODAL 1: PILIH FOTO PROFIL (SESUAI GAMBAR 2)                          -->
<!-- ===================================================================== -->
<div class="modal fade" id="photoPickerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow bg-white p-3">
            <div class="modal-header border-bottom-0 pb-1">
                <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background-color: #F1F5F9; color: #547285;">
                    <i class="bi bi-image"></i>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2">
                <h5 class="fw-bold mb-1" style="color: #1E293B;">Pilih Foto Profil</h5>
                <p class="text-muted mb-3" style="font-size: 12.5px;">Pilih file gambar dari perangkat Anda untuk dijadikan foto profil.</p>

                <!-- Area Klik Pilih File -->
                <div id="dropZoneBox" class="rounded-3 p-3 d-flex align-items-center gap-3 mb-2" style="background-color: #F8FAFC; border: 1.5px dashed #CBD5E1; cursor: pointer;">
                    <img id="modalPhotoPreview"
                         src="{{ $user->avatar_url ?? '' }}"
                         alt="Preview"
                         class="rounded-circle object-fit-cover {{ $user->avatar_url ? '' : 'd-none' }}"
                         style="width: 52px; height: 52px;">
                    <div id="modalPhotoPlaceholder"
                         class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center fw-bold {{ $user->avatar_url ? 'd-none' : '' }}"
                         style="width: 52px; height: 52px; font-size: 18px;">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <div>
                        <div id="modalFileName" class="fw-semibold" style="font-size: 13px; color: #1E293B;">Klik untuk memilih foto</div>
                        <div id="modalFileMeta" class="text-muted" style="font-size: 11.5px;">JPG atau PNG &bull; Maksimal 2 MB</div>
                    </div>
                </div>

                <div class="text-muted mb-4" style="font-size: 11.5px;">Format JPG atau PNG. Ukuran maksimal 2 MB.</div>

                <div class="row g-2">
                    <div class="col-6">
                        <button type="button" class="btn btn-outline-secondary w-100 rounded-3 py-2 fw-medium" data-bs-dismiss="modal" style="font-size: 13px;">
                            Batal
                        </button>
                    </div>
                    <div class="col-6">
                        <button type="button" id="btnUsePhoto" class="btn-slate-save w-100 py-2" data-bs-dismiss="modal">
                            Gunakan Foto
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===================================================================== -->
<!-- MODAL 2: KONFIRMASI SIMPAN PERUBAHAN PROFIL (SESUAI GAMBAR 4)         -->
<!-- ===================================================================== -->
<div class="modal fade" id="confirmProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow bg-white p-3">
            <div class="modal-header border-bottom-0 pb-1">
                <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background-color: #F1F5F9; color: #547285;">
                    <i class="bi bi-person-check"></i>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2">
                <h5 class="fw-bold mb-1" style="color: #1E293B;">Simpan Perubahan Profil?</h5>
                <p class="text-muted mb-3" style="font-size: 12.5px;">Pastikan informasi yang Anda masukkan sudah benar.</p>

                <div class="rounded-3 p-3 mb-4 text-muted" style="background-color: #F8FAFC; font-size: 12px;">
                    Perubahan akan diterapkan pada profil pribadi Anda.
                </div>

                <div class="row g-2">
                    <div class="col-5">
                        <button type="button" class="btn btn-outline-secondary w-100 rounded-3 py-2 fw-medium" data-bs-dismiss="modal" style="font-size: 13px;">
                            Kembali
                        </button>
                    </div>
                    <div class="col-7">
                        <button type="button" onclick="document.getElementById('profileForm').submit()" class="btn-slate-save w-100 py-2">
                            Simpan Perubahan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===================================================================== -->
<!-- MODAL 3: KONFIRMASI UBAH KATA SANDI (SESUAI GAMBAR 7)                 -->
<!-- ===================================================================== -->
<div class="modal fade" id="confirmPasswordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow bg-white p-3">
            <div class="modal-header border-bottom-0 pb-1">
                <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background-color: #F1F5F9; color: #547285;">
                    <i class="bi bi-lock"></i>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2">
                <h5 class="fw-bold mb-1" style="color: #1E293B;">Ubah Kata Sandi?</h5>
                <p class="text-muted mb-3" style="font-size: 12.5px;">Apakah Anda yakin ingin memperbarui kata sandi akun ini?</p>

                <div class="rounded-3 p-3 mb-4 text-muted" style="background-color: #F8FAFC; font-size: 12px;">
                    Gunakan kata sandi baru saat masuk berikutnya.
                </div>

                <div class="row g-2">
                    <div class="col-5">
                        <button type="button" class="btn btn-outline-secondary w-100 rounded-3 py-2 fw-medium" data-bs-dismiss="modal" style="font-size: 13px;">
                            Batal
                        </button>
                    </div>
                    <div class="col-7">
                        <button type="button" onclick="document.getElementById('passwordForm').submit()" class="btn-slate-save w-100 py-2">
                            Ya, Ubah Kata Sandi
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // 1. Switch Tab Profil & Keamanan
    const tabButtons = document.querySelectorAll('.profile-tab-btn');
    const tabProfile = document.getElementById('tab-profile');
    const tabSecurity = document.getElementById('tab-security');

    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            tabButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            if (btn.dataset.tab === 'profile') {
                tabProfile.classList.remove('d-none');
                tabSecurity.classList.add('d-none');
            } else {
                tabSecurity.classList.remove('d-none');
                tabProfile.classList.add('d-none');
            }
        });
    });

    // 2. Deteksi Perubahan Form Profil & Preview Foto
    const inputName = document.getElementById('inputName');
    const inputEmail = document.getElementById('inputEmail');
    const avatarInput = document.getElementById('avatarInput');
    const btnOpenConfirmProfile = document.getElementById('btnOpenConfirmProfile');
    const btnResetProfile = document.getElementById('btnResetProfile');
    const profileStatusNote = document.getElementById('profileStatusNote');
    const photoSelectedBadge = document.getElementById('photoSelectedBadge');

    const dropZoneBox = document.getElementById('dropZoneBox');
    const modalPhotoPreview = document.getElementById('modalPhotoPreview');
    const modalPhotoPlaceholder = document.getElementById('modalPhotoPlaceholder');
    const modalFileName = document.getElementById('modalFileName');
    const modalFileMeta = document.getElementById('modalFileMeta');
    const btnUsePhoto = document.getElementById('btnUsePhoto');

    const avatarMainPreview = document.getElementById('avatarMainPreview');
    const avatarMainInitial = document.getElementById('avatarMainInitial');
    let pendingAvatarDataUrl = null;

    function checkProfileDirty() {
        const nameChanged = inputName.value.trim() !== inputName.dataset.initial;
        const emailChanged = inputEmail.value.trim() !== inputEmail.dataset.initial;
        const avatarChanged = avatarInput.files && avatarInput.files.length > 0;
        const isDirty = nameChanged || emailChanged || avatarChanged;

        btnOpenConfirmProfile.disabled = !isDirty;
        if (isDirty) {
            profileStatusNote.textContent = 'Perubahan Anda belum disimpan.';
        } else {
            profileStatusNote.textContent = 'ID pengguna dan peran hanya dapat dilihat.';
        }
    }

    inputName.addEventListener('input', checkProfileDirty);
    inputEmail.addEventListener('input', checkProfileDirty);

    if (dropZoneBox) {
        dropZoneBox.addEventListener('click', () => avatarInput.click());
    }

    if (avatarInput) {
        avatarInput.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;

            const sizeKB = Math.round(file.size / 1024);
            const ext = file.name.split('.').pop().toUpperCase();
            modalFileName.textContent = file.name;
            modalFileMeta.textContent = `${ext} • ${sizeKB} KB • Dipilih`;

            const reader = new FileReader();
            reader.onload = function (e) {
                pendingAvatarDataUrl = e.target.result;
                modalPhotoPreview.src = pendingAvatarDataUrl;
                modalPhotoPreview.classList.remove('d-none');
                if (modalPhotoPlaceholder) modalPhotoPlaceholder.classList.add('d-none');
            };
            reader.readAsDataURL(file);
        });
    }

    if (btnUsePhoto) {
        btnUsePhoto.addEventListener('click', () => {
            if (pendingAvatarDataUrl) {
                avatarMainPreview.src = pendingAvatarDataUrl;
                avatarMainPreview.classList.remove('d-none');
                if (avatarMainInitial) avatarMainInitial.classList.add('d-none');
                photoSelectedBadge.classList.remove('d-none');
                checkProfileDirty();
            }
        });
    }

    if (btnResetProfile) {
        btnResetProfile.addEventListener('click', () => {
            inputName.value = inputName.dataset.initial;
            inputEmail.value = inputEmail.dataset.initial;
            avatarInput.value = '';
            pendingAvatarDataUrl = null;
            photoSelectedBadge.classList.add('d-none');
            checkProfileDirty();
        });
    }

    // 3. Show/Hide Password & Indikator Kekuatan Sandi
    document.querySelectorAll('.toggle-pw').forEach(btn => {
        btn.addEventListener('click', () => {
            const target = document.getElementById(btn.dataset.target);
            const icon = btn.querySelector('i');
            if (target.type === 'password') {
                target.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                target.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        });
    });

    const newPassword = document.getElementById('newPassword');
    const bars = [document.getElementById('bar1'), document.getElementById('bar2'), document.getElementById('bar3'), document.getElementById('bar4')];
    const strengthLabel = document.getElementById('strengthLabel');

    function updateRuleStatus(el, passed) {
        if (!el) return;
        el.style.color = passed ? '#15803D' : '#64748B';
        el.style.fontWeight = passed ? '500' : '400';
    }

    if (newPassword) {
        newPassword.addEventListener('input', () => {
            const val = newPassword.value;
            const hasLength = val.length >= 8;
            const hasCase = /[a-z]/.test(val) && /[A-Z]/.test(val);
            const hasNumber = /\d/.test(val);
            const hasSpecial = /[^A-Za-z0-9]/.test(val);

            updateRuleStatus(document.getElementById('ruleLength'), hasLength);
            updateRuleStatus(document.getElementById('ruleCase'), hasCase);
            updateRuleStatus(document.getElementById('ruleNumber'), hasNumber);
            updateRuleStatus(document.getElementById('ruleSpecial'), hasSpecial);

            const score = [hasLength, hasCase, hasNumber, hasSpecial].filter(Boolean).length;
            bars.forEach((bar, idx) => {
                if (idx < score) bar.classList.add('filled');
                else bar.classList.remove('filled');
            });

            if (val.length === 0) {
                strengthLabel.textContent = 'Kekuatan kata sandi';
                strengthLabel.style.color = '#64748B';
            } else if (score <= 1) {
                strengthLabel.textContent = 'Lemah';
                strengthLabel.style.color = '#DC2626';
            } else if (score === 2) {
                strengthLabel.textContent = 'Cukup';
                strengthLabel.style.color = '#D97706';
            } else if (score === 3) {
                strengthLabel.textContent = 'Kuat';
                strengthLabel.style.color = '#15803D';
            } else {
                strengthLabel.textContent = 'Sangat Kuat';
                strengthLabel.style.color = '#15803D';
            }
        });
    }

    const btnResetPassword = document.getElementById('btnResetPassword');
    if (btnResetPassword) {
        btnResetPassword.addEventListener('click', () => {
            document.getElementById('passwordForm').reset();
            newPassword.dispatchEvent(new Event('input'));
        });
    }
</script>
@endpush