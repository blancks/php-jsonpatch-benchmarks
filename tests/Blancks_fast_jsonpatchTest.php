<?php declare(strict_types=1);

namespace blancks\JsonPatchBenchmarkTests;
use blancks\JsonPatch\exceptions\FastJsonPatchException;
use blancks\JsonPatch\FastJsonPatch;
use PHPUnit\Framework\Attributes\DataProvider;

class Blancks_fast_jsonpatchTest extends JsonPatchCompliance
{
    #[DataProvider('validOperationsProvider')]
    public function testJsonPatchCompliance(string $json, string $patch, string $expected): void
    {
        $document = json_decode($json);
        (new FastJsonPatch($document))->apply($patch);

        $this->assertSame(
            $this->normalizeJson($expected),
            $this->normalizeJson(json_encode($document))
        );
    }

    #[DataProvider('atomicOperationsProvider')]
    public function testAtomicOperations(string $json, string $patch, string $expected): void
    {
        $FastJsonPatch = FastJsonPatch::fromJson($json);

        $this->expectException(FastJsonPatchException::class);
        $FastJsonPatch->apply($patch);

        $this->assertSame(
            $this->normalizeJson($expected),
            $this->normalizeJson($this->jsonEncode($FastJsonPatch->getDocument()))
        );
    }
}
