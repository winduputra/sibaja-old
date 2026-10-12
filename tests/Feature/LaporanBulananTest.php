<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\KatalogBulananReportService;
use App\Services\PencatatanNonTenderBulananReportService;
use Mockery;
use Tests\TestCase;

class LaporanBulananTest extends TestCase
{
    public function test_guest_is_redirected_from_laporan_bulanan_route(): void
    {
        // Given
        $url = route('laporan-bulanan.show', ['sheet' => 'rup']);

        // When
        $response = $this->get($url);

        // Then
        $response->assertRedirect(route('front'));
    }

    public function test_authenticated_request_uses_defaults_and_renders_one_requested_sheet(): void
    {
        // Given
        $user = $this->makeUser();

        // When
        $response = $this->actingAs($user)->get(route('laporan-bulanan.show', ['sheet' => 'rup']));

        // Then
        $response->assertOk();
        $response->assertViewIs('laporan-bulanan.show');
        $response->assertViewHas('report', function ($report): bool {
            return $report['sheetSlug'] === 'rup'
                && $report['sheetTitle'] === 'RUP'
                && $report['tahun'] === 2026
                && $report['bulan'] === 'Agustus'
                && $report['range'] === 'A1:L55'
                && $report['workbookSheets'] === ['RUP'];
        });

        $content = $response->getContent();
        $response->assertSee('Laporan Bulanan RUP', false);
        $response->assertSee('<caption>RUP · Agustus 2026</caption>', false);
        $response->assertSee('role="region" aria-label="Tabel Laporan Bulanan RUP periode Agustus 2026" tabindex="0"', false);
        $response->assertSee('rowspan="1" colspan="11"', false);
        $response->assertSee('rowspan="1" colspan="3"', false);
        $response->assertSee(' RENCANA UMUM PENGADAAN PROVINSI LAMPUNG TAHUN 2026');
        $response->assertSee('Badan Kepegawaian Daerah');
        $response->assertDontSee('<caption>E-Kontrak', false);
        $this->assertSame(1, substr_count($content, '<caption>'));
    }

    public function test_production_layout_renders_six_laporan_bulanan_nav_links_and_active_current_child(): void
    {
        // Given
        $user = $this->makeUser();
        $this->mockKatalogReport('Agustus');
        $expectedLinks = [
            'rup' => 'RUP',
            'e-kontrak' => 'E-Kontrak',
            'pencatatan-swakelola' => 'Pencatatan Swakelola',
            'pencatatan-non-tender' => 'Pencatatan Non Tender',
            'katalog' => 'Katalog',
            'loka-pasar' => 'Loka Pasar',
        ];

        // When
        $response = $this->actingAs($user)->get(route('laporan-bulanan.show', [
            'sheet' => 'katalog',
            'tahun' => '2026',
            'bulan' => 'Agustus',
        ]));

        // Then
        $response->assertOk();
        $response->assertSee('nav-link dropdown-toggle active', false);

        foreach ($expectedLinks as $slug => $label) {
            $response->assertSee('href="' . route('laporan-bulanan.show', ['sheet' => $slug]) . '"', false);
            $response->assertSee($label);
        }

        $response->assertSee('dropdown-item px-3 py-2 rounded-2 active"', false);
        $response->assertSee('Rekapitulasi Katalog Elektronik');
        $response->assertDontSee('<caption>RUP', false);
    }

    public function test_katalog_august_renders_dynamic_web_report(): void
    {
        $user = $this->makeUser();
        $this->mockKatalogReport('Agustus');

        $response = $this->actingAs($user)->get(route('laporan-bulanan.show', [
            'sheet' => 'katalog',
            'tahun' => '2026',
            'bulan' => 'Agustus',
        ]));

        $response->assertOk();
        $response->assertViewHas('isDynamicKatalog', true);
        $response->assertSee('Rekapitulasi Katalog Elektronik');
        $response->assertSee('class="laporan-bulanan-katalog-caption"', false);
        $response->assertSee('<caption class="visually-hidden">', false);
        $response->assertSee('class="laporan-bulanan-katalog-scroll-hint"', false);
        $response->assertSee('Pagu Perencanaan E-Purchasing');
        $response->assertSee('Nilai Transaksi s.d Juli 2026');
        $response->assertSee('Nilai Transaksi Agustus 2026');
        $response->assertSee('Nilai Transaksi Kumulatif s.d Agustus 2026');
        $response->assertSee('Persentase Kumulatif / Pagu Perencanaan');
        $response->assertSee('Biro B');
        $response->assertSee('Rp1.500,00');
        $response->assertSee('66,67%');
        $response->assertDontSee('<caption>Katalog · Agustus 2026</caption>', false);
    }

    public function test_katalog_september_updates_prior_and_selected_month_headers(): void
    {
        $user = $this->makeUser();
        $this->mockKatalogReport('September');

        $response = $this->actingAs($user)->get(route('laporan-bulanan.show', [
            'sheet' => 'katalog',
            'tahun' => '2026',
            'bulan' => 'September',
        ]));

        $response->assertOk();
        $response->assertSee('Nilai Transaksi s.d Agustus 2026');
        $response->assertSee('Nilai Transaksi September 2026');
        $response->assertSee('Nilai Transaksi Kumulatif s.d September 2026');
    }

    public function test_non_tender_august_renders_dynamic_web_report_with_zero_rows_and_total(): void
    {
        $user = $this->makeUser();
        $this->mockNonTenderReport('Agustus');

        $response = $this->actingAs($user)->get(route('laporan-bulanan.show', [
            'sheet' => 'pencatatan-non-tender',
            'tahun' => '2026',
            'bulan' => 'Agustus',
        ]));

        $response->assertOk();
        $response->assertViewHas('isDynamicPencatatanNonTender', true);
        $response->assertSee('Rekapitulasi Pencatatan Non Tender');
        $response->assertSee('class="laporan-bulanan-dynamic-caption"', false);
        $response->assertSee('<caption class="visually-hidden">', false);
        $response->assertSee('class="laporan-bulanan-dynamic-scroll-hint"', false);
        $response->assertSee('role="region" aria-label="Tabel Rekapitulasi Pencatatan Non Tender tahun 2026" tabindex="0"', false);
        $response->assertSee('<th scope="colgroup" colspan="2">Perencanaan</th>', false);
        $response->assertSee('<th scope="colgroup" colspan="2">Tercatat</th>', false);
        $response->assertSee('<th scope="colgroup" colspan="2">Belum Tercatat</th>', false);
        $response->assertSee('Nama OPD');
        $response->assertSee('Persentase Tercatat');
        $response->assertSee('Badan Kepegawaian Daerah');
        $response->assertSee('Badan Kesatuan Bangsa Dan Politik Daerah');
        $response->assertSee('Rp1.500,00');
        $response->assertSee('50,00%');
        $response->assertSee('<tfoot>', false);
        $response->assertSee('TOTAL');
        $response->assertDontSee('<caption>Pencatatan Non Tender · Agustus 2026</caption>', false);
    }

    public function test_non_tender_month_changes_context_without_changing_annual_values(): void
    {
        $user = $this->makeUser();
        $this->mockNonTenderReport('September');

        $response = $this->actingAs($user)->get(route('laporan-bulanan.show', [
            'sheet' => 'pencatatan-non-tender',
            'tahun' => '2026',
            'bulan' => 'September',
        ]));

        $response->assertOk();
        $response->assertSee('September 2026');
        $response->assertSee('Rp1.500,00');
        $response->assertSee('Rp750,00');
        $response->assertSee('50,00%');
    }

    public function test_filter_controls_render_all_months_and_preserve_selected_values(): void
    {
        // Given
        $user = $this->makeUser();
        $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        // When
        $response = $this->actingAs($user)->get(route('laporan-bulanan.show', [
            'sheet' => 'rup',
            'tahun' => '2025',
            'bulan' => 'Juli',
        ]));

        // Then
        $response->assertOk();
        $response->assertSee('action="' . route('laporan-bulanan.show', ['sheet' => 'rup']) . '"', false);
        $response->assertSee('<option value="2025" selected>2025</option>', false);
        $response->assertSee('<option value="Juli" selected>Juli</option>', false);

        foreach ($months as $month) {
            $response->assertSee('value="' . $month . '"', false);
        }
    }

    public function test_authenticated_request_rejects_invalid_filter_values(): void
    {
        // Given
        $user = $this->makeUser();

        // When
        $response = $this->actingAs($user)->from('/home')->get(route('laporan-bulanan.show', [
            'sheet' => 'rup',
            'tahun' => '20x6',
            'bulan' => 'August',
        ]));

        // Then
        $response->assertRedirect('/home');
        $response->assertSessionHasErrors(['tahun', 'bulan']);
    }

    public function test_authenticated_request_returns_unavailable_report_for_supported_but_missing_period(): void
    {
        // Given
        $user = $this->makeUser();

        // When
        $response = $this->actingAs($user)->get(route('laporan-bulanan.show', [
            'sheet' => 'e-kontrak',
            'tahun' => '2026',
            'bulan' => 'Juli',
        ]));

        // Then
        $response->assertOk();
        $response->assertViewHas('report', function ($report): bool {
            return $report['available'] === false
                && $report['sheetSlug'] === 'e-kontrak'
                && $report['sheetTitle'] === 'E-Kontrak'
                && $report['rows'] === [];
        });
        $response->assertSee('Data Laporan Bulanan E-Kontrak belum tersedia.');
        $response->assertSee('Tidak ada tabel untuk periode Juli 2026.');
        $response->assertDontSee('<table class="laporan-bulanan-table">', false);
    }

    public function test_unknown_slug_returns_404_before_controller_projection(): void
    {
        // Given
        $user = $this->makeUser();

        // When
        $response = $this->actingAs($user)->get('/laporan-bulanan/laporan-rahasia');

        // Then
        $response->assertNotFound();
    }

    public function test_rendered_cells_use_safe_workbook_font_and_projected_border_color(): void
    {
        // Given
        $user = $this->makeUser();
        $report = [
            'available' => true,
            'sheetSlug' => 'katalog',
            'sheetTitle' => 'Katalog',
            'tahun' => 2026,
            'bulan' => 'Agustus',
            'range' => 'A1:A1',
            'columns' => [['column' => 'A', 'width' => 12, 'hidden' => false]],
            'rows' => [[
                'row' => 1,
                'height' => 15,
                'hidden' => false,
                'cells' => [[
                    'coordinate' => 'A1',
                    'formattedValue' => 'Styled cell',
                    'style' => [
                        'font' => ['name' => 'Roboto'],
                        'fill' => ['type' => 'none'],
                        'border' => [
                            'top' => ['style' => 'thin', 'color' => 'CCCCCC'],
                            'right' => ['style' => 'none', 'color' => '000000'],
                            'bottom' => ['style' => 'none', 'color' => '000000'],
                            'left' => ['style' => 'none', 'color' => '000000'],
                        ],
                        'alignment' => [],
                        'wrapText' => false,
                    ],
                ]],
            ]],
            'merges' => [],
        ];

        // When
        $response = $this->actingAs($user)->view('laporan-bulanan.show', [
            'report' => $report,
            'filters' => [
                'tahun' => 2026,
                'bulan' => 'Agustus',
                'months' => ['Agustus'],
            ],
        ]);

        // Then
        $response->assertSee('font-family: Roboto, sans-serif', false);
        $response->assertSee('border-top: 1px solid #CCCCCC', false);
    }

    private function makeUser(): User
    {
        $user = new User(['name' => 'Tester', 'username' => 'tester', 'role_id' => 1]);
        $user->id = 99;

        return $user;
    }

    private function mockKatalogReport(string $month): void
    {
        $priorMonth = $month === 'Agustus' ? 'Juli' : 'Agustus';
        $service = Mockery::mock(KatalogBulananReportService::class);
        $service->shouldReceive('report')
            ->once()
            ->with(2026, $month)
            ->andReturn([
                'sheetSlug' => 'katalog',
                'sheetTitle' => 'Katalog',
                'tahun' => 2026,
                'bulan' => $month,
                'priorPeriodLabel' => "s.d {$priorMonth} 2026",
                'selectedPeriodLabel' => "{$month} 2026",
                'rows' => [[
                    'number' => 1,
                    'organizationName' => 'Biro B',
                    'planningBudget' => 1500.0,
                    'priorAmount' => 300.0,
                    'selectedAmount' => 700.0,
                    'cumulativeAmount' => 1000.0,
                    'percentage' => 66.67,
                ]],
            ]);
        $this->app->instance(KatalogBulananReportService::class, $service);
    }

    private function mockNonTenderReport(string $month): void
    {
        $service = Mockery::mock(PencatatanNonTenderBulananReportService::class);
        $service->shouldReceive('report')
            ->once()
            ->with(2026, $month)
            ->andReturn([
                'sheetSlug' => 'pencatatan-non-tender',
                'sheetTitle' => 'Pencatatan Non Tender',
                'tahun' => 2026,
                'bulan' => $month,
                'selectedPeriodLabel' => "{$month} 2026",
                'rows' => [
                    [
                        'number' => 1,
                        'nama_opd' => 'Badan Kepegawaian Daerah',
                        'perencanaan_paket' => 2,
                        'perencanaan_pagu' => 1500.0,
                        'tercatat_paket' => 1,
                        'tercatat_pagu' => 750.0,
                        'belum_tercatat_paket' => 1,
                        'belum_tercatat_pagu' => 750.0,
                        'persentase_tercatat' => 50.0,
                    ],
                    [
                        'number' => 2,
                        'nama_opd' => 'Badan Kesatuan Bangsa Dan Politik Daerah',
                        'perencanaan_paket' => 0,
                        'perencanaan_pagu' => 0.0,
                        'tercatat_paket' => 0,
                        'tercatat_pagu' => 0.0,
                        'belum_tercatat_paket' => 0,
                        'belum_tercatat_pagu' => 0.0,
                        'persentase_tercatat' => 0.0,
                    ],
                ],
                'summary' => [
                    'perencanaan_paket' => 2,
                    'perencanaan_pagu' => 1500.0,
                    'tercatat_paket' => 1,
                    'tercatat_pagu' => 750.0,
                    'belum_tercatat_paket' => 1,
                    'belum_tercatat_pagu' => 750.0,
                    'persentase_tercatat' => 50.0,
                ],
            ]);
        $this->app->instance(PencatatanNonTenderBulananReportService::class, $service);
    }
}
