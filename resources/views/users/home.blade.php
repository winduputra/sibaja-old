@extends('layouts.user')

@section('title', 'Dashboard Pengadaan')

@push('style')
<style>
  :root {
    --dashboard-bg: #F5F8FD;
    --dashboard-card: #ffffff;
    --dashboard-border: #d9e2ef;
    --dashboard-primary: #1d3c77;
    --dashboard-primary-soft: #e7effb;
    --dashboard-heading: #17305f;
    --dashboard-muted: #667085;
    --dashboard-total: #f2f6fc;
    --dashboard-body-text: #1f2937;
    --dashboard-row-alt: #fbfdff;
    --dashboard-radius: 6px;
    --dashboard-shadow: 0 4px 14px rgba(29, 60, 119, 0.08);
    --dashboard-shell-max: 1320px;
    --dashboard-table-min: 920px;
    --dashboard-number-width: 54px;
    --dashboard-satker-min: 280px;
    --dashboard-amount-min: 130px;
  }

  body { background-color: var(--dashboard-bg) !important; }

  .dashboard-page {
    background-color: var(--dashboard-bg);
    padding-bottom: 2rem;
  }

  .dashboard-shell {
    max-width: var(--dashboard-shell-max);
  }

  .dashboard-title-card {
    background: linear-gradient(135deg, var(--dashboard-card), var(--dashboard-primary-soft));
    border: 1px solid var(--dashboard-border);
    border-left: 6px solid var(--dashboard-primary);
    border-radius: var(--dashboard-radius);
  }

  .dashboard-kicker {
    color: var(--dashboard-muted);
    font-size: 0.8rem;
    letter-spacing: 0.05em;
    text-transform: uppercase;
  }

  .dashboard-recap-card {
    background-color: var(--dashboard-card);
    border: 1px solid var(--dashboard-border);
    border-radius: var(--dashboard-radius);
    box-shadow: var(--dashboard-shadow);
  }

  .dashboard-recap-title {
    color: var(--dashboard-heading);
    font-size: 1rem;
  }

  .dashboard-table {
    min-width: var(--dashboard-table-min);
    margin-bottom: 0;
    color: var(--dashboard-body-text);
  }

  .dashboard-table th,
  .dashboard-table td {
    border-color: var(--dashboard-border) !important;
    font-size: 0.78rem;
    padding: 0.45rem 0.55rem;
    vertical-align: middle;
  }

  .dashboard-table thead th {
    background-color: var(--dashboard-primary-soft);
    color: var(--dashboard-heading);
    font-weight: 700;
    text-align: center;
  }

  .dashboard-table tbody tr:nth-child(even) td {
    background-color: var(--dashboard-row-alt);
  }

  .dashboard-table tfoot td {
    background-color: var(--dashboard-total);
    color: var(--dashboard-heading);
    font-weight: 700;
  }

  .satker-cell {
    min-width: var(--dashboard-satker-min);
  }

  .amount-cell {
    min-width: var(--dashboard-amount-min);
    text-align: right;
    white-space: nowrap;
  }

  .package-cell,
  .percent-cell {
    text-align: center;
    white-space: nowrap;
  }

  .number-cell {
    width: var(--dashboard-number-width);
  }

  .dashboard-filter-form .form-label {
    color: var(--dashboard-heading);
  }

  .dashboard-filter-date {
    min-width: 145px;
  }

  .dashboard-week-range {
    background-color: var(--dashboard-primary-soft);
    border: 1px solid var(--dashboard-border);
    border-radius: var(--dashboard-radius);
    color: var(--dashboard-heading);
  }
</style>
@endpush

@section('content')
@php
  $formatNumber = function ($value) {
      return number_format((float) $value, 0, ',', '.');
  };

  $formatPercent = function ($value) {
      return number_format((float) $value, 2, ',', '.') . '%';
  };

  $recaps = $dashboardRecaps ?? [];

  $calculatePercent = function ($rencanaPagu, $realisasiNilai) {
      return $rencanaPagu > 0 ? ($realisasiNilai / $rencanaPagu) * 100 : 0;
  };

  $methodRecapTotals = function ($key) use ($recaps) {
      $rows = collect(data_get($recaps, $key . '.rows', []));

      return [
          'rencana_paket' => $rows->sum('rencana_paket'),
          'rencana_pagu' => $rows->sum('rencana_pagu'),
          'realisasi_paket' => $rows->sum('realisasi_paket'),
          'realisasi_nilai' => $rows->sum('realisasi_nilai'),
      ];
  };

  $overallMethodTotals = $methodRecapTotals('overall');
  $tenderMethodTotals = $methodRecapTotals('tender');
  $epurchasingMethodTotals = $methodRecapTotals('epurchasing');
  $swakelolaMethodTotals = $methodRecapTotals('swakelola');

  $penyediaMethodTotals = [
      'rencana_paket' => $overallMethodTotals['rencana_paket'] - $swakelolaMethodTotals['rencana_paket'],
      'rencana_pagu' => $overallMethodTotals['rencana_pagu'] - $swakelolaMethodTotals['rencana_pagu'],
      'realisasi_paket' => $overallMethodTotals['realisasi_paket'] - $swakelolaMethodTotals['realisasi_paket'],
      'realisasi_nilai' => $overallMethodTotals['realisasi_nilai'] - $swakelolaMethodTotals['realisasi_nilai'],
  ];

  $totalMethodTotals = [
      'rencana_paket' => $penyediaMethodTotals['rencana_paket'] + $swakelolaMethodTotals['rencana_paket'],
      'rencana_pagu' => $penyediaMethodTotals['rencana_pagu'] + $swakelolaMethodTotals['rencana_pagu'],
      'realisasi_paket' => $penyediaMethodTotals['realisasi_paket'] + $swakelolaMethodTotals['realisasi_paket'],
      'realisasi_nilai' => $penyediaMethodTotals['realisasi_nilai'] + $swakelolaMethodTotals['realisasi_nilai'],
  ];

  $methodSummaryRows = [
      ['metode' => 'PENYEDIA'] + $penyediaMethodTotals,
      ['metode' => 'SWAKELOLA'] + $swakelolaMethodTotals,
  ];
  $methodSummaryTotal = ['metode' => 'TOTAL'] + $totalMethodTotals;

  $methodDetailRows = collect($methodDetailRows ?? []);
  $methodDetailBodyRows = $methodDetailRows->reject(function ($row) {
      return data_get($row, 'is_total');
  })->values();
  $methodDetailTotal = $methodDetailRows->first(function ($row) {
      return data_get($row, 'is_total');
  });

  $tahunLabel = $tahun === 'all' ? 'semua tahun' : 'tahun ' . $tahun;
@endphp

<div class="dashboard-page py-4">
  <div class="container-fluid dashboard-shell px-4">
    <div class="dashboard-title-card p-4 mb-4">
      <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
        <div>
          <div class="dashboard-kicker fw-semibold mb-2">Dashboard Pengadaan</div>
          <h2 class="fw-bold text-primary mb-2">Rekapitulasi Pengadaan Pemerintah Provinsi Lampung</h2>
          <p class="text-muted mb-0">Ringkasan rencana RUP dan realisasi pengadaan per satuan kerja {{ $tahunLabel }}.</p>
        </div>
        <div class="d-flex flex-column align-items-lg-end gap-2">
          <form method="GET" action="{{ route('home') }}" class="dashboard-filter-form d-flex flex-column flex-sm-row flex-sm-wrap align-items-sm-end gap-2">
            <input type="hidden" name="kategori_chart2" value="{{ $kategoriChart2 }}">
            <div>
              <label for="tahun" class="form-label small fw-bold mb-1">Tahun Anggaran</label>
              <select name="tahun" id="tahun" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="all" {{ $tahun === 'all' ? 'selected' : '' }}>Semua Tahun</option>
                @forelse ($availableYears as $year)
                  <option value="{{ $year }}" {{ (string) $tahun === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                @empty
                  <option value="{{ $tahun }}">{{ $tahun === 'all' ? 'Semua Tahun' : $tahun }}</option>
                @endforelse
              </select>
            </div>
            <div>
              <label for="bulan" class="form-label small fw-bold mb-1">Bulan</label>
              <select name="bulan" id="bulan" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="all" {{ $selectedMonth === 'all' ? 'selected' : '' }}>Semua Bulan</option>
                @foreach ($monthOptions as $monthNumber => $monthName)
                  <option value="{{ $monthNumber }}" {{ (string) $selectedMonth === (string) $monthNumber ? 'selected' : '' }}>{{ $monthName }}</option>
                @endforeach
              </select>
            </div>
            <div>
              <label for="minggu" class="form-label small fw-bold mb-1">Minggu</label>
              <select name="minggu" id="minggu" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach ($availableWeeks as $weekNumber => $week)
                  <option value="{{ $weekNumber }}" {{ (string) $selectedWeek === (string) $weekNumber ? 'selected' : '' }}>{{ $week['label'] }}</option>
                @endforeach
              </select>
            </div>
            <div class="dashboard-filter-date">
              <label for="tanggal_mulai" class="form-label small fw-bold mb-1">Tanggal Mulai</label>
              <input type="date" name="tanggal_mulai" id="tanggal_mulai" value="{{ $selectedStartDate }}" class="form-control form-control-sm">
            </div>
            <div class="dashboard-filter-date">
              <label for="tanggal_selesai" class="form-label small fw-bold mb-1">Tanggal Selesai</label>
              <input type="date" name="tanggal_selesai" id="tanggal_selesai" value="{{ $selectedEndDate }}" class="form-control form-control-sm">
            </div>
            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary btn-sm">Terapkan</button>
              @if ($isCustomDateRangeActive)
                <a href="{{ route('home', ['tahun' => $tahun, 'kategori_chart2' => $kategoriChart2]) }}" class="btn btn-outline-secondary btn-sm">Reset</a>
              @endif
            </div>
          </form>
          <div class="dashboard-week-range px-3 py-2 small fw-semibold text-lg-end">
            <span class="dashboard-kicker d-block mb-1">Rentang Aktif</span>
            @if ($isCustomDateRangeActive)
              Tanggal Custom: {{ $activeDashboardRange['range_label'] }}
            @else
              {{ $activeWeekRange['range_label'] }}
            @endif
          </div>
        </div>
      </div>
    </div>

    <div class="dashboard-recap-card mb-4 overflow-hidden">
      <div class="px-3 py-3 border-bottom">
        <h5 class="dashboard-recap-title fw-bold mb-0">REKAPITULASI PENGADAAN SEMUA METODE PENGADAAN</h5>
      </div>

      <div class="table-responsive">
        <table class="table table-sm table-bordered dashboard-table">
          <thead>
            <tr>
              <th rowspan="2">Metode Pengadaan</th>
              <th colspan="2">Perencanaan</th>
              <th colspan="2">Realisasi</th>
              <th rowspan="2">Persentase</th>
            </tr>
            <tr>
              <th>Jumlah Paket</th>
              <th>Total Pagu</th>
              <th>Jumlah Paket</th>
              <th>Total Pagu</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($methodSummaryRows as $row)
              @php
                $persentase = $calculatePercent($row['rencana_pagu'], $row['realisasi_nilai']);
              @endphp
              <tr>
                <td class="satker-cell fw-bold">{{ $row['metode'] }}</td>
                <td class="package-cell">{{ $formatNumber($row['rencana_paket']) }}</td>
                <td class="amount-cell">{{ $formatNumber($row['rencana_pagu']) }}</td>
                <td class="package-cell">{{ $formatNumber($row['realisasi_paket']) }}</td>
                <td class="amount-cell">{{ $formatNumber($row['realisasi_nilai']) }}</td>
                <td class="percent-cell">{{ $formatPercent($persentase) }}</td>
              </tr>
            @endforeach
          </tbody>
          <tfoot>
            @php
              $totalPersentase = $calculatePercent($methodSummaryTotal['rencana_pagu'], $methodSummaryTotal['realisasi_nilai']);
            @endphp
            <tr>
              <td class="text-end">{{ $methodSummaryTotal['metode'] }}</td>
              <td class="package-cell">{{ $formatNumber($methodSummaryTotal['rencana_paket']) }}</td>
              <td class="amount-cell">{{ $formatNumber($methodSummaryTotal['rencana_pagu']) }}</td>
              <td class="package-cell">{{ $formatNumber($methodSummaryTotal['realisasi_paket']) }}</td>
              <td class="amount-cell">{{ $formatNumber($methodSummaryTotal['realisasi_nilai']) }}</td>
              <td class="percent-cell">{{ $formatPercent($totalPersentase) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <div class="dashboard-recap-card mb-4 overflow-hidden">
      <div class="px-3 py-3 border-bottom">
        <h5 class="dashboard-recap-title fw-bold mb-1">DETAIL METODE PEMILIHAN PENGADAAN</h5>
        <div class="small text-muted">Rincian rencana RUP dan realisasi berdasarkan metode pemilihan.</div>
      </div>

      <div class="table-responsive">
        <table class="table table-sm table-bordered dashboard-table">
          <thead>
            <tr>
              <th rowspan="2">Metode Pemilihan</th>
              <th colspan="2">Perencanaan</th>
              <th colspan="2">Realisasi</th>
            </tr>
            <tr>
              <th>Pagu</th>
              <th>Paket</th>
              <th>Pagu</th>
              <th>Paket</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($methodDetailBodyRows as $row)
              <tr>
                <td class="satker-cell fw-bold">{{ $row['metode'] }}</td>
                <td class="amount-cell">{{ $formatNumber($row['rencana_pagu']) }}</td>
                <td class="package-cell">{{ $formatNumber($row['rencana_paket']) }}</td>
                <td class="amount-cell">{{ $formatNumber($row['realisasi_pagu']) }}</td>
                <td class="package-cell">{{ $formatNumber($row['realisasi_paket']) }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center text-muted py-4">Data metode pemilihan {{ $tahunLabel }} belum tersedia.</td>
              </tr>
            @endforelse
          </tbody>
          @if ($methodDetailTotal)
            <tfoot>
              <tr>
                <td class="text-end">{{ strtoupper($methodDetailTotal['metode']) }}</td>
                <td class="amount-cell">{{ $formatNumber($methodDetailTotal['rencana_pagu']) }}</td>
                <td class="package-cell">{{ $formatNumber($methodDetailTotal['rencana_paket']) }}</td>
                <td class="amount-cell">{{ $formatNumber($methodDetailTotal['realisasi_pagu']) }}</td>
                <td class="package-cell">{{ $formatNumber($methodDetailTotal['realisasi_paket']) }}</td>
              </tr>
            </tfoot>
          @endif
        </table>
      </div>
    </div>

    @foreach ($recaps as $recap)
      @php
        $rows = collect($recap['rows'] ?? []);
        $totals = [
            'rencana_paket' => $rows->sum('rencana_paket'),
            'rencana_pagu' => $rows->sum('rencana_pagu'),
            'realisasi_paket' => $rows->sum('realisasi_paket'),
            'realisasi_nilai' => $rows->sum('realisasi_nilai'),
        ];
        $totals['persentase'] = $totals['rencana_pagu'] > 0 ? ($totals['realisasi_nilai'] / $totals['rencana_pagu']) * 100 : 0;
      @endphp

      <div class="dashboard-recap-card mb-4 overflow-hidden">
        <div class="px-3 py-3 border-bottom">
          <h5 class="dashboard-recap-title fw-bold mb-1">{{ $recap['title'] }}</h5>
          <div class="small text-muted">{{ $recap['subtitle'] }}</div>
        </div>

        <div class="table-responsive">
          <table class="table table-sm table-bordered dashboard-table">
            <thead>
              <tr>
                <th rowspan="2" class="number-cell">No</th>
                <th rowspan="2">Satuan Kerja</th>
                <th colspan="2">Rencana Pengadaan</th>
                <th colspan="2">Realisasi</th>
                <th rowspan="2">Persentase</th>
              </tr>
              <tr>
                <th>Paket</th>
                <th>Pagu</th>
                <th>Paket</th>
                <th>Nilai</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($rows as $row)
                <tr>
                  <td class="package-cell">{{ $loop->iteration }}</td>
                  <td class="satker-cell">{{ $row['nama_satker'] }}</td>
                  <td class="package-cell">{{ $formatNumber($row['rencana_paket']) }}</td>
                  <td class="amount-cell">{{ $formatNumber($row['rencana_pagu']) }}</td>
                  <td class="package-cell">{{ $formatNumber($row['realisasi_paket']) }}</td>
                  <td class="amount-cell">{{ $formatNumber($row['realisasi_nilai']) }}</td>
                  <td class="percent-cell">{{ $formatPercent($row['persentase']) }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center text-muted py-4">Data satuan kerja {{ $tahunLabel }} belum tersedia.</td>
                </tr>
              @endforelse
            </tbody>
            <tfoot>
              <tr>
                <td colspan="2" class="text-end">TOTAL</td>
                <td class="package-cell">{{ $formatNumber($totals['rencana_paket']) }}</td>
                <td class="amount-cell">{{ $formatNumber($totals['rencana_pagu']) }}</td>
                <td class="package-cell">{{ $formatNumber($totals['realisasi_paket']) }}</td>
                <td class="amount-cell">{{ $formatNumber($totals['realisasi_nilai']) }}</td>
                <td class="percent-cell">{{ $formatPercent($totals['persentase']) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    @endforeach
  </div>
</div>
@endsection
