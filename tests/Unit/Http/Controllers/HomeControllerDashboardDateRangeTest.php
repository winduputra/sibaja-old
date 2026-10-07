<?php

namespace Tests\Unit\Http\Controllers;

use App\Http\Controllers\HomeController;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\TestCase;

class HomeControllerDashboardDateRangeTest extends TestCase
{
    private const TABLE = 'dashboard_date_range_regression';

    private $temporaryTableCreated = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Dashboard date coercion regression requires MySQL or MariaDB.');
        }

        DB::statement('DROP TEMPORARY TABLE IF EXISTS ' . self::TABLE);
        DB::statement(
            'CREATE TEMPORARY TABLE ' . self::TABLE . ' (
                id INT UNSIGNED NOT NULL PRIMARY KEY,
                date_value DATE NULL,
                datetime_value DATETIME NULL
            )'
        );
        $this->temporaryTableCreated = true;

        DB::table(self::TABLE)->insert([
            ['id' => 1, 'date_value' => '2025-12-31', 'datetime_value' => '2025-12-31 23:59:59'],
            ['id' => 2, 'date_value' => '2026-01-01', 'datetime_value' => '2026-01-01 00:00:00'],
            ['id' => 3, 'date_value' => '2026-09-01', 'datetime_value' => '2026-09-01 00:00:00'],
            ['id' => 4, 'date_value' => '2026-09-30', 'datetime_value' => '2026-09-30 23:59:59'],
            ['id' => 5, 'date_value' => '2026-10-01', 'datetime_value' => '2026-10-01 00:00:00'],
            ['id' => 6, 'date_value' => null, 'datetime_value' => null],
        ]);
    }

    protected function tearDown(): void
    {
        if ($this->temporaryTableCreated) {
            DB::statement('DROP TEMPORARY TABLE IF EXISTS ' . self::TABLE);
        }

        parent::tearDown();
    }

    /** @test */
    public function date_column_uses_datetime_casts_and_matches_normal_where_between(): void
    {
        $this->assertColumnMatchesNormalWhereBetween('date_value');
    }

    /** @test */
    public function datetime_column_includes_first_day_and_exact_end_boundary(): void
    {
        $this->assertColumnMatchesNormalWhereBetween('datetime_value');
    }

    /** @test */
    public function selected_year_keeps_existing_year_floor_behavior(): void
    {
        $dateRange = [
            'start' => Carbon::create(2026, 1, 1)->startOfDay(),
            'end' => Carbon::create(2026, 12, 31)->endOfDay(),
        ];

        $query = $this->applyDateRangeWithYearFloor('date_value', $dateRange);

        $this->assertSame([1, 2, 3, 4, 5], $query->orderBy('id')->pluck('id')->all());
    }

    private function assertColumnMatchesNormalWhereBetween(string $column): void
    {
        $dateRange = [
            'start' => Carbon::create(2026, 9, 1)->startOfDay(),
            'end' => Carbon::create(2026, 9, 30)->endOfDay(),
        ];
        $qualifiedColumn = self::TABLE . '.' . $column;
        $normalIds = DB::table(self::TABLE)
            ->whereBetween($column, [$dateRange['start'], $dateRange['end']])
            ->orderBy('id')
            ->pluck('id')
            ->all();
        $query = $this->applyDateRangeWithYearFloor($qualifiedColumn, $dateRange);
        $sql = strtolower($query->toSql());
        $castColumn = 'cast(' . $qualifiedColumn . ' as datetime)';
        $expectedExpression = "case when {$castColumn} < cast(? as datetime) "
            . "then cast(? as datetime) else {$castColumn} end "
            . 'between cast(? as datetime) and cast(? as datetime)';

        $this->assertStringContainsString($expectedExpression, $sql);
        $this->assertSame([
            '2026-01-01 00:00:00',
            '2026-01-01 00:00:00',
            '2026-09-01 00:00:00',
            '2026-09-30 23:59:59',
        ], $query->getBindings());
        $this->assertSame([3, 4], $normalIds);
        $this->assertSame($normalIds, $query->orderBy('id')->pluck('id')->all());
    }

    private function applyDateRangeWithYearFloor(string $column, array $dateRange): Builder
    {
        $method = new ReflectionMethod(HomeController::class, 'applyDashboardDateRangeWithYearFloor');
        $method->setAccessible(true);

        return $method->invoke(
            new HomeController(),
            DB::table(self::TABLE)->select(self::TABLE . '.id'),
            $column,
            $dateRange,
            '2026'
        );
    }
}
