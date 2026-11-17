@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0">Daftar Pengajuan Anggaran</h3>
    @if(Auth::user()->role === 'koordiv')
        <a href="{{ route('proposals.create') }}" class="btn btn-success">+ Ajukan Anggaran</a>
    @endif
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Tanggal</th>
                    <th>Pemohon (Divisi)</th>
                    <th>Judul Pengajuan</th>
                    <th>Nominal</th>
                    <th>Alasan</th>
                    <th>Status</th>
                    @if(in_array(Auth::user()->role, ['ketua', 'bendahara']))
                        <th>Persetujuan</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($proposals as $prop)
                <tr>
                    <td>{{ $prop->created_at->format('d M Y') }}</td>
                    <td>{{ $prop->user->name }} ({{ $prop->user->division }})</td>
                    <td class="fw-semibold">{{ $prop->title }}</td>
                    <td>Rp {{ number_format($prop->amount, 0, ',', '.') }}</td>
                    <td>{{ $prop->reason }}</td>
                    <td>
                        @if($prop->status === 'pending')
                            <span class="badge bg-warning text-dark">Pending</span>
                        @elseif($prop->status === 'approved')
                            <span class="badge bg-success">Disetujui</span>
                        @else
                            <span class="badge bg-danger">Ditolak</span>
                        @endif
                    </td>
                    @if(in_array(Auth::user()->role, ['ketua', 'bendahara']))
                    <td>
                        @if($prop->status === 'pending')
                            <form action="{{ route('proposals.updateStatus', $prop->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="approved">
                                <button type="submit" class="btn btn-sm btn-success">Setujui</button>
                            </form>
                            <form action="{{ route('proposals.updateStatus', $prop->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="rejected">
                                <button type="submit" class="btn btn-sm btn-danger">Tolak</button>
                            </form>
                        @else
                            <span class="text-muted small">Selesai</span>
                        @endif
                    </td>
                    @endif
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">Belum ada pengajuan anggaran.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection