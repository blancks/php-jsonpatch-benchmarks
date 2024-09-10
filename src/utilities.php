<?php declare(strict_types=1);

namespace blancks\JsonPatchBenchmark;

/**
 * Returns all available library for the benchmark suite
 * @param string $libFolder path to the folder where libraries are implemented for the benchmark
 * @return string[]
 */
function getAvailableLibraries(string $libFolder): array
{
    $libraries = [];

    foreach (array_diff(scandir($libFolder), ['.', '..']) as $lib) {
        $libraries[] = pathinfo($lib, PATHINFO_FILENAME);
    }

    return $libraries;
}

/**
 * Runs the PHPUnit test suite for $library
 * @param string $library target library from getAvailableLibraries()
 * @return array{
 *     status: bool,
 *     output: string
 * }
 */
function runAutomatedTests(string $library): array
{
    $cmd = sprintf(PHPUNIT_TEST_CMD, ucwords(str_replace('-', '_', $library)));
    $output = shell_exec($cmd);

    if (($position = strpos($output, 'OK ')) !== false) {
        return [
            'status' => true,
            'output' => substr($output, $position)
        ];
    }

    if (($position = strpos($output, 'FAILURES!')) !== false) {
        return [
            'status' => false,
            'output' => substr($output, $position)
        ];
    }

    if (($position = strpos($output, 'ERRORS!')) !== false) {
        return [
            'status' => false,
            'output' => substr($output, $position)
        ];
    }

    return [
        'status' => false,
        'output' => 'Unknown Failure'
    ];
}

/**
 * Waits for all Fibers to complete their job and then releases
 * @param \Fiber[] $fibers
 * @param int|null $releaseAfterCompletedCount if provided will release after the specified amount of fibers has finished
 * @return void
 * @throws \Throwable
 */
function waitForFibers(array &$fibers, ?int $releaseAfterCompletedCount = null): void
{
    $completedFibers = 0;
    $releaseAfterCompletedCount ??= count($fibers);

    while (count($fibers) > 0 && $completedFibers < $releaseAfterCompletedCount) {
        // wait a little before checking if child has completed the task
        usleep(1000);

        foreach ($fibers as $i => $Fiber) {
            if ($Fiber->isSuspended()) {
                $Fiber->resume();
            } else if ($Fiber->isTerminated()) {
                ++$completedFibers;
                unset($fibers[$i]);
            }
        }
    }
}

/**
 * Spawn a child process that runs the actual benchmark.
 * @param string $library target library from getAvailableLibraries()
 * @param int $patchsize number of operation of the patch for this benchmark
 * @param int $iterationstart iteration counter starts at this value
 * @param int $iterationend once the patch is applied $iterationend-$iterationstart times the benchmarks ends
 * @return \Fiber a started Fiber for the spawned process
 * @throws \Throwable
 */
function spawnProcessFiber(string $library, int $patchsize, int $iterationstart, int $iterationend): \Fiber
{
    $Fiber = new \Fiber(function(string $library, int $patchsize, int $iterationstart, int $iterationend): void
    {
        $cmd = sprintf(
            'php %s %s %d %d %d',
            BENCHMARK_CMD,
            $library,
            $patchsize,
            $iterationstart,
            $iterationend
        );

        $proc = proc_open(
            $cmd,
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes
        );

        if (!$proc) {
            throw new \RuntimeException('Unable to spawn child process');
        }

        do {
            \Fiber::suspend();
            $status = proc_get_status($proc);
        } while ($status['running']);

        foreach ($pipes as $pipe) {
            fclose($pipe);
        }

        proc_close($proc);

        if ($status['exitcode'] !== 0) {
            throw new \RuntimeException('Unable to perform the benchmark with command "'. $cmd .'"');
        }
    });
    $Fiber->start(...func_get_args());
    return $Fiber;
}

/**
 * Returns the extimated time left in seconds for a given operation to complete
 * @param float $startmicrotime microtime(true) of the time which the operation you want to see progress started
 * @param float $taskCurrentIteration your current task progress
 * @param float $taskMaxIterations the max value that $taskCurrentIteration must reach for the process to be completed
 * @param float $factor allows to extimate non-linear timings
 * @return float
 */
function extimatedTimeLeft(float $startmicrotime, float $taskCurrentIteration, float $taskMaxIterations, float $factor = 1.0): float
{
    return ceil(
        ((microtime(true) - $startmicrotime) / $taskCurrentIteration)
        * ($taskMaxIterations - $taskCurrentIteration)
        * $factor
    );
}

/**
 * Transforms the number of $seconds into minutes, hours and days if needed providing a better human-readable string
 * @param float $seconds
 * @return string
 */
function secondsToHumanTime(float $seconds): string
{
    static $padder = null;
    $padder ??= fn(float $item, int $length) => str_pad((string) $item, $length, ' ', STR_PAD_LEFT);

    $daysLeft = floor($seconds / 86400);
    $hoursLeft = floor(($seconds - ($daysLeft * 86400)) / 3600);
    $minutesLeft = floor(($seconds - ($daysLeft * 86400) - ($hoursLeft * 3600)) / 60);
    $secondsLeft = floor($seconds - ($daysLeft * 86400) - ($hoursLeft * 3600) - ($minutesLeft * 60));

    $output = '';

    if ($daysLeft) {
        $output .= $padder($daysLeft, 2) . 'd, ';
    }

    if ($hoursLeft) {
        $output .= $padder($hoursLeft, 2) . 'h, ';
    }

    if ($minutesLeft) {
        $output .= $padder($minutesLeft, 2) . 'm, ';
    }

    $output .= $padder($secondsLeft, 2) . 's';
    return $output;
}