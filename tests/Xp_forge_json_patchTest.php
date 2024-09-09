<?php declare(strict_types=1);

namespace blancks\JsonPatchBenchmarkTests;

use PHPUnit\Framework\Attributes\DataProvider;

class Xp_forge_json_patchTest extends JsonPatchCompliance
{
    #[DataProvider('validOperationsProvider')]
    public function testJsonPatchCompliance(string $json, string $patch, string $expected): void
    {
        $document = json_decode($json, true);
        $patch = json_decode($patch, true);

        $Changes = new \text\json\patch\Changes(...$patch);
        $document = $Changes->apply($document)->value();

        $this->assertSame(
            $this->normalizeJson($expected),
            $this->normalizeJson(json_encode($document))
        );
    }

    #[DataProvider('atomicOperationsProvider')]
    public function testAtomicOperations(string $json, string $patch, string $expected): void
    {
        $document = json_decode($json, true);
        $patch = json_decode($patch, true);

        try {
            $Changes = new \text\json\patch\Changes(...$patch);
            $document = $Changes->apply($document)->value();
        } catch (\Throwable) {
            // expecting some error
        }

        $this->assertSame(
            $this->normalizeJson($expected),
            $this->normalizeJson(json_encode($document))
        );
    }
}
