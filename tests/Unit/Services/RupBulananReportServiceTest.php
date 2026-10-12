<?php

namespace Tests\Unit\Services;

use App\Services\RupBulananReportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RupBulananReportServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => false,
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('satkers', function (Blueprint $table): void {
            $table->id();
            $table->string('tahun_anggaran');
            $table->string('kd_satker');
            $table->string('nama_satker')->nullable();
            $table->string('kd_klpd')->nullable();
            $table->decimal('belanja_pengadaan', 20, 2)->nullable();
        });

        foreach (['penyedias', 'swakelolas'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table): void {
                $table->id();
                $table->string('tahun_anggaran');
                $table->string('kd_satker')->nullable();
                $table->string('kd_klpd')->nullable();
                $table->string('nama_klpd')->nullable();
                $table->boolean('status_aktif_rup')->default(true);
                $table->decimal('pagu', 20, 2)->default(0);
            });
        }
    }

    /** @test */
    public function it_uses_scraped_satker_budget_without_changing_the_rup_total(): void
    {
        DB::table('satkers')->insert([
            'tahun_anggaran' => '2026',
            'kd_satker' => '100001',
            'nama_satker' => 'Satker Lampung',
            'kd_klpd' => 'D264',
            'belanja_pengadaan' => 1000000,
        ]);

        DB::table('penyedias')->insert($this->planningRow(300000));
        DB::table('swakelolas')->insert($this->planningRow(200000));

        $report = app(RupBulananReportService::class)->report(2026, 'Januari');
        $row = $report['rows'][0];

        $this->assertSame(1000000.0, $row['procurementBudget']);
        $this->assertSame(500000.0, $row['totalBudget']);
        $this->assertSame(30.0, $row['providerPercentage']);
        $this->assertSame(20.0, $row['selfManagedPercentage']);
        $this->assertSame(50.0, $row['totalPercentage']);
        $this->assertSame(1000000.0, $report['summary']['procurementBudget']);
        $this->assertSame(500000.0, $report['summary']['totalBudget']);
    }

    /** @return array<string, int|string|bool> */
    private function planningRow(int $budget): array
    {
        return [
            'tahun_anggaran' => '2026',
            'kd_satker' => '100001',
            'kd_klpd' => 'D264',
            'nama_klpd' => 'Provinsi Lampung',
            'status_aktif_rup' => true,
            'pagu' => $budget,
        ];
    }
}
