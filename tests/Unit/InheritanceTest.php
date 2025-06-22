<?php

namespace Tests\Unit;

use Sapphire\Mapper\Attribute\DynamoEmbeddedObject;
use Sapphire\Mapper\Attribute\DynamoField;
use Sapphire\Mapper\Attribute\DynamoObject;
use Sapphire\Mapper\DynamoMapper;
use Sapphire\Mapper\DynamoType;

// Base abstract class with common fields
abstract class BaseEntity
{
    #[DynamoField(type: DynamoType::STRING)]
    public readonly \DateTimeImmutable $createdAt;

    #[DynamoField(type: DynamoType::STRING)]
    public \DateTimeImmutable $updatedAt;

    #[DynamoField(type: DynamoType::STRING)]
    protected string $entityType;

    public function __construct(string $entityType = "base")
    {
        $this->createdAt = new \DateTimeImmutable("2023-01-01T00:00:00Z");
        $this->updatedAt = new \DateTimeImmutable("2023-01-01T00:00:00Z");
        $this->entityType = $entityType;
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }
}

// Auditable base class with tracking fields
class AuditableEntity extends BaseEntity
{
    #[DynamoField(type: DynamoType::STRING)]
    public ?string $createdBy;

    #[DynamoField(type: DynamoType::STRING)]
    public ?string $updatedBy;

    #[DynamoField(type: DynamoType::NUMBER)]
    public int $version;

    #[DynamoField(type: DynamoType::BOOL)]
    public bool $isActive;

    public function __construct(string $entityType = "auditable")
    {
        parent::__construct($entityType);
        $this->createdBy = null;
        $this->updatedBy = null;
        $this->version = 1;
        $this->isActive = true;
    }
}

// Embedded object for testing inheritance with embedded objects
#[DynamoEmbeddedObject]
class PersonalInfo
{
    public function __construct(
        #[DynamoField(type: DynamoType::STRING)] public readonly string $firstName,
        #[DynamoField(type: DynamoType::STRING)] public readonly string $lastName,
        #[DynamoField(type: DynamoType::NUMBER)] public readonly int $age
    ) {}
}

// Simple inheritance test case
#[DynamoObject("simple_inheritance")]
class SimpleInheritanceTest extends BaseEntity
{
    #[DynamoField(type: DynamoType::STRING)]
    public string $name;

    #[DynamoField(type: DynamoType::NUMBER)]
    public float $value;

    public function __construct()
    {
        parent::__construct("simple");
    }
}

// Multi-level inheritance test case
#[DynamoObject("multi_level_inheritance")]
class MultiLevelInheritanceTest extends AuditableEntity
{
    #[DynamoField(type: DynamoType::STRING)]
    public string $title;

    #[DynamoField(type: DynamoType::LIST)]
    public array $tags;

    #[DynamoField(type: DynamoType::MAP)]
    public PersonalInfo $personalInfo;

    public function __construct()
    {
        parent::__construct("multi_level");
    }
}

// Inheritance with all scalar types
#[DynamoObject("all_types_inheritance")]
class AllTypesInheritanceTest extends AuditableEntity
{
    #[DynamoField(type: DynamoType::STRING)]
    public string $stringField;

    #[DynamoField(type: DynamoType::NUMBER)]
    public int $intField;

    #[DynamoField(type: DynamoType::NUMBER)]
    public float $floatField;

    #[DynamoField(type: DynamoType::BOOL)]
    public bool $boolField;

    #[DynamoField(type: DynamoType::BINARY)]
    public string $binaryField;

    #[DynamoField(type: DynamoType::STRING)]
    public ?string $nullableStringField;

    #[DynamoField(type: DynamoType::NUMBER)]
    public ?int $nullableIntField;

    #[DynamoField(type: DynamoType::BOOL)]
    public ?bool $nullableBoolField;

    public function __construct()
    {
        parent::__construct("all_types");
    }
}

// Inheritance with private and protected fields
#[DynamoObject("private_fields_inheritance")]
class PrivateFieldsInheritanceTest extends BaseEntity
{
    #[DynamoField(type: DynamoType::STRING)]
    private string $privateField;

    #[DynamoField(type: DynamoType::STRING)]
    protected string $protectedField;

    #[DynamoField(type: DynamoType::STRING)]
    public string $publicField;

    public function __construct()
    {
        parent::__construct("private_fields");
        $this->privateField = "private_value";
        $this->protectedField = "protected_value";
        $this->publicField = "public_value";
    }

    public function getPrivateField(): string
    {
        return $this->privateField;
    }

    public function getProtectedField(): string
    {
        return $this->protectedField;
    }

    public function setPrivateField(string $value): void
    {
        $this->privateField = $value;
    }

    public function setProtectedField(string $value): void
    {
        $this->protectedField = $value;
    }
}

// Inheritance with overridden field behavior
#[DynamoObject("override_inheritance")]
class OverrideInheritanceTest extends AuditableEntity
{
    #[DynamoField(type: DynamoType::STRING)]
    public string $name;

    #[DynamoField(type: DynamoType::STRING)]
    public string $description;

    public function __construct()
    {
        parent::__construct("override");
        // Override some parent field values
        $this->version = 2;
        $this->isActive = false;
        $this->createdBy = "system";
        $this->updatedBy = "admin";
    }
}

test("Maps simple class inheritance correctly", function () {
    $testObject = new SimpleInheritanceTest();
    $testObject->name = "Simple Test";
    $testObject->value = 42.5;
    $testObject->updatedAt = new \DateTimeImmutable("2024-01-01T12:00:00Z");

    $mapper = new DynamoMapper();
    $item = $mapper->toPutItem($testObject);

    expect($item["TableName"])
        ->toBe("simple_inheritance")
        ->and($item["Item"]["name"])
        ->toBe(["S" => "Simple Test"])
        ->and($item["Item"]["value"])
        ->toBe(["N" => "42.5"])
        ->and($item["Item"]["createdAt"])
        ->toBe(["S" => "2023-01-01T00:00:00+00:00"])
        ->and($item["Item"]["updatedAt"])
        ->toBe(["S" => "2024-01-01T12:00:00+00:00"])
        ->and($item["Item"]["entityType"])
        ->toBe(["S" => "simple"]);
});

test("Maps multi-level inheritance correctly", function () {
    $personalInfo = new PersonalInfo(
        firstName: "John",
        lastName: "Doe",
        age: 30
    );

    $testObject = new MultiLevelInheritanceTest();
    $testObject->title = "Senior Developer";
    $testObject->tags = ["php", "dynamodb", "inheritance"];
    $testObject->personalInfo = $personalInfo;
    $testObject->createdBy = "admin";
    $testObject->updatedBy = "system";
    $testObject->version = 3;
    $testObject->isActive = true;
    $testObject->updatedAt = new \DateTimeImmutable("2024-06-15T10:30:00Z");

    $mapper = new DynamoMapper();
    $item = $mapper->toPutItem($testObject);

    expect($item["TableName"])
        ->toBe("multi_level_inheritance")
        ->and($item["Item"]["entityType"]["S"])
        ->toBe("multi_level")
        ->and($item["Item"]["createdBy"]["S"])
        ->toBe("admin")
        ->and($item["Item"]["updatedBy"]["S"])
        ->toBe("system")
        ->and($item["Item"]["version"]["N"])
        ->toBe("3")
        ->and($item["Item"]["isActive"]["BOOL"])
        ->toBe(true)
        ->and($item["Item"]["title"]["S"])
        ->toBe("Senior Developer")
        ->and($item["Item"]["tags"]["L"])
        ->toBe([["S" => "php"], ["S" => "dynamodb"], ["S" => "inheritance"]])
        ->and($item["Item"]["personalInfo"]["M"])
        ->toBe([
            "firstName" => ["S" => "John"],
            "lastName" => ["S" => "Doe"],
            "age" => ["N" => "30"],
        ]);
});

test("Maps inheritance with all scalar types", function () {
    $testObject = new AllTypesInheritanceTest();
    $testObject->stringField = "test string";
    $testObject->intField = 100;
    $testObject->floatField = 3.14159;
    $testObject->boolField = true;
    $testObject->binaryField = base64_encode("binary data");
    $testObject->nullableStringField = null;
    $testObject->nullableIntField = 42;
    $testObject->nullableBoolField = null;

    // Set parent fields
    $testObject->createdBy = "test_user";
    $testObject->updatedBy = null;
    $testObject->version = 5;
    $testObject->isActive = false;

    $mapper = new DynamoMapper();
    $item = $mapper->toPutItem($testObject);

    expect($item["Item"]["stringField"])
        ->toBe(["S" => "test string"])
        ->and($item["Item"]["intField"])
        ->toBe(["N" => "100"])
        ->and($item["Item"]["floatField"])
        ->toBe(["N" => "3.14159"])
        ->and($item["Item"]["boolField"])
        ->toBe(["BOOL" => true])
        ->and($item["Item"]["binaryField"])
        ->toBe(["B" => base64_encode("binary data")])
        ->and($item["Item"]["nullableStringField"])
        ->toBe(["NULL" => true])
        ->and($item["Item"]["nullableIntField"])
        ->toBe(["N" => "42"])
        ->and($item["Item"]["nullableBoolField"])
        ->toBe(["NULL" => true])
        ->and($item["Item"]["createdBy"])
        ->toBe(["S" => "test_user"])
        ->and($item["Item"]["updatedBy"])
        ->toBe(["NULL" => true])
        ->and($item["Item"]["version"])
        ->toBe(["N" => "5"])
        ->and($item["Item"]["isActive"])
        ->toBe(["BOOL" => false])
        ->and($item["Item"]["entityType"])
        ->toBe(["S" => "all_types"]);
});

test("Maps inheritance with private and protected fields", function () {
    $testObject = new PrivateFieldsInheritanceTest();
    $testObject->setPrivateField("updated_private");
    $testObject->setProtectedField("updated_protected");
    $testObject->publicField = "updated_public";
    $testObject->updatedAt = new \DateTimeImmutable("2024-03-15T08:45:00Z");

    $mapper = new DynamoMapper();
    $item = $mapper->toPutItem($testObject);

    expect($item["Item"]["privateField"])
        ->toBe(["S" => "updated_private"])
        ->and($item["Item"]["protectedField"])
        ->toBe(["S" => "updated_protected"])
        ->and($item["Item"]["publicField"])
        ->toBe(["S" => "updated_public"])
        ->and($item["Item"]["entityType"])
        ->toBe(["S" => "private_fields"])
        ->and($item["Item"]["createdAt"])
        ->toBe(["S" => "2023-01-01T00:00:00+00:00"])
        ->and($item["Item"]["updatedAt"])
        ->toBe(["S" => "2024-03-15T08:45:00+00:00"]);
});

test("Maps inheritance with overridden parent values", function () {
    $testObject = new OverrideInheritanceTest();
    $testObject->name = "Override Test";
    $testObject->description = "Testing value overrides";
    $testObject->updatedAt = new \DateTimeImmutable("2024-12-01T15:30:00Z");

    $mapper = new DynamoMapper();
    $item = $mapper->toPutItem($testObject);

    expect($item["Item"]["name"])
        ->toBe(["S" => "Override Test"])
        ->and($item["Item"]["description"])
        ->toBe(["S" => "Testing value overrides"])
        ->and($item["Item"]["version"])
        ->toBe(["N" => "2"]) // Overridden in constructor
        ->and($item["Item"]["isActive"])
        ->toBe(["BOOL" => false]) // Overridden in constructor
        ->and($item["Item"]["createdBy"])
        ->toBe(["S" => "system"]) // Overridden in constructor
        ->and($item["Item"]["updatedBy"])
        ->toBe(["S" => "admin"]) // Overridden in constructor
        ->and($item["Item"]["entityType"])
        ->toBe(["S" => "override"])
        ->and($item["Item"]["createdAt"])
        ->toBe(["S" => "2023-01-01T00:00:00+00:00"])
        ->and($item["Item"]["updatedAt"])
        ->toBe(["S" => "2024-12-01T15:30:00+00:00"]);
});

test("Validates field inheritance order and precedence", function () {
    $testObject = new MultiLevelInheritanceTest();
    $testObject->title = "Field Order Test";
    $testObject->tags = ["inheritance", "validation"];
    $testObject->personalInfo = new PersonalInfo("Test", "User", 25);

    // Set fields from different inheritance levels
    $testObject->createdBy = "base_user"; // From AuditableEntity
    $testObject->version = 10; // From AuditableEntity
    $testObject->isActive = true; // From AuditableEntity
    $testObject->updatedAt = new \DateTimeImmutable("2024-08-20T14:15:00Z"); // From BaseEntity

    $mapper = new DynamoMapper();
    $item = $mapper->toPutItem($testObject);

    // Verify all fields from all inheritance levels are present
    $expectedFields = [
        "createdAt",
        "updatedAt",
        "entityType", // From BaseEntity
        "createdBy",
        "updatedBy",
        "version",
        "isActive", // From AuditableEntity
        "title",
        "tags",
        "personalInfo", // From MultiLevelInheritanceTest
    ];

    foreach ($expectedFields as $field) {
        expect($item["Item"])->toHaveKey($field);
    }

    // Verify field count (should have all fields from inheritance chain)
    expect($item["Item"])->toHaveCount(
        10,
        "Should have exactly 10 fields from inheritance chain"
    );
});

test("Maps inheritance with edge cases and null values", function () {
    $testObject = new AllTypesInheritanceTest();

    // Set edge case values
    $testObject->stringField = ""; // Empty string
    $testObject->intField = 0; // Zero
    $testObject->floatField = 0.0; // Zero float
    $testObject->boolField = false; // False
    $testObject->binaryField = base64_encode(""); // Empty binary
    $testObject->nullableStringField = ""; // Empty but not null
    $testObject->nullableIntField = null; // Null
    $testObject->nullableBoolField = false; // False but not null

    // Parent field edge cases
    $testObject->createdBy = ""; // Empty string
    $testObject->updatedBy = null; // Null
    $testObject->version = 0; // Zero version
    $testObject->isActive = false; // Inactive

    $mapper = new DynamoMapper();
    $item = $mapper->toPutItem($testObject);

    expect($item["Item"]["stringField"])
        ->toBe(["S" => ""])
        ->and($item["Item"]["intField"])
        ->toBe(["N" => "0"])
        ->and($item["Item"]["floatField"])
        ->toBe(["N" => "0"])
        ->and($item["Item"]["boolField"])
        ->toBe(["BOOL" => false])
        ->and($item["Item"]["binaryField"])
        ->toBe(["B" => base64_encode("")])
        ->and($item["Item"]["nullableStringField"])
        ->toBe(["S" => ""])
        ->and($item["Item"]["nullableIntField"])
        ->toBe(["NULL" => true])
        ->and($item["Item"]["nullableBoolField"])
        ->toBe(["BOOL" => false])
        ->and($item["Item"]["createdBy"])
        ->toBe(["S" => ""])
        ->and($item["Item"]["updatedBy"])
        ->toBe(["NULL" => true])
        ->and($item["Item"]["version"])
        ->toBe(["N" => "0"])
        ->and($item["Item"]["isActive"])
        ->toBe(["BOOL" => false]);
});

test("Maps inheritance with complex datetime handling", function () {
    $testObject = new MultiLevelInheritanceTest();
    $testObject->title = "DateTime Test";
    $testObject->tags = ["datetime", "inheritance"];
    $testObject->personalInfo = new PersonalInfo("Time", "Tester", 40);

    // Test different datetime formats and timezones
    $testObject->updatedAt = new \DateTimeImmutable(
        "2024-12-25T23:59:59+05:30"
    ); // With timezone
    $testObject->createdBy = "datetime_test_user";
    $testObject->version = 1;

    $mapper = new DynamoMapper();
    $item = $mapper->toPutItem($testObject);

    expect($item["Item"]["createdAt"])
        ->toBe(["S" => "2023-01-01T00:00:00+00:00"]) // Original fixed datetime
        ->and($item["Item"]["updatedAt"])
        ->toBe(["S" => "2024-12-25T23:59:59+05:30"]) // Keep original timezone format
        ->and($item["Item"]["entityType"])
        ->toBe(["S" => "multi_level"])
        ->and($item["Item"]["title"])
        ->toBe(["S" => "DateTime Test"]);
});
