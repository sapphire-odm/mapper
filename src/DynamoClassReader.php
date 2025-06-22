<?php

namespace Sapphire\Mapper;

use Sapphire\Mapper\Attribute\DynamoEmbeddedObject;
use Sapphire\Mapper\Attribute\DynamoObject;
use Sapphire\Mapper\Exception\MissingAttributeException;

/**
 * @internal
 */
final class DynamoClassReader
{
    private \ReflectionClass $reflectionClass;
    private ?DynamoObject $dynamoObjectAttribute;

    /**
     * @var DynamoClassProperty[]
     */
    private array $properties = [];

    public function __construct(object $object)
    {
        $this->reflectionClass = new \ReflectionClass($object);

        /** @var DynamoObject | null $dynamoObjectAttribute */
        $this->dynamoObjectAttribute = $this->reflectionClass->getAttributes(DynamoObject::class)
            ? $this->reflectionClass->getAttributes(DynamoObject::class)[0]->newInstance()
            : null;

        foreach ($this->getAllProperties($object) as $property) {
            $propertyReflector = DynamoClassProperty::fromReflectionProperty($property);
            if (!$propertyReflector) {
                continue;
            }

            $this->properties[$property->getName()] = $propertyReflector;
        }
    }

    public function getReflectionClass(): \ReflectionClass
    {
        return $this->reflectionClass;
    }

    public function getDynamoObjectMeta(): DynamoObject
    {
        if ($this->dynamoObjectAttribute === null) {
            throw new MissingAttributeException(DynamoObject::class);
        }

        return $this->dynamoObjectAttribute;
    }

    /**
     * @return DynamoClassProperty[]
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * Get all properties from an object, including inherited properties
     *
     * @param object $object
     * @return array<\ReflectionProperty>
     */
    private function getAllProperties(object $object): array
    {
        $properties = [];

        $parent = new \ReflectionClass($object);
        while ($parent) {
            foreach ($parent->getProperties() as $property) {
                if ($property->getDeclaringClass()->getName() === $parent->getName()) {
                    $properties[] = $property;
                }
            }
            $parent = $parent->getParentClass();
        }

        return $properties;
    }
}