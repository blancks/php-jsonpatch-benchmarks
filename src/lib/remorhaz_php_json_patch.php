<?php declare(strict_types=1);

use Remorhaz\JSON\Data\Value\EncodedJson;
use Remorhaz\JSON\Patch\Processor\Processor;
use Remorhaz\JSON\Patch\Query\QueryFactory;

function applyJsonPatch(
    string &$documentString,
    string $patchString,
    array|\stdClass &$documentReference,
    array $patch
): float {

    $encodedValueFactory = EncodedJson\NodeValueFactory::create();
    $queryFactory = QueryFactory::create();
    $processor = Processor::create();

    $microtime = microtime(true);

    $patch = $encodedValueFactory->createValue($patchString);
    $query = $queryFactory->createQuery($patch);
    $document = $encodedValueFactory->createValue($documentString);
    $result = $processor->apply($query, $document);
    $documentString = $result->encode();

    return microtime(true) - $microtime;

}