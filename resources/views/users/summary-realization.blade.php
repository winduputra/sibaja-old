@extends('layouts.user')

@section('title', $pageTitle)

@push('style')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<style>
  .realization-report table th,
  .realization-report table td {
    border: 1px solid #000;
    vertical-align: middle;
    white-space: nowrap;
  }

  .realization-report thead th {
    font-weight: 600;
    text-align: center;
  }

  .realization-report .small-heading {
    font-size: 11px;
  }
</style>
@endpush

@section('content')
<section class="content py-3 realization-report">
  <div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
      <h2 class="h4 mb-0 fw-bold text-primary">{{ $pageTitle }}</h2>
      <a href="{{ $exportRoute }}" class="btn btn-success btn-sm shadow-sm">
        <i class="fas fa-file-pdf me-1"></i> Export PDF
      </a>
    </div>

    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body bg-light rounded">
        <form action="{{ $filterRoute }}" method="GET" class="row g-3 align-items-end">
          <div class="col-md-auto">
            <label class="form-label small fw-bold">Provinsi</label>
            <input type="text" class="form-control form-control-sm bg-white" value="Provinsi Lampung" readonly style="width: 170px;">
          </div>

          <div class="col-md-auto">
            <label class="form-label small fw-bold">Tahun</label>
            <select name="year" class="form-select form-select-sm" style="width: 100px;">
              @for($y = now()->year + 1; $y >= 2020; $y--)
                <option value="{{ $y }}" {{ (int) $year === $y ? 'selected' : '' }}>{{ $y }}</option>
              @endfor
            </select>
          </div>

          <div class="col-md-auto">
            <label class="form-label small fw-bold">Bulan</label>
            <select name="month" class="form-select form-select-sm" style="width: 150px;">
              <option value="ALL" {{ $month === 'ALL' ? 'selected' : '' }}>Semua Bulan</option>
              @for($m = 1; $m <= 12; $m++)
                <option value="{{ $m }}" {{ (string) $month === (string) $m ? 'selected' : '' }}>{{ getMonthName($m) }}</option>
              @endfor
            </select>
          </div>

          <div class="col-md-auto">
            <label class="form-label small fw-bold">Tanggal</label>
            <select name="day" class="form-select form-select-sm" style="width: 130px;">
              <option value="ALL" {{ $day === 'ALL' ? 'selected' : '' }}>Semua Tanggal</option>
              @for($d = 1; $d <= 31; $d++)
                <option value="{{ $d }}" {{ (string) $day === (string) $d ? 'selected' : '' }}>{{ $d }}</option>
              @endfor
            </select>
          </div>

          <div class="col-md-auto">
            <label class="form-label small fw-bold">Status Paket</label>
            <select name="status" class="form-select form-select-sm" style="width: 160px;">
              <option value="ALL" {{ $status === 'ALL' ? 'selected' : '' }}>Semua Status</option>
              @foreach($statusOptions as $statusOption)
                <option value="{{ $statusOption }}" {{ $status === $statusOption ? 'selected' : '' }}>{{ $statusOption }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-md-auto d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm px-3">
              <i class="fas fa-filter me-1"></i> Tampilkan
            </button>
            <a href="{{ $filterRoute }}" class="btn btn-outline-secondary btn-sm px-3">Reset</a>
          </div>
        </form>
      </div>
    </div>

    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <div class="text-center mb-3 text-uppercase text-dark">
          <div class="fw-bold">{{ $tableTitle }}</div>
          <div class="fw-bold">OPD PROVINSI LAMPUNG</div>
          <div class="fw-bold">
            @if ($month !== 'ALL' && $day !== 'ALL')
              TAHUN ANGGARAN {{ $year }} TANGGAL {{ $day }} {{ strtoupper(getMonthName($month)) }} {{ $year }}
            @elseif ($month !== 'ALL')
              TAHUN ANGGARAN {{ $year }} {{ strtoupper(getMonthName($month)) }} {{ $year }}
            @else
              TAHUN ANGGARAN {{ $year }} S.D TANGGAL {{ date('d') }} {{ strtoupper(getMonthName(date('m'))) }} {{ date('Y') }}
            @endif
          </div>
        </div>

        <div class="table-responsive">
          <table id="realizationTable" class="table table-sm table-bordered align-middle w-100">
            <thead>
              <tr>
                <th rowspan="2">No.</th>
                <th rowspan="2">OPD</th>
                <th rowspan="2">STATUS</th>
                <th rowspan="2" class="small-heading">JUMLAH<br>PAKET</th>
                <th colspan="4">JENIS PEKERJAAN</th>
                <th colspan="3">JUMLAH</th>
                <th rowspan="2">EFISIENSI</th>
              </tr>
              <tr>
                <th class="small-heading">KONSTRUKSI</th>
                <th class="small-heading">KONSULTASI</th>
                <th class="small-heading">BARANG</th>
                <th class="small-heading">JASA LAINNYA</th>
                <th class="small-heading">PAGU</th>
                <th class="small-heading">HPS</th>
                <th class="small-heading">NILAI TERKONTRAK</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($data as $item)
                <tr>
                  <td class="text-center">{{ $loop->iteration }}</td>
                  <td class="text-wrap" style="min-width: 220px;">{{ $item['name'] }}</td>
                  <td class="text-wrap" style="min-width: 140px;">
                    @if (!empty($item['status_list']))
                      @php
                        $statusCounts = [];
                        foreach ($item['status_list'] as $statusItem) {
                            $statusCounts[$statusItem] = $item['status_count'][$statusItem] ?? 0;
                        }
                      @endphp
                      {{ implode(', ', collect($statusCounts)->map(fn($count, $statusItem) => "$statusItem ($count)")->toArray()) }}
                    @else
                      -
                    @endif
                  </td>
                  <td class="text-center">{{ $item['package_count'] }}</td>
                  <td class="text-center">{{ $item['constructions'] }}</td>
                  <td class="text-center">{{ $item['consultations'] }}</td>
                  <td class="text-center">{{ $item['goods'] }}</td>
                  <td class="text-center">{{ $item['services'] }}</td>
                  <td class="text-end">{{ format_hps_pagu($item['pagu']) }}</td>
                  <td class="text-end">{{ format_hps_pagu($item['hps']) }}</td>
                  <td class="text-end">{{ moneyFormat($item['nilai_terkontrak']) }}</td>
                  <td class="text-end">{{ moneyFormat($item['efficiency']) }}</td>
                </tr>
              @endforeach
            </tbody>
            <tfoot>
              <tr class="fw-bold">
                <td colspan="2" class="text-end">Total</td>
                <td class="text-center">{{ $total['package_count'] }}</td>
                <td class="text-center">{{ $total['package_count'] }}</td>
                <td class="text-center">{{ $total['constructions'] }}</td>
                <td class="text-center">{{ $total['consultations'] }}</td>
                <td class="text-center">{{ $total['goods'] }}</td>
                <td class="text-center">{{ $total['services'] }}</td>
                <td class="text-end">{{ format_hps_pagu($total['pagu']) }}</td>
                <td class="text-end">{{ format_hps_pagu($total['hps']) }}</td>
                <td class="text-end">{{ moneyFormat($total['nilai_terkontrak']) }}</td>
                <td class="text-end">{{ moneyFormat($total['efficiency']) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection

@push('script')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script>
  $(function () {
    $('#realizationTable').DataTable({
      scrollX: true,
      autoWidth: false,
      ordering: false,
      language: {
        search: 'Cari:',
        lengthMenu: 'Tampilkan _MENU_ data',
        info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
        infoEmpty: 'Tidak ada data',
        zeroRecords: 'Data tidak ditemukan',
        paginate: {
          first: 'Pertama',
          last: 'Terakhir',
          next: 'Selanjutnya',
          previous: 'Sebelumnya'
        }
      }
    });
  });
</script>
@endpush
