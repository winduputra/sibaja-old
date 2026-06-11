@extends('layouts.user')

@section('title', 'Rekapitulasi Nasional')

@push('style')
<style>
    .rekap-nasional-page {
        --rekap-sheet-bg: #ffffff;
        --rekap-grid: #000000;
        --rekap-header: #bdd7ee;
        --rekap-header-strong: #9dc3e6;
        --rekap-total: #d9eaf7;
        --rekap-sticky: #ffffff;
        --rekap-text: #111111;
        --rekap-muted: #404040;
        --rekap-shadow: rgba(0, 0, 0, 0.08);
        --rekap-space-1: 0.25rem;
        --rekap-space-2: 0.5rem;
        --rekap-space-3: 0.75rem;
        --rekap-space-4: 1rem;
        --rekap-space-6: 1.5rem;
        --rekap-no-width: 3.25rem;
        --rekap-province-width: 15rem;
        color: var(--rekap-text);
        background: var(--rekap-sheet-bg);
        min-height: 100vh;
        padding: var(--rekap-space-4) 0 var(--rekap-space-6);
    }

    .rekap-nasional-title {
        color: var(--rekap-text);
        font-size: 1.15rem;
        font-weight: 700;
        letter-spacing: 0.025em;
        line-height: 1.25;
        margin-bottom: var(--rekap-space-1);
        text-align: center;
    }

    .rekap-nasional-subtitle {
        color: var(--rekap-text);
        font-size: 1.15rem;
        font-weight: 700;
        letter-spacing: 0.025em;
        line-height: 1.25;
        margin-bottom: var(--rekap-space-4);
        text-align: center;
    }

    .rekap-nasional-actions {
        display: flex;
        flex-wrap: wrap;
        gap: var(--rekap-space-2);
        justify-content: center;
        margin-bottom: var(--rekap-space-4);
    }

    .rekap-nasional-sheet {
        background: var(--rekap-sheet-bg);
        box-shadow: 0 var(--rekap-space-1) var(--rekap-space-4) var(--rekap-shadow);
        overflow-x: auto;
        overflow-y: visible;
        padding: var(--rekap-space-2);
    }

    .rekap-nasional-table {
        border-collapse: separate;
        border-spacing: 0;
        font-size: 0.72rem;
        line-height: 1.2;
        min-width: 86rem;
        table-layout: fixed;
        text-transform: uppercase;
        width: 100%;
    }

    .rekap-nasional-table th,
    .rekap-nasional-table td {
        border-bottom: 1px solid var(--rekap-grid);
        border-right: 1px solid var(--rekap-grid);
        padding: var(--rekap-space-1) var(--rekap-space-2);
        vertical-align: middle;
        white-space: nowrap;
    }

    .rekap-nasional-table th:first-child,
    .rekap-nasional-table td:first-child {
        border-left: 1px solid var(--rekap-grid);
    }

    .rekap-nasional-table thead th {
        background: var(--rekap-header);
        border-top: 1px solid var(--rekap-grid);
        font-weight: 700;
        position: sticky;
        text-align: center;
        top: 0;
        z-index: 4;
    }

    .rekap-nasional-table thead tr:first-child th {
        background: var(--rekap-header-strong);
    }

    .rekap-nasional-table tbody td {
        background: var(--rekap-sheet-bg);
    }

    .rekap-nasional-table tfoot td {
        background: var(--rekap-total);
        font-weight: 700;
    }

    .rekap-nasional-table .cell-no,
    .rekap-nasional-table .cell-province {
        left: 0;
        position: sticky;
        z-index: 3;
    }

    .rekap-nasional-table .cell-no {
        text-align: center;
        width: var(--rekap-no-width);
    }

    .rekap-nasional-table .cell-province {
        left: var(--rekap-no-width);
        min-width: var(--rekap-province-width);
        text-align: left;
        width: var(--rekap-province-width);
    }

    .rekap-nasional-table thead .cell-no,
    .rekap-nasional-table thead .cell-province {
        background: var(--rekap-header-strong);
        z-index: 5;
    }

    .rekap-nasional-table tbody .cell-no,
    .rekap-nasional-table tbody .cell-province {
        background: var(--rekap-sticky);
    }

    .rekap-nasional-table tfoot .cell-no,
    .rekap-nasional-table tfoot .cell-province {
        background: var(--rekap-total);
    }

    .rekap-nasional-table .cell-number,
    .rekap-nasional-table .cell-percent {
        font-variant-numeric: tabular-nums;
        text-align: right;
    }

    .rekap-nasional-table .cell-percent {
        width: 6.25rem;
    }

    .rekap-nasional-table .cell-money {
        width: 10.75rem;
    }

    @media (max-width: 767.98px) {
        .rekap-nasional-page {
            --rekap-province-width: 12rem;
        }
    }
</style>
@endpush

@section('content')
<div class="rekap-nasional-page">
    <h1 class="rekap-nasional-title">{{ $heading }}</h1>
    <div class="rekap-nasional-subtitle">{{ $subtitle }}</div>

    <div class="rekap-nasional-actions" aria-label="Aksi export Rekapitulasi Nasional">
        <a href="{{ route('rekapitulasi-nasional.export-pdf') }}" class="btn btn-danger btn-sm shadow-sm" target="_blank">
            <i class="fas fa-file-pdf mr-1"></i> Export PDF
        </a>
        <a href="{{ route('rekapitulasi-nasional.export-excel') }}" class="btn btn-success btn-sm shadow-sm">
            <i class="fas fa-file-excel mr-1"></i> Export Excel
        </a>
    </div>

    <div class="rekap-nasional-sheet" role="region" aria-label="Tabel Rekapitulasi Nasional" tabindex="0">
        <table class="rekap-nasional-table">
            <thead>
                <tr>
                    <th rowspan="2" class="cell-no">NO</th>
                    <th rowspan="2" class="cell-province">NAMA PROVINSI</th>
                    <th colspan="2">REALISASI PENYEDIA</th>
                    <th rowspan="2" class="cell-percent">PERSENTASE</th>
                    <th colspan="2">REALISASI SWAKELOLA</th>
                    <th rowspan="2" class="cell-percent">PERSENTASE</th>
                    <th colspan="2">REKAPITULASI TOTAL</th>
                    <th rowspan="2" class="cell-percent">PERSENTASE</th>
                </tr>
                <tr>
                    <th class="cell-money">REALISASI</th>
                    <th class="cell-money">PERENCANAAN</th>
                    <th class="cell-money">REALISASI</th>
                    <th class="cell-money">PERENCANAAN</th>
                    <th class="cell-money">REALISASI</th>
                    <th class="cell-money">PERENCANAAN</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td class="cell-no">{{ $loop->iteration }}</td>
                        <td class="cell-province">{{ $row[0] }}</td>
                        <td class="cell-number cell-money">{{ $row[1] }}</td>
                        <td class="cell-number cell-money">{{ $row[2] }}</td>
                        <td class="cell-percent">{{ $row[3] }}</td>
                        <td class="cell-number cell-money">{{ $row[4] }}</td>
                        <td class="cell-number cell-money">{{ $row[5] }}</td>
                        <td class="cell-percent">{{ $row[6] }}</td>
                        <td class="cell-number cell-money">{{ $row[7] }}</td>
                        <td class="cell-number cell-money">{{ $row[8] }}</td>
                        <td class="cell-percent">{{ $row[9] }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td class="cell-no"></td>
                    <td class="cell-province">TOTAL</td>
                    <td class="cell-number cell-money">{{ $totals[0] }}</td>
                    <td class="cell-number cell-money">{{ $totals[1] }}</td>
                    <td class="cell-percent">{{ $totals[2] }}</td>
                    <td class="cell-number cell-money">{{ $totals[3] }}</td>
                    <td class="cell-number cell-money">{{ $totals[4] }}</td>
                    <td class="cell-percent">{{ $totals[5] }}</td>
                    <td class="cell-number cell-money">{{ $totals[6] }}</td>
                    <td class="cell-number cell-money">{{ $totals[7] }}</td>
                    <td class="cell-percent">{{ $totals[8] }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
