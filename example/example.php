<?php

require_once __DIR__ . "/../vendor/autoload.php";

use Sapphire\Mapper\Attribute\DynamoEmbeddedObject;
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
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }
}

#[DynamoEmbeddedObject]
class TranslatedDescription
{
    public function __construct(
        #[DynamoField(type: DynamoType::STRING)] public readonly string $en,
        #[DynamoField(type: DynamoType::STRING)] public readonly string $de,
    )
    {
    }
}

#[DynamoObject("products")]
class Product extends BaseModel
{
    #[DynamoField(type: DynamoType::STRING)]
    private string $id;

    #[DynamoField(type: DynamoType::STRING)]
    private string $name;

    #[DynamoField(type: DynamoType::NUMBER)]
    private float $price;

    #[DynamoField(type: DynamoType::MAP)]
    public TranslatedDescription $description;

    #[DynamoField(type: DynamoType::LIST)]
    public array $tags;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
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

    public function getDescription(): TranslatedDescription
    {
        return $this->description;
    }

    public function setDescription(TranslatedDescription $description): void
    {
        $this->description = $description;
    }
}

$product = new Product();
$product->setId("product:1000");
$product->setName("Dominator 2025");
$product->setPrice(13.37);
$product->setDescription(
    new TranslatedDescription(
        en: "The best Dominator on the planet",
        de: "Der beste Dominator auf der Erde",
    ),
);
$product->tags = ["top seller", "hot", 2025];

$mapper = new DynamoMapper();

dump($mapper->toPutItem($product));

// The result you get from the mapper:
$result = [
    "TableName" => "products",
    "Item" => [
        "id" => [
            "S" => "product:1000",
        ],
        "name" => [
            "S" => "Dominator 2025",
        ],
        "price" => [
            "N" => "13.37",
        ],
        "description" => [
            "M" => [
                "en" => [
                    "S" => "The best Dominator on the planet",
                ],
                "de" => [
                    "S" => "Der beste Dominator auf der Erde",
                ],
            ],
        ],
        "tags" => [
            "L" => [
                [
                    "S" => "top seller",
                ],
                [
                    "S" => "hot",
                ],
                [
                    "N" => "2025",
                ],
            ],
        ],
        "createdAt" => [
            "S" => "2025-06-22T10:53:32+00:00",
        ],
        "updatedAt" => [
            "S" => "2025-06-22T10:53:32+00:00",
        ],
    ],
];
