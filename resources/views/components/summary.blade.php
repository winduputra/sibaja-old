@php
  $filters = [
    'year' => request('year'),
    'kd_satker' => request('kd_satker'),
    'code' => request('code'),
    'name' => request('name'),
    'category' => request('category'),
    'status_tender' => request('status_tender'),
    'status_nontender' => request('status_nontender'),
  ];
@endphp

<div class="row g-4 mb-4">
    {{-- Tender --}}
    <div class="col-lg-3 col-md-6">
        <div class="card border-0 shadow-sm overflow-hidden h-100" style="background: linear-gradient(135deg, #17a2b8 0%, #117a8b 100%); color: white;">
            <div class="card-body p-4 position-relative">
                <div class="position-absolute top-0 end-0 p-3 opacity-25">
                    <i class="fas fa-list-ol fa-4x"></i>
                </div>
                <h3 class="fw-bold mb-1">{{ number_format(getTenderCount($filters), 0, ',', '.') }}</h3>
                <p class="mb-0">Paket Tender</p>
            </div>
            <a href="{{ route('tender.list') }}" class="card-footer bg-black bg-opacity-10 text-white text-decoration-none py-2 text-center small">
                Lihat Detail <i class="fas fa-arrow-circle-right ms-1"></i>
            </a>
        </div>
    </div>

    {{-- Non Tender --}}
    <div class="col-lg-3 col-md-6">
        <div class="card border-0 shadow-sm overflow-hidden h-100" style="background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%); color: #212529;">
            <div class="card-body p-4 position-relative">
                <div class="position-absolute top-0 end-0 p-3 opacity-25">
                    <i class="fas fa-list-ul fa-4x"></i>
                </div>
                <h3 class="fw-bold mb-1">{{ number_format(getNonTenderCount($filters), 0, ',', '.') }}</h3>
                <p class="mb-0">Paket Non Tender</p>
            </div>
            <a href="{{ route('non-tender.list') }}" class="card-footer bg-black bg-opacity-10 text-dark text-decoration-none py-2 text-center small font-weight-bold">
                Lihat Detail <i class="fas fa-arrow-circle-right ms-1"></i>
            </a>
        </div>
    </div>

    {{-- e-Katalog --}}
    <div class="col-lg-3 col-md-6">
        <div class="card border-0 shadow-sm overflow-hidden h-100" style="background: linear-gradient(135deg, #28a745 0%, #218838 100%); color: white;">
            <div class="card-body p-4 position-relative">
                <div class="position-absolute top-0 end-0 p-3 opacity-25">
                    <i class="fas fa-shopping-cart fa-4x"></i>
                </div>
                <h3 class="fw-bold mb-1">{{ number_format(getEkatalogCount($filters), 0, ',', '.') }}</h3>
                <p class="mb-0">E-Katalog</p>
            </div>
            <a href="{{ route('report.ekatalog') }}" class="card-footer bg-black bg-opacity-10 text-white text-decoration-none py-2 text-center small">
                Lihat Detail <i class="fas fa-arrow-circle-right ms-1"></i>
            </a>
        </div>
    </div>

    {{-- Toko Daring --}}
    <div class="col-lg-3 col-md-6">
        <div class="card border-0 shadow-sm overflow-hidden h-100" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white;">
            <div class="card-body p-4 position-relative">
                <div class="position-absolute top-0 end-0 p-3 opacity-25">
                    <i class="fas fa-store fa-4x"></i>
                </div>
                <h3 class="fw-bold mb-1">{{ number_format(getBelaCount($filters), 0, ',', '.') }}</h3>
                <p class="mb-0">Toko Daring</p>
            </div>
            <div class="card-footer bg-black bg-opacity-10 d-flex justify-content-between py-2 px-3 small">
                <a href="{{ route('report.tokodaring') }}" class="text-white text-decoration-none">
                    Lihat Detail <i class="fas fa-arrow-circle-right ms-1"></i>
                </a>
                @if (Auth::user()->role_id == 1)
                    <a href="{{ route('bela.update') }}" class="text-warning text-decoration-none fw-bold">
                        Update Data
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
