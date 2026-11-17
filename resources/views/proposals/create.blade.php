@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold">Ajukan Anggaran Kegiatan</div>
            <div class="card-body">
                <form action="{{ route('proposals.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Judul Pengajuan</label>
                        <input type="text" name="title" class="form-control" placeholder="Contoh: Pengadaan Konsumsi Seminar" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nominal Dana (Rp)</label>
                        <input type="number" name="amount" class="form-control" min="1" placeholder="Contoh: 500000" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Alasan / Rincian Keperluan</label>
                        <textarea name="reason" class="form-control" rows="3" placeholder="Jelaskan rincian penggunaan dana..." required></textarea>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('proposals.index') }}" class="btn btn-secondary">Kembali</a>
                        <button type="submit" class="btn btn-success">Kirim Pengajuan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection