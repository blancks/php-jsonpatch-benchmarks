<?php declare(strict_types=1);

namespace blancks\JsonPatchBenchmarkTests;

use PHPUnit\Framework\Attributes\DataProvider;

class Remorhaz_php_json_patchTest extends JsonPatchComplianceTest
{
    #[DataProvider('validOperationsProvider')]
    public function testJsonPatchCompliance(string $json, string $patch, string $expected): void
    {
        $encodedValueFactory = \Remorhaz\JSON\Data\Value\EncodedJson\NodeValueFactory::create();
        $queryFactory = \Remorhaz\JSON\Patch\Query\QueryFactory::create();
        $processor = \Remorhaz\JSON\Patch\Processor\Processor::create();

        $patch = $encodedValueFactory->createValue($patch);
        $query = $queryFactory->createQuery($patch);
        $document = $encodedValueFactory->createValue($json);
        $result = $processor->apply($query, $document);
        $documentString = $result->encode();

        $this->assertSame(
            $this->normalizeJson($expected),
            $this->normalizeJson($documentString)
        );
    }
}
