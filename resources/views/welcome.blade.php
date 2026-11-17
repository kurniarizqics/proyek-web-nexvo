<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexvo Finance - Sistem Manajemen Keuangan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#">Nexvo Finance</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('dashboard', Auth::user()->role) }}">Dashboard</a>
                </li>
                @if(in_array(Auth::user()->role, ['bendahara', 'admin', 'ketua']))
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('transactions.index') }}">Transaksi Kas</a>
                </li>
                @endif
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('proposals.index') }}">Pengajuan Dana</a>
                </li>
            </ul>
            <div class="d-flex align-items-center text-white gap-3">
                <span class="badge bg-light text-primary text-uppercase">{{ Auth::user()->role }}</span>
                <span>{{ Auth::user()->name }}</span>
                <form action="{{ route('logout') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-light">Logout</button>
                </form>
            </div>
        </div>
    </div>
</nav>

<div class="container py-4">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @yield('content')
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>