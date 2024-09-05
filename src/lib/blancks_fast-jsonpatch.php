<?php declare(strict_types=1);

namespace blancks\JsonPatchBenchmark\lib;

function applyJsonPatch(string &$json, string $patch): float
{
    $jsonDecoded = json_decode($json);
    $patchDecoded = json_decode($patch);

    $microtime = microtime(true);
    \blancks\JsonPatch\FastJsonPatch::applyByReference($jsonDecoded, $patchDecoded);
    $output = microtime(true) - $microtime;

    $json = json_encode($jsonDecoded);
    return $output;
}