<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body>
    <table>
        <tr>
            <th colspan="6" style="font-size: 16px; font-weight: bold; text-align: left;">NEXVO STUDIO - LAPORAN KEUANGAN</th>
        </tr>
        <tr>
            <td colspan="6">Periode: {{ $startDate }} s/d {{ $endDate }}</td>
        </tr>
        <tr><td colspan="6"></td></tr>
        <tr>
            <td><strong>Saldo Awal</strong></td>
            <td>{{ $openingBalance }}</td>
            <td><strong>Total Pemasukan</strong></td>
            <td>{{ $totalIncome }}</td>
            <td><strong>Saldo Akhir</strong></td>
            <td>{{ $endingBalance }}</td>
        </tr>
        <tr><td colspan="6"></td></tr>
        <tr style="background-color: #243242; color: #FFFFFF; font-weight: bold;">
            <th>Tanggal</th>
            <th>Kategori</th>
            <th>Keterangan</th>
            <th>Kas Masuk</th>
            <th>Kas Keluar</th>
            <th>Saldo Berjalan</th>
        </tr>
        @foreach($transactions as $trx)
        @php
            $trxType = $trx->resolved_type ?? ($trx->category?->type ?? 'expense');
        @endphp
        <tr>
            <td>{{ $trx->transaction_date }}</td>
            <td>{{ $trx->category->name ?? 'Operasional' }}</td>
            <td>{{ $trx->description }}</td>
            <td>{{ $trxType === 'income' ? $trx->amount : 0 }}</td>
            <td>{{ $trxType === 'expense' ? $trx->amount : 0 }}</td>
            <td>{{ $trx->running_balance }}</td>
        </tr>
        @endforeach
        <tr style="font-weight: bold; background-color: #F6EFE6;">
            <td colspan="3">TOTAL KESELURUHAN</td>
            <td>{{ $totalIncome }}</td>
            <td>{{ $totalExpense }}</td>
            <td>{{ $endingBalance }}</td>
        </tr>
    </table>
</body>
</html>