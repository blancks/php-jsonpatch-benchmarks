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
 * Output filename where test data will be written to
 */
const OUTPUT_FILENAME = 'benchmark.csv';

/**
 * CLI instruction to run automated tests
 * the %s value will be replaced with the library name
 */
const PHPUNIT_TEST_CMD = 'php ./vendor/phpunit/phpunit/phpunit --no-configuration --filter "/(JsonPatchTest::test%s)( .*)?$/" --test-suffix JsonPatchTest.php .\tests';

// eof
