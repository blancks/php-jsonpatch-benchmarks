<?php declare(strict_types=1);

use blancks\JsonPatch\exceptions\InvalidPatchPathException;
use blancks\JsonPatch\FastJsonPatch;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FastJsonPatch::class)]
#[UsesClass(InvalidPatchPathException::class)]
final class JsonPatchTest extends TestCase
{
    #[DataProvider('validOperationsProvider')]
    public function testBlancks_fast_jsonpatch(string $json, string $patch, string $expected): void
    {
        $this->assertSame(
            json_encode(json_decode($expected, false, 512, JSON_THROW_ON_ERROR)),
            FastJsonPatch::apply($json, $patch)
        );
    }

    #[DataProvider('validOperationsProvider')]
    public function testGamringer_php_json_patch(string $json, string $patch, string $expected): void
    {
        $document = json_decode($json);
        $Patch = \gamringer\JSONPatch\Patch::fromJSON($patch);
        $Patch->apply($document);

        $this->assertSame(
            $this->normalizeJson($expected),
            $this->normalizeJson(json_encode($document))
        );
    }

    #[DataProvider('validOperationsProvider')]
    public function testMikemccabe_json_patch_php(string $json, string $patch, string $expected): void
    {
        $json = json_decode($json, true);
        $patch = json_decode($patch, true);
        $document = \mikemccabe\JsonPatch\JsonPatch::patch($json, $patch);

        $this->assertSame(
            $this->normalizeJson($expected),
            $this->normalizeJson(json_encode($document))
        );
    }

    #[DataProvider('validOperationsProvider')]
    public function testPhp_jsonpatch_php_jsonpatch(string $json, string $patch, string $expected): void
    {
        $Patch = new Rs\Json\Patch($json, $patch);
        $document = json_decode($Patch->apply(), false, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(
            $this->normalizeJson($expected),
            $this->normalizeJson(json_encode($document))
        );
    }

    #[DataProvider('validOperationsProvider')]
    public function testRemorhaz_php_json_patch(string $json, string $patch, string $expected): void
    {
        $encodedValueFactory = Remorhaz\JSON\Data\Value\EncodedJson\NodeValueFactory::create();
        $queryFactory = Remorhaz\JSON\Patch\Query\QueryFactory::create();
        $processor = Remorhaz\JSON\Patch\Processor\Processor::create();

        $patch = $encodedValueFactory->createValue($patch);
        $query = $queryFactory->createQuery($patch);
        $document = $encodedValueFactory->createValue($json);
        $result = $processor->apply($query, $document);
        $documentString = $result->encode();

        $this->assertSame(
            $this->normalizeJson($expected),
            $this->normalizeJson($documentString)
        );
    }

    #[DataProvider('validOperationsProvider')]
    public function testSwaggest_json_diff(string $json, string $patch, string $expected): void
    {
        $document = json_decode($json);
        $patch = json_decode($patch);
        $patch = \Swaggest\JsonDiff\JsonPatch::import($patch);
        $patch->apply($document);

        $this->assertSame(
            $this->normalizeJson($expected),
            $this->normalizeJson(json_encode($document))
        );
    }

    #[DataProvider('validOperationsProvider')]
    public function testXp_forge_json_patch(string $json, string $patch, string $expected): void
    {
        $document = json_decode($json, true);
        $patch = json_decode($patch, true);

        $Changes = new \text\json\patch\Changes(...$patch);
        $document = $Changes->apply($document)->value();

        $this->assertSame(
            $this->normalizeJson($expected),
            $this->normalizeJson(json_encode($document))
        );
    }

    public static function validOperationsProvider(): array
    {
        return [
            /*
             Some classes handles empty patch by throwing an exception.
             I'll avoid these test cases to be as much as fair as possible

            'Empty patch against empty document' => [
                '{}',
                '[]',
                '{}'
            ],
            'Empty patch against non-empty document' => [
                '{"foo": 1}',
                '[]',
                '{"foo": 1}'
            ],
            'Empty patch against top-level array document' => [
                '["foo"]',
                '[]',
                '["foo"]'
            ],
            */
            'Add patch replaces existing value' => [
                '{"foo": 1}',
                '[{"op": "add", "path": "/foo", "value": "Hello World"}]',
                '{"foo": "Hello World"}'
            ],
            'Add item to index zero into top-level array document' => [
                '[]',
                '[{"op": "add", "path": "/0", "value": "foo"}]',
                '["foo"]'
            ],
            'Add item to index one into top-level array document' => [
                '["foo"]',
                '[{"op": "add", "path": "/1", "value": "bar"}]',
                '["foo","bar"]'
            ],
            'Add item ahead of existing ones into top-level array document' => [
                '["foo","bar"]',
                '[{"op": "add", "path": "/0", "value": "first"}]',
                '["first","foo","bar"]'
            ],
            'Add item in the middle of two existing ones into top-level array document' => [
                '["foo","bar"]',
                '[{"op": "add", "path": "/1", "value": "inbetween"}]',
                '["foo","inbetween","bar"]'
            ],
            'Add item at the end of existing ones into top-level array document' => [
                '["foo","bar"]',
                '[{"op": "add", "path": "/2", "value": "last"}]',
                '["foo","bar","last"]'
            ],
            'Add new property with zero as object property name' => [
                '{"foo": 1}',
                '[{"op": "add", "path": "/0", "value": "bar"}]',
                '{"foo": 1, "0": "bar" }'
            ],
            'Add item into top-level array document with the append symbol "-"' => [
                '[]',
                '[{"op": "add", "path": "/-", "value": "foo"}]',
                '["foo"]'
            ],
            'Add null into top-level array document with the append symbol "-"' => [
                '[]',
                '[{"op": "add", "path": "/-", "value": null}]',
                '[null]'
            ],
            'Add object into top-level array document with the append symbol "-"' => [
                '[]',
                '[{"op": "add", "path": "/-", "value":{"foo":"bar"}}]',
                '[{"foo":"bar"}]'
            ],
            'Add object into nested array with the append symbol "-"' => [
                '[ 1, 2, [ 3, [ 4, 5 ] ] ]',
                '[ { "op": "add", "path": "/2/1/-", "value": { "foo": [ "bar", "baz" ] } } ]',
                '[ 1, 2, [ 3, [ 4, 5, { "foo": [ "bar", "baz" ] } ] ] ]'
            ],
            'Add test against unexpected flattened values in document array' => [
                '["foo", "sil"]',
                '[{"op": "add", "path": "/1", "value": ["bar", "baz"]}]',
                '["foo", ["bar", "baz"], "sil"]'
            ],
            'Add numeric string into top-level object' => [
                '{}',
                '[{"op": "add", "path": "/foo", "value": "1"}]',
                '{"foo":"1"}'
            ],
            'Add integer into top-level object' => [
                '{}',
                '[{"op": "add", "path": "/foo", "value": 1}]',
                '{"foo":1}'
            ],
            'Add integer into top-level object with an empty string key' => [
                '{}',
                '[{"op": "add", "path": "/", "value": 1}]',
                '{"":1}'
            ],
            'Add integer into top-level object with a numeric key' => [
                '{}',
                '[{"op": "add", "path": "/0", "value": 1}]',
                '{"0":1}'
            ],
            'Add new array value property at top-level object document' => [
                '{"foo": 1}',
                '[{"op": "add", "path": "/bar", "value": [1, 2]}]',
                '{"foo": 1, "bar": [1,2]}'
            ],
            'Add item into existing array' => [
                '{"foo": 1, "baz": [{"qux": "hello"}]}',
                '[{"op": "add", "path": "/baz/0/foo", "value": "world"}]',
                '{"foo": 1, "baz": [{"qux": "hello", "foo": "world"}]}'
            ],
            'Add new boolean value property (true) into object document' => [
                '{"foo": 1}',
                '[{"op": "add", "path": "/bar", "value": true}]',
                '{"foo": 1, "bar": true}'
            ],
            'Add new boolean value property (false) into object document' => [
                '{"foo": 1}',
                '[{"op": "add", "path": "/bar", "value": false}]',
                '{"foo": 1, "bar": false}'
            ],
            'Add new NULL value property into object document' => [
                '{"foo": 1}',
                '[{"op": "add", "path": "/bar", "value": null}]',
                '{"foo": 1, "bar": null}'
            ],
            'Add can replace the root of the document' => [
                '{"foo": "bar"}',
                '[{"op": "add", "path": "", "value": {"baz": "qux"}}]',
                '{"baz":"qux"}'
            ],
            'Add multiple patches at once' => [
                '{}',
                '[{"op": "add", "path": "/foo", "value": "Hello"},
                {"op": "add", "path": "/bar", "value": "World"},
                {"op": "add", "path": "/array", "value": []},
                {"op": "add", "path": "/array/-", "value": "one"},
                {"op": "add", "path": "/array/1", "value": "three"},
                {"op": "add", "path": "/array/1", "value": "two"}]',
                '{"foo":"Hello", "bar":"World", "array":["one","two","three"]}'
            ],
            'Move to same location has no effect' => [
                '{"foo": 1}',
                '[{"op": "move", "from": "/foo", "path": "/foo"}]',
                '{"foo": 1}'
            ],
            'Move property into the same object' => [
                '{"foo": 1, "baz": [{"qux": "hello"}]}',
                '[{"op": "move", "from": "/foo", "path": "/bar"}]',
                '{"baz": [{"qux": "hello"}], "bar": 1}'
            ],
            'Move an object property value into an array' => [
                '{"baz": [{"qux": "hello"}], "bar": 1}',
                '[{"op": "move", "from": "/baz/0/qux", "path": "/baz/1"}]',
                '{"baz": [{}, "hello"], "bar": 1}'
            ],
            'Move entire object into an array' => [
                '{"baz": [], "bar": {"qux": "hello"}}',
                '[{"op": "move", "from": "/bar", "path": "/baz/0"}]',
                '{"baz": [{"qux": "hello"}]}'
            ],
            'Copy a null value' => [
                '{"baz": null}',
                '[{"op": "copy", "from": "/baz", "path": "/foo"}]',
                '{"baz": null, "foo": null}'
            ],
            'Copy a boolean true value' => [
                '{"baz": true}',
                '[{"op": "copy", "from": "/baz", "path": "/foo"}]',
                '{"baz": true, "foo": true}'
            ],
            'Copy a boolean false value' => [
                '{"baz": true}',
                '[{"op": "copy", "from": "/baz", "path": "/foo"}]',
                '{"baz": true, "foo": true}'
            ],
            'Copy a integer value' => [
                '{"baz": 1}',
                '[{"op": "copy", "from": "/baz", "path": "/foo"}]',
                '{"baz": 1, "foo": 1}'
            ],
            'Copy a string value' => [
                '{"baz": "Hello World"}',
                '[{"op": "copy", "from": "/baz", "path": "/foo"}]',
                '{"baz": "Hello World", "foo": "Hello World"}'
            ],
            'Copy an object to a different nesting level' => [
                '{"baz": [{"qux": "hello"}], "bar": 1}',
                '[{"op": "copy", "from": "/baz/0", "path": "/boo"}]',
                '{"baz":[{"qux":"hello"}],"bar":1,"boo":{"qux":"hello"}}'
            ],
            'Copy an array to a different nesting level' => [
                '{"baz": [], "bar": 1, "qux": ["hello", "world"]}',
                '[{"op": "copy", "from": "/qux", "path": "/baz/0"}]',
                '{"baz": [["hello", "world"]], "bar": 1, "qux": ["hello", "world"]}'
            ],
            'Remove null value' => [
                '{"foo": null}',
                '[{"op": "remove", "path": "/foo"}]',
                '{}'
            ],
            'Remove boolean true value' => [
                '{"foo": true}',
                '[{"op": "remove", "path": "/foo"}]',
                '{}'
            ],
            'Remove boolean false value' => [
                '{"foo": false}',
                '[{"op": "remove", "path": "/foo"}]',
                '{}'
            ],
            'Remove integer value' => [
                '{"foo": 1}',
                '[{"op": "remove", "path": "/foo"}]',
                '{}'
            ],
            'Remove string value' => [
                '{"foo": "Hello World"}',
                '[{"op": "remove", "path": "/foo"}]',
                '{}'
            ],
            'Remove object property from document' => [
                '{"foo": 1, "bar": [1, 2, 3, 4]}',
                '[{"op": "remove", "path": "/bar"}]',
                '{"foo": 1}'
            ],
            'Remove object property leaving an empty object' => [
                '{"foo": 1, "baz": [{"qux": "hello"}]}',
                '[{"op": "remove", "path": "/baz/0/qux"}]',
                '{"foo": 1, "baz": [{}]}'
            ],
            'Remove on array items' => [
                '[1, 2, 3, 4]',
                '[{"op": "remove", "path": "/0"}]',
                '[2, 3, 4]'
            ],
            'Remove entire array' => [
                '[1, 2, 3, 4, [1,2]]',
                '[{"op": "remove", "path": "/4"}]',
                '[1, 2, 3, 4]'
            ],
            'Replace object property with a different value type' => [
                '{"foo": 1, "baz": [{"qux": "hello"}]}',
                '[{"op": "replace", "path": "/foo", "value": [1, 2, 3, 4]}]',
                '{"baz": [{"qux": "hello"}], "foo": [1, 2, 3, 4]}'
            ],
            'Replace a more nested object property' => [
                '{"foo": [1, 2, 3, 4], "baz": [{"qux": "hello"}]}',
                '[{"op": "replace", "path": "/baz/0/qux", "value": "world"}]',
                '{"foo": [1, 2, 3, 4], "baz": [{"qux": "world"}]}'
            ],
            'Replace an indexed array item' => [
                '["foo"]',
                '[{"op": "replace", "path": "/0", "value": "bar"}]',
                '["bar"]'
            ],
            'Replace an empty string item with a zero' => [
                '[""]',
                '[{"op": "replace", "path": "/0", "value": 0}]',
                '[0]'
            ],
            'Replace an empty string item with boolean true' => [
                '[""]',
                '[{"op": "replace", "path": "/0", "value": true}]',
                '[true]'
            ],
            'Replace an empty string item with boolean false' => [
                '[""]',
                '[{"op": "replace", "path": "/0", "value": false}]',
                '[false]'
            ],
            'Replace an empty string item with a null value' => [
                '[""]',
                '[{"op": "replace", "path": "/0", "value": null}]',
                '[null]'
            ],
            'Replace value in array without flattening' => [
                '["foo", "sil"]',
                '[{"op": "replace", "path": "/1", "value": ["bar", "baz"]}]',
                '["foo", ["bar", "baz"]]'
            ],
            'Replace whole document' => [
                '{"foo": "bar"}',
                '[{"op": "replace", "path": "", "value": {"baz": "qux"}}]',
                '{"baz": "qux"}'
            ],
            'Test against implementation-specific numeric parsing' => [
                '{"1e0": "foo"}',
                '[{"op": "test", "path": "/1e0", "value": "foo"}]',
                '{"1e0": "foo"}'
            ],
            'Test with optional patch properties' => [
                '{"foo": 1}',
                '[{"op": "test", "path": "/foo", "value": 1, "eeeew": 1}]',
                '{"foo": 1}'
            ],
            'Test null properties are still valid' => [
                '{"foo": null}',
                '[{"op": "test", "path": "/foo", "value": null}]',
                '{"foo": null}'
            ],
            'Test should pass despite different arrangement' => [
                '{"foo": {"foo": 1, "bar": 2}}',
                '[{"op": "test", "path": "/foo", "value": {"bar": 2, "foo": 1}}]',
                '{"foo": {"foo": 1, "bar": 2}}'
            ],
            'Test should pass despite different arrangement (array nested)' => [
                '{"foo": [{"foo": 1, "bar": 2}]}',
                '[{"op": "test", "path": "/foo", "value": [{"bar": 2, "foo": 1}]}]',
                '{"foo": [{"foo": 1, "bar": 2}]}'
            ],
            'Test indexed array' => [
                '{"foo": {"bar": [1, 2, 5, 4]}}',
                '[{"op": "test", "path": "/foo", "value": {"bar": [1, 2, 5, 4]}}]',
                '{"foo": {"bar": [1, 2, 5, 4]}}'
            ],
            'Test whole document' => [
                '{ "foo": 1 }',
                '[{"op": "test", "path": "", "value": {"foo": 1}}]',
                '{ "foo": 1 }'
            ],
            'Test empty string element' => [
                '{ "": 1 }',
                '[{"op": "test", "path": "/", "value": 1}]',
                '{ "": 1 }'
            ],
            // https://datatracker.ietf.org/doc/html/rfc6901#section-5
            'Test valid JSON pointers' => [
                '{
                    "foo": ["bar", "baz"],
                    "": 0,
                    "a/b": 1,
                    "c%d": 2,
                    "e^f": 3,
                    "g|h": 4,
                    "i\\\\j": 5,
                    "k\"l": 6,
                    " ": 7,
                    "m~n": 8
                }',
                '[
                    {"op": "test", "path": "/foo", "value": ["bar", "baz"]},
                    {"op": "test", "path": "/foo/0", "value": "bar"},
                    {"op": "test", "path": "/", "value": 0},
                    {"op": "test", "path": "/a~1b", "value": 1},
                    {"op": "test", "path": "/c%d", "value": 2},
                    {"op": "test", "path": "/e^f", "value": 3},
                    {"op": "test", "path": "/g|h", "value": 4},
                    {"op": "test", "path":  "/i\\\\j", "value": 5},
                    {"op": "test", "path": "/k\"l", "value": 6},
                    {"op": "test", "path": "/ ", "value": 7},
                    {"op": "test", "path": "/m~0n", "value": 8}
                ]',
                '{
                    "foo": ["bar", "baz"],
                    "": 0,
                    "a/b": 1,
                    "c%d": 2,
                    "e^f": 3,
                    "g|h": 4,
                    "i\\\\j": 5,
                    "k\"l": 6,
                    " ": 7,
                    "m~n": 8
                }'
            ],
        ];
    }

    private function normalizeJson(string $json): string
    {
        $document = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
        $this->recursiveKeySort($document);
        return json_encode($document);
    }

    private function recursiveKeySort(array|\stdClass &$a): void
    {
        foreach ($a as &$item) {
            if (is_array($item) || is_object($item)) {
                if ($item instanceof \stdClass) {
                    $item = (array)$item;
                    $this->recursiveKeySort($item);
                    $item = (object)$item;
                    continue;
                }

                $this->recursiveKeySort($item);
            }
        }

        if ($a instanceof \stdClass) {
            $a = (array)$a;
            ksort($a, SORT_STRING);
            $a = (object)$a;
            return;
        }

        ksort($a, SORT_STRING);
    }
}
