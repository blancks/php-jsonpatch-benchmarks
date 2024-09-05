<?php declare(strict_types=1);

require dirname(__FILE__, 2) .'/vendor/autoload.php';


/************************************************************
 *                INPUT (Main Process Only)                 *
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

    foreach (array_diff(scandir($libFolder), ['.', '..']) as $lib) {
        echo "    ", pathinfo($lib, PATHINFO_FILENAME), PHP_EOL;
    }

    echo PHP_EOL;
    exit(1);
}

require $libFolder . DIRECTORY_SEPARATOR . $cmdLibraryInput .'.php';

try {

    $tmpFilename = OUTPUT_FILENAME .'.tmp';

    if (!$isChildProcess) {

        /************************************************************
         *                       MAIN PROCESS                       *
         ************************************************************/

        // Running PHPUnit Tests
        if (!runAutomatedTests($cmdLibraryInput)) {
            echo $cmdLibraryInput, ' did not passed automated tests', PHP_EOL;
            echo 'Benchmark will still run if the class does not fail', PHP_EOL;
            // exit(1);
        }

        $start = microtime(true);
        $available_threads = THREADS - 1;
        $maxTestIterations = MAX_PATCHSIZE * ITERATIONS_PER_PATCH;
        $currentTestIteration = 0;
        $fibers = [];

        // create/erase file
        file_put_contents($tmpFilename, '', LOCK_EX);
        echo 'Start running ', number_format($maxTestIterations, 0, '.', ''), ' iterations!', PHP_EOL;

        for ($patchsize = 1; $patchsize <= MAX_PATCHSIZE; ++$patchsize) {
            $iterationCount = 0;

            do {
                if (MAX_PATCHSIZE - $patchsize > 0) {
                    $iterationsPerThread = ITERATIONS_PER_PATCH;
                } else {
                    $iterationsPerThread = ceil(min((ITERATIONS_PER_PATCH - $iterationCount) / $available_threads, 500));
                }

                $iterationEnd = min($iterationCount + $iterationsPerThread, ITERATIONS_PER_PATCH);

                $fiber = new Fiber(spawnChildProcess(...));
                $fiber->start($cmdLibraryInput, $patchsize, $iterationCount, $iterationEnd);
                $fibers[] = $fiber;

                if (count($fibers) >= $available_threads) {
                    waitForChildProcess($fibers, 1);
                }

                $currentTestIteration += $iterationEnd - $iterationCount;
                $iterationCount = $iterationEnd;

                $progess = number_format($currentTestIteration / $maxTestIterations * 100, 2, '.', '');
                $ETA = secondsToHumanTime(
                    ceil(
                        ((microtime(true) - $start) / $currentTestIteration) * ($maxTestIterations - $currentTestIteration)
                    )
                );

                echo "\e[0G\e[2KTest in progress... [Patch Size: {$patchsize}] {$progess}% - ETA: {$ETA}";
            } while ($iterationCount < ITERATIONS_PER_PATCH);
        }

        waitForChildProcess($fibers);

        echo PHP_EOL, 'Completed in ', secondsToHumanTime(microtime(true) - $start), PHP_EOL;
        echo 'Data will now be gathered for a summary', PHP_EOL;

        $summaryIndex = 0;
        $summaryStartTime = microtime(true);
        $benchmarkSummary = [];
        $avgFunction = fn(float $previousAvg, float $value, int $count): float => ($previousAvg*$count+$value)/($count+1);
        $tmpResource = fopen($tmpFilename, 'r');

        while (!feof($tmpResource) && ++$summaryIndex <= $maxTestIterations) {
            [$size, $microtime, $memory] = explode(',', trim(fgets($tmpResource, 4096)));

            if (!isset($benchmarkSummary[$size])) {

                $benchmarkSummary[$size] = [
                    'count' => 1,
                    'microtime' => ['min' => $microtime, 'avg' => (float)$microtime, 'max' => $microtime],
                    'memory' => ['min' => $memory, 'avg' => (float)$memory, 'max' => $memory],
                ];

            } else {

                ++$benchmarkSummary[$size]['count'];
                $benchmarkSummary[$size]['microtime']['min'] = min($benchmarkSummary[$size]['microtime']['min'], $microtime);
                $benchmarkSummary[$size]['microtime']['max'] = max($benchmarkSummary[$size]['microtime']['max'], $microtime);
                $benchmarkSummary[$size]['microtime']['avg'] = $avgFunction(
                    $benchmarkSummary[$size]['microtime']['avg'],
                    (float)$microtime,
                    $benchmarkSummary[$size]['count']
                );

                $benchmarkSummary[$size]['memory']['min'] = min($benchmarkSummary[$size]['memory']['min'], $memory);
                $benchmarkSummary[$size]['memory']['max'] = max($benchmarkSummary[$size]['memory']['max'], $memory);
                $benchmarkSummary[$size]['memory']['avg'] = $avgFunction(
                    $benchmarkSummary[$size]['memory']['avg'],
                    (float)$memory,
                    $benchmarkSummary[$size]['count']
                );

            }

            $progess = number_format($summaryIndex / $maxTestIterations * 100, 2, '.', '');
            $ETA = secondsToHumanTime(
                ceil(
                    ((microtime(true) - $summaryStartTime) / $summaryIndex) * ($maxTestIterations - $summaryIndex)
                )
            );

            echo "\e[0G\e[2KAnalysis in progress... {$progess}% - ETA: {$ETA}";
        }

        fclose($tmpResource);
        unlink($tmpFilename);

        $summaryResource = fopen(OUTPUT_FILENAME, 'w');
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
                (int)$benchmarkSummary[$i]['microtime']['avg'],
                $benchmarkSummary[$i]['microtime']['min'],
                $benchmarkSummary[$i]['microtime']['max'],
                (int)$benchmarkSummary[$i]['memory']['avg'],
                $benchmarkSummary[$i]['memory']['min'],
                $benchmarkSummary[$i]['memory']['max'],
            ]);
        }

        fclose($summaryResource);

        echo PHP_EOL, 'Completed in ', secondsToHumanTime(time() - $start);

    } else {

        /************************************************************
         *                       CHILD PROCESS                      *
         ************************************************************/

        $patchsize = (int) $cmdPatchSizeInput;
        $iterationStart = (int) $iterationStart;
        $iterationEnd = (int) $iterationEnd;
        $iterationCount = 0;

        $buffer = '';

        for ($iteration = $iterationStart; $iteration < $iterationEnd; ++$iteration) {
            $operations = [];

            for ($i = 0; $i < $patchsize; ++$i) {
                $operations[] = $patch[$iterationCount++ % 6];
            }

            $patchString = json_encode($operations);
            $benchmark = applyJsonPatch($documentString, $patchString, $document, $operations);
            $benchmark = intval($benchmark * 1000000);
            $memory = memory_get_usage(true) / 1024;
            $buffer .= implode(',', [$patchsize, $benchmark, $memory]) . PHP_EOL;
        }

        file_put_contents($tmpFilename, $buffer, FILE_APPEND | LOCK_EX);

    }

} catch (\Throwable $e) {

    error_log((string) $e);

}


/************************************************************
 *                         UTILITIES                        *
 ************************************************************/

function runAutomatedTests(string $library): bool {
    $cmd = sprintf(PHPUNIT_TEST_CMD, ucwords(str_replace('-', '_', $library)));
    $output = shell_exec($cmd);

    echo 'PHPUnit Test Results: ';

    if (($position = strpos($output, 'OK ')) !== false) {
        echo substr($output, $position);
        return true;
    }

    if (($position = strpos($output, 'FAILURES!')) !== false) {
        echo substr($output, $position);
        return false;
    }

    echo 'Unknown Failure', PHP_EOL;
    return false;
}

function spawnChildProcess(string $library, int $patchsize, int $iterationstart, int $iterationend): void
{
    $proc = proc_open(
        sprintf(
            'php %s%s%s %s %d %d %d',
            dirname(__FILE__),
            DIRECTORY_SEPARATOR,
            basename(__FILE__),
            $library,
            $patchsize,
            $iterationstart,
            $iterationend
        ),
        [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ],
        $pipes
    );

    if (!$proc) {
        throw new \RuntimeException('Unable to spawn child process');
    }

    do {
        Fiber::suspend();
        $status = proc_get_status($proc);
    } while ($status['running']);

    foreach ($pipes as $pipe) {
        fclose($pipe);
    }

    proc_close($proc);

    if ($status['exitcode'] !== 0) {
        throw new \RuntimeException('Unable to perform the benchmark');
    }
}

function waitForChildProcess(array &$fibers, ?int $releaseAfterCompletedCount = null): void
{
    $completedFibers = 0;
    $releaseAfterCompletedCount ??= count($fibers);

    while (count($fibers) > 0 && $completedFibers < $releaseAfterCompletedCount) {
        // wait a little before checking if child has completed the task
        usleep(1000);

        foreach ($fibers as $i => $fiber) {
            if ($fiber->isSuspended()) {
                $fiber->resume();
            } else if ($fiber->isTerminated()) {
                ++$completedFibers;
                unset($fibers[$i]);
            }
        }
    }
}

function secondsToHumanTime(float $seconds, bool $zeropad = false): string
{
    $padder = fn(float $item, int $length) => str_pad((string) $item, $length, $zeropad ? '0' : ' ', STR_PAD_LEFT);

    if ($seconds < 1) {
        return $padder(floor($seconds * 1000), 3) . 'ms';
    }

    if ($seconds < 60) {
        return $padder(floor($seconds), 2) . 's';
    }

    if ($seconds < 3600) {
        $minutesLeft = floor($seconds / 60);
        $secondsLeft = floor($seconds - ($minutesLeft * 60));

        return $padder($minutesLeft, 2) . 'min'
            . ', ' . $padder($secondsLeft, 2) . 's';
    }

    if ($seconds < 86400) {
        $hoursLeft = floor($seconds / 3600);
        $minutesLeft = floor(($seconds - ($hoursLeft * 3600)) / 60);
        $secondsLeft = floor($seconds - ($hoursLeft * 3600) - ($minutesLeft * 60));

        return $padder($hoursLeft, 2) . 'h'
            . ', ' . $padder($minutesLeft, 2) . 'min'
            . ', ' . $padder($secondsLeft, 2) . 's';
    }

    $daysLeft = floor($seconds / 86400);
    $hoursLeft = floor(($seconds - ($daysLeft * 86400)) / 3600);
    $minutesLeft = floor(($seconds - ($daysLeft * 86400) - ($hoursLeft * 3600)) / 60);
    $secondsLeft = floor($seconds - ($daysLeft * 86400) - ($hoursLeft * 3600) - ($minutesLeft * 60));

    return $padder($daysLeft, 3) . 'd'
        . ', ' . $padder($hoursLeft, 2) . 'h'
        . ', ' . $padder($minutesLeft, 2) . 'min'
        . ', ' . $padder($secondsLeft, 2) . 's';
}

// eof
