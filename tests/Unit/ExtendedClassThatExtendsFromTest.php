<?php

namespace Tests\Unit;

use Sapphire\Mapper\Attribute\DynamoField;
use Sapphire\Mapper\Attribute\DynamoObject;
use Sapphire\Mapper\DynamoMapper;
use Sapphire\Mapper\DynamoType;

class BaseModel
{
    #[DynamoField(type: DynamoType::STRING)]
    public readonly \DateTimeImmutable $createdAt;

    #[DynamoField(type: DynamoType::STRING)]
    public \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable('2023-01-01 00:00:00');
        $this->updatedAt = new \DateTimeImmutable();
    }
}

#[DynamoObject('extended_classes')]
class ClassThatExtendsFrom extends BaseModel
{
    #[DynamoField(type: DynamoType::STRING)]
    public string $name;
}

test('Converts a extended PHP class to an DynamoDB item', function () {
    $embeddedClass = new ClassThatExtendsFrom();
    $embeddedClass->name = "Extend TestCase";
    $embeddedClass->updatedAt = new \DateTimeImmutable('2025-01-01 00:00:00');

    $mapper = new DynamoMapper();
    $item = $mapper->toPutItem($embeddedClass);

    expect($item)
        ->toBeArray()
        ->and($item["TableName"])
        ->toBeString()
        ->toBe("extended_classes")
        ->and($item["Item"])
        ->toBeArray()
        ->toBe([
            "name" => [
                "S" => "Extend TestCase",
            ],
            "createdAt" => [
                "S" => "2023-01-01T00:00:00+00:00",
            ],
            "updatedAt" => [
                "S" => "2025-01-01T00:00:00+00:00",
            ]
        ]);
});
