<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexvo Finance - Financial Management</title>
    <!-- Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 & Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        :root {
            --bg-main: #F4EFEA;
            --navy-dark: #243242;
            --slate-blue: #607990;
            --active-bg: #C5D3DF;
            --parent-active-bg: #E8EEF3;
            --cream-item: #EFECE8;
            --text-dark: #1E293B;
            --text-muted: #64748B;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-main);
            color: var(--text-dark);
            overflow-x: hidden;
        }

        /* Sidebar Styling */
        .sidebar {
            width: 260px;
            height: 100vh;
            overflow-y: auto;
            background-color: #FFFFFF;
            border-right: 1px solid #E5E7EB;
            padding: 24px 16px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .sidebar.collapsed {
            margin-left: -260px;
        }

        .brand-box {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 8px 24px 8px;
        }

        .brand-logo-circle {
            width: 38px;
            height: 38px;
            background-color: var(--active-bg);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: var(--navy-dark);
            flex-shrink: 0;
        }

        .menu-label {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted);
            letter-spacing: 0.8px;
            padding: 0 12px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        /* Main Menu Item */
        .nav-item-main {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 14px;
            border-radius: 10px;
            color: var(--text-dark);
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 500;
            margin-bottom: 4px;
            cursor: pointer;
            transition: background-color 0.15s ease, color 0.15s ease;
            user-select: none;
        }

        .nav-item-main:hover {
            background-color: #F1F5F9;
            color: var(--navy-dark);
        }

        /* Aksen Merah untuk Tombol Keluar */
        .nav-item-logout {
            color: #DC2626;
        }

        .nav-item-logout:hover {
            background-color: #FEF2F2;
            color: #B91C1C;
        }

        /* Main Menu aktif tunggal */
        .nav-item-main.active {
            background-color: var(--active-bg);
            color: var(--navy-dark);
            font-weight: 600;
        }

        /* Main Menu aktif sebagai induk sub-menu */
        .nav-item-main.parent-active {
            background-color: var(--parent-active-bg);
            color: var(--navy-dark);
            font-weight: 600;
        }

        .chevron-icon {
            transition: transform 0.25s ease;
        }

        .nav-item-main[aria-expanded="true"] .chevron-icon {
            transform: rotate(180deg);
        }

        /* Sub Menu List */
        .nav-sub-list {
            list-style: none;
            padding-left: 26px;
            margin-bottom: 8px;
            margin-top: 2px;
        }

        .nav-sub-list li {
            margin-bottom: 3px;
        }

        .nav-sub-list li a {
            display: flex;
            align-items: center;
            padding: 8px 14px;
            border-radius: 9px;
            font-size: 12.5px;
            color: var(--text-muted);
            text-decoration: none;
            position: relative;
            transition: all 0.15s ease;
        }

        .nav-sub-list li a:hover {
            color: var(--navy-dark);
            background-color: #F8FAFC;
        }

        /* Sub Menu Terpilih */
        .nav-sub-list li a.active-sub {
            background-color: var(--active-bg);
            color: var(--navy-dark);
            font-weight: 600;
        }

        .nav-sub-list li a.active-sub::before {
            content: "";
            position: absolute;
            left: 5px;
            top: 22%;
            height: 56%;
            width: 3.5px;
            background-color: var(--slate-blue);
            border-radius: 4px;
        }

        .badge-count {
            background-color: var(--slate-blue);
            color: white;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 12px;
        }

        /* Main Wrapper & Sticky Topbar */
        .main-wrapper {
            margin-left: 260px;
            min-height: 100vh;
            transition: all 0.3s ease;
        }

        .main-wrapper.expanded {
            margin-left: 0;
        }

        .topbar {
            background-color: #FFFFFF;
            padding: 14px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #EAE5DF;
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .btn-menu-toggle {
            background-color: var(--navy-dark);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 8px 16px;
            font-size: 13.5px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .search-box {
            background-color: #F5F5F5;
            border-radius: 10px;
            padding: 8px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            width: 280px;
        }

        .search-box input {
            border: none;
            background: transparent;
            outline: none;
            font-size: 13px;
            width: 100%;
        }

        .topbar-icon-btn {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background-color: #F8FAFC;
            border: 1px solid #E2E8F0;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            color: var(--navy-dark);
        }

        .notif-dot {
            width: 8px;
            height: 8px;
            background-color: #EF4444;
            border-radius: 50%;
            position: absolute;
            top: 2px;
            right: 2px;
        }

        .content-area {
            padding: 28px 32px;
        }

        /* Custom Cards & Buttons */
        .nexvo-card {
            background-color: #FFFFFF;
            border-radius: 14px;
            border: 1px solid #EAE5DF;
            padding: 20px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
        }

        .btn-nexvo-dark {
            background-color: var(--navy-dark);
            color: #FFFFFF;
            border-radius: 8px;
            padding: 7px 16px;
            font-size: 12.5px;
            font-weight: 500;
            border: none;
            text-decoration: none;
        }

        .btn-nexvo-dark:hover {
            background-color: #1A2530;
            color: #FFFFFF;
        }

        .btn-nexvo-soft {
            background-color: var(--active-bg);
            color: var(--navy-dark);
            border-radius: 8px;
            padding: 7px 16px;
            font-size: 12.5px;
            font-weight: 500;
            border: none;
            text-decoration: none;
        }

        .btn-nexvo-soft:hover {
            background-color: #B4C6D6;
            color: var(--navy-dark);
        }

        /* Modal Konfirmasi Logout */
        .logout-modal-card {
            background-color: #F7F2EC;
            border: none;
            border-radius: 16px;
            padding: 28px 28px 24px 28px;
        }

        .btn-logout-cancel {
            background-color: #FFFFFF;
            color: #2B394A;
            border: 1px solid #E5E7EB;
            border-radius: 10px;
            padding: 11px 16px;
            font-size: 13.5px;
            font-weight: 600;
            transition: background-color 0.15s ease;
        }

        .btn-logout-cancel:hover {
            background-color: #F8FAFC;
            color: #1E293B;
        }

        .btn-logout-confirm {
            background-color: #2B394A;
            color: #FFFFFF;
            border: none;
            border-radius: 10px;
            padding: 11px 16px;
            font-size: 13.5px;
            font-weight: 600;
            transition: background-color 0.15s ease;
        }

        .btn-logout-confirm:hover {
            background-color: #1F2A37;
            color: #FFFFFF;
        }
    </style>
</head>
<body>

@php
    $role = Auth::user()->role;
    $isAdminRole = ($role === 'admin');
    $isBendaharaRole = ($role === 'bendahara');
    $isFinanceViewerRole = in_array($role, ['ketua', 'bendahara']);

    $isMyProposals = request()->routeIs('proposals.index') && request()->query('tab', 'my') === 'my';
    $isPenyaluranSection = request()->query('section') === 'penyaluran';
    $isAntreanPencairan = request()->routeIs('proposals.index') && request()->query('tab') === 'approvals' && $isPenyaluranSection;
    $isValidasiBukti = request()->routeIs('transactions.index') && $isPenyaluranSection;
    $isPenyaluranGroup = $isAntreanPencairan || $isValidasiBukti;

    $isWaitingApproval = request()->routeIs('proposals.index') && request()->query('tab') === 'approvals' && !$isPenyaluranSection;
    $isApprovalHistory = request()->routeIs('proposals.index') && request()->query('tab') === 'history';
    $isProposalGroup = request()->routeIs('proposals.create') || $isMyProposals;
    $isApprovalGroup = $isWaitingApproval || $isApprovalHistory;

    $isTransactionMenu = (request()->routeIs('transactions.*') && !$isValidasiBukti) || request()->routeIs('reports.*');
    $isAuditMenu = request()->routeIs('audit-logs.*');
    $isProfileMenu = request()->routeIs('profile.*');

    // Hitung antrean validasi khusus sesuai wewenang role operasional keuangan
    $pendingCount = 0;
    $bendaharaApprovalCount = 0;

    if ($role === 'koordiv') {
        $pendingCount = \App\Models\Proposal::where('status', 'pending_koordiv')
            ->where('user_id', '!=', Auth::id())
            ->whereHas('user', fn($q) => $q->where('division', Auth::user()->division)->where('role', 'anggota'))
            ->count();
    } elseif ($role === 'ketua') {
        $pendingCount = \App\Models\Proposal::where('status', 'pending_ketua')
            ->where('user_id', '!=', Auth::id())
            ->count();
    } elseif ($role === 'bendahara') {
        // 1. Badge Penyaluran Dana -> Antrean Pencairan (siap dicairkan)
        $pendingCount = \App\Models\Proposal::where('user_id', '!=', Auth::id())
            ->where(function ($q) {
                $q->where('status', 'pending_disbursement')
                  ->orWhere(function ($sub) {
                      $sub->where('status', 'pending_bendahara')
                          ->whereHas('user', fn($u) => $u->whereRaw('LOWER(role) != ?', ['ketua']));
                  });
            })
            ->count();

        // 2. Badge Persetujuan Dana -> Menunggu Validasi (khusus pengajuan dari Ketua)
        $bendaharaApprovalCount = \App\Models\Proposal::where('status', 'pending_bendahara')
            ->where('user_id', '!=', Auth::id())
            ->whereHas('user', fn($q) => $q->whereRaw('LOWER(role) = ?', ['ketua']))
            ->count();
    }
@endphp

<!-- Sidebar Kiri -->
<aside class="sidebar" id="sidebar">
    <!-- Bagian Atas Sidebar: Brand & Menu Utama -->
    <div>
        <div class="brand-box">
            <div class="brand-logo-circle">
                <svg width="24" height="26" viewBox="0 0 100 120" fill="none">
                    <path d="M22 108C14 82 22 45 45 20L66 56C82 38 86 18 82 6C92 26 88 58 63 82L43 46C26 66 20 90 22 108Z" fill="#243242"/>
                </svg>
            </div>
            <div>
                <div class="fw-bold" style="font-size: 13.5px; letter-spacing: 0.3px;">NEXVO FINANCE</div>
                <div class="text-muted" style="font-size: 10.5px;">Financial Management</div>
            </div>
        </div>

        <div class="menu-label">Menu Utama</div>

        <!-- 1. Dashboard (Semua Role) -->
        <a href="{{ route('dashboard', $role) }}" class="nav-item-main {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <span><i class="bi bi-grid-1x2 me-2"></i> Dashboard</span>
        </a>

        @if($isAdminRole)
            <!-- ================================================================= -->
            <!-- SIDEBAR KHUSUS ADMIN (MANAJEMEN DATA MASTER & SISTEM)             -->
            <!-- ================================================================= -->
            <a href="{{ route('admin.users.index') }}" class="nav-item-main {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <span><i class="bi bi-people me-2"></i> Manajemen Pengguna</span>
            </a>

            <a href="{{ route('admin.divisions.index') }}" class="nav-item-main {{ request()->routeIs('admin.divisions.*') ? 'active' : '' }}">
                <span><i class="bi bi-diagram-3 me-2"></i> Manajemen Divisi</span>
            </a>

            <div class="nav-item-main {{ $isAuditMenu ? 'parent-active' : '' }}" data-bs-toggle="collapse" data-bs-target="#menuAudit" aria-expanded="{{ $isAuditMenu ? 'true' : 'false' }}">
                <span><i class="bi bi-clock-history me-2"></i> Audit Log</span>
                <i class="bi bi-chevron-down small chevron-icon"></i>
            </div>
            <div class="collapse {{ $isAuditMenu ? 'show' : '' }}" id="menuAudit">
                <ul class="nav-sub-list">
                    <li>
                        <a href="{{ route('audit-logs.index') }}" class="{{ $isAuditMenu ? 'active-sub' : '' }}">
                            Aktivitas Pengguna
                        </a>
                    </li>
                </ul>
            </div>
        @else
            <!-- ================================================================= -->
            <!-- SIDEBAR OPERASIONAL KEUANGAN (ANGGOTA, KOORDIV, KETUA, BENDAHARA) -->
            <!-- ================================================================= -->

            <!-- 2. Pengajuan Dana (Anggota, Koordiv, Ketua, Bendahara) -->
            <div class="nav-item-main {{ $isProposalGroup ? 'parent-active' : '' }}" data-bs-toggle="collapse" data-bs-target="#menuPengajuan" aria-expanded="{{ $isProposalGroup ? 'true' : 'false' }}">
                <span><i class="bi bi-plus-circle me-2"></i> Pengajuan Dana</span>
                <i class="bi bi-chevron-down small chevron-icon"></i>
            </div>
            <div class="collapse {{ $isProposalGroup ? 'show' : '' }}" id="menuPengajuan">
                <ul class="nav-sub-list">
                    <li>
                        <a href="{{ route('proposals.create') }}" class="{{ request()->routeIs('proposals.create') ? 'active-sub' : '' }}">
                            Buat Pengajuan
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('proposals.index', ['tab' => 'my']) }}" class="{{ $isMyProposals ? 'active-sub' : '' }}">
                            Riwayat Pengajuanku
                        </a>
                    </li>
                </ul>
            </div>

            <!-- 2b. Penyaluran Dana (Khusus Bendahara) -->
            @if($isBendaharaRole)
            <div class="nav-item-main {{ $isPenyaluranGroup ? 'parent-active' : '' }}" data-bs-toggle="collapse" data-bs-target="#menuPenyaluran" aria-expanded="{{ $isPenyaluranGroup ? 'true' : 'false' }}">
                <span><i class="bi bi-send-check me-2"></i> Penyaluran Dana</span>
                <div class="d-flex align-items-center gap-2">
                    @if($pendingCount > 0)
                        <span class="badge-count">{{ $pendingCount }}</span>
                    @endif
                    <i class="bi bi-chevron-down small chevron-icon"></i>
                </div>
            </div>
            <div class="collapse {{ $isPenyaluranGroup ? 'show' : '' }}" id="menuPenyaluran">
                <ul class="nav-sub-list">
                    <li>
                        <a href="{{ route('proposals.index', ['tab' => 'approvals', 'section' => 'penyaluran']) }}" class="{{ $isAntreanPencairan ? 'active-sub' : '' }}">
                            Antrean Pencairan
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('transactions.index', ['section' => 'penyaluran']) }}" class="{{ $isValidasiBukti ? 'active-sub' : '' }}">
                            Validasi Bukti
                        </a>
                    </li>
                </ul>
            </div>
            @endif

            <!-- 3. Persetujuan Dana (Hanya untuk Koordiv, Ketua, Bendahara) -->
            @if(in_array($role, ['koordiv', 'ketua', 'bendahara']))
            <div class="nav-item-main {{ $isApprovalGroup ? 'parent-active' : '' }}" data-bs-toggle="collapse" data-bs-target="#menuPersetujuan" aria-expanded="{{ $isApprovalGroup ? 'true' : 'false' }}">
                <span><i class="bi bi-check-circle me-2"></i> Persetujuan Dana</span>
                <div class="d-flex align-items-center gap-2">
                    @if(!$isBendaharaRole && $pendingCount > 0)
                        <span class="badge-count">{{ $pendingCount }}</span>
                    @elseif($isBendaharaRole && $bendaharaApprovalCount > 0)
                        <span class="badge-count">{{ $bendaharaApprovalCount }}</span>
                    @endif
                    <i class="bi bi-chevron-down small chevron-icon"></i>
                </div>
            </div>
            <div class="collapse {{ $isApprovalGroup ? 'show' : '' }}" id="menuPersetujuan">
                <ul class="nav-sub-list">
                    <li>
                        <a href="{{ route('proposals.index', ['tab' => 'approvals']) }}" class="{{ $isWaitingApproval ? 'active-sub' : '' }}">
                            Menunggu Validasi
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('proposals.index', ['tab' => 'history']) }}" class="{{ $isApprovalHistory ? 'active-sub' : '' }}">
                            Riwayat Persetujuan
                        </a>
                    </li>
                </ul>
            </div>
            @endif

            <!-- 4. Transaksi & Bukti Kas + 5. Audit Log (Hanya Ketua & Bendahara) -->
            @if($isFinanceViewerRole)
            <div class="nav-item-main {{ $isTransactionMenu ? 'parent-active' : '' }}" data-bs-toggle="collapse" data-bs-target="#menuTransaksi" aria-expanded="{{ $isTransactionMenu ? 'true' : 'false' }}">
                <span>
                    <i class="bi bi-receipt me-2"></i> Transaksi &amp; Bukti Kas
                </span>
                <i class="bi bi-chevron-down small chevron-icon"></i>
            </div>
            <div class="collapse {{ $isTransactionMenu ? 'show' : '' }}" id="menuTransaksi">
                <ul class="nav-sub-list">
                    <li>
                        <a href="{{ route('transactions.index') }}" class="{{ (request()->routeIs('transactions.index') && !$isValidasiBukti) ? 'active-sub' : '' }}">
                            Buku Kas
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('reports.index') }}" class="{{ request()->routeIs('reports.*') ? 'active-sub' : '' }}">
                            Laporan Keuangan
                        </a>
                    </li>
                </ul>
            </div>

            <div class="nav-item-main {{ $isAuditMenu ? 'parent-active' : '' }}" data-bs-toggle="collapse" data-bs-target="#menuAudit" aria-expanded="{{ $isAuditMenu ? 'true' : 'false' }}">
                <span><i class="bi bi-clock-history me-2"></i> Audit Log</span>
                <i class="bi bi-chevron-down small chevron-icon"></i>
            </div>
            <div class="collapse {{ $isAuditMenu ? 'show' : '' }}" id="menuAudit">
                <ul class="nav-sub-list">
                    <li>
                        <a href="{{ route('audit-logs.index') }}" class="{{ $isAuditMenu ? 'active-sub' : '' }}">
                            Aktivitas Pengguna
                        </a>
                    </li>
                </ul>
            </div>
            @endif
        @endif
    </div>

    <!-- Bagian Bawah Sidebar: Pengaturan & Keluar -->
    <div class="mt-auto pt-3">
        <div class="menu-label">Pengaturan</div>
        <div class="nav-item-main {{ $isProfileMenu ? 'parent-active' : '' }}" data-bs-toggle="collapse" data-bs-target="#menuProfil" aria-expanded="{{ $isProfileMenu ? 'true' : 'false' }}">
            <span><i class="bi bi-person-circle me-2"></i> Profil Saya</span>
            <i class="bi bi-chevron-down small chevron-icon"></i>
        </div>
        <div class="collapse {{ $isProfileMenu ? 'show' : '' }}" id="menuProfil">
            <ul class="nav-sub-list">
                <li>
                    <a href="{{ route('profile.index') }}" class="{{ $isProfileMenu ? 'active-sub' : '' }}">
                        Pengaturan Akun
                    </a>
                </li>
            </ul>
        </div>

        <button type="button" class="nav-item-main nav-item-logout w-100 border-0 bg-transparent text-start mt-1" data-bs-toggle="modal" data-bs-target="#logoutConfirmModal">
            <span><i class="bi bi-box-arrow-left me-2"></i> Keluar</span>
        </button>
    </div>
</aside>

<!-- Area Konten Kanan -->
<div class="main-wrapper" id="mainWrapper">
    <!-- Topbar Sticky -->
    <header class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button class="btn-menu-toggle" id="toggleSidebarBtn">
                <i class="bi bi-chevron-left"></i> Menu
            </button>
            <div class="search-box d-none d-md-flex">
                <i class="bi bi-search text-muted"></i>
                <input type="text" placeholder="Search...">
            </div>
        </div>

        <div class="d-flex align-items-center gap-3">
            <div class="topbar-icon-btn">
                <i class="bi bi-bell-fill"></i>
                <span class="notif-dot"></span>
            </div>
            <div class="topbar-icon-btn">
                <i class="bi bi-chat-dots"></i>
                <span class="notif-dot"></span>
            </div>
            <a href="{{ route('profile.index') }}" class="d-flex align-items-center gap-2 ms-2 text-decoration-none text-dark">
                @if(Auth::user()->avatar_url)
                    <img src="{{ Auth::user()->avatar_url }}" alt="Avatar" class="rounded-circle object-fit-cover border" style="width: 40px; height: 40px;">
                @else
                    <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                @endif
                <div style="line-height: 1.2;">
                    <div class="fw-bold" style="font-size: 14px;">{{ Auth::user()->name }}</div>
                    <div class="text-muted" style="font-size: 12px;">{{ ucfirst(Auth::user()->role) }}</div>
                </div>
            </a>
        </div>
    </header>

    <!-- Konten Utama -->
    <main class="content-area">
        @if(session('success') && !session('success_title'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </main>
</div>

<!-- Modal Konfirmasi Keluar dari Akun -->
<div class="modal fade" id="logoutConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 390px;">
        <div class="modal-content logout-modal-card shadow">
            <h5 class="fw-bold mb-2" style="color: #2B394A; font-size: 19px;">Keluar dari akun?</h5>
            <p class="mb-4" style="color: #334155; font-size: 14px;">Anda yakin ingin mengakhiri sesi ini?</p>

            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <div class="row g-2">
                    <div class="col-6">
                        <button type="button" class="btn-logout-cancel w-100" data-bs-dismiss="modal">
                            Batal
                        </button>
                    </div>
                    <div class="col-6">
                        <button type="submit" class="btn-logout-confirm w-100">
                            Keluar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('toggleSidebarBtn').addEventListener('click', function () {
        document.getElementById('sidebar').classList.toggle('collapsed');
        document.getElementById('mainWrapper').classList.toggle('expanded');
    });
</script>
@stack('scripts')
</body>
</html>