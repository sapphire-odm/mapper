# Sapphire-ODM Mapper

![sapphire logo](./sapphire-logo-horizontal.png "Logo")

## PHP Class Mapper for AWS DynamoDB

This library converts your PHP class with the help of PHP attributes to the array structure needed to work with AWS DynamoDB.


## Example

**Your class:**
```php
#[DynamoObject(tableName: "products")]
class Product
{
    #[DynamoField(type: DynamoType::STRING)]
    private string $id;

    #[DynamoField(type: DynamoType::STRING)]
    public string $name;

    #[DynamoField(type: DynamoType::NUMBER)]
    public float $price;

    #[DynamoField(type: DynamoType::LIST)]
    public array $tags = [];

    public function __construct()
    {
        $this->id = uniqid();
    }

    public function getId(): string
    {
        return $this->id;
    }
}
```

**Usage of the mapper:**
```php
$product = new Product();
$product->name = "Dominator 2025";
$product->price = 13.37;
$product->tags = ["top seller", "hot", 2025];

$mapper = new DynamoMapper();

$item = $mapper->toPutItem($product);
```

**Result:**
```php
// The $item looks like this:
array:2 [
  "TableName" => "products"
  "Item" => array:4 [
    "id" => array:1 [
      "S" => "6857e3326fdc2"
    ]
    "name" => array:1 [
      "S" => "Dominator 2025"
    ]
    "price" => array:1 [
      "N" => "13.37"
    ]
    "tags" => array:1 [
      "L" => array:3 [
        0 => array:1 [
          "S" => "top seller"
        ]
        1 => array:1 [
          "S" => "hot"
        ]
        2 => array:1 [
          "N" => "2025"
        ]
      ]
    ]
  ]
]
```


## Installation

`composer req <todo>`


## Run tests

**Unit tests:**

`composer test:unit`

**Feature/Integration tests:**

`composer test:feature`
