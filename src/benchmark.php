<?php declare(strict_types=1);

namespace blancks\JsonPatchBenchmark;

require dirname(__FILE__, 2) .'/vendor/autoload.php';


/************************************************************
 *                INPUT (Main|Child Process)                *
 ************************************************************/

$cmdLibraryInput = $argv[1]?? null;


/************************************************************
 *                INPUT (Child Process Only)                *
 ************************************************************/

$cmdPatchSizeInput = $argv[2] ?? null;
$iterationStart = $argv[3] ?? null;
$iterationEnd = $argv[4] ?? null;
$isChildProcess = !is_null($cmdPatchSizeInput) && !is_null($iterationStart) && !is_null($iterationEnd);


/************************************************************
 *                       TEST DOCUMENT                      *
 ************************************************************/

$document = new \stdClass;

for ($i = 0; $i < PATCH_NESTED_LEVEL - 1; ++$i) {
    $document = [$document];
}

$documentString = json_encode($document);


/************************************************************
 *                        TEST PATCH                        *
 ************************************************************/

$patch = json_decode(
    sprintf(
        '[
            {"op": "add", "path": "%1$s/foo", "value": "hello world"},
            {"op": "move", "from": "%1$s/foo", "path": "%1$s/foo"},
            {"op": "copy", "from": "%1$s/foo", "path": "%1$s/bar"},
            {"op": "remove", "path": "%1$s/bar"},
            {"op": "replace", "path": "%1$s/foo", "value": "hello world"},
            {"op": "test", "path": "%1$s/foo", "value": "hello world"}
        ]',
        str_repeat('/0', PATCH_NESTED_LEVEL - 1)
    )
);


/************************************************************
 *                          START!                          *
 ************************************************************/

$libFolder = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'lib';

if (empty($cmdLibraryInput)) {
    echo "Please run the script with one of these parameters:", PHP_EOL, PHP_EOL;

    foreach (getAvailableLibraries($libFolder) as $lib) {
        echo "    ", $lib, PHP_EOL;
    }

    echo PHP_EOL;
    exit(1);
}

require $libFolder . DIRECTORY_SEPARATOR . $cmdLibraryInput .'.php';

if (OUTPUT_FOLDER !== '' && !file_exists(OUTPUT_FOLDER)) {
    if (!mkdir(OUTPUT_FOLDER, 0777, true)) {
        throw new \RuntimeException('Unable to create output directory in: '. OUTPUT_FOLDER);
    }
}

// temporary file where all iteration results are stored
$tmpFilename = (OUTPUT_FOLDER === ''? '.' : OUTPUT_FOLDER) . DIRECTORY_SEPARATOR . $cmdLibraryInput .'.tmp';
$csvFilename = dirname($tmpFilename) . DIRECTORY_SEPARATOR . $cmdLibraryInput .'.csv';


/************************************************************
 *                       CHILD PROCESS                      *
 ************************************************************/

if ($isChildProcess) {
    $patchsize = (int)$cmdPatchSizeInput;
    $iterationStart = (int)$iterationStart;
    $iterationEnd = (int)$iterationEnd;
    $resultsBuffer = '';
    $patchIndex = 0;

    for ($iteration = $iterationStart; $iteration < $iterationEnd; ++$iteration) {
        $operations = [];

        for ($i = 0; $i < $patchsize; ++$i) {
            $operations[] = $patch[$patchIndex++ % 6];
        }

        $patchString = json_encode($operations);
        $benchmarkSeconds = \blancks\JsonPatchBenchmark\lib\applyJsonPatch($documentString, $patchString);
        $benchmarkMicrotime = intval($benchmarkSeconds * 1e6);
        $memoryKiB = memory_get_usage(true) / 1024;
        $resultsBuffer .= implode(',', [$patchsize, $benchmarkMicrotime, $memoryKiB]) . PHP_EOL;
    }

    // Atomically appends data to the file. Yes, it really does it with the proper flags.
    file_put_contents($tmpFilename, $resultsBuffer, FILE_APPEND | LOCK_EX);
    exit(0);
}


/************************************************************
 *                       MAIN PROCESS                       *
 ************************************************************/

$UnitTest = runAutomatedTests($cmdLibraryInput);
echo 'Unit Test Results: ', $UnitTest['output'], PHP_EOL;

if (!$UnitTest['status']) {
    echo $cmdLibraryInput, ' did not passed automated tests', PHP_EOL;
    echo 'Benchmark will still run if the class does not fail', PHP_EOL;
}

$start = microtime(true);
$available_threads = THREADS - 1;
$maxTestIterations = MAX_PATCHSIZE * ITERATIONS_PER_PATCH;
$currentTestIteration = 0;
$fibers = [];

// create/erase file
if (file_put_contents($tmpFilename, '', LOCK_EX) === false) {
    throw new \RuntimeException('Unable to write/create the file "'. $tmpFilename .'". Please check your permissions and retry');
}

echo 'Start running ', number_format($maxTestIterations, 0, '.', ''), ' iterations!', PHP_EOL;

for ($patchsize = 1; $patchsize <= MAX_PATCHSIZE; ++$patchsize) {
    $iterationCount = 0;

    do {

        if (MAX_PATCHSIZE - $patchsize > 0) {
            // set the process to run all iterations for the given patch operation size
            $iterationsPerThread = ITERATIONS_PER_PATCH;
        } else {
            // when are few benchmark left to complete the task split the remaining works across available threads
            // this will happen near the end of the whole benchmark run
            $iterationsPerThread = ceil(min((ITERATIONS_PER_PATCH - $iterationCount) / $available_threads, 500));
        }

        $iterationEnd = (int)min($iterationCount + $iterationsPerThread, ITERATIONS_PER_PATCH);

        // keeps spawning child processes until we ran out of threads
        $fibers[] = spawnProcessFiber($cmdLibraryInput, $patchsize, $iterationCount, $iterationEnd);

        // once we have used all available threads we must wait for at least one of them to complete
        // before spawning a new process
        if (count($fibers) >= $available_threads) {
            waitForFibers($fibers, 1);
        }

        $currentTestIteration += $iterationEnd - $iterationCount;
        $iterationCount = $iterationEnd;

        // lets the user know about the benchmark progress
        $progess = number_format($currentTestIteration / $maxTestIterations * 100, 2, '.', '');
        $ETA = secondsToHumanTime(extimatedTimeLeft($start, $currentTestIteration, $maxTestIterations, pow(1+((MAX_PATCHSIZE-$patchsize)/MAX_PATCHSIZE),2)));
        echo "\e[0G\e[2KTest in progress... [Patch Size: {$patchsize}] {$progess}% - ETA: {$ETA}";

    } while ($iterationCount < ITERATIONS_PER_PATCH);
}

// waits for all running processes to complete
waitForFibers($fibers);

echo PHP_EOL, 'Completed in ', secondsToHumanTime(microtime(true) - $start), PHP_EOL;
echo 'Data will now be gathered for a summary', PHP_EOL;

$summaryBuffer = [];
$summaryStartTime = microtime(true);

// returns the average value based on the previous value, the current value, and how many value we have seen so far
$avgFunction = fn(float $previousAvg, float $value, int $count): float => ($previousAvg*$count+$value)/($count+1);

$summaryIndex = 0;
$tmpResource = fopen($tmpFilename, 'r');

while (!feof($tmpResource) && ++$summaryIndex <= $maxTestIterations) {
    [$size, $microtime, $memoryKiB] = explode(',', trim(fgets($tmpResource, 4096)));

    if (!isset($summaryBuffer[$size])) {

        $summaryBuffer[$size] = [
            'count' => 1,
            'microtime' => ['min' => $microtime, 'avg' => (float)$microtime, 'max' => $microtime],
            'memory' => ['min' => $memoryKiB, 'avg' => (float)$memoryKiB, 'max' => $memoryKiB],
        ];

    } else {

        ++$summaryBuffer[$size]['count'];
        $summaryBuffer[$size]['microtime']['min'] = min($summaryBuffer[$size]['microtime']['min'], $microtime);
        $summaryBuffer[$size]['microtime']['max'] = max($summaryBuffer[$size]['microtime']['max'], $microtime);
        $summaryBuffer[$size]['microtime']['avg'] = $avgFunction(
            $summaryBuffer[$size]['microtime']['avg'],
            (float)$microtime,
            $summaryBuffer[$size]['count']
        );

        $summaryBuffer[$size]['memory']['min'] = min($summaryBuffer[$size]['memory']['min'], $memoryKiB);
        $summaryBuffer[$size]['memory']['max'] = max($summaryBuffer[$size]['memory']['max'], $memoryKiB);
        $summaryBuffer[$size]['memory']['avg'] = $avgFunction(
            $summaryBuffer[$size]['memory']['avg'],
            (float)$memoryKiB,
            $summaryBuffer[$size]['count']
        );

    }

    // lets the user know about the data summary progress
    $progess = number_format($summaryIndex / $maxTestIterations * 100, 2, '.', '');
    $ETA = secondsToHumanTime(extimatedTimeLeft($summaryStartTime, $summaryIndex, $maxTestIterations));
    echo "\e[0G\e[2KAnalysis in progress... {$progess}% - ETA: {$ETA}";
}

fclose($tmpResource);

// writes the summary as csv file
$summaryResource = fopen($csvFilename, 'w');
fputcsv($summaryResource, [
    'patchsize',
    'microtime (avg)',
    'microtime (min)',
    'microtime (max)',
    'memory (avg)',
    'memory (min)',
    'memory (max)'
]);

for ($i = 1; $i <= MAX_PATCHSIZE; ++$i) {
    fputcsv($summaryResource, [
        $i,
        (int)$summaryBuffer[$i]['microtime']['avg'],
        $summaryBuffer[$i]['microtime']['min'],
        $summaryBuffer[$i]['microtime']['max'],
        (int)$summaryBuffer[$i]['memory']['avg'],
        $summaryBuffer[$i]['memory']['min'],
        $summaryBuffer[$i]['memory']['max'],
    ]);
}

fclose($summaryResource);

// deletes the .tmp file
unlink($tmpFilename);

echo PHP_EOL, 'Completed in ', secondsToHumanTime(time() - $start);
exit(0);
