<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pratinjau Laporan Keuangan - NEXVO STUDIO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #F6EFE6;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: #1E293B;
        }
        .topbar-preview {
            background-color: #FFFFFF;
            border-bottom: 1px solid #E2E8F0;
            padding: 14px 32px;
        }
        .paper-sheet {
            background-color: #FFFFFF;
            max-width: 850px;
            margin: 32px auto;
            padding: 48px 54px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }
        .logo-box {
            width: 48px;
            height: 48px;
            background-color: #C7D6E4;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 24px;
            color: #1E293B;
        }
        @media print {
            .no-print { display: none !important; }
            body { background-color: #FFFFFF; }
            .paper-sheet {
                box-shadow: none;
                margin: 0;
                padding: 20px;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
@php
    $startCarbon = \Carbon\Carbon::parse($startDate);
    $endCarbon = \Carbon\Carbon::parse($endDate);
    $periodText = $startCarbon->locale('id')->translatedFormat('d F Y') . ' - ' . $endCarbon->locale('id')->translatedFormat('d F Y');
@endphp

<!-- Header Bar Pratinjau (Tidak ikut tercetak saat Print / Save PDF) -->
<div class="topbar-preview d-flex flex-wrap justify-content-between align-items-center gap-2 no-print">
    <h5 class="fw-bold mb-0" style="color: #1E293B;">Pratinjau Laporan Keuangan</h5>
    <div class="d-flex gap-2">
        <button type="button" onclick="window.print()" class="btn btn-outline-secondary rounded-3 px-4 py-2 fw-medium" style="font-size: 13px;">
            <i class="bi bi-printer me-1"></i> Cetak / Simpan PDF
        </button>
        <a href="{{ route('reports.index', ['start_date' => $startDate, 'end_date' => $endDate, 'type' => $typeFilter]) }}" class="btn rounded-3 px-4 py-2 text-white fw-medium text-decoration-none" style="background-color: #243242; font-size: 13px;">
            Kembali ke laporan
        </a>
    </div>
</div>

<!-- Lembar Dokumen Laporan -->
<div class="paper-sheet">
    <!-- Kop Surat -->
    <div class="d-flex align-items-center gap-3 pb-3 mb-4" style="border-bottom: 2px solid #243242;">
        <div class="logo-box">N</div>
        <div>
            <h4 class="fw-bold mb-0" style="letter-spacing: 0.5px; color: #1E293B;">NEXVO STUDIO</h4>
            <div style="font-size: 13px; color: #334155;">Organisasi Kreatif dan Produksi Konten</div>
            <div class="text-muted" style="font-size: 11.5px;">Jl. Margonda Raya, Depok, Jawa Barat 16424</div>
        </div>
    </div>

    <!-- Judul Laporan -->
    <div class="text-center mb-4">
        <h4 class="fw-bold mb-1" style="color: #1E293B;">LAPORAN KEUANGAN BULANAN</h4>
        <div class="text-muted" style="font-size: 13px;">Periode {{ $periodText }}</div>
    </div>

    <!-- Ringkasan Keuangan -->
    <div class="mb-4">
        <div class="fw-bold mb-2" style="font-size: 11.5px; letter-spacing: 0.5px; color: #334155;">RINGKASAN KEUANGAN</div>
        <div class="p-4 rounded-3" style="background-color: #F6EFE6;">
            <div class="row g-3" style="font-size: 13.5px;">
                <div class="col-md-6 d-flex justify-content-between pe-md-4">
                    <span class="text-muted">Saldo Awal</span>
                    <span class="fw-bold">Rp {{ number_format($openingBalance, 0, ',', '.') }}</span>
                </div>
                <div class="col-md-6 d-flex justify-content-between ps-md-4">
                    <span class="text-muted">Total Pemasukan</span>
                    <span class="fw-bold">Rp {{ number_format($totalIncome, 0, ',', '.') }}</span>
                </div>
                <div class="col-md-6 d-flex justify-content-between pe-md-4">
                    <span class="text-muted">Total Pengeluaran</span>
                    <span class="fw-bold">Rp {{ number_format($totalExpense, 0, ',', '.') }}</span>
                </div>
                <div class="col-md-6 d-flex justify-content-between ps-md-4">
                    <span class="text-muted">Saldo Akhir</span>
                    <span class="fw-bold">Rp {{ number_format($endingBalance, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Rincian Transaksi -->
    <div class="mb-2">
        <div class="fw-bold mb-2" style="font-size: 11.5px; letter-spacing: 0.5px; color: #334155;">RINCIAN TRANSAKSI</div>
        <table class="table align-middle mb-0" style="font-size: 12.5px;">
            <thead style="background-color: #243242; color: #FFFFFF;">
                <tr>
                    <th class="py-2 ps-3 fw-semibold">Tanggal</th>
                    <th class="py-2 fw-semibold">Keterangan</th>
                    <th class="py-2 text-end fw-semibold">Kas Masuk</th>
                    <th class="py-2 text-end fw-semibold">Kas Keluar</th>
                    <th class="py-2 pe-3 text-end fw-semibold">Saldo</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $trx)
                @php
                    $isIncome = (($trx->resolved_type ?? $trx->category?->type) === 'income');
                @endphp
                <tr>
                    <td class="py-2 ps-3 text-muted">{{ \Carbon\Carbon::parse($trx->transaction_date)->locale('id')->translatedFormat('d M Y') }}</td>
                    <td class="py-2">{{ $trx->description }}</td>
                    <td class="py-2 text-end">{{ $isIncome ? 'Rp ' . number_format($trx->amount, 0, ',', '.') : '-' }}</td>
                    <td class="py-2 text-end">{{ !$isIncome ? 'Rp ' . number_format($trx->amount, 0, ',', '.') : '-' }}</td>
                    <td class="py-2 pe-3 text-end fw-medium">Rp {{ number_format($trx->running_balance, 0, ',', '.') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">Tidak ada rincian transaksi pada periode ini.</td>
                </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr style="background-color: #F6EFE6;">
                    <td colspan="2" class="py-2 ps-3 fw-bold">TOTAL KESELURUHAN</td>
                    <td class="py-2 text-end fw-bold">Rp {{ number_format($totalIncome, 0, ',', '.') }}</td>
                    <td class="py-2 text-end fw-bold">Rp {{ number_format($totalExpense, 0, ',', '.') }}</td>
                    <td class="py-2 pe-3 text-end fw-bold">Rp {{ number_format($endingBalance, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="text-muted mb-5" style="font-size: 11px;">
        Sumber: Transaksi kas yang dicatat Bendahara &bull; Mata uang: Rupiah (IDR)
    </div>

    <!-- Area Tanda Tangan -->
    <div class="row text-center mt-5 pt-3" style="font-size: 13px;">
        <div class="col-6">
            <div>Dibuat oleh,</div>
            <div class="fw-bold">Bendahara</div>
            <div style="height: 65px;"></div>
            <div class="mx-auto border-top pt-1" style="width: 210px; border-color: #334155 !important;">
                ({{ $bendaharaName }})
            </div>
        </div>
        <div class="col-6">
            <div>Mengetahui,</div>
            <div class="fw-bold">Ketua</div>
            <div style="height: 65px;"></div>
            <div class="mx-auto border-top pt-1" style="width: 210px; border-color: #334155 !important;">
                ({{ $ketuaName }})
            </div>
        </div>
    </div>

    <!-- Footer Dokumen -->
    <div class="d-flex justify-content-between text-muted mt-5 pt-3 border-top" style="font-size: 11px;">
        <div>Diterbitkan pada: {{ $endCarbon->locale('id')->translatedFormat('d F Y') }}</div>
        <div>Halaman 1 dari 1</div>
    </div>
</div>
</body>
</html>