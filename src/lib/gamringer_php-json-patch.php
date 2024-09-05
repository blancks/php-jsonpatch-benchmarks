<?php declare(strict_types=1);

function applyJsonPatch(
    string &$documentString,
    string $patchString,
    array|\stdClass &$documentReference,
    array $patch
): float {

    $microtime = microtime(true);
    $patch = new \gamringer\JSONPatch\Patch();

    // This should be the faster option to feed this class with JSON patch operations
    foreach ($patch as $operation) {
        $patch->addOperation(\gamringer\JSONPatch\Operation::fromDecodedJSON($operation));
    }

    $patch->apply($documentReference);
    return microtime(true) - $microtime;

}