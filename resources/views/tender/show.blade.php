@extends('layouts.user')

@section('title', 'Detail Tender - ' . $tender->nama_paket)

@section('content')
<div class="container-fluid px-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold text-primary">Detail Tender</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('tender.list') }}">Tender</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $tender->kd_tender }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row g-4">
        <!-- Informasi Umum -->
        <div class="col-lg-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-info-circle me-2 text-primary"></i>Informasi Paket</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 border rounded bg-light">
                                <small class="text-muted d-block mb-1">Nama Paket</small>
                                <span class="fw-bold">{{ $tender->nama_paket }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 border rounded bg-light">
                                <small class="text-muted d-block mb-1">Kode Tender</small>
                                <span class="fw-bold text-primary">{{ $tender->kd_tender }}</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 border rounded bg-light">
                                <small class="text-muted d-block mb-1">Instansi / Unit</small>
                                <span class="fw-bold">{{ $tender->nama_klpd }}</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 border rounded bg-light">
                                <small class="text-muted d-block mb-1">Pagu Anggaran</small>
                                <span class="fw-bold text-success">Rp{{ number_format($tender->pagu, 0, ',', '.') }}</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 border rounded bg-light">
                                <small class="text-muted d-block mb-1">HPS</small>
                                <span class="fw-bold text-danger">Rp{{ number_format($tender->hps, 0, ',', '.') }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 border rounded bg-light">
                                <small class="text-muted d-block mb-1">Jenis Pengadaan</small>
                                <span class="fw-bold">{{ $tender->jenis_pengadaan }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 border rounded bg-light">
                                <small class="text-muted d-block mb-1">Lokasi Pekerjaan</small>
                                <span class="fw-bold">{{ $tender->lokasi_pekerjaan }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tahapan Tender -->
        <div class="col-lg-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-calendar-alt me-2 text-primary"></i>Tahapan Tender</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">No.</th>
                                    <th>Tahapan</th>
                                    <th class="text-center">Tanggal Mulai</th>
                                    <th class="text-center">Tanggal Selesai</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($tender->schedules as $key => $item)
                                <tr>
                                    <td class="ps-4">{{ $key + 1 }}</td>
                                    <td>{{ $item->tahapan }}</td>
                                    <td class="text-center">{{ $item->tanggal_awal }}</td>
                                    <td class="text-center">{{ $item->tanggal_akhir }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Tidak ada data tahapan</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daftar Peserta -->
        <div class="col-lg-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-users me-2 text-primary"></i>Daftar Peserta & Pemenang</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">No.</th>
                                    <th>Nama Penyedia</th>
                                    <th>NPWP</th>
                                    <th class="text-end">Penawaran</th>
                                    <th class="text-center">Status</th>
                                    <th class="pe-4">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($tender->participants as $key => $item)
                                <tr class="{{ $item->pemenang ? 'table-success' : '' }}">
                                    <td class="ps-4">{{ $key + 1 }}</td>
                                    <td>
                                        <div class="fw-bold">{{ $item->nama_penyedia }}</div>
                                        @if($item->pemenang)
                                            <span class="badge bg-success small">Pemenang</span>
                                        @endif
                                    </td>
                                    <td>{{ $item->npwp_penyedia }}</td>
                                    <td class="text-end fw-bold">Rp{{ number_format($item->nilai_penawaran, 0, ',', '.') }}</td>
                                    <td class="text-center">
                                        @if($item->pemenang_terverifikasi == 'Ya')
                                            <i class="fas fa-check-circle text-success" title="Terverifikasi"></i>
                                        @else
                                            <i class="fas fa-clock text-warning" title="Proses"></i>
                                        @endif
                                    </td>
                                    <td class="pe-4 small text-muted">{{ $item->alasan ?: '-' }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Tidak ada data peserta</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
