@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0">Riwayat Transaksi Kas</h3>
    @if(in_array(Auth::user()->role, ['bendahara', 'admin']))
        <a href="{{ route('transactions.create') }}" class="btn btn-primary">+ Tambah Transaksi</a>
    @endif
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Tanggal</th>
                    <th>Kategori</th>
                    <th>Jenis</th>
                    <th>Nominal</th>
                    <th>Keterangan</th>
                    <th>Dicatat Oleh</th>
                    @if(in_array(Auth::user()->role, ['bendahara', 'admin']))
                        <th>Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $item)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($item->transaction_date)->format('d M Y') }}</td>
                    <td>{{ $item->category->name }}</td>
                    <td>
                        @if($item->category->type === 'income')
                            <span class="badge bg-success">Pemasukan</span>
                        @else
                            <span class="badge bg-danger">Pengeluaran</span>
                        @endif
                    </td>
                    <td class="fw-semibold">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
                    <td>{{ $item->description ?? '-' }}</td>
                    <td>{{ $item->user->name }}</td>
                    @if(in_array(Auth::user()->role, ['bendahara', 'admin']))
                    <td>
                        <a href="{{ route('transactions.edit', $item->id) }}" class="btn btn-sm btn-warning">Edit</a>
                        <form action="{{ route('transactions.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus transaksi ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                        </form>
                    </td>
                    @endif
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">Belum ada data transaksi tercatat.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection