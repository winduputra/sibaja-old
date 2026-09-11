<?php

namespace Tests\Feature\Console;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LegacyDestructiveApiImportRefreshTest extends TestCase
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
        Cache::flush();
    }

    /**
     * @return array<string, array{0: string, 1: array<int, string>}>
     */
    public static function legacyDisabledCommandsProvider(): array
    {
        return [
            'nontender:update' => [
                'nontender:update',
                [
                    'non_tender_pengumuman',
                    'non_tender_selesai',
                    'non_tender_pencatatan',
                    'non_tender_realisasi',
                    'non_tender_spmk',
                    'non_tender_schedules',
                    'non_tender_contract',
                    'non_tender_bapbast',
                ],
            ],
            'update:tender-data' => [
                'update:tender-data',
                [
                    'tender_pengumuman_data',
                    'tender_selesai_data',
                    'tender_selesai_nilai_data',
                    'sppbj_data',
                    'spmk_spp_data',
                    'bapbast_data',
                    'kontrak_data',
                ],
            ],
            'update:swakelola-realisasi' => [
                'update:swakelola-realisasi',
                ['swakelola_realisasi'],
            ],
            'tokodaring:update' => [
                'tokodaring:update',
                ['toko_darings'],
            ],
            'ekatalog:update' => [
                'ekatalog:update',
                ['ekatalog_v5_pakets', 'ekatalog_v6_pakets'],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider legacyDisabledCommandsProvider
     *
     * @param array<int, string> $tables
     */
    public function legacy_isb_commands_are_disabled_without_mutating_sentinels_or_sending_http_request(
        string $commandName,
        array $tables
    ): void {
        $this->createEmptyTables($tables);
        $this->seedRows($tables, 'sentinel');
        $beforeSnapshot = $this->snapshotTables($tables);

        Http::fake();

        $exitCode = Artisan::call($commandName);
        $output = Artisan::output();

        $this->assertNotSame(0, $exitCode);
        $this->assertSame($beforeSnapshot, $this->snapshotTables($tables));
        Http::assertNothingSent();
        $this->assertStringContainsString('ISB lama sudah dinonaktifkan', $output);
    }

    /**
     * @param array<int, string> $tables
     */
    private function createEmptyTables(array $tables): void
    {
        foreach ($tables as $tableName) {
            Schema::dropIfExists($tableName);
            Schema::create($tableName, function (Blueprint $table): void {
                $table->id();
                $table->string('marker')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * @param array<int, string> $tables
     */
    private function seedRows(array $tables, string $prefix): void
    {
        foreach ($tables as $tableName) {
            DB::table($tableName)->insert([
                'marker' => sprintf('%s-%s', $prefix, $tableName),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * @param array<int, string> $tables
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function snapshotTables(array $tables): array
    {
        $snapshot = [];

        foreach ($tables as $tableName) {
            $snapshot[$tableName] = DB::table($tableName)
                ->orderBy('id')
                ->get()
                ->map(static fn ($row): array => (array) $row)
                ->all();
        }

        return $snapshot;
    }
}
