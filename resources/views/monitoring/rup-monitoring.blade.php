@extends('layouts.user')

@push('style')
<style>
    .nav-tabs-custom > .nav-tabs > li.active > a, .nav-tabs-custom > .nav-tabs > li.active > a:hover, .nav-tabs-custom > .nav-tabs > li.active > a:focus {
        border-radius: 4px 4px 0 0;
    }

    .table-wrapper {
        overflow-x: auto;
        background-color: #ffffff;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        margin-bottom: 20px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        min-width: 600px;
    }

    th, td {
        border: 1px solid #ddd;
        padding: 12px;
        text-align: left;
    }

    th {
        background-color: #f4f4f4;
        font-weight: bold;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    tr:hover {
        background-color: #f0f0f0;
    }

    .total-row {
        background-color: #f4f4f4;
        font-weight: bold;
    }

    .satker-name {
        color: #0066cc;
        cursor: pointer;
        text-decoration: underline;
        font-weight: 500;
    }

    .satker-name:hover {
        color: #0052a3;
    }

    .badge-status {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: bold;
    }

    .badge-unrealized {
        background-color: #ff6b6b;
        color: white;
    }

    .filter-section {
        background-color: #fff;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .form-group {
        margin-bottom: 0;
    }

    .form-group label {
        font-weight: 600;
        margin-bottom: 5px;
        display: block;
    }

    .form-group select {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .btn-export {
        background-color: #28a745;
        color: white;
        padding: 8px 16px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: bold;
    }

    .btn-export:hover {
        background-color: #218838;
    }

    .rupList-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
        font-size: 12px;
    }

    .rupList-table th, .rupList-table td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }

    .rupList-table th {
        background-color: #e8f4f8;
        font-weight: bold;
    }

    .rupList-table tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    .currency-format {
        text-align: right;
        font-family: monospace;
    }

    .no-data {
        text-align: center;
        padding: 30px;
        color: #666;
    }

    .row-filter {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }

    @media (max-width: 768px) {
        .row-filter {
            grid-template-columns: 1fr;
        }
    }

    .btn-group-export {
        display: flex;
        gap: 10px;
        margin-top: 10px;
    }

    .btn-export-pdf {
        background-color: #dc3545;
        color: white;
        padding: 8px 16px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: bold;
        text-decoration: none;
        display: inline-block;
    }

    .btn-export-pdf:hover {
        background-color: #c82333;
    }
</style>
@endpush

@section('content')
<div class="container-fluid" style="padding: 20px;">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2><i class="fas fa-chart-line"></i> RUP Monitoring</h2>
            <p class="text-muted">Pantau RUP yang belum terealisasi atau belum dilakukan pengadaan</p>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <form method="GET" action="{{ route('monitoring.rup.index') }}" id="filterForm" class="row-filter">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="form-group">
                <label>Tahun Anggaran</label>
                <select name="tahun" class="form-control" onchange="document.getElementById('filterForm').submit();">
                    @foreach($availableYears as $y)
                        <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Metode Pengadaan</label>
                @php
                    $metodeOptions = $tab === 'penyedia' ? $metodePengadaanPenyedia : $metodePengadaanSwakelola;
                @endphp
                <select name="metode" class="form-control">
                    <option value="">-- Pilih Metode --</option>
                    @foreach($metodeOptions as $metode)
                        <option value="{{ $metode }}" {{ $metodeFilter == $metode ? 'selected' : '' }}>{{ $metode }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Status Konsolidasi</label>
                <select name="konsolidasi" class="form-control">
                    <option value="all" {{ ($konsolidasiFilter ?? 'all') == 'all' ? 'selected' : '' }}>-- Semua --</option>
                    <option value="Konsolidasi" {{ ($konsolidasiFilter ?? '') == 'Konsolidasi' ? 'selected' : '' }}>Konsolidasi Saja</option>
                    <option value="Non-Konsolidasi" {{ ($konsolidasiFilter ?? '') == 'Non-Konsolidasi' ? 'selected' : '' }}>Non-Konsolidasi Saja</option>
                </select>
            </div>

            <div class="form-group">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-search"></i> Filter
                </button>
            </div>

            @if($metodeFilter)
            <div class="form-group">
                <label>&nbsp;</label>
                <a href="{{ route('monitoring.rup.export-pdf', ['tahun' => $year, 'tab' => $tab, 'metode' => $metodeFilter, 'konsolidasi' => $konsolidasiFilter]) }}" class="btn btn-export-pdf btn-block" style="text-align: center; display: block; text-decoration: none;">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </a>
            </div>
            @endif
        </form>
    </div>

    <!-- Tabs -->
    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs">
            <li class="{{ $tab === 'penyedia' ? 'active' : '' }}">
                <a href="{{ route('monitoring.rup.index', ['tahun' => $year, 'tab' => 'penyedia', 'metode' => $metodeFilter, 'konsolidasi' => $konsolidasiFilter]) }}">
                    <i class="fas fa-building"></i> RUP Penyedia
                </a>
            </li>
            <li class="{{ $tab === 'swakelola' ? 'active' : '' }}">
                <a href="{{ route('monitoring.rup.index', ['tahun' => $year, 'tab' => 'swakelola', 'metode' => $metodeFilter, 'konsolidasi' => $konsolidasiFilter]) }}">
                    <i class="fas fa-handshake"></i> RUP Swakelola
                </a>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane active">
                @if(count($data) > 0)
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th width="5%">No</th>
                                    <th width="35%">Nama Satker</th>
                                    <th width="15%">
                                        {{ $tab === 'penyedia' ? 'Metode Pengadaan' : 'Tipe Swakelola' }}
                                    </th>
                                    <th width="12%">Total Paket</th>
                                    <th width="18%">Total Pagu (Rp)</th>
                                    <th width="15%">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($data as $index => $item)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <a href="javascript:void(0)" class="satker-name" 
                                               onclick="showDetail('{{ $item['kd_satker_str'] }}', '{{ $tab }}')">
                                                {{ $item['nama_satker'] }}
                                            </a>
                                        </td>
                                        <td>
                                            {{ $tab === 'penyedia' ? $item['metode_pengadaan'] : $item['tipe_swakelola'] }}
                                        </td>
                                        <td>{{ $item['total_paket'] }}</td>
                                        <td class="currency-format">
                                            Rp {{ number_format($item['total_pagu'], 0, ',', '.') }}
                                        </td>
                                        <td>
                                            <span class="badge badge-unrealized">Belum Terealisasi</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="total-row">
                                    <td colspan="3" class="text-right">Total</td>
                                    <td>{{ collect($data)->sum('total_paket') }}</td>
                                    <td class="currency-format">
                                        Rp {{ number_format(collect($data)->sum('total_pagu'), 0, ',', '.') }}
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="no-data">
                        <i class="fas fa-inbox" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
                        <p>Tidak ada data RUP yang belum terealisasi</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail RUP -->
<div class="modal fade" id="detailModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail RUP yang Belum Terealisasi</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="detailContent">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function showDetail(kdSatkerStr, tab) {
    $.ajax({
        url: '{{ route("monitoring.rup.detail") }}',
        type: 'GET',
        data: {
            kd_satker_str: kdSatkerStr,
            tahun: '{{ $year }}',
            tab: tab,
            metode: '{{ $metodeFilter }}',
            konsolidasi: '{{ $konsolidasiFilter }}'
        },
        success: function(response) {
            let html = '<div style="max-height: 600px; overflow-y: auto;">';
            html += '<h6 style="margin-bottom: 15px; font-weight: bold;">' + response.nama_satker + '</h6>';
            html += '<table class="rupList-table">';
            html += '<thead><tr>';
            html += '<th width="5%">No</th>';
            html += '<th width="15%">Kode RUP</th>';
            html += '<th width="40%">Nama Paket</th>';
            html += '<th width="20%">Pagu (Rp)</th>';
            html += '<th width="20%">Status</th>';
            html += '</tr></thead>';
            html += '<tbody>';

            if (response.rupList && response.rupList.length > 0) {
                response.rupList.forEach(function(rup, index) {
                    html += '<tr>';
                    html += '<td>' + (index + 1) + '</td>';
                    html += '<td>' + rup.kd_rup + '</td>';
                    html += '<td>';
                    html += rup.nama_paket;
                    if (rup.status_konsolidasi === 'Konsolidasi') {
                        html += ' <span class="badge badge-warning" style="font-size: 0.7rem;">Konsolidasi</span>';
                    }
                    html += '</td>';
                    html += '<td class="currency-format">Rp ' + formatCurrency(rup.pagu) + '</td>';
                    html += '<td><span class="badge badge-unrealized">' + rup.status + '</span></td>';
                    html += '</tr>';
                });
            } else {
                html += '<tr><td colspan="5" class="text-center">Tidak ada data</td></tr>';
            }

            html += '</tbody></table></div>';

            $('#detailContent').html(html);
            $('#detailModal').modal('show');
        },
        error: function() {
            alert('Gagal mengambil data');
        }
    });
}

function formatCurrency(value) {
    return new Intl.NumberFormat('id-ID').format(value);
}
</script>
@endpush
