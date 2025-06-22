<?php

namespace Tests\Unit;

use Sapphire\Mapper\Attribute\DynamoField;
use Sapphire\Mapper\Attribute\DynamoObject;
use Sapphire\Mapper\DynamoMapper;
use Sapphire\Mapper\DynamoType;
use Sapphire\Mapper\Exception\MissingAttributeException;

// Test class without DynamoObject attribute
class ClassWithoutDynamoObjectAttribute
{
    #[DynamoField(type: DynamoType::STRING)]
    public string $name = "test";
}

// Test class with DynamoObject but no DynamoDB fields
#[DynamoObject("no_fields")]
class ClassWithoutDynamoFields
{
    public string $regularField = "not mapped";
    public int $anotherField = 42;
}

test("MissingAttributeException is thrown when DynamoObject attribute is missing", function () {
    $mapper = new DynamoMapper();
    $objectWithoutAttribute = new ClassWithoutDynamoObjectAttribute();

    expect(fn() => $mapper->getTableName($objectWithoutAttribute))
        ->toThrow(MissingAttributeException::class);
});

test("MissingAttributeException is thrown when calling toPutItem on class without DynamoObject", function () {
    $mapper = new DynamoMapper();
    $objectWithoutAttribute = new ClassWithoutDynamoObjectAttribute();

    expect(fn() => $mapper->toPutItem($objectWithoutAttribute))
        ->toThrow(MissingAttributeException::class);
});

test("Objects with DynamoObject but no DynamoDB fields work correctly", function () {
    $mapper = new DynamoMapper();
    $objectWithoutFields = new ClassWithoutDynamoFields();

    // Should not throw exception, just return empty item
    $tableName = $mapper->getTableName($objectWithoutFields);
    $item = $mapper->toItem($objectWithoutFields);
    $putItem = $mapper->toPutItem($objectWithoutFields);

    expect($tableName)->toBe("no_fields")
        ->and($item)->toBe([])
        ->and($putItem)->toBe([
            "TableName" => "no_fields",
            "Item" => []
        ]);
});
