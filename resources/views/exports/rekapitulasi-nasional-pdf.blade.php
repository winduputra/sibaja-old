<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page {
            size: A4 landscape;
            margin: 12mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #111111;
        }

        .report-title {
            font-size: 14px;
            font-weight: 700;
            text-align: center;
            margin: 0 0 4px;
        }

        .report-subtitle {
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.025em;
            line-height: 1.25;
            text-align: center;
            margin: 0 0 10px;
        }

        table {
            border-collapse: collapse;
            table-layout: fixed;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #000000;
            padding: 4px 5px;
            vertical-align: middle;
        }

        thead tr:first-child th {
            background: #9dc3e6;
            font-size: 8.5px;
        }

        thead tr:nth-child(2) th {
            background: #bdd7ee;
            font-size: 8.5px;
        }

        th {
            text-align: center;
        }

        td {
            background: #ffffff;
        }

        tfoot td {
            background: #d9eaf7;
            font-weight: 700;
        }

        .col-no {
            width: 4%;
            text-align: center;
        }

        .col-province {
            width: 18%;
            text-align: left;
        }

        .col-money {
            width: 9%;
            text-align: right;
            white-space: nowrap;
        }

        .col-percent {
            width: 8%;
            text-align: right;
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <div class="report-title">{{ $heading }}</div>
    <div class="report-subtitle">{{ $subtitle }}</div>

    <table>
        <thead>
            <tr>
                <th rowspan="2" class="col-no">NO</th>
                <th rowspan="2" class="col-province">NAMA PROVINSI</th>
                <th colspan="2">REALISASI PENYEDIA</th>
                <th rowspan="2" class="col-percent">PERSENTASE</th>
                <th colspan="2">REALISASI SWAKELOLA</th>
                <th rowspan="2" class="col-percent">PERSENTASE</th>
                <th colspan="2">REKAPITULASI TOTAL</th>
                <th rowspan="2" class="col-percent">PERSENTASE</th>
            </tr>
            <tr>
                <th class="col-money">REALISASI</th>
                <th class="col-money">PERENCANAAN</th>
                <th class="col-money">REALISASI</th>
                <th class="col-money">PERENCANAAN</th>
                <th class="col-money">REALISASI</th>
                <th class="col-money">PERENCANAAN</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td class="col-no">{{ $loop->iteration }}</td>
                    <td class="col-province">{{ $row[0] }}</td>
                    <td class="col-money">{{ $row[1] }}</td>
                    <td class="col-money">{{ $row[2] }}</td>
                    <td class="col-percent">{{ $row[3] }}</td>
                    <td class="col-money">{{ $row[4] }}</td>
                    <td class="col-money">{{ $row[5] }}</td>
                    <td class="col-percent">{{ $row[6] }}</td>
                    <td class="col-money">{{ $row[7] }}</td>
                    <td class="col-money">{{ $row[8] }}</td>
                    <td class="col-percent">{{ $row[9] }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td class="col-no"></td>
                <td class="col-province">TOTAL</td>
                <td class="col-money">{{ $totals[0] }}</td>
                <td class="col-money">{{ $totals[1] }}</td>
                <td class="col-percent">{{ $totals[2] }}</td>
                <td class="col-money">{{ $totals[3] }}</td>
                <td class="col-money">{{ $totals[4] }}</td>
                <td class="col-percent">{{ $totals[5] }}</td>
                <td class="col-money">{{ $totals[6] }}</td>
                <td class="col-money">{{ $totals[7] }}</td>
                <td class="col-percent">{{ $totals[8] }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
