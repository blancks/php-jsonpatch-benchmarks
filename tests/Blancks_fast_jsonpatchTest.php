<?php declare(strict_types=1);

namespace blancks\JsonPatchBenchmarkTests;

use PHPUnit\Framework\Attributes\DataProvider;

class Blancks_fast_jsonpatchTest extends JsonPatchComplianceTest
{
    #[DataProvider('validOperationsProvider')]
    public function testJsonPatchCompliance(string $json, string $patch, string $expected): void
    {
        $this->assertSame(
            json_encode(json_decode($expected, false, 512, JSON_THROW_ON_ERROR)),
            \blancks\JsonPatch\FastJsonPatch::apply($json, $patch)
        );
    }
}
