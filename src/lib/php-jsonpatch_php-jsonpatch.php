<?php declare(strict_types=1);

namespace blancks\JsonPatchBenchmark\lib;

function applyJsonPatch(string &$json, string $patch): float
{
    $microtime = microtime(true);
    $Patch = new \Rs\Json\Patch($json, $patch);
    $json = $Patch->apply();
    return microtime(true) - $microtime;
}