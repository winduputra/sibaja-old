@extends('layouts.user')

@section('content')
<style>
    .swakelola-report-card {
        background: #ffffff;
        border-radius: 10px;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08);
        padding: 1rem;
    }

    .swakelola-table {
        border-collapse: collapse;
        min-width: 1180px;
        width: 100%;
        font-size: 14px;
    }

    .swakelola-table th,
    .swakelola-table td {
        border: 1px solid #000;
        padding: 6px 8px;
        vertical-align: middle;
    }

    .swakelola-table thead th {
        background: #a6a6a6;
        color: #000;
        font-weight: 600;
        text-align: center;
    }

    .swakelola-table tbody tr:nth-child(even) {
        background: #d9d9d9;
    }

    .swakelola-table .number-cell {
        text-align: right;
        white-space: nowrap;
    }

    .swakelola-table .center-cell {
        text-align: center;
        white-space: nowrap;
    }

    .swakelola-table .total-row td {
        background: #bfbfbf;
        font-weight: 700;
    }

    .swakelola-title {
        font-size: 20px;
        font-weight: 700;
        letter-spacing: 0.02em;
        text-align: center;
        text-transform: uppercase;
    }
</style>

<div class="swakelola-report-card">
    <div class="swakelola-title mb-3">Evaluasi Data Pencatatan Swakelola</div>

    <form method="GET" action="{{ route('realisasi.pencatatan.swakelola') }}" class="row g-2 align-items-end mb-3">
        <div class="col-md-2">
            <label for="tahun" class="form-label mb-1">Tahun</label>
            <select id="tahun" name="tahun" class="form-select" onchange="this.form.submit()">
                @foreach($tahunOptions as $option)
                    <option value="{{ $option }}" {{ (int) $option === (int) $tahun ? 'selected' : '' }}>{{ $option }}</option>
                @endforeach
            </select>
        </div>
    </form>

    @if($apiError)
        <div class="alert alert-warning" role="alert">
            {{ $apiError }} Pastikan token INAPROC tersedia di konfigurasi aplikasi.
        </div>
    @endif

    <div class="table-responsive">
        <table class="swakelola-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 56px;">No</th>
                    <th rowspan="2" style="min-width: 320px;">Nama OPD</th>
                    <th colspan="2">Perencanaan</th>
                    <th colspan="2">Tercatat</th>
                    <th colspan="2">Belum Tercatat</th>
                    <th rowspan="2" style="width: 170px;">Persentase<br>Tercatat</th>
                </tr>
                <tr>
                    <th style="width: 110px;">Paket</th>
                    <th style="width: 170px;">Pagu</th>
                    <th style="width: 110px;">Paket</th>
                    <th style="width: 170px;">Pagu</th>
                    <th style="width: 110px;">Paket</th>
                    <th style="width: 170px;">Pagu</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $index => $row)
                    <tr>
                        <td class="center-cell">{{ $index + 1 }}</td>
                        <td>{{ $row['nama_opd'] }}</td>
                        <td class="center-cell">{{ number_format($row['perencanaan_paket'], 0, ',', '.') }}</td>
                        <td class="number-cell">{{ number_format($row['perencanaan_pagu'], 0, ',', '.') }}</td>
                        <td class="center-cell">{{ number_format($row['tercatat_paket'], 0, ',', '.') }}</td>
                        <td class="number-cell">{{ number_format($row['tercatat_pagu'], 0, ',', '.') }}</td>
                        <td class="center-cell">{{ number_format($row['belum_tercatat_paket'], 0, ',', '.') }}</td>
                        <td class="number-cell">{{ number_format($row['belum_tercatat_pagu'], 0, ',', '.') }}</td>
                        <td class="number-cell">{{ number_format($row['persentase_tercatat'], 2, ',', '.') }}%</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="center-cell">Data tidak tersedia.</td>
                    </tr>
                @endforelse

                @if($rows->isNotEmpty())
                    <tr class="total-row">
                        <td colspan="2">TOTAL</td>
                        <td class="center-cell">{{ number_format($summary['perencanaan_paket'], 0, ',', '.') }}</td>
                        <td class="number-cell">{{ number_format($summary['perencanaan_pagu'], 0, ',', '.') }}</td>
                        <td class="center-cell">{{ number_format($summary['tercatat_paket'], 0, ',', '.') }}</td>
                        <td class="number-cell">{{ number_format($summary['tercatat_pagu'], 0, ',', '.') }}</td>
                        <td class="center-cell">{{ number_format($summary['belum_tercatat_paket'], 0, ',', '.') }}</td>
                        <td class="number-cell">{{ number_format($summary['belum_tercatat_pagu'], 0, ',', '.') }}</td>
                        <td class="number-cell">{{ number_format($summary['persentase_tercatat'], 2, ',', '.') }}%</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
@endsection
