<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        
        .header h1 {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .header p {
            font-size: 11px;
            margin: 2px 0;
        }
        
        .info-section {
            margin-bottom: 15px;
            page-break-inside: avoid;
        }
        
        .section-title {
            font-weight: bold;
            font-size: 12px;
            background-color: #e8e8e8;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #000;
        }
        
        .satker-group {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        
        .satker-name {
            font-weight: bold;
            font-size: 12px;
            background-color: #f0f0f0;
            padding: 8px;
            border: 1px solid #ccc;
            margin-bottom: 8px;
        }
        
        .summary-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 10px;
            padding: 8px;
            background-color: #fafafa;
            border: 1px solid #ddd;
            font-size: 10px;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
        }
        
        .summary-item .label {
            font-weight: bold;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        
        table thead {
            background-color: #d0d0d0;
        }
        
        table th {
            border: 1px solid #000;
            padding: 6px;
            text-align: center;
            font-weight: bold;
            font-size: 10px;
        }
        
        table td {
            border: 1px solid #ccc;
            padding: 6px;
            font-size: 10px;
        }
        
        table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .currency {
            font-family: 'Courier New', monospace;
            text-align: right;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }
        
        .page-break {
            page-break-after: always;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN RUP MONITORING</h1>
        <p>Paket yang Belum Terealisasi</p>
        <p>
            @if($tab === 'penyedia')
                RUP PENYEDIA
            @elseif($tab === 'swakelola')
                RUP SWAKELOLA
            @endif
        </p>
        <p>Metode Pengadaan: <strong>{{ $metodeFilter }}</strong></p>
        <p>Tahun Anggaran: <strong>{{ $year }}</strong></p>
        <p>Tanggal Cetak: {{ \Carbon\Carbon::now()->format('d-m-Y H:i:s') }}</p>
    </div>

    @php
        $grandTotalPaket = 0;
        $grandTotalPagu = 0;
        $pageCount = 0;
    @endphp

    @foreach($data as $groupIndex => $satkerGroup)
        @php
            $grandTotalPaket += $satkerGroup['total_paket'];
            $grandTotalPagu += $satkerGroup['total_pagu'];
        @endphp

        <div class="satker-group">
            <div class="satker-name">
                {{ $satkerGroup['nama_satker'] }}
            </div>

            <div class="summary-info">
                <div class="summary-item">
                    <span class="label">Total Paket:</span>
                    <span>{{ $satkerGroup['total_paket'] }} paket</span>
                </div>
                <div class="summary-item">
                    <span class="label">Total Pagu:</span>
                    <span>Rp {{ number_format($satkerGroup['total_pagu'], 0, ',', '.') }}</span>
                </div>
                @if($tab === 'penyedia')
                    <div class="summary-item">
                        <span class="label">Metode:</span>
                        <span>{{ $satkerGroup['metode_pengadaan'] }}</span>
                    </div>
                @else
                    <div class="summary-item">
                        <span class="label">Tipe:</span>
                        <span>{{ $satkerGroup['tipe_swakelola'] }}</span>
                    </div>
                @endif
            </div>

            <table>
                <thead>
                    <tr>
                        <th width="8%">No</th>
                        <th width="18%">Kode RUP</th>
                        <th width="50%">Nama Paket</th>
                        <th width="24%">Pagu (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($satkerGroup['rupList'] as $rup)
                        <tr>
                            <td class="text-center">{{ $rup['no'] }}</td>
                            <td>{{ $rup['kd_rup'] }}</td>
                            <td>{{ $rup['nama_paket'] }}</td>
                            <td class="currency">{{ number_format($rup['pagu'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if(($groupIndex + 1) % 3 == 0 && ($groupIndex + 1) < count($data))
            <div class="page-break"></div>
        @endif
    @endforeach

    <div style="margin-top: 30px; padding: 15px; background-color: #f0f0f0; border: 2px solid #000;">
        <h3 style="text-align: center; font-size: 12px; margin-bottom: 10px;">RINGKASAN KESELURUHAN</h3>
        <table>
            <tr>
                <td width="50%" style="text-align: left; font-weight: bold; border: 1px solid #ccc; padding: 8px;">
                    Total Paket Belum Terealisasi
                </td>
                <td width="50%" style="text-align: right; font-weight: bold; border: 1px solid #ccc; padding: 8px;">
                    {{ $grandTotalPaket }} Paket
                </td>
            </tr>
            <tr>
                <td style="text-align: left; font-weight: bold; border: 1px solid #ccc; padding: 8px;">
                    Total Pagu Belum Terealisasi
                </td>
                <td style="text-align: right; font-weight: bold; border: 1px solid #ccc; padding: 8px; font-family: 'Courier New', monospace;">
                    Rp {{ number_format($grandTotalPagu, 0, ',', '.') }}
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>Laporan ini dihasilkan secara otomatis oleh Sistem Informasi Pengadaan</p>
        <p>Confidential - Untuk Penggunaan Internal</p>
    </div>
</body>
</html>
