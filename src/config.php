<?php declare(strict_types=1);

/**
 * Don't exceed your CPU processor count
 */
const THREADS = 4;

/**
 * Test will iterate for each patch size up until the number here
 */
const MAX_PATCHSIZE = 1000;

/**
 * For each patch size the test will be repeated up until the number here
 */
const ITERATIONS_PER_PATCH = 1000;

/**
 * Set patch to work on that nesting level
 */
const PATCH_NESTED_LEVEL = 1;

/**
 * Output folder where the OUTPUT_FILENAME will be written to
 */
const OUTPUT_FOLDER = 'results';

/**
 * CLI instruction to run benchmark processes
 */
const BENCHMARK_CMD = 'php jpbench';

/**
 * CLI instruction to run automated tests
 * the %s value will be replaced with the library name
 */
const PHPUNIT_TEST_CMD = 'php ./vendor/phpunit/phpunit/phpunit --no-configuration --test-suffix %sTest.php ./tests';

// eof
