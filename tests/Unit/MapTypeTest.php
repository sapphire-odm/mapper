<?php

namespace Tests\Unit;

use Sapphire\Mapper\Attribute\DynamoEmbeddedObject;
use Sapphire\Mapper\Attribute\DynamoField;
use Sapphire\Mapper\Attribute\DynamoObject;
use Sapphire\Mapper\DynamoMapper;
use Sapphire\Mapper\DynamoType;

// Simple embedded object with basic scalar types
#[DynamoEmbeddedObject]
class BasicAddress
{
    public function __construct(
        #[DynamoField(type: DynamoType::STRING)] public readonly string $street,
        #[DynamoField(type: DynamoType::STRING)] public readonly string $city,
        #[DynamoField(type: DynamoType::STRING)] public readonly string $zipCode
    ) {}
}

// Embedded object with all scalar types
#[DynamoEmbeddedObject]
class CompleteProfile
{
    public function __construct(
        #[DynamoField(type: DynamoType::STRING)] public readonly string $name,
        #[DynamoField(type: DynamoType::NUMBER)] public readonly int $age,
        #[DynamoField(type: DynamoType::BOOL)] public readonly bool $isVerified,
        #[DynamoField(type: DynamoType::STRING)] public readonly ?string $nickname,
        #[DynamoField(type: DynamoType::NUMBER)] public readonly ?float $rating,
        #[DynamoField(type: DynamoType::BINARY)] public readonly string $avatar
    ) {}
}

// Nested embedded objects
#[DynamoEmbeddedObject]
class ContactDetails
{
    public function __construct(
        #[DynamoField(type: DynamoType::STRING)] public readonly string $email,
        #[DynamoField(type: DynamoType::STRING)] public readonly string $phone,
        #[DynamoField(type: DynamoType::MAP)] public readonly BasicAddress $address
    ) {}
}

// Embedded object with complex nested structure
#[DynamoEmbeddedObject]
class CompanyInfo
{
    public function __construct(
        #[DynamoField(type: DynamoType::STRING)] public readonly string $name,
        #[DynamoField(type: DynamoType::NUMBER)] public readonly int $employees,
        #[DynamoField(type: DynamoType::MAP)] public readonly ContactDetails $contact,
        #[DynamoField(type: DynamoType::LIST)] public readonly array $departments
    ) {}
}

// Embedded object with nullable fields
#[DynamoEmbeddedObject]
class OptionalData
{
    public function __construct(
        #[DynamoField(type: DynamoType::STRING)] public readonly ?string $description,
        #[DynamoField(type: DynamoType::NUMBER)] public readonly ?int $priority,
        #[DynamoField(type: DynamoType::BOOL)] public readonly ?bool $isActive
    ) {}
}

// Test classes for different scenarios
#[DynamoObject("SimpleMapTest")]
class SimpleMapTest
{
    public function __construct(
        #[DynamoField(type: DynamoType::STRING)] public readonly string $id,
        #[DynamoField(type: DynamoType::MAP)] public readonly BasicAddress $address
    ) {}
}

#[DynamoObject("ComplexMapTest")]
class ComplexMapTest
{
    public function __construct(
        #[DynamoField(type: DynamoType::STRING)] public readonly string $id,
        #[DynamoField(type: DynamoType::MAP)] public readonly CompleteProfile $profile,
        #[DynamoField(type: DynamoType::MAP)] public readonly ContactDetails $contact,
        #[DynamoField(type: DynamoType::MAP)] public readonly ?OptionalData $metadata
    ) {}
}

#[DynamoObject("NestedMapTest")]
class NestedMapTest
{
    public function __construct(
        #[DynamoField(type: DynamoType::STRING)] public readonly string $id,
        #[DynamoField(type: DynamoType::MAP)]
        public readonly CompanyInfo $company
    ) {}
}

test("Maps simple embedded object correctly", function () {
    $address = new BasicAddress(
        street: "123 Main Street",
        city: "New York",
        zipCode: "10001"
    );

    $testObject = new SimpleMapTest(id: "simple:001", address: $address);

    $mapper = new DynamoMapper();
    $item = $mapper->toPutItem($testObject);

    expect($item["TableName"])
        ->toBe("SimpleMapTest")
        ->and($item["Item"]["id"])
        ->toBe(["S" => "simple:001"])
        ->and($item["Item"]["address"])
        ->toBe([
            "M" => [
                "street" => ["S" => "123 Main Street"],
                "city" => ["S" => "New York"],
                "zipCode" => ["S" => "10001"],
            ],
        ]);
});

test("Maps embedded object with all scalar types", function () {
    $profile = new CompleteProfile(
        name: "John Doe",
        age: 30,
        isVerified: true,
        nickname: null,
        rating: 4.5,
        avatar: base64_encode("avatar_data")
    );

    $contact = new ContactDetails(
        email: "john@example.com",
        phone: "+1-555-0123",
        address: new BasicAddress("456 Oak Ave", "Boston", "02101")
    );

    $testObject = new ComplexMapTest(
        id: "complex:001",
        profile: $profile,
        contact: $contact,
        metadata: null
    );

    $mapper = new DynamoMapper();
    $item = $mapper->toPutItem($testObject);

    expect($item["Item"]["profile"]["M"])
        ->toBe([
            "name" => ["S" => "John Doe"],
            "age" => ["N" => "30"],
            "isVerified" => ["BOOL" => true],
            "nickname" => ["NULL" => true],
            "rating" => ["N" => "4.5"],
            "avatar" => ["B" => base64_encode("avatar_data")],
        ])
        ->and($item["Item"]["contact"]["M"])
        ->toBe([
            "email" => ["S" => "john@example.com"],
            "phone" => ["S" => "+1-555-0123"],
            "address" => [
                "M" => [
                    "street" => ["S" => "456 Oak Ave"],
                    "city" => ["S" => "Boston"],
                    "zipCode" => ["S" => "02101"],
                ],
            ],
        ])
        ->and($item["Item"]["metadata"])
        ->toBe(["NULL" => true]);
});

test("Maps deeply nested embedded objects", function () {
    $address = new BasicAddress(
        street: "789 Corporate Blvd",
        city: "San Francisco",
        zipCode: "94105"
    );

    $contact = new ContactDetails(
        email: "info@techcorp.com",
        phone: "+1-415-555-0199",
        address: $address
    );

    $company = new CompanyInfo(
        name: "TechCorp Inc.",
        employees: 150,
        contact: $contact,
        departments: ["Engineering", "Sales", "Marketing"]
    );

    $testObject = new NestedMapTest(id: "nested:001", company: $company);

    $mapper = new DynamoMapper();
    $item = $mapper->toPutItem($testObject);

    expect($item["Item"]["company"]["M"]["name"])
        ->toBe(["S" => "TechCorp Inc."])
        ->and($item["Item"]["company"]["M"]["employees"])
        ->toBe(["N" => "150"])
        ->and($item["Item"]["company"]["M"]["contact"]["M"]["email"])
        ->toBe(["S" => "info@techcorp.com"])
        ->and(
            $item["Item"]["company"]["M"]["contact"]["M"]["address"]["M"][
                "street"
            ]
        )
        ->toBe(["S" => "789 Corporate Blvd"])
        ->and($item["Item"]["company"]["M"]["departments"]["L"])
        ->toBe([
            ["S" => "Engineering"],
            ["S" => "Sales"],
            ["S" => "Marketing"],
        ]);
});

test("Maps embedded objects with nullable fields", function () {
    $optionalData = new OptionalData(
        description: "Test description",
        priority: null,
        isActive: false
    );

    $profile = new CompleteProfile(
        name: "Jane Smith",
        age: 25,
        isVerified: false,
        nickname: "Janie",
        rating: null,
        avatar: base64_encode("")
    );

    $contact = new ContactDetails(
        email: "jane@example.com",
        phone: "+1-555-0199",
        address: new BasicAddress("321 Pine St", "Seattle", "98101")
    );

    $testObject = new ComplexMapTest(
        id: "nullable:001",
        profile: $profile,
        contact: $contact,
        metadata: $optionalData
    );

    $mapper = new DynamoMapper();
    $item = $mapper->toPutItem($testObject);

    expect($item["Item"]["profile"]["M"]["nickname"])
        ->toBe(["S" => "Janie"])
        ->and($item["Item"]["profile"]["M"]["rating"])
        ->toBe(["NULL" => true])
        ->and($item["Item"]["metadata"]["M"])
        ->toBe([
            "description" => ["S" => "Test description"],
            "priority" => ["NULL" => true],
            "isActive" => ["BOOL" => false],
        ]);
});

test("Maps empty and edge case embedded objects", function () {
    $emptyOptional = new OptionalData(
        description: null,
        priority: null,
        isActive: null
    );

    $edgeProfile = new CompleteProfile(
        name: "",
        age: 0,
        isVerified: false,
        nickname: "",
        rating: 0.0,
        avatar: base64_encode("")
    );

    $unicodeAddress = new BasicAddress(
        street: "Straße der Einheit 42",
        city: "München",
        zipCode: "80331"
    );

    $unicodeContact = new ContactDetails(
        email: "测试@example.com",
        phone: "+49-89-123456",
        address: $unicodeAddress
    );

    $testObject = new ComplexMapTest(
        id: "edge:001",
        profile: $edgeProfile,
        contact: $unicodeContact,
        metadata: $emptyOptional
    );

    $mapper = new DynamoMapper();
    $item = $mapper->toPutItem($testObject);

    expect($item["Item"]["profile"]["M"]["name"])
        ->toBe(["S" => ""])
        ->and($item["Item"]["profile"]["M"]["age"])
        ->toBe(["N" => "0"])
        ->and($item["Item"]["profile"]["M"]["nickname"])
        ->toBe(["S" => ""])
        ->and($item["Item"]["profile"]["M"]["rating"])
        ->toBe(["N" => "0"])
        ->and($item["Item"]["contact"]["M"]["email"])
        ->toBe(["S" => "测试@example.com"])
        ->and($item["Item"]["contact"]["M"]["address"]["M"]["street"])
        ->toBe(["S" => "Straße der Einheit 42"])
        ->and($item["Item"]["contact"]["M"]["address"]["M"]["city"])
        ->toBe(["S" => "München"])
        ->and($item["Item"]["metadata"]["M"])
        ->toBe([
            "description" => ["NULL" => true],
            "priority" => ["NULL" => true],
            "isActive" => ["NULL" => true],
        ]);
});

test("Maps complex scenario with multiple embedded objects", function () {
    $homeAddress = new BasicAddress(
        street: "123 Home Street",
        city: "Portland",
        zipCode: "97201"
    );

    $workAddress = new BasicAddress(
        street: "456 Business Ave",
        city: "Portland",
        zipCode: "97204"
    );

    $personalProfile = new CompleteProfile(
        name: "Alice Johnson",
        age: 35,
        isVerified: true,
        nickname: "AJ",
        rating: 4.8,
        avatar: base64_encode("personal_avatar")
    );

    $homeContact = new ContactDetails(
        email: "alice.personal@example.com",
        phone: "+1-503-555-0123",
        address: $homeAddress
    );

    $workContact = new ContactDetails(
        email: "alice.work@company.com",
        phone: "+1-503-555-0199",
        address: $workAddress
    );

    $metadata = new OptionalData(
        description: "VIP Customer",
        priority: 1,
        isActive: true
    );

    // Create two test objects to verify consistency
    $testObject1 = new ComplexMapTest(
        id: "multi:001",
        profile: $personalProfile,
        contact: $homeContact,
        metadata: $metadata
    );

    $testObject2 = new ComplexMapTest(
        id: "multi:002",
        profile: $personalProfile,
        contact: $workContact,
        metadata: $metadata
    );

    $mapper = new DynamoMapper();
    $item1 = $mapper->toPutItem($testObject1);
    $item2 = $mapper->toPutItem($testObject2);

    // Verify both objects have the same profile but different contacts
    expect($item1["Item"]["profile"])
        ->toBe($item2["Item"]["profile"])
        ->and($item1["Item"]["contact"]["M"]["address"]["M"]["street"])
        ->toBe(["S" => "123 Home Street"])
        ->and($item2["Item"]["contact"]["M"]["address"]["M"]["street"])
        ->toBe(["S" => "456 Business Ave"])
        ->and($item1["Item"]["metadata"])
        ->toBe($item2["Item"]["metadata"])
        ->toBe([
            "M" => [
                "description" => ["S" => "VIP Customer"],
                "priority" => ["N" => "1"],
                "isActive" => ["BOOL" => true],
            ],
        ]);
});

test("Validates MAP type structure and nested depth", function () {
    $deeplyNestedAddress = new BasicAddress(
        street: "999 Deep Nest Lane",
        city: "Complexity City",
        zipCode: "99999"
    );

    $deepContact = new ContactDetails(
        email: "deep@nested.example",
        phone: "+1-999-555-0000",
        address: $deeplyNestedAddress
    );

    $deepCompany = new CompanyInfo(
        name: "Deep Nesting Corp",
        employees: 1,
        contact: $deepContact,
        departments: ["Recursion", "Abstraction", "Complexity"]
    );

    $testObject = new NestedMapTest(id: "depth:001", company: $deepCompany);

    $mapper = new DynamoMapper();
    $item = $mapper->toPutItem($testObject);

    // Verify the structure has correct MAP nesting
    expect($item["Item"])
        ->toHaveKey("company")
        ->and($item["Item"]["company"])
        ->toHaveKey("M")
        ->and($item["Item"]["company"]["M"])
        ->toHaveKey("contact")
        ->and($item["Item"]["company"]["M"]["contact"])
        ->toHaveKey("M")
        ->and($item["Item"]["company"]["M"]["contact"]["M"])
        ->toHaveKey("address")
        ->and($item["Item"]["company"]["M"]["contact"]["M"]["address"])
        ->toHaveKey("M")
        ->and(
            $item["Item"]["company"]["M"]["contact"]["M"]["address"]["M"][
                "city"
            ]
        )
        ->toBe(["S" => "Complexity City"]);

    // Verify LIST within MAP structure
    expect($item["Item"]["company"]["M"]["departments"])
        ->toHaveKey("L")
        ->and($item["Item"]["company"]["M"]["departments"]["L"])
        ->toHaveCount(3)
        ->toBe([
            ["S" => "Recursion"],
            ["S" => "Abstraction"],
            ["S" => "Complexity"],
        ]);
});
