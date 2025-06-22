<?php

namespace Tests\Unit;

use Sapphire\Mapper\Attribute\DynamoField;
use Sapphire\Mapper\Attribute\DynamoObject;
use Sapphire\Mapper\DynamoMapper;
use Sapphire\Mapper\DynamoType;

class TranslatedDescription
{
    public function __construct(
        #[DynamoField(type: DynamoType::STRING)] public readonly string $en,
        #[DynamoField(type: DynamoType::STRING)] public readonly string $de,
    ) {}
}

#[DynamoObject('EmbeddedClass')]
class EmbeddedClass
{
    #[DynamoField(type: DynamoType::STRING)]
    private string $name;

    #[DynamoField(type: DynamoType::MAP)]
    private TranslatedDescription $description;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): TranslatedDescription
    {
        return $this->description;
    }

    public function setDescription(TranslatedDescription $description): void
    {
        $this->description = $description;
    }
}

test('Converts a PHP class with an embedded class to an DynamoDB item', function () {
    $embeddedClass = new EmbeddedClass();
    $embeddedClass->setName("TestCase");
    $embeddedClass->setDescription(new TranslatedDescription(
        en: "This is a test case",
        de: "Dass ist ein Testfall"
    ));

    $mapper = new DynamoMapper();
    $item = $mapper->toPutItem($embeddedClass);

    expect($item)
        ->toBeArray()
        ->and($item["TableName"])
        ->toBeString()
        ->toBe("EmbeddedClass")
        ->and($item["Item"])
        ->toBeArray()
        ->toBe([
            "name" => [
                "S" => "TestCase",
            ],
            "description" => [
                "M" => [
                    "en" => [
                        "S" => "This is a test case",
                    ],
                    "de" => [
                        "S" => "Dass ist ein Testfall",
                    ]
                ]
            ]
        ]);
});
