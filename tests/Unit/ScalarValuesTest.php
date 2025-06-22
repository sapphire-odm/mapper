<?php

namespace Tests\Unit;

use Sapphire\Mapper\Attribute\DynamoField;
use Sapphire\Mapper\Attribute\DynamoObject;
use Sapphire\Mapper\DynamoMapper;
use Sapphire\Mapper\DynamoType;

#[DynamoObject("ScalarValuesTestTable")]
class ScalarValuesTest
{
    #[DynamoField(type: DynamoType::STRING)]
    public string $stringValue;

    #[DynamoField(type: DynamoType::STRING)]
    public ?string $nullableStringValue;

    #[DynamoField(type: DynamoType::STRING)]
    public string $emptyStringValue;

    #[DynamoField(type: DynamoType::STRING)]
    public string $unicodeStringValue;

    #[DynamoField(type: DynamoType::NUMBER)]
    public int $integerValue;

    #[DynamoField(type: DynamoType::NUMBER)]
    public float $floatValue;

    #[DynamoField(type: DynamoType::NUMBER)]
    public int $negativeIntegerValue;

    #[DynamoField(type: DynamoType::NUMBER)]
    public float $negativeFloatValue;

    #[DynamoField(type: DynamoType::NUMBER)]
    public int $zeroValue;

    #[DynamoField(type: DynamoType::NUMBER)]
    public ?int $nullableNumberValue;

    #[DynamoField(type: DynamoType::BOOL)]
    public bool $trueBoolValue;

    #[DynamoField(type: DynamoType::BOOL)]
    public bool $falseBoolValue;

    #[DynamoField(type: DynamoType::BOOL)]
    public ?bool $nullableBoolValue;

    #[DynamoField(type: DynamoType::BINARY)]
    public string $binaryValue;

    #[DynamoField(type: DynamoType::BINARY)]
    public ?string $nullableBinaryValue;
}

test("Maps string scalar values correctly", function () {
    $testObject = new ScalarValuesTest();
    $testObject->stringValue = "Hello World";
    $testObject->nullableStringValue = null;
    $testObject->emptyStringValue = "";
    $testObject->unicodeStringValue = "Hello 世界 🌍";

    // Set other required fields to avoid errors
    $testObject->integerValue = 0;
    $testObject->floatValue = 0.0;
    $testObject->negativeIntegerValue = 0;
    $testObject->negativeFloatValue = 0.0;
    $testObject->zeroValue = 0;
    $testObject->nullableNumberValue = null;
    $testObject->trueBoolValue = true;
    $testObject->falseBoolValue = false;
    $testObject->nullableBoolValue = null;
    $testObject->binaryValue = base64_encode("test data");
    $testObject->nullableBinaryValue = null;

    $mapper = new DynamoMapper();
    $item = $mapper->toPutItem($testObject);

    expect($item["Item"]["stringValue"])
        ->toBe(["S" => "Hello World"])
        ->and($item["Item"]["nullableStringValue"])
        ->toBe(["NULL" => true])
        ->and($item["Item"]["emptyStringValue"])
        ->toBe(["S" => ""])
        ->and($item["Item"]["unicodeStringValue"])
        ->toBe(["S" => "Hello 世界 🌍"]);
});

test("Maps number scalar values correctly", function () {
    $testObject = new ScalarValuesTest();
    $testObject->integerValue = 42;
    $testObject->floatValue = 13.37;
    $testObject->negativeIntegerValue = -100;
    $testObject->negativeFloatValue = -99.99;
    $testObject->zeroValue = 0;
    $testObject->nullableNumberValue = null;

    // Set other required fields to avoid errors
    $testObject->stringValue = "test";
    $testObject->nullableStringValue = "test";
    $testObject->emptyStringValue = "";
    $testObject->unicodeStringValue = "test";
    $testObject->trueBoolValue = true;
    $testObject->falseBoolValue = false;
    $testObject->nullableBoolValue = null;
    $testObject->binaryValue = base64_encode("test data");
    $testObject->nullableBinaryValue = null;

    $mapper = new DynamoMapper();
    $item = $mapper->toPutItem($testObject);

    expect($item["Item"]["integerValue"])
        ->toBe(["N" => "42"])
        ->and($item["Item"]["floatValue"])
        ->toBe(["N" => "13.37"])
        ->and($item["Item"]["negativeIntegerValue"])
        ->toBe(["N" => "-100"])
        ->and($item["Item"]["negativeFloatValue"])
        ->toBe(["N" => "-99.99"])
        ->and($item["Item"]["zeroValue"])
        ->toBe(["N" => "0"])
        ->and($item["Item"]["nullableNumberValue"])
        ->toBe(["NULL" => true]);
});

test("Maps boolean scalar values correctly", function () {
    $testObject = new ScalarValuesTest();
    $testObject->trueBoolValue = true;
    $testObject->falseBoolValue = false;
    $testObject->nullableBoolValue = null;

    // Set other required fields to avoid errors
    $testObject->stringValue = "test";
    $testObject->nullableStringValue = "test";
    $testObject->emptyStringValue = "";
    $testObject->unicodeStringValue = "test";
    $testObject->integerValue = 0;
    $testObject->floatValue = 0.0;
    $testObject->negativeIntegerValue = 0;
    $testObject->negativeFloatValue = 0.0;
    $testObject->zeroValue = 0;
    $testObject->nullableNumberValue = null;
    $testObject->binaryValue = base64_encode("test data");
    $testObject->nullableBinaryValue = null;

    $mapper = new DynamoMapper();
    $item = $mapper->toPutItem($testObject);

    expect($item["Item"]["trueBoolValue"])
        ->toBe(["BOOL" => true])
        ->and($item["Item"]["falseBoolValue"])
        ->toBe(["BOOL" => false])
        ->and($item["Item"]["nullableBoolValue"])
        ->toBe(["NULL" => true]);
});

test("Maps binary scalar values correctly", function () {
    $testData = "This is test binary data";
    $encodedData = base64_encode($testData);

    $testObject = new ScalarValuesTest();
    $testObject->binaryValue = $encodedData;
    $testObject->nullableBinaryValue = null;

    // Set other required fields to avoid errors
    $testObject->stringValue = "test";
    $testObject->nullableStringValue = "test";
    $testObject->emptyStringValue = "";
    $testObject->unicodeStringValue = "test";
    $testObject->integerValue = 0;
    $testObject->floatValue = 0.0;
    $testObject->negativeIntegerValue = 0;
    $testObject->negativeFloatValue = 0.0;
    $testObject->zeroValue = 0;
    $testObject->nullableNumberValue = null;
    $testObject->trueBoolValue = true;
    $testObject->falseBoolValue = false;
    $testObject->nullableBoolValue = null;

    $mapper = new DynamoMapper();
    $item = $mapper->toPutItem($testObject);

    expect($item["Item"]["binaryValue"])
        ->toBe(["B" => $encodedData])
        ->and($item["Item"]["nullableBinaryValue"])
        ->toBe(["NULL" => true]);
});

test("Maps all scalar values together in a comprehensive test", function () {
    $testObject = new ScalarValuesTest();

    // String values
    $testObject->stringValue = "Comprehensive Test";
    $testObject->nullableStringValue = null;
    $testObject->emptyStringValue = "";
    $testObject->unicodeStringValue = "Test with émojis 🎉 and ümlauts";

    // Number values
    $testObject->integerValue = 2025;
    $testObject->floatValue = 3.14159;
    $testObject->negativeIntegerValue = -42;
    $testObject->negativeFloatValue = -0.001;
    $testObject->zeroValue = 0;
    $testObject->nullableNumberValue = null;

    // Boolean values
    $testObject->trueBoolValue = true;
    $testObject->falseBoolValue = false;
    $testObject->nullableBoolValue = null;

    // Binary values
    $binaryData = base64_encode(
        'Binary test data with special chars: !@#$%^&*()'
    );
    $testObject->binaryValue = $binaryData;
    $testObject->nullableBinaryValue = null;

    $mapper = new DynamoMapper();
    $item = $mapper->toPutItem($testObject);

    expect($item)
        ->toBeArray()
        ->and($item["TableName"])
        ->toBe("ScalarValuesTestTable")
        ->and($item["Item"])
        ->toBeArray()
        ->toBe([
            "stringValue" => ["S" => "Comprehensive Test"],
            "nullableStringValue" => ["NULL" => true],
            "emptyStringValue" => ["S" => ""],
            "unicodeStringValue" => ["S" => "Test with émojis 🎉 and ümlauts"],
            "integerValue" => ["N" => "2025"],
            "floatValue" => ["N" => "3.14159"],
            "negativeIntegerValue" => ["N" => "-42"],
            "negativeFloatValue" => ["N" => "-0.001"],
            "zeroValue" => ["N" => "0"],
            "nullableNumberValue" => ["NULL" => true],
            "trueBoolValue" => ["BOOL" => true],
            "falseBoolValue" => ["BOOL" => false],
            "nullableBoolValue" => ["NULL" => true],
            "binaryValue" => ["B" => $binaryData],
            "nullableBinaryValue" => ["NULL" => true],
        ]);
});

test("Handles edge cases for scalar values", function () {
    $testObject = new ScalarValuesTest();

    // Edge case values
    $testObject->stringValue = "String with\nnewlines\tand\ttabs";
    $testObject->nullableStringValue = null;
    $testObject->emptyStringValue = "";
    $testObject->unicodeStringValue = "نص باللغة العربية";

    $testObject->integerValue = PHP_INT_MAX;
    $testObject->floatValue = PHP_FLOAT_MAX;
    $testObject->negativeIntegerValue = PHP_INT_MIN;
    $testObject->negativeFloatValue = -PHP_FLOAT_MAX;
    $testObject->zeroValue = 0;
    $testObject->nullableNumberValue = null;

    $testObject->trueBoolValue = true;
    $testObject->falseBoolValue = false;
    $testObject->nullableBoolValue = null;

    // Binary with empty data
    $testObject->binaryValue = base64_encode("");
    $testObject->nullableBinaryValue = null;

    $mapper = new DynamoMapper();
    $item = $mapper->toPutItem($testObject);

    expect($item["Item"]["stringValue"]["S"])
        ->toBe("String with\nnewlines\tand\ttabs")
        ->and($item["Item"]["unicodeStringValue"]["S"])
        ->toBe("نص باللغة العربية")
        ->and($item["Item"]["integerValue"]["N"])
        ->toBe((string) PHP_INT_MAX)
        ->and($item["Item"]["negativeIntegerValue"]["N"])
        ->toBe((string) PHP_INT_MIN)
        ->and($item["Item"]["binaryValue"]["B"])
        ->toBe(base64_encode(""));
});

test("Validates binary data correctly", function () {
    $testObject = new ScalarValuesTest();

    // Test with valid base64 encoded data
    $validBinaryData = base64_encode("Valid binary content");
    $testObject->binaryValue = $validBinaryData;
    $testObject->nullableBinaryValue = null;

    // Set other required fields to avoid errors
    $testObject->stringValue = "test";
    $testObject->nullableStringValue = "test";
    $testObject->emptyStringValue = "";
    $testObject->unicodeStringValue = "test";
    $testObject->integerValue = 0;
    $testObject->floatValue = 0.0;
    $testObject->negativeIntegerValue = 0;
    $testObject->negativeFloatValue = 0.0;
    $testObject->zeroValue = 0;
    $testObject->nullableNumberValue = null;
    $testObject->trueBoolValue = true;
    $testObject->falseBoolValue = false;
    $testObject->nullableBoolValue = null;

    $mapper = new DynamoMapper();
    $item = $mapper->toPutItem($testObject);

    expect($item["Item"]["binaryValue"])
        ->toBe(["B" => $validBinaryData])
        ->and(base64_decode($item["Item"]["binaryValue"]["B"], true))
        ->not()
        ->toBeFalse()
        ->and($item["Item"]["nullableBinaryValue"])
        ->toBe(["NULL" => true]);
});
