@extends('layouts.user')

@section('title', 'Rekap e-Katalog')

@push('style')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
  div.dataTables_wrapper div.dataTables_info,
  div.dataTables_wrapper div.dataTables_paginate {
    margin-top: 15px !important;
  }

  .select2-container--default .select2-selection--single {
    height: 38px !important;
    padding: 5px 10px;
    font-size: 0.875rem;
    border: 1px solid #dee2e6 !important;
    border-radius: 0.375rem;
  }

  .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 28px !important;
  }

  .select2-container--default .select2-selection--single .select2-selection__arrow {
    top: 6px !important;
  }

  .select2-container {
    min-width: 150px;
  }

  .select2-container--default.select2-satker {
    width: 100% !important;
  }
</style>
@endpush

@section('content')
<section class="content py-3">
  <div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="h4 mb-0 fw-bold text-primary">Laporan e-Katalog Versi {{ strtoupper($versi) }} - Tahun {{ $tahun }}</h2>
      <a href="{{ route('report.ekatalog.exportpdf', [
            'tahun' => $tahun,
            'versi' => $versi,
            'satker' => $satker,
            'status' => $status
          ]) }}"
             class="btn btn-success btn-sm shadow-sm" target="_blank">
            <i class="fas fa-file-pdf me-1"></i> Export PDF
      </a>
    </div>

    {{-- Filter Card --}}
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body bg-light rounded">
        <form id="filterForm" action="{{ route('report.ekatalog') }}" method="GET" class="row g-3 align-items-end">
          <div class="col-md-auto">
            <label class="form-label small fw-bold">Provinsi</label>
            <input type="text" class="form-control form-control-sm bg-white" value="Provinsi Lampung" readonly style="width: 160px;">
          </div>

          <div class="col-md-auto">
            <label class="form-label small fw-bold">Tahun</label>
            <select name="tahun" class="form-select form-select-sm select2-filter">
              @foreach($tahunTersedia as $t)
                <option value="{{ $t }}" {{ $tahun == $t ? 'selected' : '' }}>{{ $t }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-md-auto">
            <label class="form-label small fw-bold">Versi</label>
            <select name="versi" class="form-select form-select-sm select2-filter">
              <option value="V5" {{ $versi == 'V5' ? 'selected' : '' }}>e-Katalog V5</option>
              <option value="V6" {{ $versi == 'V6' ? 'selected' : '' }}>e-Katalog V6</option>
            </select>
          </div>

          <div class="col-md">
            <label class="form-label small fw-bold">Satuan Kerja</label>
            <select name="satker" class="form-select form-select-sm select2-filter select2-satker">
              <option value="Semua" {{ $satker == 'Semua' ? 'selected' : '' }}>Semua Satuan Kerja</option>
              @foreach($satkerList as $s)
                <option value="{{ $s }}" {{ $satker == $s ? 'selected' : '' }}>{{ $s }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-md-auto">
            <label class="form-label small fw-bold">Status</label>
            <select name="status" class="form-select form-select-sm select2-filter">
              <option value="Semua" {{ $status == 'Semua' ? 'selected' : '' }}>Semua Status</option>
              <option value="Proses" {{ $status == 'Proses' ? 'selected' : '' }}>Paket Proses</option>
              <option value="Selesai" {{ $status == 'Selesai' ? 'selected' : '' }}>Paket Selesai</option>
            </select>
          </div>

          <div class="col-md-auto">
            <button type="submit" class="btn btn-primary btn-sm px-3">
              <i class="fas fa-filter me-1"></i> Filter
            </button>
          </div>
        </form>
      </div>
    </div>

    {{-- Summary Box --}}
    <div class="row mb-4">
      <div class="col-md-6">
        <div class="card border-0 shadow-sm bg-info text-white h-100">
          <div class="card-body d-flex align-items-center">
            <div class="flex-grow-1">
              <div class="text-uppercase small opacity-75 fw-bold">Total Paket</div>
              <div class="h2 mb-0 fw-bold">{{ number_format($totalPaket, 0, ',', '.') }}</div>
            </div>
            <div class="opacity-50">
              <i class="fas fa-box fa-3x"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card border-0 shadow-sm bg-success text-white h-100">
          <div class="card-body d-flex align-items-center">
            <div class="flex-grow-1">
              <div class="text-uppercase small opacity-75 fw-bold">Total Nilai Transaksi</div>
              <div class="h2 mb-0 fw-bold">Rp {{ number_format($totalNilai, 0, ',', '.') }}</div>
            </div>
            <div class="opacity-50">
              <i class="fas fa-money-bill-wave fa-3x"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Tabel --}}
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-bottom py-3">
        <h3 class="card-title h6 mb-0 fw-bold text-secondary">Rekapitulasi e-Katalog</h3>
      </div>

      <div class="card-body table-responsive">
        <table id="ekatalogTable" class="table table-hover align-middle w-100">
          <thead class="table-light">
            <tr>
              <th class="border-0">No</th>
              <th class="border-0">ID RUP</th>
              <th class="border-0">Satuan Kerja</th>
              <th class="border-0">Nama Paket</th>
              <th class="border-0">Status Paket</th>
              <th class="border-0 text-end">Nilai Kontrak</th>
            </tr>
          </thead>
          <tbody>
            @foreach($data as $item)
              <tr>
                <td>{{ $loop->iteration }}</td>
                <td><span class="badge bg-light text-dark fw-normal border">{{ $item['id_rup'] }}</span></td>
                <td class="small">{{ $item['nama_satker'] }}</td>
                <td class="text-wrap" style="min-width: 250px;">{{ $item['nama_paket'] }}</td>
                <td>
                  @if(str_contains($item['status'], 'Selesai'))
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2">Selesai</span>
                  @else
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2">Proses</span>
                  @endif
                </td>
                <td class="text-end fw-bold text-primary">Rp {{ number_format($item['nilai_kontrak'], 0, ',', '.') }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
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
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
  $(function () {
    $('#ekatalogTable').DataTable({
      scrollX: true,
      autoWidth: false,
      language: {
        search: "Cari:",
        lengthMenu: "Tampilkan _MENU_ entri",
        zeroRecords: "Tidak ditemukan",
        info: "Menampilkan _START_ hingga _END_ dari _TOTAL_ entri",
        infoEmpty: "Tidak ada entri",
        paginate: { previous: "Sebelumnya", next: "Berikutnya" }
      }
    });

    $('.select2-filter').select2({
      placeholder: "Pilih",
      width: 'resolve',
      minimumResultsForSearch: 5
    });

    $('.select2-satker').each(function () {
      $(this).next('.select2-container').addClass('select2-satker');
    });
  });
</script>
@endpush
