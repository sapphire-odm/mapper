<?php

namespace Sapphire\Mapper;

use Sapphire\Mapper\Attribute\DynamoField;

/**
 * @internal
 */
final readonly class DynamoClassProperty
{
    public function __construct(
        private \ReflectionProperty $reflectionProperty,
        private DynamoField $dynamoField,
    ) {}

    public static function fromReflectionProperty(\ReflectionProperty $reflectionProperty): self | false
    {
        $dynamoFieldAttributes = $reflectionProperty->getAttributes(DynamoField::class);

        if (!$dynamoFieldAttributes) {
            return false;
        }

        return new self($reflectionProperty, $dynamoFieldAttributes[0]->newInstance());
    }

    public function getReflectionProperty(): \ReflectionProperty
    {
        return $this->reflectionProperty;
    }

    public function getDynamoFieldMeta(): DynamoField
    {
        return $this->dynamoField;
    }
}