<?php declare(strict_types=1);

namespace blancks\JsonPatchBenchmarkTests;

use PHPUnit\Framework\Attributes\DataProvider;

class Php_jsonpatch_php_jsonpatchTest extends JsonPatchCompliance
{
    #[DataProvider('validOperationsProvider')]
    public function testJsonPatchCompliance(string $json, string $patch, string $expected): void
    {
        $Patch = new \Rs\Json\Patch($json, $patch);
        $document = json_decode($Patch->apply(), false, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(
            $this->normalizeJson($expected),
            $this->normalizeJson(json_encode($document))
        );
    }

    #[DataProvider('atomicOperationsProvider')]
    public function testAtomicOperations(string $json, string $patch, string $expected): void
    {
        $document = json_decode($json, true);

        try {
            $Patch = new \Rs\Json\Patch($json, $patch);
            $document = json_decode($Patch->apply(), false, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            // expecting some error
        }

        $this->assertSame(
            $this->normalizeJson($expected),
            $this->normalizeJson(json_encode($document))
        );
    }
}
