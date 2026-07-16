@extends('layouts.user')

@section('title', 'Pengadaan Langsung')

@push('style')
<style>
    .pengadaan-langsung-page {
        --sheet-bg: #ffffff;
        --sheet-grid: #000000;
        --sheet-header: #d9e8fb;
        --sheet-text: #111111;
        --sheet-shadow: rgba(0, 0, 0, 0.08);
        color: var(--sheet-text);
        background: var(--sheet-bg);
        min-height: 100vh;
        padding: 1rem 0 1.5rem;
    }

    .pengadaan-langsung-title {
        color: var(--sheet-text);
        font-size: 1.5rem;
        font-weight: 800;
        letter-spacing: 0.02em;
        line-height: 1.2;
        margin-bottom: 1rem;
        text-align: center;
        text-transform: uppercase;
    }

    .pengadaan-langsung-actions {
        align-items: end;
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        justify-content: center;
        margin-bottom: 1rem;
    }

    .pengadaan-langsung-sheet {
        background: var(--sheet-bg);
        box-shadow: 0 0.25rem 1rem var(--sheet-shadow);
        overflow-x: auto;
        padding: 0.25rem;
    }

    .pengadaan-langsung-table {
        border-collapse: separate;
        border-spacing: 0;
        font-size: 0.9rem;
        line-height: 1.25;
        min-width: 105rem;
        table-layout: fixed;
        width: 100%;
    }

    .pengadaan-langsung-table th,
    .pengadaan-langsung-table td {
        border-bottom: 1px solid var(--sheet-grid);
        border-right: 1px solid var(--sheet-grid);
        padding: 0.65rem 0.75rem;
        vertical-align: middle;
    }

    .pengadaan-langsung-table th:first-child,
    .pengadaan-langsung-table td:first-child {
        border-left: 1px solid var(--sheet-grid);
    }

    .pengadaan-langsung-table thead th {
        background: var(--sheet-header);
        border-top: 1px solid var(--sheet-grid);
        font-size: 1rem;
        font-weight: 800;
        position: sticky;
        text-align: center;
        top: 0;
        z-index: 2;
    }

    .pengadaan-langsung-table thead tr:first-child th {
        height: 5rem;
    }

    .pengadaan-langsung-table .column-number-row th {
        font-style: italic;
        height: 2.25rem;
        padding-bottom: 0.35rem;
        padding-top: 0.35rem;
    }

    .pengadaan-langsung-table tbody td {
        background: var(--sheet-bg);
        height: 4.5rem;
    }

    .pengadaan-langsung-table tfoot td {
        background: var(--sheet-header);
        font-weight: 800;
    }

    .cell-no {
        text-align: center;
        width: 4.25rem;
    }

    .cell-opd {
        width: 18rem;
    }

    .cell-package {
        width: 22rem;
    }

    .cell-activity {
        text-align: center;
        width: 14rem;
    }

    .cell-provider,
    .cell-address {
        width: 16rem;
    }

    .cell-money {
        font-variant-numeric: tabular-nums;
        text-align: right;
        width: 14rem;
    }
</style>
@endpush

@section('content')
<div class="pengadaan-langsung-page">
    <h1 class="pengadaan-langsung-title">PENGADAAN LANGSUNG TA {{ $selectedYear }}</h1>

    <div class="pengadaan-langsung-actions" aria-label="Filter dan export Pengadaan Langsung">
        <form action="{{ route('pengadaan-langsung') }}" method="GET" class="d-flex align-items-end gap-2">
            <div>
                <label for="tahun" class="form-label mb-1 fw-semibold">Tahun</label>
                <select id="tahun" name="tahun" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach ($years as $year)
                        <option value="{{ $year }}" {{ $selectedYear === $year ? 'selected' : '' }}>{{ $year }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-sm shadow-sm">Tampilkan</button>
        </form>

        <a href="{{ route('pengadaan-langsung.export-excel', ['tahun' => $selectedYear]) }}" class="btn btn-success btn-sm shadow-sm">
            <i class="fas fa-file-excel mr-1"></i> Export Excel
        </a>
    </div>

    @if ($apiError)
        <div class="alert alert-warning mx-auto" style="max-width: 60rem;">
            Data belum bisa diambil dari API. Pastikan token INAPROC sudah terisi dan coba lagi.
        </div>
    @endif

    <div class="pengadaan-langsung-sheet" role="region" aria-label="Tabel Pengadaan Langsung" tabindex="0">
        <table class="pengadaan-langsung-table">
            <thead>
                <tr>
                    <th class="cell-no">No.</th>
                    <th class="cell-opd">Perangkat Daerah</th>
                    <th class="cell-package">Nama Paket</th>
                    <th class="cell-activity">Kegiatan</th>
                    <th class="cell-provider">Penyedia</th>
                    <th class="cell-address">Alamat Penyedia</th>
                    <th class="cell-money">Nilai Pagu</th>
                    <th class="cell-money">Nilai Final</th>
                </tr>
                <tr class="column-number-row">
                    @for ($column = 1; $column <= 8; $column++)
                        <th>{{ $column }}</th>
                    @endfor
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td class="cell-no">{{ $loop->iteration }}</td>
                        <td class="cell-opd">{{ $row['perangkat_daerah'] }}</td>
                        <td class="cell-package">{{ $row['nama_paket'] }}</td>
                        <td class="cell-activity">{{ $row['kegiatan'] }}</td>
                        <td class="cell-provider">{{ $row['penyedia'] }}</td>
                        <td class="cell-address">{{ $row['alamat_penyedia'] }}</td>
                        <td class="cell-money">{{ number_format($row['nilai_pagu'], 0, ',', '.') }}</td>
                        <td class="cell-money">{{ number_format($row['nilai_final'], 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4">Tidak ada data Pengadaan Langsung untuk tahun {{ $selectedYear }}.</td>
                    </tr>
                @endforelse
            </tbody>
            @if (count($rows) > 0)
                <tfoot>
                    <tr>
                        <td colspan="6" class="text-end">TOTAL</td>
                        <td class="cell-money">{{ number_format($totalPagu, 0, ',', '.') }}</td>
                        <td class="cell-money">{{ number_format($totalFinal, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection
