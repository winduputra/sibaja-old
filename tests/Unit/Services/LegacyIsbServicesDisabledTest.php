<?php

namespace Tests\Unit\Services;

use App\Services\NonTenderScheduleService;
use App\Services\NonTenderService;
use App\Services\SatkerService;
use App\Services\TenderParticipantService;
use App\Services\TenderScheduleService;
use App\Services\TenderService;
use Mockery;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

class LegacyIsbServicesDisabledTest extends TestCase
{
    /**
     * @return array<string, array{0: class-string, 1: string, 2: array<int, mixed>, 3: array<int, string>}>
     */
    public static function legacyIsbServiceMethodsProvider(): array
    {
        return [
            'tender getAll' => [TenderService::class, 'getAll', [2026, '121'], ['year', 'lpse']],
            'tender getDone' => [TenderService::class, 'getDone', [2026, '121'], ['year', 'lpse']],
            'tender schedule getByCode' => [TenderScheduleService::class, 'getByCode', ['TDR-001'], ['code']],
            'tender participant getByCode' => [TenderParticipantService::class, 'getByCode', ['TDR-001'], ['code']],
            'non tender getAll' => [NonTenderService::class, 'getAll', [2026, '121'], ['year', 'lpse']],
            'non tender getDone' => [NonTenderService::class, 'getDone', [2026, '121'], ['year', 'lpse']],
            'non tender schedule getByCode' => [NonTenderScheduleService::class, 'getByCode', ['NT-001'], ['code']],
            'satker getMaster' => [SatkerService::class, 'getMaster', [2026, 'D264'], ['year', 'klpd']],
        ];
    }

    /**
     * @test
     * @dataProvider legacyIsbServiceMethodsProvider
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function public_methods_preserve_signatures_and_fail_before_guzzle_request(
        string $serviceClass,
        string $methodName,
        array $arguments,
        array $expectedParameterNames
    ): void {
        $reflectionMethod = new ReflectionMethod($serviceClass, $methodName);

        $this->assertTrue($reflectionMethod->isPublic());
        $this->assertSame(
            $expectedParameterNames,
            array_map(static fn ($parameter): string => $parameter->getName(), $reflectionMethod->getParameters())
        );

        $client = Mockery::mock('overload:GuzzleHttp\\Client');
        $client->shouldNotReceive('get');

        $thrownThrowable = null;

        try {
            $serviceClass::$methodName(...$arguments);
        } catch (\Throwable $throwable) {
            $thrownThrowable = $throwable;
        }

        Mockery::close();

        $this->assertNotNull($thrownThrowable);
        $this->assertInstanceOf(RuntimeException::class, $thrownThrowable);
        $this->assertMatchesRegularExpression('/dinonaktifkan/i', $thrownThrowable->getMessage());
    }
}
