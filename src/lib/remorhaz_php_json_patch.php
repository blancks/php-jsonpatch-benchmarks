<?php declare(strict_types=1);

namespace blancks\JsonPatchBenchmark\lib;
use Remorhaz\JSON\Data\Value\EncodedJson;
use Remorhaz\JSON\Patch\Processor\Processor;
use Remorhaz\JSON\Patch\Query\QueryFactory;

function applyJsonPatch(string &$json, string $patch): float
{
    $EncodedValueFactory = EncodedJson\NodeValueFactory::create();
    $QueryFactory = QueryFactory::create();
    $Processor = Processor::create();

    $microtime = microtime(true);

    $Patch = $EncodedValueFactory->createValue($patch);
    $Document = $EncodedValueFactory->createValue($json);
    $Result = $Processor->apply($QueryFactory->createQuery($Patch), $Document);
    $json = $Result->encode();

    return microtime(true) - $microtime;
}