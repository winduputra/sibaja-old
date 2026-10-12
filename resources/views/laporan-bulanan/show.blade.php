@extends('layouts.user')

@section('title', 'Laporan Bulanan ' . $report['sheetTitle'])

@push('style')
<style>
    .laporan-bulanan-page {
        --laporan-brand-primary: #2b529a;
        --laporan-brand-deep: #0f2c74;
        --laporan-surface: #ffffff;
        --laporan-surface-soft: #f5f8fd;
        --laporan-border: #dddddd;
        --laporan-text: #1c1c1c;
        --laporan-muted: #666666;
        --laporan-font-primary: 'Public Sans', sans-serif;
        --laporan-katalog-table-min-width: 64rem;
        --laporan-katalog-organization-min-width: 18rem;
        --laporan-dynamic-table-min-width: 64rem;
        --laporan-ekontrak-table-min-width: 112rem;
        --laporan-dynamic-organization-min-width: 20rem;
        --laporan-focus: rgba(43, 82, 154, 0.3);
        --laporan-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        color: var(--laporan-text);
        max-width: 100%;
        overflow-x: hidden;
        padding: 1rem 0 1.5rem;
    }

    .laporan-bulanan-panel,
    .laporan-bulanan-sheet,
    .laporan-bulanan-empty {
        background: var(--laporan-surface);
        border: 1px solid var(--laporan-border);
        border-radius: 0.5rem;
        box-shadow: var(--laporan-shadow);
    }

    .laporan-bulanan-panel {
        align-items: end;
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        justify-content: space-between;
        margin-bottom: 1rem;
        padding: 1rem;
    }

    .laporan-bulanan-title {
        color: var(--laporan-brand-deep);
        font-size: 1.25rem;
        font-weight: 700;
        line-height: 1.25;
        margin: 0;
    }

    .laporan-bulanan-meta {
        color: var(--laporan-muted);
        font-size: 0.875rem;
        margin: 0.25rem 0 0;
    }

    .laporan-bulanan-filter {
        align-items: end;
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .laporan-bulanan-sheet {
        max-width: 100%;
        overflow: hidden;
    }

    .laporan-bulanan-scroll {
        max-width: 100%;
        overflow-x: auto;
        overflow-y: visible;
        padding: 0.5rem;
        scrollbar-color: var(--laporan-brand-primary) var(--laporan-surface-soft);
    }

    .laporan-bulanan-scroll:focus {
        box-shadow: 0 0 0 0.2rem var(--laporan-focus);
        outline: 0;
    }

    .laporan-bulanan-scroll::after {
        color: var(--laporan-muted);
        content: 'Geser tabel ke samping untuk melihat seluruh kolom';
        display: block;
        font-size: 0.75rem;
        padding-top: 0.5rem;
        text-align: right;
    }

    .laporan-bulanan-katalog-scroll::after,
    .laporan-bulanan-dynamic-scroll::after {
        content: none;
        display: none;
    }

    .laporan-bulanan-table {
        background: var(--laporan-surface);
        border-collapse: collapse;
        color: #111111;
        font-size: 0.75rem;
        line-height: 1.2;
        table-layout: fixed;
        width: max-content;
    }

    .laporan-bulanan-table caption {
        caption-side: top;
        color: var(--laporan-brand-deep);
        font-size: 1rem;
        font-weight: 700;
        padding: 0 0 0.5rem;
        text-align: left;
    }

    .laporan-bulanan-table td {
        background-clip: padding-box;
        min-width: 2.5rem;
        overflow-wrap: anywhere;
        padding: 0.2rem 0.35rem;
        vertical-align: middle;
        white-space: pre-wrap;
    }

    .laporan-bulanan-katalog-table,
    .laporan-bulanan-dynamic-table {
        color: var(--laporan-text);
        font-family: var(--laporan-font-primary);
        font-size: 0.875rem;
    }

    .laporan-bulanan-katalog-table {
        min-width: var(--laporan-katalog-table-min-width);
    }

    .laporan-bulanan-dynamic-table {
        min-width: var(--laporan-dynamic-table-min-width);
    }

    .laporan-bulanan-ekontrak-table {
        min-width: var(--laporan-ekontrak-table-min-width);
    }

    .laporan-bulanan-katalog-caption,
    .laporan-bulanan-dynamic-caption {
        color: var(--laporan-brand-deep);
        font-size: 1rem;
        font-weight: 700;
        margin: 0;
        padding: 1rem 1rem 0.25rem;
    }

    .laporan-bulanan-dynamic-context {
        color: var(--laporan-muted);
        font-family: var(--laporan-font-primary);
        font-size: 0.8125rem;
        margin: 0;
        padding: 0 1rem 0.75rem;
    }

    .laporan-bulanan-katalog-scroll-hint,
    .laporan-bulanan-dynamic-scroll-hint {
        color: var(--laporan-muted);
        font-family: var(--laporan-font-primary);
        font-size: 0.75rem;
        margin: 0;
        padding: 0 1rem 1rem;
        text-align: right;
    }

    .laporan-bulanan-katalog-table th,
    .laporan-bulanan-katalog-table td,
    .laporan-bulanan-dynamic-table th,
    .laporan-bulanan-dynamic-table td {
        border-color: var(--laporan-border);
        padding: 0.75rem;
        vertical-align: middle;
    }

    .laporan-bulanan-katalog-table thead th,
    .laporan-bulanan-dynamic-table thead th,
    .laporan-bulanan-dynamic-table tfoot th,
    .laporan-bulanan-dynamic-table tfoot td {
        background: var(--laporan-surface-soft);
        color: var(--laporan-brand-deep);
        font-weight: 700;
    }

    .laporan-bulanan-katalog-organization,
    .laporan-bulanan-dynamic-organization {
        color: var(--laporan-text);
        font-weight: 600;
        white-space: normal;
    }

    .laporan-bulanan-katalog-organization {
        min-width: var(--laporan-katalog-organization-min-width);
    }

    .laporan-bulanan-dynamic-organization {
        min-width: var(--laporan-dynamic-organization-min-width);
    }

    .laporan-bulanan-katalog-number,
    .laporan-bulanan-dynamic-number {
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    .laporan-bulanan-empty {
        padding: 1.25rem;
        text-align: center;
    }

    .laporan-bulanan-empty strong {
        color: var(--laporan-brand-deep);
        display: block;
        margin-bottom: 0.25rem;
    }

    @media (max-width: 767.98px) {
        .laporan-bulanan-panel {
            align-items: stretch;
        }

        .laporan-bulanan-filter {
            width: 100%;
        }
    }
</style>
@endpush

@section('content')
@php
    $isDynamicKatalogView = $isDynamicKatalog ?? false;
    $isDynamicEKontrakView = $isDynamicEKontrak ?? false;
    $isDynamicPencatatanNonTenderView = $isDynamicPencatatanNonTender ?? false;
    $isDynamicPencatatanSwakelolaView = $isDynamicPencatatanSwakelola ?? false;
    $isDynamicRupView = $isDynamicRup ?? false;
    $yearOptions = collect(range(2024, 2028))
        ->push((int) $filters['tahun'])
        ->unique()
        ->sort()
        ->values();

    $formatMoney = fn ($value): string => 'Rp' . number_format((float) $value, 2, ',', '.');
    $formatPercentage = fn ($value): string => number_format((float) $value, 2, ',', '.') . '%';
    $formatInteger = fn ($value): string => number_format((int) $value, 0, ',', '.');

    if (! $isDynamicKatalogView && ! $isDynamicEKontrakView && ! $isDynamicPencatatanNonTenderView && ! $isDynamicPencatatanSwakelolaView && ! $isDynamicRupView) {
        $mergeByCoordinate = [];
        $coveredCoordinates = [];

        $columnIndex = function (string $column): int {
            $index = 0;
            foreach (str_split(strtoupper($column)) as $letter) {
                $index = ($index * 26) + (ord($letter) - 64);
            }

            return $index;
        };

        $columnName = function (int $index): string {
            $name = '';
            while ($index > 0) {
                $index--;
                $name = chr(65 + ($index % 26)) . $name;
                $index = intdiv($index, 26);
            }

            return $name;
        };

        $coordinateParts = function (string $coordinate) use ($columnIndex): array {
            preg_match('/^([A-Z]+)(\d+)$/', strtoupper($coordinate), $matches);

            return [
                'column' => $matches[1] ?? 'A',
                'columnIndex' => $columnIndex($matches[1] ?? 'A'),
                'row' => (int) ($matches[2] ?? 1),
            ];
        };

        foreach ($report['merges'] as $merge) {
            $mergeByCoordinate[$merge['coordinate']] = $merge;

            if (preg_match('/^([A-Z]+)(\d+):([A-Z]+)(\d+)$/', strtoupper($merge['range']), $matches) !== 1) {
                continue;
            }

            $startColumn = $columnIndex($matches[1]);
            $startRow = (int) $matches[2];
            $endColumn = $columnIndex($matches[3]);
            $endRow = (int) $matches[4];

            for ($rowNumber = $startRow; $rowNumber <= $endRow; $rowNumber++) {
                for ($columnNumber = $startColumn; $columnNumber <= $endColumn; $columnNumber++) {
                    $coordinate = $columnName($columnNumber) . $rowNumber;
                    if ($coordinate !== $merge['coordinate']) {
                        $coveredCoordinates[$coordinate] = true;
                    }
                }
            }
        }

        $validColor = fn (?string $color): bool => is_string($color) && preg_match('/^[A-Fa-f0-9]{6}$/', $color) === 1;
        $fontMap = [
            'Calibri' => 'Calibri, sans-serif',
            'Arial' => 'Arial, sans-serif',
            'Roboto' => 'Roboto, sans-serif',
        ];

        $columnStyles = function (array $column): string {
            if ($column['hidden']) {
                return 'display: none; width: 0; min-width: 0;';
            }

            $width = is_numeric($column['width']) && (float) $column['width'] > 0
                ? max(36, min(420, (float) $column['width'] * 7))
                : 64;

            return 'width: ' . number_format($width, 2, '.', '') . 'px;';
        };

        $rowStyles = function (array $row): string {
            if ($row['hidden']) {
                return 'display: none;';
            }

            if (! is_numeric($row['height']) || (float) $row['height'] <= 0) {
                return '';
            }

            $height = max(16, min(180, (float) $row['height'] * 1.333));

            return 'height: ' . number_format($height, 2, '.', '') . 'px;';
        };

        $cellStyles = function (array $cell, bool $hiddenColumn) use ($validColor, $fontMap): string {
            if ($hiddenColumn) {
                return 'display: none;';
            }

            $style = $cell['style'];
            $styles = [];
            $font = $style['font'] ?? [];
            $fill = $style['fill'] ?? [];
            $border = $style['border'] ?? [];
            $alignment = $style['alignment'] ?? [];
            $borderMap = [
                'hair' => '1px solid',
                'thin' => '1px solid',
                'medium' => '2px solid',
                'thick' => '3px solid',
                'dashed' => '1px dashed',
                'dotted' => '1px dotted',
                'dashDot' => '1px dashed',
                'dashDotDot' => '1px dotted',
                'double' => '3px double',
            ];
            $alignmentMap = [
                'left' => 'left',
                'center' => 'center',
                'right' => 'right',
                'justify' => 'justify',
                'general' => 'left',
            ];
            $verticalMap = [
                'top' => 'top',
                'center' => 'middle',
                'bottom' => 'bottom',
            ];

            if (($font['bold'] ?? false) === true) {
                $styles[] = 'font-weight: 700';
            }

            if (($font['italic'] ?? false) === true) {
                $styles[] = 'font-style: italic';
            }

            if (is_numeric($font['size'] ?? null)) {
                $fontSize = max(8, min(18, (float) $font['size']));
                $styles[] = 'font-size: ' . number_format($fontSize, 2, '.', '') . 'pt';
            }

            $fontName = $font['name'] ?? null;
            if (is_string($fontName) && isset($fontMap[$fontName])) {
                $styles[] = 'font-family: ' . $fontMap[$fontName];
            }

            if ($validColor($font['color'] ?? null) && strtoupper($font['color']) !== '000000') {
                $styles[] = 'color: #' . strtoupper($font['color']);
            }

            if (($fill['type'] ?? 'none') !== 'none' && $validColor($fill['color'] ?? null)) {
                $styles[] = 'background-color: #' . strtoupper($fill['color']);
            }

            foreach (['top', 'right', 'bottom', 'left'] as $side) {
                $borderSide = $border[$side] ?? [];
                $borderStyle = $borderSide['style'] ?? 'none';
                if (isset($borderMap[$borderStyle])) {
                    $borderColor = $validColor($borderSide['color'] ?? null) ? strtoupper($borderSide['color']) : '000000';
                    $styles[] = 'border-' . $side . ': ' . $borderMap[$borderStyle] . ' #' . $borderColor;
                }
            }

            $horizontal = $alignment['horizontal'] ?? 'general';
            if (isset($alignmentMap[$horizontal])) {
                $styles[] = 'text-align: ' . $alignmentMap[$horizontal];
            }

            $vertical = $alignment['vertical'] ?? 'bottom';
            if (isset($verticalMap[$vertical])) {
                $styles[] = 'vertical-align: ' . $verticalMap[$vertical];
            }

            $styles[] = ($style['wrapText'] ?? false) ? 'white-space: pre-wrap' : 'white-space: nowrap';

            return implode('; ', $styles) . ';';
        };
    }
@endphp

<div class="laporan-bulanan-page">
    <div class="laporan-bulanan-panel">
        <div>
            <h1 class="laporan-bulanan-title">Laporan Bulanan {{ $report['sheetTitle'] }}</h1>
            <p class="laporan-bulanan-meta">{{ $report['bulan'] }} {{ $report['tahun'] }}@if (! $isDynamicKatalogView && ! $isDynamicEKontrakView && ! $isDynamicPencatatanNonTenderView && ! $isDynamicPencatatanSwakelolaView && ! $isDynamicRupView && ($report['range'] ?? null)) · Rentang {{ $report['range'] }}@endif</p>
        </div>

        <form method="GET" action="{{ route('laporan-bulanan.show', ['sheet' => $report['sheetSlug']]) }}" class="laporan-bulanan-filter" aria-label="Filter Laporan Bulanan">
            <div>
                <label for="tahun" class="form-label mb-1 fw-semibold">Tahun</label>
                <select id="tahun" name="tahun" class="form-select form-select-sm">
                    @foreach ($yearOptions as $yearOption)
                        <option value="{{ $yearOption }}" {{ (int) $filters['tahun'] === (int) $yearOption ? 'selected' : '' }}>{{ $yearOption }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="bulan" class="form-label mb-1 fw-semibold">Bulan</label>
                <select id="bulan" name="bulan" class="form-select form-select-sm">
                    @foreach ($filters['months'] as $month)
                        <option value="{{ $month }}" {{ $filters['bulan'] === $month ? 'selected' : '' }}>{{ $month }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-sm shadow-sm">Tampilkan</button>
        </form>
    </div>

    @if ($isDynamicRupView)
        <section class="laporan-bulanan-sheet" aria-labelledby="laporan-bulanan-rup-heading">
            <h2 id="laporan-bulanan-rup-heading" class="laporan-bulanan-dynamic-caption">Rekapitulasi {{ $report['sheetTitle'] }} · {{ $report['selectedPeriodLabel'] }}</h2>
            <div class="laporan-bulanan-scroll laporan-bulanan-dynamic-scroll" role="region" aria-label="Tabel Rekapitulasi {{ $report['sheetTitle'] }} periode {{ $report['selectedPeriodLabel'] }}" tabindex="0">
                <table class="table table-sm table-bordered laporan-bulanan-dynamic-table mb-0">
                    <caption class="visually-hidden">Rekapitulasi {{ $report['sheetTitle'] }} · {{ $report['selectedPeriodLabel'] }}</caption>
                    <thead>
                        <tr>
                            <th scope="col" rowspan="2" class="text-center">No</th>
                            <th scope="col" rowspan="2">Satuan Kerja</th>
                            <th scope="col" rowspan="2" class="text-end">Belanja Pengadaan</th>
                            <th scope="colgroup" colspan="3" class="text-center">Penyedia</th>
                            <th scope="colgroup" colspan="3" class="text-center">Swakelola</th>
                            <th scope="colgroup" colspan="3" class="text-center">Total</th>
                        </tr>
                        <tr>
                            <th scope="col" class="text-end">Paket</th>
                            <th scope="col" class="text-end">Pagu</th>
                            <th scope="col" class="text-end">%</th>
                            <th scope="col" class="text-end">Paket</th>
                            <th scope="col" class="text-end">Pagu</th>
                            <th scope="col" class="text-end">%</th>
                            <th scope="col" class="text-end">Paket</th>
                            <th scope="col" class="text-end">Pagu</th>
                            <th scope="col" class="text-end">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($report['rows'] as $row)
                            <tr>
                                <td class="text-center laporan-bulanan-dynamic-number">{{ $formatInteger($row['number']) }}</td>
                                <th scope="row" class="laporan-bulanan-dynamic-organization">{{ $row['organizationName'] }}</th>
                                <td class="text-end laporan-bulanan-dynamic-number">{{ $formatMoney($row['procurementBudget']) }}</td>
                                <td class="text-end laporan-bulanan-dynamic-number">{{ $formatInteger($row['providerPackageCount']) }}</td>
                                <td class="text-end laporan-bulanan-dynamic-number">{{ $formatMoney($row['providerBudget']) }}</td>
                                <td class="text-end laporan-bulanan-dynamic-number">{{ $formatPercentage($row['providerPercentage']) }}</td>
                                <td class="text-end laporan-bulanan-dynamic-number">{{ $formatInteger($row['selfManagedPackageCount']) }}</td>
                                <td class="text-end laporan-bulanan-dynamic-number">{{ $formatMoney($row['selfManagedBudget']) }}</td>
                                <td class="text-end laporan-bulanan-dynamic-number">{{ $formatPercentage($row['selfManagedPercentage']) }}</td>
                                <td class="text-end laporan-bulanan-dynamic-number">{{ $formatInteger($row['totalPackageCount']) }}</td>
                                <td class="text-end laporan-bulanan-dynamic-number">{{ $formatMoney($row['totalBudget']) }}</td>
                                <td class="text-end laporan-bulanan-dynamic-number">{{ $formatPercentage($row['totalPercentage']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="py-4 text-center text-muted">Tidak ada data {{ $report['sheetTitle'] }} untuk periode {{ $report['selectedPeriodLabel'] }}.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th scope="row" colspan="2" class="text-center">TOTAL</th>
                            <td class="text-end laporan-bulanan-dynamic-number">{{ $formatMoney($report['summary']['procurementBudget']) }}</td>
                            <td class="text-end laporan-bulanan-dynamic-number">{{ $formatInteger($report['summary']['providerPackageCount']) }}</td>
                            <td class="text-end laporan-bulanan-dynamic-number">{{ $formatMoney($report['summary']['providerBudget']) }}</td>
                            <td class="text-end laporan-bulanan-dynamic-number">{{ $formatPercentage($report['summary']['providerPercentage']) }}</td>
                            <td class="text-end laporan-bulanan-dynamic-number">{{ $formatInteger($report['summary']['selfManagedPackageCount']) }}</td>
                            <td class="text-end laporan-bulanan-dynamic-number">{{ $formatMoney($report['summary']['selfManagedBudget']) }}</td>
                            <td class="text-end laporan-bulanan-dynamic-number">{{ $formatPercentage($report['summary']['selfManagedPercentage']) }}</td>
                            <td class="text-end laporan-bulanan-dynamic-number">{{ $formatInteger($report['summary']['totalPackageCount']) }}</td>
                            <td class="text-end laporan-bulanan-dynamic-number">{{ $formatMoney($report['summary']['totalBudget']) }}</td>
                            <td class="text-end laporan-bulanan-dynamic-number">{{ $formatPercentage($report['summary']['totalPercentage']) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <p class="laporan-bulanan-dynamic-scroll-hint">Geser tabel ke samping untuk melihat seluruh kolom</p>
        </section>
    @elseif ($isDynamicKatalogView)
        <section class="laporan-bulanan-sheet" aria-labelledby="laporan-bulanan-katalog-heading">
            <h2 id="laporan-bulanan-katalog-heading" class="laporan-bulanan-katalog-caption">Rekapitulasi Katalog Elektronik · {{ $report['selectedPeriodLabel'] }}</h2>
            <div class="laporan-bulanan-scroll laporan-bulanan-katalog-scroll" role="region" aria-label="Tabel Rekapitulasi Katalog Elektronik periode {{ $report['selectedPeriodLabel'] }}" tabindex="0">
                <table class="table table-sm table-bordered laporan-bulanan-katalog-table mb-0">
                    <caption class="visually-hidden">Rekapitulasi Katalog Elektronik · {{ $report['selectedPeriodLabel'] }}</caption>
                    <thead>
                        <tr>
                            <th scope="col" class="text-center">No</th>
                            <th scope="col">Perangkat Daerah</th>
                            <th scope="col" class="text-end">Pagu Perencanaan E-Purchasing</th>
                            <th scope="col" class="text-end">Nilai Transaksi {{ $report['priorPeriodLabel'] }}</th>
                            <th scope="col" class="text-end">Nilai Transaksi {{ $report['selectedPeriodLabel'] }}</th>
                            <th scope="col" class="text-end">Nilai Transaksi Kumulatif s.d {{ $report['selectedPeriodLabel'] }}</th>
                            <th scope="col" class="text-end">Persentase Kumulatif / Pagu Perencanaan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($report['rows'] as $row)
                            <tr>
                                <td class="text-center">{{ $row['number'] }}</td>
                                <th scope="row" class="laporan-bulanan-katalog-organization">{{ $row['organizationName'] }}</th>
                                <td class="text-end laporan-bulanan-katalog-number">{{ $formatMoney($row['planningBudget']) }}</td>
                                <td class="text-end laporan-bulanan-katalog-number">{{ $formatMoney($row['priorAmount']) }}</td>
                                <td class="text-end laporan-bulanan-katalog-number">{{ $formatMoney($row['selectedAmount']) }}</td>
                                <td class="text-end laporan-bulanan-katalog-number">{{ $formatMoney($row['cumulativeAmount']) }}</td>
                                <td class="text-end laporan-bulanan-katalog-number">{{ $formatPercentage($row['percentage']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-4 text-center text-muted">Tidak ada data Katalog Elektronik untuk periode {{ $report['selectedPeriodLabel'] }}.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="laporan-bulanan-katalog-scroll-hint">Geser tabel ke samping untuk melihat seluruh kolom</p>
        </section>
    @elseif ($isDynamicEKontrakView)
        @php
            $eKontrakMilestones = [
                'sppbj' => 'SPPBJ',
                'kontrak' => 'Kontrak',
                'spmkSpp' => 'SPMK/SPP',
                'bap' => 'BAP',
                'bast' => 'BAST',
            ];
            $eKontrakPeriods = [
                'prior' => $report['priorPeriodLabel'],
                'selected' => $report['selectedPeriodLabel'],
                'cumulative' => 'Kumulatif s.d ' . $report['selectedPeriodLabel'],
            ];
        @endphp
        <section class="laporan-bulanan-sheet" aria-labelledby="laporan-bulanan-ekontrak-heading">
            <h2 id="laporan-bulanan-ekontrak-heading" class="laporan-bulanan-dynamic-caption">Rekapitulasi {{ $report['sheetTitle'] }} · {{ $report['selectedPeriodLabel'] }}</h2>
            <div class="laporan-bulanan-scroll laporan-bulanan-dynamic-scroll" role="region" aria-label="Tabel Rekapitulasi {{ $report['sheetTitle'] }} periode {{ $report['selectedPeriodLabel'] }}" tabindex="0">
                <table class="table table-sm table-bordered laporan-bulanan-dynamic-table laporan-bulanan-ekontrak-table mb-0">
                    <caption class="visually-hidden">Rekapitulasi {{ $report['sheetTitle'] }} · {{ $report['selectedPeriodLabel'] }}</caption>
                    <thead>
                        <tr>
                            <th scope="col" rowspan="2" class="text-center">No</th>
                            <th scope="col" rowspan="2">Perangkat Daerah</th>
                            <th scope="col" rowspan="2" class="text-end">Paket Perencanaan</th>
                            <th scope="col" rowspan="2" class="text-end">Jumlah Paket Terealisasi</th>
                            @foreach ($eKontrakPeriods as $periodLabel)
                                <th scope="colgroup" colspan="5" class="text-center">{{ $periodLabel }}</th>
                            @endforeach
                        </tr>
                        <tr>
                            @foreach ($eKontrakPeriods as $periodLabel)
                                @foreach ($eKontrakMilestones as $milestoneLabel)
                                    <th scope="col" class="text-end">{{ $milestoneLabel }}</th>
                                @endforeach
                            @endforeach
                        </tr>
                        <tr>
                            <th scope="col" class="text-center">1</th>
                            <th scope="col" class="text-center">2</th>
                            <th scope="col" class="text-center">3</th>
                            <th scope="col" class="text-center">4</th>
                            <th scope="col" colspan="5" class="text-center">5</th>
                            <th scope="col" colspan="5" class="text-center">6</th>
                            <th scope="col" colspan="5" class="text-center">7</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($report['rows'] as $row)
                            <tr>
                                <td class="text-center laporan-bulanan-dynamic-number">{{ $formatInteger($row['number']) }}</td>
                                <th scope="row" class="laporan-bulanan-dynamic-organization">{{ $row['organizationName'] }}</th>
                                <td class="text-end laporan-bulanan-dynamic-number">{{ $formatInteger($row['planningPackages']) }}</td>
                                <td class="text-end laporan-bulanan-dynamic-number">{{ $formatInteger($row['realizedPackages']) }}</td>
                                @foreach ($eKontrakPeriods as $periodKey => $periodLabel)
                                    @foreach ($eKontrakMilestones as $milestoneKey => $milestoneLabel)
                                        <td class="text-end laporan-bulanan-dynamic-number">{{ $formatInteger($row[$periodKey][$milestoneKey]) }}</td>
                                    @endforeach
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="19" class="py-4 text-center text-muted">Tidak ada data {{ $report['sheetTitle'] }} untuk periode {{ $report['selectedPeriodLabel'] }}.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th scope="row" colspan="2" class="text-center">TOTAL</th>
                            <td class="text-end laporan-bulanan-dynamic-number">{{ $formatInteger($report['summary']['planningPackages']) }}</td>
                            <td class="text-end laporan-bulanan-dynamic-number">{{ $formatInteger($report['summary']['realizedPackages']) }}</td>
                            @foreach ($eKontrakPeriods as $periodKey => $periodLabel)
                                @foreach ($eKontrakMilestones as $milestoneKey => $milestoneLabel)
                                    <td class="text-end laporan-bulanan-dynamic-number">{{ $formatInteger($report['summary'][$periodKey][$milestoneKey]) }}</td>
                                @endforeach
                            @endforeach
                        </tr>
                    </tfoot>
                </table>
            </div>
            <p class="laporan-bulanan-dynamic-scroll-hint">Geser tabel ke samping untuk melihat seluruh kolom</p>
        </section>
    @elseif ($isDynamicPencatatanNonTenderView || $isDynamicPencatatanSwakelolaView)
        <section class="laporan-bulanan-sheet" aria-labelledby="laporan-bulanan-dynamic-heading">
            <h2 id="laporan-bulanan-dynamic-heading" class="laporan-bulanan-dynamic-caption">Rekapitulasi {{ $report['sheetTitle'] }} · {{ $report['selectedPeriodLabel'] }}</h2>
            <div class="laporan-bulanan-scroll laporan-bulanan-dynamic-scroll" role="region" aria-label="Tabel Rekapitulasi {{ $report['sheetTitle'] }} periode {{ $report['selectedPeriodLabel'] }}" tabindex="0">
                <table class="table table-sm table-bordered laporan-bulanan-dynamic-table mb-0">
                    <caption class="visually-hidden">Rekapitulasi {{ $report['sheetTitle'] }} · {{ $report['selectedPeriodLabel'] }}</caption>
                    <thead>
                        <tr>
                            <th scope="col" rowspan="2" class="text-center">NO</th>
                            <th scope="col" rowspan="2">PERANGKAT DAERAH</th>
                            <th scope="col" rowspan="2" class="text-end">PERENCANAAN</th>
                            <th scope="col" rowspan="2" class="text-end">{{ str_replace('S.D', 's.d', strtoupper($report['priorPeriodLabel'])) }}</th>
                            <th scope="col" rowspan="2" class="text-end">{{ strtoupper($report['selectedPeriodLabel']) }}</th>
                            <th scope="colgroup" colspan="2" class="text-center">s.d {{ strtoupper($report['selectedPeriodLabel']) }}</th>
                        </tr>
                        <tr>
                            <th scope="col" class="text-end">NILAI</th>
                            <th scope="col" class="text-end">PROSENTASE</th>
                        </tr>
                        <tr>
                            <th scope="col" class="text-center">1</th>
                            <th scope="col" class="text-center">2</th>
                            <th scope="col" class="text-center">3</th>
                            <th scope="col" class="text-center">4</th>
                            <th scope="col" class="text-center">5</th>
                            <th scope="col" class="text-center">6</th>
                            <th scope="col" class="text-center">7</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($report['rows'] as $row)
                            <tr>
                                <td class="text-center laporan-bulanan-dynamic-number">{{ $row['number'] }}</td>
                                <th scope="row" class="laporan-bulanan-dynamic-organization">{{ $row['organizationName'] }}</th>
                                <td class="text-end laporan-bulanan-dynamic-number">{{ $formatMoney($row['planningBudget']) }}</td>
                                <td class="text-end laporan-bulanan-dynamic-number">{{ $formatMoney($row['priorAmount']) }}</td>
                                <td class="text-end laporan-bulanan-dynamic-number">{{ $formatMoney($row['selectedAmount']) }}</td>
                                <td class="text-end laporan-bulanan-dynamic-number">{{ $formatMoney($row['cumulativeAmount']) }}</td>
                                <td class="text-end laporan-bulanan-dynamic-number">{{ $formatPercentage($row['percentage']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-4 text-center text-muted">Tidak ada data {{ $report['sheetTitle'] }} untuk periode {{ $report['selectedPeriodLabel'] }}.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="laporan-bulanan-dynamic-scroll-hint">Geser tabel ke samping untuk melihat seluruh kolom</p>
        </section>
    @elseif (! $report['available'])
        <div class="laporan-bulanan-empty" role="status">
            <strong>Data Laporan Bulanan {{ $report['sheetTitle'] }} belum tersedia.</strong>
            <span>Tidak ada tabel untuk periode {{ $report['bulan'] }} {{ $report['tahun'] }}.</span>
        </div>
    @else
        <section class="laporan-bulanan-sheet" aria-labelledby="laporan-bulanan-heading">
            <div id="laporan-bulanan-heading" class="visually-hidden">Tabel Laporan Bulanan {{ $report['sheetTitle'] }} periode {{ $report['bulan'] }} {{ $report['tahun'] }}</div>
            <div class="laporan-bulanan-scroll" role="region" aria-label="Tabel Laporan Bulanan {{ $report['sheetTitle'] }} periode {{ $report['bulan'] }} {{ $report['tahun'] }}" tabindex="0">
                <table class="laporan-bulanan-table">
                    <caption>{{ $report['sheetTitle'] }} · {{ $report['bulan'] }} {{ $report['tahun'] }}</caption>
                    <colgroup>
                        @foreach ($report['columns'] as $column)
                            <col style="{{ $columnStyles($column) }}">
                        @endforeach
                    </colgroup>
                    <tbody>
                        @foreach ($report['rows'] as $row)
                            <tr style="{{ $rowStyles($row) }}">
                                @foreach ($row['cells'] as $cell)
                                    @php
                                        $parts = $coordinateParts($cell['coordinate']);
                                        $column = $report['columns'][$parts['columnIndex'] - 1] ?? ['hidden' => false];
                                        $merge = $mergeByCoordinate[$cell['coordinate']] ?? null;
                                    @endphp
                                    @continue(isset($coveredCoordinates[$cell['coordinate']]))
                                    <td @if ($merge) rowspan="{{ $merge['rowspan'] }}" colspan="{{ $merge['colspan'] }}" @endif style="{{ $cellStyles($cell, $column['hidden'] ?? false) }}">{{ $cell['formattedValue'] }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</div>
@endsection
