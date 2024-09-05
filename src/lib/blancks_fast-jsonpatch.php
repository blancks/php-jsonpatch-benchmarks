<?php declare(strict_types=1);

function applyJsonPatch(
    string &$documentString,
    string $patchString,
    array|\stdClass &$documentReference,
    array $patch
): float {

    $microtime = microtime(true);
    \blancks\JsonPatch\FastJsonPatch::applyByReference($documentReference, $patch);
    return microtime(true) - $microtime;

}