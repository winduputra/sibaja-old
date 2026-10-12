<?php

namespace Tests\Unit\Services;

use App\Services\LaporanBulananWorkbookService;
use InvalidArgumentException;
use Tests\TestCase;

class LaporanBulananWorkbookServiceTest extends TestCase
{
    public function test_safe_slug_map_contains_only_the_six_supported_workbook_sheets(): void
    {
        // Given
        $expectedMap = [
            'rup' => 'RUP',
            'e-kontrak' => 'E-Kontrak',
            'pencatatan-swakelola' => 'Pencatatan Swakelola',
            'pencatatan-non-tender' => 'Pencatatan Non Tender',
            'katalog' => 'Katalog',
            'loka-pasar' => 'Loka Pasar',
        ];

        // When
        $actualMap = LaporanBulananWorkbookService::sheetMap();

        // Then
        $this->assertSame($expectedMap, $actualMap);
    }

    public function test_unknown_slug_is_rejected_before_workbook_loading(): void
    {
        // Given
        $service = new LaporanBulananWorkbookService();

        // Then
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported laporan bulanan sheet slug: laporan-rahasia');

        // When
        $service->project('laporan-rahasia', 2026, 'Agustus');
    }

    public function test_unsupported_period_returns_unavailable_model_without_fabricated_sheet_data(): void
    {
        // Given
        $service = new LaporanBulananWorkbookService();

        // When
        $model = $service->project('rup', 2025, 'Agustus');

        // Then
        $this->assertFalse($model['available']);
        $this->assertSame('rup', $model['sheetSlug']);
        $this->assertSame('RUP', $model['sheetTitle']);
        $this->assertSame(2025, $model['tahun']);
        $this->assertSame('Agustus', $model['bulan']);
        $this->assertSame([], $model['rows']);
        $this->assertSame([], $model['merges']);
    }

    public function test_august_2026_rup_projection_preserves_structure_data_and_formulas(): void
    {
        // Given
        $service = new LaporanBulananWorkbookService();

        // When
        $model = $service->project('rup', 2026, 'Agustus');

        // Then
        $this->assertTrue($model['available']);
        $this->assertSame('RUP', $model['sheetTitle']);
        $this->assertSame('A1:L55', $model['range']);
        $this->assertCount(55, $model['rows']);
        $this->assertCount(12, $model['rows'][0]['cells']);
        $this->assertSame(' RENCANA UMUM PENGADAAN PROVINSI LAMPUNG TAHUN 2026', $model['rows'][0]['cells'][0]['formattedValue']);
        $this->assertSame('BULAN AGUSTUS 2026', $model['rows'][1]['cells'][0]['formattedValue']);
        $this->assertSame('Badan Kepegawaian Daerah', $model['rows'][5]['cells'][1]['formattedValue']);
        $this->assertSame('2,536,022,469', trim($model['rows'][5]['cells'][10]['formattedValue']));
        $this->assertSame('=K6/C6', $model['rows'][5]['cells'][11]['formula']);
        $this->assertEquals(1.0, $model['rows'][5]['cells'][11]['calculatedValue']);
        $this->assertSame('100%', $model['rows'][5]['cells'][11]['formattedValue']);
        $this->assertSame('TOTAL', $model['rows'][54]['cells'][1]['formattedValue']);
        $this->assertSame('=SUM(C6:C54)', $model['rows'][54]['cells'][2]['formula']);
        $this->assertSame('3,468,789,511,866', trim($model['rows'][54]['cells'][10]['formattedValue']));
        $this->assertSame([], $model['calculationErrors']);
        $this->assertSame([], $model['excelErrors']);
    }

    public function test_august_2026_projection_uses_only_the_requested_sheet_and_logical_range(): void
    {
        // Given
        $service = new LaporanBulananWorkbookService();

        // When
        $model = $service->project('katalog', 2026, 'Agustus');

        // Then
        $this->assertTrue($model['available']);
        $this->assertSame('Katalog', $model['sheetTitle']);
        $this->assertSame(['Katalog'], $model['workbookSheets']);
        $this->assertSame('A1:G65', $model['range']);
        $this->assertCount(65, $model['rows']);
        $this->assertCount(7, $model['rows'][0]['cells']);
        $this->assertSame('REKAPITULASI PROGRES KATALOG ELKETRONIK', $model['rows'][0]['cells'][0]['formattedValue']);
        $this->assertSame('=F55/C55', $model['rows'][54]['cells'][6]['formula']);
        $this->assertSame('8.96%', $model['rows'][54]['cells'][6]['formattedValue']);
        $this->assertSame('(7) Prosentase Realisasi Pengadaan melalui Katalog Elektronik sampai Bulan Agustus 2026', $model['rows'][64]['cells'][1]['formattedValue']);
    }

    /**
     * @dataProvider august2026SheetProvider
     */
    public function test_august_2026_each_supported_sheet_has_expected_structure_and_representative_formula(string $slug, string $title, string $range, string $heading, int $rowIndex, int $cellIndex, string $formula, string $formattedValue): void
    {
        // Given
        $service = new LaporanBulananWorkbookService();

        // When
        $model = $service->project($slug, 2026, 'Agustus');

        // Then
        $this->assertTrue($model['available']);
        $this->assertSame($title, $model['sheetTitle']);
        $this->assertSame($range, $model['range']);
        $this->assertSame($heading, $model['rows'][0]['cells'][0]['formattedValue']);
        $this->assertSame($formula, $model['rows'][$rowIndex]['cells'][$cellIndex]['formula']);
        $this->assertSame($formattedValue, trim($model['rows'][$rowIndex]['cells'][$cellIndex]['formattedValue']));
        $this->assertSame([], $model['calculationErrors']);
        $this->assertSame([], $model['excelErrors']);
    }

    public function august2026SheetProvider(): array
    {
        return [
            ['rup', 'RUP', 'A1:L55', ' RENCANA UMUM PENGADAAN PROVINSI LAMPUNG TAHUN 2026', 54, 2, '=SUM(C6:C54)', '3,468,789,511,866'],
            ['e-kontrak', 'E-Kontrak', 'A1:S21', 'REKAPITULASI PROGRES E KONTRAK TENDER', 6, 14, '=E7+J7', '1'],
            ['pencatatan-swakelola', 'Pencatatan Swakelola', 'A1:G65', 'REKAPITULASI PENCATATAN SWAKELOLA', 54, 6, '=F55/C55', '0.00%'],
            ['pencatatan-non-tender', 'Pencatatan Non Tender', 'A1:G65', 'REKAPITULASI PENCATATAN NON TENDER', 54, 6, '=F55/C55', '0.00%'],
            ['katalog', 'Katalog', 'A1:G65', 'REKAPITULASI PROGRES KATALOG ELKETRONIK', 54, 6, '=F55/C55', '8.96%'],
            ['loka-pasar', 'Loka Pasar', 'A1:G65', 'REKAPITULASI PROGRES LOKA PASAR MBIZ MARKET', 54, 6, '=F55/C55', '1.75%'],
        ];
    }

    public function test_projection_preserves_merges_dimensions_hidden_state_and_sanitized_styles(): void
    {
        // Given
        $service = new LaporanBulananWorkbookService();

        // When
        $model = $service->project('rup', 2026, 'Agustus');

        // Then
        $this->assertContains([
            'coordinate' => 'A1',
            'range' => 'A1:K1',
            'rowspan' => 1,
            'colspan' => 11,
        ], $model['merges']);
        $this->assertContains([
            'coordinate' => 'D4',
            'range' => 'D4:F4',
            'rowspan' => 1,
            'colspan' => 3,
        ], $model['merges']);
        $this->assertSame('A', $model['columns'][0]['column']);
        $this->assertFalse($model['columns'][0]['hidden']);
        $this->assertGreaterThan(0, $model['columns'][0]['width']);
        $this->assertSame(1, $model['rows'][0]['row']);
        $this->assertFalse($model['rows'][0]['hidden']);
        $this->assertArrayHasKey('font', $model['rows'][0]['cells'][0]['style']);
        $this->assertArrayHasKey('fill', $model['rows'][0]['cells'][0]['style']);
        $this->assertArrayHasKey('border', $model['rows'][0]['cells'][0]['style']);
        $this->assertArrayHasKey('alignment', $model['rows'][0]['cells'][0]['style']);
        $this->assertArrayHasKey('wrapText', $model['rows'][0]['cells'][0]['style']);
    }

    public function test_projection_preserves_workbook_font_name_and_per_side_border_colors(): void
    {
        // Given
        $service = new LaporanBulananWorkbookService();

        // When
        $model = $service->project('katalog', 2026, 'Agustus');
        $cell = $model['rows'][3]['cells'][1];

        // Then
        $this->assertSame('B4', $cell['coordinate']);
        $this->assertSame('Roboto', $cell['style']['font']['name']);
        $this->assertSame([
            'top' => ['style' => 'thin', 'color' => '000000'],
            'right' => ['style' => 'thin', 'color' => '000000'],
            'bottom' => ['style' => 'thin', 'color' => '000000'],
            'left' => ['style' => 'thin', 'color' => '000000'],
        ], $cell['style']['border']);
    }
}
