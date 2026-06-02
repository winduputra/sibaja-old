@extends('layouts.user')

@push('style')
<style>
    .tracker-card {
        border: 1px solid var(--bs-border-color);
        border-radius: var(--bs-border-radius-lg);
        box-shadow: var(--bs-box-shadow-sm);
    }

    .tracker-value {
        font-family: var(--bs-font-monospace);
        white-space: nowrap;
    }

    .tracker-table th,
    .tracker-table td {
        vertical-align: middle;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
        <div>
            <h2 class="mb-1"><i class="fas fa-chart-pie me-2"></i>PDN/UMK Tracker</h2>
            <p class="text-muted mb-0">Monitoring target RUP PDN dan UKM/UMK serta realisasi RUP lintas tender, non tender, dan e-purchasing.</p>
        </div>

        <form method="GET" action="{{ route('monitoring.pdn-umk-tracker') }}" class="d-flex align-items-end gap-2">
            <div>
                <label for="tahun" class="form-label fw-semibold">Tahun Anggaran</label>
                <select name="tahun" id="tahun" class="form-select" onchange="this.form.submit()">
                    @foreach($availableYears as $availableYear)
                        <option value="{{ $availableYear }}" {{ (int) $availableYear === (int) $year ? 'selected' : '' }}>{{ $availableYear }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Tampilkan</button>
        </form>
    </div>

    @if($availableYears->isEmpty())
        <div class="alert alert-warning">Belum ada data RUP penyedia yang sesuai filter monitoring.</div>
    @endif

    <div class="row g-4">
        @foreach($buckets as $bucket)
            <div class="col-12">
                <div class="card tracker-card">
                    <div class="card-header bg-white d-flex flex-column flex-md-row justify-content-between gap-2">
                        <div>
                            <h5 class="mb-1">{{ $bucket['label'] }}</h5>
                            <span class="text-muted small">RUP dapat masuk ke PDN dan UKM/UMK sekaligus jika statusnya memenuhi keduanya.</span>
                        </div>
                        <div class="d-flex gap-2">
                            <span class="badge {{ $bucket['valid']['nilai'] ? 'text-bg-success' : 'text-bg-warning' }}">
                                Nilai {{ $bucket['valid']['nilai'] ? 'Match' : 'Selisih' }}
                            </span>
                            <span class="badge {{ $bucket['valid']['paket'] ? 'text-bg-success' : 'text-bg-warning' }}">
                                Paket {{ $bucket['valid']['paket'] ? 'Match' : 'Selisih' }}
                            </span>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted small mb-1">RUP Nilai</div>
                                    <div class="fw-bold tracker-value">Rp {{ number_format($bucket['rup']['nilai'], 0, ',', '.') }}</div>
                                    <div class="text-muted small">Target Rp {{ number_format($bucket['target']['nilai'], 0, ',', '.') }}</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted small mb-1">RUP Paket</div>
                                    <div class="fw-bold tracker-value">{{ number_format($bucket['rup']['paket'], 0, ',', '.') }}</div>
                                    <div class="text-muted small">Target {{ number_format($bucket['target']['paket'], 0, ',', '.') }} paket</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted small mb-1">Realisasi Nilai dari RUP</div>
                                    <div class="fw-bold tracker-value">Rp {{ number_format($bucket['realized']['nilai'], 0, ',', '.') }}</div>
                                    <div class="text-muted small">{{ number_format($bucket['realized']['persen_nilai'], 2, ',', '.') }}% dari RUP</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted small mb-1">Realisasi Paket dari RUP</div>
                                    <div class="fw-bold tracker-value">{{ number_format($bucket['realized']['paket'], 0, ',', '.') }}</div>
                                    <div class="text-muted small">{{ number_format($bucket['realized']['persen_paket'], 2, ',', '.') }}% dari RUP</div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive mb-4">
                            <table class="table table-bordered table-striped tracker-table mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Validasi Target</th>
                                        <th class="text-end">RUP</th>
                                        <th class="text-end">Target</th>
                                        <th class="text-end">Delta</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Nilai</td>
                                        <td class="text-end tracker-value">Rp {{ number_format($bucket['rup']['nilai'], 0, ',', '.') }}</td>
                                        <td class="text-end tracker-value">Rp {{ number_format($bucket['target']['nilai'], 0, ',', '.') }}</td>
                                        <td class="text-end tracker-value">Rp {{ number_format($bucket['delta']['nilai'], 0, ',', '.') }}</td>
                                        <td class="text-center">
                                            <span class="badge {{ $bucket['valid']['nilai'] ? 'text-bg-success' : 'text-bg-warning' }}">
                                                {{ $bucket['valid']['nilai'] ? 'Valid / Match' : 'Selisih' }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Paket</td>
                                        <td class="text-end tracker-value">{{ number_format($bucket['rup']['paket'], 0, ',', '.') }}</td>
                                        <td class="text-end tracker-value">{{ number_format($bucket['target']['paket'], 0, ',', '.') }}</td>
                                        <td class="text-end tracker-value">{{ number_format($bucket['delta']['paket'], 0, ',', '.') }}</td>
                                        <td class="text-center">
                                            <span class="badge {{ $bucket['valid']['paket'] ? 'text-bg-success' : 'text-bg-warning' }}">
                                                {{ $bucket['valid']['paket'] ? 'Valid / Match' : 'Selisih' }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <h6 class="mb-3">Realisasi per Metode</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover tracker-table mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Metode</th>
                                        <th class="text-end">Paket Unik Match RUP</th>
                                        <th class="text-end">Nilai Pagu RUP Match</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($bucket['methods'] as $method)
                                        <tr>
                                            <td>{{ $method['label'] }}</td>
                                            <td class="text-end tracker-value">{{ number_format($method['paket'], 0, ',', '.') }}</td>
                                            <td class="text-end tracker-value">Rp {{ number_format($method['nilai'], 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light fw-semibold">
                                    <tr>
                                        <td>Total Unik Lintas Metode</td>
                                        <td class="text-end tracker-value">{{ number_format($bucket['realized']['paket'], 0, ',', '.') }}</td>
                                        <td class="text-end tracker-value">Rp {{ number_format($bucket['realized']['nilai'], 0, ',', '.') }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
