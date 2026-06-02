<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Realisasi Pengadaan</title>
    <style>
        @page { margin: 20px; size: A4 landscape; }
        body { font-family: Arial, sans-serif; font-size: 10px; margin: 0; }

        h3 {
            text-align: center;
            margin-bottom: 16px;
            font-size: 14px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th, td {
            border: 1px solid #000;
            padding: 5px 4px;
            text-align: center; /* Default rata tengah */
            font-size: 9px;
            vertical-align: middle;
        }

        th {
            background-color: #e2e8f0;
        }

        td.text-left {
            text-align: left !important; /* Khusus kolom Nama Satker */
        }

        .total-row {
            font-weight: bold;
            background-color: #fef9c3;
        }
    </style>
</head>
<body>

    <h3>Realisasi Pengadaan Tahun {{ $tahun }}</h3>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Satker</th>
                <th>Pagu RUP Penyedia</th>
                <th>Realisasi Penyedia<br><span style="font-size: 10px;">(Tender, Non-Tender, E-Purchasing)</span></th>
                <th>% Penyedia</th>
                <th>Pagu RUP Swakelola</th>
                <th>Realisasi Swakelola</th>
                <th>% Swakelola</th>
                <th>Total Pagu Keseluruhan</th>
                <th>Total Realisasi Keseluruhan</th>
                <th>% Global</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="text-left">{{ $item->nama_satker }}</td>
                    <td>{{ number_format($item->pagu_penyedia, 0, ',', '.') }}</td>
                    <td>{{ number_format($item->realisasi_penyedia, 0, ',', '.') }}</td>
                    <td>{{ number_format($item->persentase_penyedia, 2, ',', '.') }}%</td>
                    <td>{{ number_format($item->pagu_swakelola, 0, ',', '.') }}</td>
                    <td>{{ number_format($item->realisasi_swakelola, 0, ',', '.') }}</td>
                    <td>{{ number_format($item->persentase_swakelola, 2, ',', '.') }}%</td>
                    <td>{{ number_format($item->pagu_global, 0, ',', '.') }}</td>
                    <td>{{ number_format($item->realisasi_global, 0, ',', '.') }}</td>
                    <td>{{ number_format($item->persentase_global, 2, ',', '.') }}%</td>
                </tr>
            @endforeach

            @php $jumlahData = $data->count(); @endphp

            @if($jumlahData > 0)
                <tr class="total-row">
                    <td colspan="2">TOTAL</td>
                    <td>{{ number_format($summary->pagu_penyedia, 0, ',', '.') }}</td>
                    <td>{{ number_format($summary->realisasi_penyedia, 0, ',', '.') }}</td>
                    <td>{{ number_format($summary->persentase_penyedia, 2, ',', '.') }}%</td>
                    <td>{{ number_format($summary->pagu_swakelola, 0, ',', '.') }}</td>
                    <td>{{ number_format($summary->realisasi_swakelola, 0, ',', '.') }}</td>
                    <td>{{ number_format($summary->persentase_swakelola, 2, ',', '.') }}%</td>
                    <td>{{ number_format($summary->pagu_global, 0, ',', '.') }}</td>
                    <td>{{ number_format($summary->realisasi_global, 0, ',', '.') }}</td>
                    <td>{{ number_format($summary->persentase_global, 2, ',', '.') }}%</td>
                </tr>
            @endif
        </tbody>
    </table>

</body>
</html>
