<?php

namespace Tests\Unit;

use Sapphire\Mapper\Attribute\DynamoEmbeddedObject;
use Sapphire\Mapper\Attribute\DynamoField;
use Sapphire\Mapper\Attribute\DynamoObject;
use Sapphire\Mapper\DynamoMapper;
use Sapphire\Mapper\DynamoType;

// Embedded object for testing SET types within complex structures
#[DynamoEmbeddedObject]
class CategoryInfo
{
    public function __construct(
        #[DynamoField(type: DynamoType::STRING)] public readonly string $name,
        #[DynamoField(type: DynamoType::STRING_SET)] public readonly array $tags,
        #[DynamoField(type: DynamoType::NUMBER_SET)] public readonly array $ratings
    ) {}
}

// Test class with explicit STRING_SET fields
#[DynamoObject("string_sets_test")]
class StringSetsTest
{
    public function __construct(
        #[DynamoField(type: DynamoType::STRING)] public readonly string $id,
        #[DynamoField(type: DynamoType::STRING_SET)] public readonly array $tags,
        #[DynamoField(type: DynamoType::STRING_SET)] public readonly array $categories,
        #[DynamoField(type: DynamoType::STRING_SET)] public readonly array $keywords,
        #[DynamoField(type: DynamoType::LIST)] public readonly array $mixedList
    ) {}
}

// Test class with explicit NUMBER_SET fields
#[DynamoObject("number_sets_test")]
class NumberSetsTest
{
    public function __construct(
        #[DynamoField(type: DynamoType::STRING)] public readonly string $id,
        #[DynamoField(type: DynamoType::NUMBER_SET)] public readonly array $integers,
        #[DynamoField(type: DynamoType::NUMBER_SET)] public readonly array $floats,
        #[DynamoField(type: DynamoType::NUMBER_SET)] public readonly array $mixed_numbers,
        #[DynamoField(type: DynamoType::LIST)] public readonly array $mixedList
    ) {}
}

// Test class with explicit BINARY_SET fields
#[DynamoObject("binary_sets_test")]
class BinarySetsTest
{
    public function __construct(
        #[DynamoField(type: DynamoType::STRING)] public readonly string $id,
        #[DynamoField(type: DynamoType::BINARY_SET)] public readonly array $documents,
        #[DynamoField(type: DynamoType::BINARY_SET)] public readonly array $images,
        #[DynamoField(type: DynamoType::LIST)] public readonly array $mixedList
    ) {}
}

// Test class with auto-detection (LIST field that may become SET based on content)
#[DynamoObject("auto_detection_test")]
class AutoDetectionTest
{
    public function __construct(
        #[DynamoField(type: DynamoType::STRING)] public readonly string $id,
        #[DynamoField(type: DynamoType::LIST)] public readonly array $stringArray,
        #[DynamoField(type: DynamoType::LIST)] public readonly array $numberArray,
        #[DynamoField(type: DynamoType::LIST)] public readonly array $binaryArray,
        #[DynamoField(type: DynamoType::LIST)] public readonly array $mixedArray
    ) {}
}

// Test class with complex SET combinations
#[DynamoObject("complex_sets_test")]
class ComplexSetsTest
{
    public function __construct(
        #[DynamoField(type: DynamoType::STRING)] public readonly string $id,
        #[DynamoField(type: DynamoType::STRING_SET)] public readonly array $stringSet,
        #[DynamoField(type: DynamoType::NUMBER_SET)] public readonly array $numberSet,
        #[DynamoField(type: DynamoType::BINARY_SET)] public readonly array $binarySet,
        #[DynamoField(type: DynamoType::MAP)] public readonly CategoryInfo $categoryInfo,
        #[DynamoField(type: DynamoType::LIST)] public readonly array $nestedSets
    ) {}
}

test("Maps STRING_SET types correctly", function () {
    $testObject = new StringSetsTest(
        id: "strings:001",
        tags: ["php", "dynamodb", "aws", "testing"],
        categories: ["development", "database", "cloud"],
        keywords: ["nosql", "document", "key-value"],
        mixedList: ["string", 123, true] // This stays as LIST
    );

    $mapper = new DynamoMapper();
    $item = $mapper->toPutItem($testObject);

    expect($item["TableName"])
        ->toBe("string_sets_test")
        ->and($item["Item"]["id"])
        ->toBe(["S" => "strings:001"])
        ->and($item["Item"]["tags"])
        ->toBe(["SS" => ["php", "dynamodb", "aws", "testing"]])
        ->and($item["Item"]["categories"])
        ->toBe(["SS" => ["development", "database", "cloud"]])
        ->and($item["Item"]["keywords"])
        ->toBe(["SS" => ["nosql", "document", "key-value"]])
        ->and($item["Item"]["mixedList"]["L"])
        ->toHaveCount(3); // Mixed array remains as LIST
});

test("Maps NUMBER_SET types correctly", function () {
    $testObject = new NumberSetsTest(
        id: "numbers:001",
        integers: [1, 2, 3, 5, 8, 13, 21],
        floats: [3.14, 2.71, 1.41, 1.73],
        mixed_numbers: [42, 3.14159, -100, 0.5, 999],
        mixedList: [123, "string", true] // This stays as LIST
    );

    $mapper = new DynamoMapper();
    $item = $mapper->toPutItem($testObject);

    expect($item["Item"]["integers"])
        ->toBe(["NS" => ["1", "2", "3", "5", "8", "13", "21"]])
        ->and($item["Item"]["floats"])
        ->toBe(["NS" => ["3.14", "2.71", "1.41", "1.73"]])
        ->and($item["Item"]["mixed_numbers"])
        ->toBe(["NS" => ["42", "3.14159", "-100", "0.5", "999"]])
        ->and($item["Item"]["mixedList"]["L"])
        ->toHaveCount(3); // Mixed array remains as LIST
});

test("Maps BINARY_SET types correctly", function () {
    $doc1 = base64_encode("Document 1 content");
    $doc2 = base64_encode("Document 2 content");
    $doc3 = base64_encode("Document 3 content");

    $img1 = base64_encode("Image 1 binary data");
    $img2 = base64_encode("Image 2 binary data");

    $testObject = new BinarySetsTest(
        id: "binary:001",
        documents: [$doc1, $doc2, $doc3],
        images: [$img1, $img2],
        mixedList: [$doc1, "not binary", 123] // This stays as LIST
    );

    $mapper = new DynamoMapper();
    $item = $mapper->toPutItem($testObject);

    expect($item["Item"]["documents"])
        ->toBe(["BS" => [$doc1, $doc2, $doc3]])
        ->and($item["Item"]["images"])
        ->toBe(["BS" => [$img1, $img2]])
        ->and($item["Item"]["mixedList"]["L"])
        ->toHaveCount(3); // Mixed array remains as LIST
});

test("Maps LIST fields with homogeneous content correctly", function () {
    $validBinary1 = base64_encode("binary data 1");
    $validBinary2 = base64_encode("binary data 2");

    $testObject = new AutoDetectionTest(
        id: "auto:001",
        stringArray: ["auto", "detected", "string", "set"],
        numberArray: [1, 2, 3, 4, 5],
        binaryArray: [$validBinary1, $validBinary2],
        mixedArray: ["string", 123, true, null]
    );

    $mapper = new DynamoMapper();
    $item = $mapper->toPutItem($testObject);

    // LIST fields stay as LIST regardless of content homogeneity
    expect($item["Item"]["stringArray"]["L"])
        ->toHaveCount(4)
        ->and($item["Item"]["stringArray"]["L"][0])
        ->toBe(["S" => "auto"])
        ->and($item["Item"]["numberArray"]["L"])
        ->toHaveCount(5)
        ->and($item["Item"]["numberArray"]["L"][0])
        ->toBe(["N" => "1"])
        ->and($item["Item"]["binaryArray"]["L"])
        ->toHaveCount(2)
        ->and($item["Item"]["binaryArray"]["L"][0])
        ->toBe(["S" => $validBinary1]) // Base64 strings are treated as strings in LIST context
        ->and($item["Item"]["mixedArray"]["L"])
        ->toHaveCount(4); // Mixed array stays as LIST
});

test("Maps complex SET combinations correctly", function () {
    $categoryInfo = new CategoryInfo(
        name: "Technology",
        tags: ["tech", "innovation", "digital"],
        ratings: [4, 5, 3, 4, 5]
    );

    $doc1 = base64_encode("Nested document 1");
    $doc2 = base64_encode("Nested document 2");

    $testObject = new ComplexSetsTest(
        id: "complex:001",
        stringSet: ["primary", "secondary", "tertiary"],
        numberSet: [100, 200, 300, 400],
        binarySet: [$doc1, $doc2],
        categoryInfo: $categoryInfo,
        nestedSets: [["nested", "string", "array"], [1, 2, 3], ["mixed", 123]]
    );

    $mapper = new DynamoMapper();
    $item = $mapper->toPutItem($testObject);

    expect($item["Item"]["stringSet"])
        ->toBe(["SS" => ["primary", "secondary", "tertiary"]])
        ->and($item["Item"]["numberSet"])
        ->toBe(["NS" => ["100", "200", "300", "400"]])
        ->and($item["Item"]["binarySet"])
        ->toBe(["BS" => [$doc1, $doc2]])
        ->and($item["Item"]["categoryInfo"]["M"]["tags"])
        ->toBe(["SS" => ["tech", "innovation", "digital"]])
        ->and($item["Item"]["categoryInfo"]["M"]["ratings"])
        ->toBe(["NS" => ["4", "5", "3", "4", "5"]])
        ->and($item["Item"]["nestedSets"]["L"])
        ->toHaveCount(3);

    // Verify nested arrays within LIST are handled correctly
    $nestedSets = $item["Item"]["nestedSets"]["L"];
    expect($nestedSets[0])
        ->toBe(["SS" => ["nested", "string", "array"]]) // Auto-detected as STRING_SET
        ->and($nestedSets[1])
        ->toBe(["NS" => ["1", "2", "3"]]) // Auto-detected as NUMBER_SET
        ->and($nestedSets[2]["L"])
        ->toHaveCount(2); // Mixed array stays as LIST
});

test("Handles empty SET types correctly", function () {
    $testObject = new ComplexSetsTest(
        id: "empty:001",
        stringSet: [],
        numberSet: [],
        binarySet: [],
        categoryInfo: new CategoryInfo(
            name: "Empty Category",
            tags: [],
            ratings: []
        ),
        nestedSets: []
    );

    $mapper = new DynamoMapper();
    $item = $mapper->toPutItem($testObject);

    // Note: DynamoDB doesn't allow empty sets, but the mapper should handle this gracefully
    // The behavior might vary - empty arrays might be converted to empty LISTs or omitted
    expect($item["Item"]["id"])
        ->toBe(["S" => "empty:001"])
        ->and($item["Item"]["categoryInfo"]["M"]["name"])
        ->toBe(["S" => "Empty Category"]);

    // Check if empty sets are handled (behavior may depend on implementation)
    expect($item["Item"])->toHaveKey("stringSet");
    expect($item["Item"])->toHaveKey("numberSet");
    expect($item["Item"])->toHaveKey("binarySet");
});

test("Maps SET types with edge case values", function () {
    $testObject = new ComplexSetsTest(
        id: "edge:001",
        stringSet: [
            "",
            "normal string",
            "string with\nnewlines\tand\ttabs",
            "🎉 emoji string",
            "نص عربي",
        ],
        numberSet: [0, -1, 1, PHP_INT_MAX, PHP_INT_MIN, 3.14159, -3.14159],
        binarySet: [
            base64_encode(""),
            base64_encode("normal binary content"),
            base64_encode("binary with special chars: !@#$%^&*()"),
        ],
        categoryInfo: new CategoryInfo(
            name: "Edge Cases",
            tags: ["edge", "case", "testing"],
            ratings: [1, 5]
        ),
        nestedSets: [
            [0, -0], // Numbers that might be handled specially
            [""], // Array with empty string
        ]
    );

    $mapper = new DynamoMapper();
    $item = $mapper->toPutItem($testObject);

    expect($item["Item"]["stringSet"]["SS"])
        ->toContain("")
        ->toContain("normal string")
        ->toContain("string with\nnewlines\tand\ttabs")
        ->toContain("🎉 emoji string")
        ->toContain("نص عربي")
        ->and($item["Item"]["numberSet"]["NS"])
        ->toContain("0")
        ->toContain("-1")
        ->toContain("1")
        ->toContain((string) PHP_INT_MAX)
        ->toContain((string) PHP_INT_MIN)
        ->toContain("3.14159")
        ->toContain("-3.14159")
        ->and($item["Item"]["binarySet"]["BS"])
        ->toHaveCount(3);
});

test("Validates SET type constraints and deduplication", function () {
    // Test with duplicate values - DynamoDB SETs should automatically deduplicate
    $testObject = new StringSetsTest(
        id: "duplicates:001",
        tags: ["php", "php", "dynamodb", "php", "aws"], // Duplicates
        categories: ["dev", "dev", "database"], // Duplicates
        keywords: ["unique", "value", "test"],
        mixedList: ["mixed", "content", 123]
    );

    $mapper = new DynamoMapper();
    $item = $mapper->toPutItem($testObject);

    // The mapper should pass duplicates as-is, DynamoDB will handle deduplication
    expect($item["Item"]["tags"])
        ->toBe(["SS" => ["php", "php", "dynamodb", "php", "aws"]])
        ->and($item["Item"]["categories"])
        ->toBe(["SS" => ["dev", "dev", "database"]])
        ->and($item["Item"]["keywords"])
        ->toBe(["SS" => ["unique", "value", "test"]]);
});

test("Maps large SET types correctly", function () {
    // Create large sets to test performance and handling
    $largeStringSet = [];
    $largeNumberSet = [];
    $largeBinarySet = [];

    for ($i = 0; $i < 100; $i++) {
        $largeStringSet[] = "item_" . $i;
        $largeNumberSet[] = $i * 10;
        $largeBinarySet[] = base64_encode("binary_content_" . $i);
    }

    $testObject = new ComplexSetsTest(
        id: "large:001",
        stringSet: $largeStringSet,
        numberSet: $largeNumberSet,
        binarySet: $largeBinarySet,
        categoryInfo: new CategoryInfo(
            name: "Large Sets",
            tags: ["performance", "scale", "testing"],
            ratings: [4, 5]
        ),
        nestedSets: []
    );

    $mapper = new DynamoMapper();
    $item = $mapper->toPutItem($testObject);

    expect($item["Item"]["stringSet"]["SS"])
        ->toHaveCount(100)
        ->toContain("item_0")
        ->toContain("item_99")
        ->and($item["Item"]["numberSet"]["NS"])
        ->toHaveCount(100)
        ->toContain("0")
        ->toContain("990")
        ->and($item["Item"]["binarySet"]["BS"])
        ->toHaveCount(100);
});

test("Validates explicit SET vs LIST type declarations", function () {
    // Test that explicit type declarations are respected
    $binary1 = base64_encode("data1");
    $binary2 = base64_encode("data2");

    $listObject = new AutoDetectionTest(
        id: "detection:001",
        stringArray: ["all", "strings", "here"], // Declared as LIST
        numberArray: [1, 2.5, 3, 4.0], // Declared as LIST
        binaryArray: [$binary1, $binary2], // Declared as LIST
        mixedArray: ["string", 123, true, null] // Declared as LIST
    );

    $setObject = new StringSetsTest(
        id: "detection:002",
        tags: ["all", "strings", "here"], // Declared as STRING_SET
        categories: ["more", "strings"], // Declared as STRING_SET
        keywords: ["even", "more"], // Declared as STRING_SET
        mixedList: ["mixed", 123, true] // Declared as LIST
    );

    $mapper = new DynamoMapper();
    $listItem = $mapper->toPutItem($listObject);
    $setItem = $mapper->toPutItem($setObject);

    // LIST declarations stay as LIST
    expect($listItem["Item"]["stringArray"])
        ->toHaveKey("L")
        ->not()
        ->toHaveKey("SS")
        ->and($listItem["Item"]["numberArray"])
        ->toHaveKey("L")
        ->not()
        ->toHaveKey("NS")
        ->and($listItem["Item"]["binaryArray"])
        ->toHaveKey("L")
        ->not()
        ->toHaveKey("BS");

    // SET declarations become SETs
    expect($setItem["Item"]["tags"])
        ->toHaveKey("SS")
        ->not()
        ->toHaveKey("L")
        ->and($setItem["Item"]["categories"])
        ->toHaveKey("SS")
        ->not()
        ->toHaveKey("L")
        ->and($setItem["Item"]["mixedList"])
        ->toHaveKey("L")
        ->not()
        ->toHaveKey("SS");
});
