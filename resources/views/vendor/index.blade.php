@extends('layouts.user')

@push('style')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
@endpush

@section('content')
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0">Vendor</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item active">Vendor</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">

    @include('components.summary')
      
    <div class="row ">
      <div class="col-12">

        <div class="card">
          <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
              <h3 class="card-title">Data Vendor</h3>
              <div>
                <a href="#" class="btn btn-sm bg-primary" target="_blank">Download</a>
              </div>
            </div>
          </div>
          <div class="card-body">
            <table id="vendor" class="table table-head-fixed table-hover text-nowrap">
              <thead>
                <tr>
                  <th>Kode</th>
                  <th>Nama</th>
                  <th>NPWP</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($data as $item)
                <tr>
                  <td>{{ $item->kd_penyedia }}</td>
                  <td>{{ $item->nama_penyedia }}</td>
                  <td>{{ $item->npwp_penyedia }}</td>
                  <td>
                    <a href="{{ route('vendor.show', ['code' => $item->kd_penyedia]) }}" class="btn btn-sm btn-primary">
                      <i class="fas fa-eye"></i>
                    </a>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>

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
    $('#vendor').DataTable({
      "paging": true,
      "lengthChange": true,
      "searching": true,
      "ordering": true,
      "info": true,
      "autoWidth": false,
      "responsive": true,
    });
  });
</script>
@endpush
