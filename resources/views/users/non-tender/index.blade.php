@extends('layouts.user')

@section('content')
<div class="container-fluid px-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold text-primary">Daftar Non Tender</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Non Tender</li>
                </ol>
            </nav>
        </div>
    </div>

    @include('components.summary')

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Kode</label>
                    <input id="filter-kode" type="text" placeholder="Cari Kode..." class="form-control" value="{{ request('code') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Nama Paket</label>
                    <input id="filter-nama" type="text" placeholder="Cari Nama Paket..." class="form-control" value="{{ request('name') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Satuan Kerja</label>
                    <select name="kd_satker" id="kd_satker" class="form-select select2" onchange="filterBySatker()">
                        <option value="">Semua Satuan Kerja</option>
                        @foreach ($satkers as $item)
                            <option value="{{ $item->kd_satker_str }}" {{ $satkerCode == $item->kd_satker_str ? 'selected' : '' }}>
                                {{ $item->nama_satker }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Tahun</label>
                    <select name="year" id="year" class="form-select" onchange="filterBySatker()">
                        @foreach ($years as $item)
                            <option value="{{ $item }}" {{ $year == $item ? 'selected' : '' }}>{{ $item }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Status</label>
                    <select name="status_nontender" id="status_nontender" class="form-select" onchange="filterBySatker()">
                        @foreach ($statusList as $key => $val)
                            <option value="{{ $key }}" {{ $status == $key ? 'selected' : '' }}>{{ $val }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    @php
        $urlBase = url()->current()
            . '?year=' . urlencode($year)
            . '&kd_satker=' . urlencode($satkerCode)
            . '&code=' . urlencode($code)
            . '&name=' . urlencode($name)
            . '&status_nontender=' . urlencode($status);
    @endphp

    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ $urlBase }}" class="btn btn-sm {{ !$categoryParam ? 'btn-primary' : 'btn-outline-primary' }}">
                    Semua ({{ $totalFull }})
                </a>
                @foreach ($categories as $key => $value)
                    <a href="{{ $urlBase . '&category=' . urlencode($value) }}" class="btn btn-sm {{ $categoryParam == $value ? 'btn-primary' : 'btn-outline-primary' }}">
                        {{ $value }} ({{ $categoriesCount[$key] }})
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="nontenderTable" class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Kode</th>
                            <th>Nama Paket</th>
                            <th>Status</th>
                            <th>HPS</th>
                            <th>Nilai PDN</th>
                            <th class="pe-4">Nilai UMK</th>
                        </tr>
                    </thead>
                    <tbody>
                        @include('components.tables.nontender-rows', ['data' => $data])
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-0 py-3">
            <div class="pagination-container d-flex justify-content-center">
                {{ $data->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>
</div>
@endsection

@push('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container--default .select2-selection--single {
        height: 38px;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px;
        padding-left: 12px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
    .btn-sm {
        border-radius: 20px;
        padding: 5px 15px;
    }
    #nontenderTable thead th {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        color: #6c757d;
        padding-top: 1rem;
        padding-bottom: 1rem;
    }
</style>
@endpush

@push('js')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(function () {
    $('.select2').select2({
        theme: 'default',
        width: '100%'
    });

    let categoryParam = @json($categoryParam);

    $('#filter-kode, #filter-nama').on('keyup', function () {
        searchNonTender(1);
    });

    $(document).on('click', '.pagination-container a', function(e) {
        e.preventDefault();
        let url = new URL($(this).attr('href'), window.location.origin);
        let page = url.searchParams.get('page') || 1;
        searchNonTender(page);
    });

    function searchNonTender(page = 1) {
        const code = $('#filter-kode').val().trim();
        const name = $('#filter-nama').val().trim();
        const kd_satker = $('#kd_satker').val();
        const year = $('#year').val();
        const status_nontender = $('#status_nontender').val();

        $.ajax({
            url: "{{ route('non-tender.search') }}",
            data: {
                code, name, kd_satker, year,
                category: categoryParam,
                status_nontender,
                page
            },
            success: function (response) {
                $('#nontenderTable tbody').html(response.html);
                if (response.lastPage > 1) {
                    $('.pagination-container').html(response.pagination).show();
                } else {
                    $('.pagination-container').hide();
                }
            },
            error: function () {
                $('#nontenderTable tbody').html('<tr><td colspan="6" class="text-center text-danger">Gagal memuat data</td></tr>');
                $('.pagination-container').hide();
            }
        });
    }

    window.filterBySatker = function() {
        const satker = $('#kd_satker').val();
        const year = $('#year').val();
        const status = $('#status_nontender').val();
        const url = new URL(window.location.href.split('?')[0]);
        if (satker) url.searchParams.set('kd_satker', satker);
        if (year) url.searchParams.set('year', year);
        if (status) url.searchParams.set('status_nontender', status);
        else url.searchParams.delete('status_nontender');
        window.location.href = url.toString();
    }
});
</script>
@endpush
