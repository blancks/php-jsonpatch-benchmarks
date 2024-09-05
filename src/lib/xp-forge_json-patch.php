<?php declare(strict_types=1);

function applyJsonPatch(
    string &$documentString,
    string $patchString,
    array|\stdClass &$documentReference,
    array $patch
): float {
    // this class only supports document and patch operations as associative arrays
    $documentReference = json_decode($documentString, true);
    $patch = json_decode($patchString, true);

    $microtime = microtime(true);
    $Changes = new \text\json\patch\Changes(...$patch);
    $documentReference = $Changes->apply($documentReference)->value();
    $benchmarkTime = microtime(true) - $microtime;

    $documentString = json_encode($documentReference);
    return $benchmarkTime;

}