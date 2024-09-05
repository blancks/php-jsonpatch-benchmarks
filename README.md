# PHP JSON Patch Benchmarks

This repository provides benchmarks for various PHP libraries that implement the JSON Patch standard ([RFC 6902](https://datatracker.ietf.org/doc/html/rfc6902)). \
JSON Patch is a format for describing changes to a JSON document, and there are multiple PHP implementations available. \
This project aims to compare their performance and functionality.


## Libraries Tested

The following PHP JSON Patch libraries are included in the benchmark:

1. [**blancks/fast-jsonpatch-php**](https://github.com/blancks/fast-jsonpatch-php) (v1.1.3)
2. [**mikemccabe/json-patch-php**](https://github.com/mikemccabe/json-patch-php) (dev-master)
3. [**php-jsonpatch/php-jsonpatch**](https://github.com/raphaelstolt/php-jsonpatch) (v4.1.0)
4. [**xp-forge/json-patch**](https://github.com/xp-forge/json-patch) (v2.1.0)
5. [**gamringer/php-json-patch**](https://github.com/gamringer/JSONPatch) (v1.0)
6. [**swaggest/json-diff**](https://github.com/swaggest/json-diff) (v.3.11)
7. [**remorhaz/php-json-patch**](https://github.com/remorhaz/php-json-patch) (v.0.6.1)


## Benchmark Methodology

* The benchmark is designed to measure the performance of each library in applying JSON patches regardless of target document size.
* An automated test suite will be run before the actual benchmark to check if the library correctly implements the RFC.
* Document and patch content is fixed in order to benchmark any additional memory required by the class itself to perform the patch.
* With the standard config, each test benchmarks the application of patches up until 1000 operations size
* Each of the six patch operations is iterated sequentially during the test
* Each benchmark test runs the patch application multiple times to minimize the effects of transient system states. The average execution time is then calculated.


## Environment

The benchmarks were executed on the following system:

- **PHP Version:** 8.2.2-nts
- **Operating System:** Windows 10 Porfessional
- **Memory:** 32GB RAM (4 x 8 GB) 3600 MT/s C18
- **Processor:** Ryzen 7 3700x


## RFC 6902 Compliance Test Results

| Library                     | Tests                                                         |
|-----------------------------|---------------------------------------------------------------|
| blancks/fast-jsonpatch-php  | ![#008000](https://placehold.co/15x15/008000/008000.png) PASS |
| mikemccabe/json-patch-php   | ![#990000](https://placehold.co/15x15/990000/990000.png) FAIL |
| php-jsonpatch/php-jsonpatch | ![#990000](https://placehold.co/15x15/990000/990000.png) FAIL |
| xp-forge/json-patch         | ![#990000](https://placehold.co/15x15/990000/990000.png) FAIL |
| gamringer/php-json-patch    | ![#990000](https://placehold.co/15x15/990000/990000.png) FAIL |
| swaggest/json-diff          | ![#008000](https://placehold.co/15x15/008000/008000.png) PASS |
| remorhaz/php-json-patch     | ![#008000](https://placehold.co/15x15/008000/008000.png) PASS |

> **Note:** Libraries that fails this test will be benchmarked as well if no error occurs

## Benchmark Results

...

> **Note:** These results are indicative and may vary depending on the specific use case and system environment.


## How to Run the Benchmarks

To run the benchmarks yourself, clone this repository and install the dependencies using Composer:

```bash
git clone https://github.com/blancks/php-jsonpatch-benchmarks
cd php-jsonpatch-benchmarks
composer install
```

Then, you can execute the benchmark script:
```bash
php jpbench [library]
```

**[library]** may be one of the following:
* blancks_fast-jsonpatch
* gamringer_php-json-patch
* mikemccabe_json-patch-php
* php-jsonpatch_php-jsonpatch
* remorhaz_php_json_patch
* swaggest_json-diff
* xp-forge_json-patch

for example:
```bash
php jpbench blancks_fast-jsonpatch
```


## Contributing

Contributions are welcome! If you know of another JSON Patch library that should be included in these benchmarks or have suggestions for improving the benchmarking process, please open an issue or submit a pull request.


## Acknowledgements

Special thanks to the authors of the JSON Patch libraries tested in this benchmark.


## License

This software is licensed under the [MIT License](LICENSE.md).