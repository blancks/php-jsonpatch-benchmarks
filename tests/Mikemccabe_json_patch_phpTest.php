<?php declare(strict_types=1);

namespace blancks\JsonPatchBenchmarkTests;

use PHPUnit\Framework\Attributes\DataProvider;

class Mikemccabe_json_patch_phpTest extends JsonPatchComplianceTest
{
    #[DataProvider('validOperationsProvider')]
    public function testJsonPatchCompliance(string $json, string $patch, string $expected): void
    {
        $json = json_decode($json, true);
        $patch = json_decode($patch, true);
        $document = \mikemccabe\JsonPatch\JsonPatch::patch($json, $patch);

        $this->assertSame(
            $this->normalizeJson($expected),
            $this->normalizeJson(json_encode($document))
        );
    }
}
