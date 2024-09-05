<?php declare(strict_types=1);

function applyJsonPatch(
    string &$documentString,
    string $patchString,
    array|\stdClass &$documentReference,
    array $patch
): float {

    $microtime = microtime(true);
    $Patch = new Rs\Json\Patch($documentString, $patchString);
    $documentString = $Patch->apply();
    return microtime(true) - $microtime;

}