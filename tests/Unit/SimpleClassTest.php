<?php

namespace Tests\Unit;

use Sapphire\Mapper\Attribute\DynamoField;
use Sapphire\Mapper\Attribute\DynamoObject;
use Sapphire\Mapper\DynamoMapper;
use Sapphire\Mapper\DynamoType;

#[DynamoObject('SimpleClass')]
class SimpleClass
{
    #[DynamoField(type: DynamoType::STRING)]
    private string $id;

    #[DynamoField(type: DynamoType::STRING)]
    private string $name;

    #[DynamoField(type: DynamoType::NUMBER)]
    private float $price;

    #[DynamoField(type: DynamoType::BOOL)]
    public bool $isAvailable;

    #[DynamoField(type: DynamoType::STRING)]
    public ?string $description;

    #[DynamoField(type: DynamoType::LIST)]
    public array $tags;

    public function __construct()
    {
        $this->id = 'the-same-id-for-all-items-for-easy-testing';
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(float $price): void
    {
        $this->price = $price;
    }
}

test('Converts a simple PHP class to an DynamoDB item', function () {
    $simpleClass = new SimpleClass();
    $simpleClass->setName("Sapphire");
    $simpleClass->setPrice(13.37);
    $simpleClass->isAvailable = true;
    $simpleClass->description = null;
    $simpleClass->tags = ["php", "odm", 2025];

    $mapper = new DynamoMapper();
    $item = $mapper->toPutItem($simpleClass);

    expect($item)
        ->toBeArray()
        ->and($item["TableName"])
        ->toBeString()
        ->toBe("SimpleClass")
        ->and($item["Item"])
        ->toBeArray()
        ->toBe([
            "id" => [
                "S" => "the-same-id-for-all-items-for-easy-testing",
            ],
            "name" => [
                "S" => "Sapphire",
            ],
            "price" => [
                "N" => "13.37",
            ],
            "isAvailable" => [
                "BOOL" => true,
            ],
            "description" => [
                "NULL" => true,
            ],
            "tags" => [
                "L" => [
                    [
                        "S" => "php",
                    ],
                    [
                        "S" => "odm",
                    ],
                    [
                        "N" => "2025",
                    ],
                ],
            ],
        ]);
});
