@extends('layouts.app')

@section('content')
<!-- Header Halaman -->
<div class="mb-4">
    <h3 class="fw-bold mb-1" style="color: #1E293B;">Buat Pengajuan Dana</h3>
    <p class="text-muted mb-0" style="font-size: 13px;">Isi formulir di bawah ini untuk mengajukan permohonan anggaran baru.</p>
</div>

<!-- Form Card -->
<div class="nexvo-card p-4">
    <div class="mb-4">
        <h5 class="fw-bold mb-1" style="font-size: 16px; color: #1E293B;">Detail pengajuan</h5>
        <p class="text-muted mb-0" style="font-size: 12px;">Lengkapi informasi kebutuhan dan lampirkan dokumen pendukung.</p>
    </div>

    @if($errors->any())
        <div class="alert alert-danger rounded-3 py-2 px-3 small mb-4">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('proposals.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <!-- Judul Keperluan -->
        <div class="mb-3">
            <label for="title" class="form-label fw-semibold" style="font-size: 13px; color: #1E293B;">Judul Keperluan</label>
            <input type="text" name="title" id="title" class="form-control rounded-3" value="{{ old('title') }}" placeholder="Contoh: Pembelian aset studio" style="font-size: 13.5px; padding: 11px 16px; border-color: #94A3B8;" required autofocus>
        </div>

        <!-- Baris Nominal Pengajuan & Kategori Anggaran -->
        <div class="row g-3 mb-3">
            <div class="col-md-8">
                <label for="amount" class="form-label fw-semibold" style="font-size: 13px; color: #1E293B;">Nominal Pengajuan</label>
                <div class="input-group">
                    <span class="input-group-text fw-bold border-end-0 rounded-start-3" style="background-color: #EFECE8; color: #1E293B; padding: 11px 18px; font-size: 14px;">Rp</span>
                    <input type="number" name="amount" id="amount" class="form-control border-start-0 rounded-end-3" value="{{ old('amount') }}" min="1" placeholder="0" style="font-size: 14px; padding: 11px 16px; border-color: #E2E8F0;" required>
                </div>
            </div>

            <div class="col-md-4">
                <label for="category_select" class="form-label fw-semibold" style="font-size: 13px; color: #1E293B;">Kategori Anggaran</label>
                <select name="category_name" id="category_select" class="form-select rounded-3" style="font-size: 13.5px; padding: 11px 16px; border-color: #E2E8F0;">
                    <option value="">Pilih kategori</option>
                    @if(isset($categories) && $categories->count() > 0)
                        @foreach($categories as $cat)
                            <option value="{{ $cat->name }}">{{ $cat->name }}</option>
                        @endforeach
                    @else
                        <option value="Operasional">Operasional</option>
                        <option value="Aset & Peralatan">Aset & Peralatan</option>
                        <option value="Event & Kegiatan">Event & Kegiatan</option>
                        <option value="Teknologi">Teknologi</option>
                    @endif
                </select>
            </div>
        </div>

        <!-- Keterangan / Rincian -->
        <div class="mb-4">
            <label for="reason" class="form-label fw-semibold" style="font-size: 13px; color: #1E293B;">Keterangan / Rincian</label>
            <textarea name="reason" id="reason" class="form-control rounded-3" rows="4" placeholder="Jelaskan rincian penggunaan dana..." style="font-size: 13.5px; padding: 12px 16px; border-color: #E2E8F0;" required>{{ old('reason') }}</textarea>
        </div>

        <!-- Dokumen Pendukung (Opsional Drag & Drop Box) -->
        <div class="mb-4">
            <label class="form-label fw-semibold mb-0" style="font-size: 13px; color: #1E293B;">Dokumen Pendukung <span class="text-muted fw-normal">(Opsional)</span></label>
            <p class="text-muted mb-2" style="font-size: 11.5px;">Lampirkan Rencana Anggaran Biaya (RAB) atau penawaran harga jika ada.</p>

            <div id="dropZone" class="rounded-3 p-4 text-center position-relative" style="background-color: #F4F7FA; border: 1.5px dashed #B8C7D6; cursor: pointer; transition: 0.2s;">
                <input type="file" name="document" id="documentInput" accept=".pdf,.jpg,.jpeg,.png" class="position-absolute top-0 start-0 w-100 h-100 opacity-0" style="cursor: pointer;">
                <div class="mb-2" style="color: #607990; font-size: 24px;">
                    <i class="bi bi-cloud-arrow-up"></i>
                </div>
                <div class="fw-semibold" id="fileLabelTitle" style="font-size: 13px; color: #1E293B;">
                    Tarik &amp; Lepas file di sini atau Klik untuk mencari
                </div>
                <div class="text-muted mt-1" id="fileLabelSub" style="font-size: 11px;">
                    Format yang didukung: PDF, JPG, PNG. Ukuran maksimal: 5 MB.
                </div>
            </div>
        </div>

        <!-- Footer Form: Info Validasi + Tombol Batal & Kirim -->
        <div class="d-flex flex-wrap justify-content-between align-items-center pt-3 border-top gap-3">
            <div class="text-muted" style="font-size: 11.5px; max-width: 340px;">
                Pengajuan {{ ucfirst(Auth::user()->role) }} akan diteruskan ke antrean validasi Bendahara/Pimpinan.
            </div>

            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('proposals.index') }}" class="btn btn-outline-secondary rounded-3 px-4 py-2" style="font-size: 13px; min-width: 110px;">
                    Batal
                </a>
                <button type="submit" class="btn-nexvo-dark py-2 px-4" style="font-size: 13px; min-width: 190px;">
                    Kirim Pengajuan
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    const docInput = document.getElementById('documentInput');
    const dropZone = document.getElementById('dropZone');
    const fileTitle = document.getElementById('fileLabelTitle');
    const fileSub = document.getElementById('fileLabelSub');

    docInput.addEventListener('change', function () {
        if (this.files && this.files.length > 0) {
            const file = this.files[0];
            const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
            dropZone.style.backgroundColor = '#E6F4EA';
            dropZone.style.borderColor = '#16A34A';
            fileTitle.innerHTML = `<i class="bi bi-file-earmark-check text-success me-1"></i> ${file.name}`;
            fileSub.textContent = `Ukuran file: ${sizeMB} MB • Siap diunggah`;
        } else {
            dropZone.style.backgroundColor = '#F4F7FA';
            dropZone.style.borderColor = '#B8C7D6';
            fileTitle.textContent = 'Tarik & Lepas file di sini atau Klik untuk mencari';
            fileSub.textContent = 'Format yang didukung: PDF, JPG, PNG. Ukuran maksimal: 5 MB.';
        }
    });
</script>
@endpush