<?php declare(strict_types=1);

namespace blancks\JsonPatchBenchmarkTests;

use PHPUnit\Framework\Attributes\DataProvider;

class Gamringer_php_json_patchTest extends JsonPatchComplianceTest
{
    #[DataProvider('validOperationsProvider')]
    public function testJsonPatchCompliance(string $json, string $patch, string $expected): void
    {
        $document = json_decode($json);
        $Patch = \gamringer\JSONPatch\Patch::fromJSON($patch);
        $Patch->apply($document);

        $this->assertSame(
            $this->normalizeJson($expected),
            $this->normalizeJson(json_encode($document))
        );
    }
}
