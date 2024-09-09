<?php declare(strict_types=1);

namespace blancks\JsonPatchBenchmarkTests;

use PHPUnit\Framework\Attributes\DataProvider;

class Swaggest_json_diffTest extends JsonPatchCompliance
{
    #[DataProvider('validOperationsProvider')]
    public function testJsonPatchCompliance(string $json, string $patch, string $expected): void
    {
        $document = json_decode($json);
        $patch = json_decode($patch);
        $patch = \Swaggest\JsonDiff\JsonPatch::import($patch);
        $patch->apply($document);

        $this->assertSame(
            $this->normalizeJson($expected),
            $this->normalizeJson(json_encode($document))
        );
    }

    #[DataProvider('atomicOperationsProvider')]
    public function testAtomicOperations(string $json, string $patch, string $expected): void
    {
        $document = json_decode($json);
        $patch = json_decode($patch);

        try {
            $patch = \Swaggest\JsonDiff\JsonPatch::import($patch);
            $patch->apply($document);
        } catch (\Throwable) {
            // expecting some error
        }

        $this->assertSame(
            $this->normalizeJson($expected),
            $this->normalizeJson(json_encode($document))
        );
    }
}
