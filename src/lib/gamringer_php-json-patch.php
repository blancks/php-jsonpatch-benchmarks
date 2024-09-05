<?php declare(strict_types=1);

namespace blancks\JsonPatchBenchmark\lib;

function applyJsonPatch(string &$json, string $patch): float
{
    $jsonDecoded = json_decode($json);
    $patchDecoded = json_decode($patch);

    $microtime = microtime(true);
    $patch = new \gamringer\JSONPatch\Patch();

    // This should be the fastest option to feed this class with JSON patch operations
    foreach ($patchDecoded as $operation) {
        $patch->addOperation(\gamringer\JSONPatch\Operation::fromDecodedJSON($operation));
    }

    $patch->apply($jsonDecoded);
    $output = microtime(true) - $microtime;
    $json = json_encode($jsonDecoded);

    return $output;
}