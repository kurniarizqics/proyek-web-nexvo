@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-0">Dashboard {{ ucfirst($role) }}</h3>
        <p class="text-muted mb-0">Selamat datang kembali, {{ Auth::user()->name }} (Divisi {{ Auth::user()->division }})</p>
    </div>
</div>

<!-- Kartu Ringkasan Keuangan -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-primary text-white h-100">
            <div class="card-body">
                <h6 class="card-title text-uppercase small opacity-75">Saldo Kas Saat Ini</h6>
                <h3 class="fw-bold mb-0">Rp {{ number_format($balance, 0, ',', '.') }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="card-title text-uppercase small text-muted">Total Pemasukan</h6>
                <h3 class="fw-bold text-success mb-0">Rp {{ number_format($totalIncome, 0, ',', '.') }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="card-title text-uppercase small text-muted">Total Pengeluaran</h6>
                <h3 class="fw-bold text-danger mb-0">Rp {{ number_format($totalExpense, 0, ',', '.') }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="card-title text-uppercase small text-muted">Pengajuan Pending</h6>
                <h3 class="fw-bold text-warning mb-0">{{ $pendingProposals }} Pengajuan</h3>
            </div>
        </div>
    </div>
</div>

<!-- Panel Aksi Cepat Sesuai Role -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-bold">Akses Cepat Menu</div>
    <div class="card-body d-flex flex-wrap gap-2">
        @if(in_array($role, ['bendahara', 'admin']))
            <a href="{{ route('transactions.create') }}" class="btn btn-primary">+ Catat Transaksi Kas</a>
        @endif

        @if(in_array($role, ['bendahara', 'admin', 'ketua']))
            <a href="{{ route('transactions.index') }}" class="btn btn-outline-primary">Lihat Riwayat Transaksi</a>
        @endif

        @if($role === 'koordiv')
            <a href="{{ route('proposals.create') }}" class="btn btn-success">+ Ajukan Anggaran Baru</a>
        @endif

        <a href="{{ route('proposals.index') }}" class="btn btn-outline-secondary">Daftar Pengajuan Dana</a>
    </div>
</div>
@endsection