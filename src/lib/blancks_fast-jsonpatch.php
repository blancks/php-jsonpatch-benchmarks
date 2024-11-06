<?php declare(strict_types=1);

namespace blancks\JsonPatchBenchmark\lib;

function applyJsonPatch(string &$json, string $patch): float
{
    static $FastJsonPatch = null;
    $jsonDecoded = json_decode($json);
    $FastJsonPatch ??= new \blancks\JsonPatch\FastJsonPatch($jsonDecoded);

    $microtime = microtime(true);
    $FastJsonPatch->apply($patch);
    $output = microtime(true) - $microtime;

    $json = json_encode($jsonDecoded);
    return $output;
}