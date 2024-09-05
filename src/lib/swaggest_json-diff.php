<?php declare(strict_types=1);

function applyJsonPatch(
    string &$documentString,
    string $patchString,
    array|\stdClass &$documentReference,
    array $patch
): float {

    $microtime = microtime(true);
    $patch = \Swaggest\JsonDiff\JsonPatch::import($patch);
    $patch->apply($documentReference);
    return microtime(true) - $microtime;

}