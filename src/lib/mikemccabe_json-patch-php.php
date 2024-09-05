<?php declare(strict_types=1);

namespace blancks\JsonPatchBenchmark\lib;

function applyJsonPatch(string &$json, string $patch): float
{
    // this class only supports document and patch operations as associative arrays
    $jsonDecoded = json_decode($json, true);
    $patchDecoded = json_decode($patch, true);

    $microtime = microtime(true);
    $jsonDecoded = \mikemccabe\JsonPatch\JsonPatch::patch($jsonDecoded, $patchDecoded, true);
    $output = microtime(true) - $microtime;

    $json = json_encode($jsonDecoded);
    return $output;
}