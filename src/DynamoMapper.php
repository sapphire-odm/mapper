<?php

namespace Sapphire\Mapper;

use Sapphire\Mapper\Attribute\DynamoField;
use Sapphire\Mapper\Caster\CasterRegistry;
use Sapphire\Mapper\Caster\DateToStringCaster;
use Sapphire\Mapper\Caster\NumericToStringCaster;
use Sapphire\Mapper\Caster\StringToStringCaster;

class DynamoMapper implements DynamoMapperInterface
{
    private CasterRegistry $casterRegistry;

    public function __construct()
    {
        $this->casterRegistry = new CasterRegistry([
            new DateToStringCaster(),
            new NumericToStringCaster(),
            new StringToStringCaster(),
        ]);
    }

    public function getCasterRegistry(): CasterRegistry
    {
        return $this->casterRegistry;
    }

    /**
     * @inheritDoc
     */
    public function getTableName(object|string $object): string
    {
        return (new DynamoClassReader($object))
            ->getDynamoObjectMeta()
            ->getTableName();
    }

    /**
     * @inheritDoc
     */
    public function toPutItem(object $object): array
    {
        return [
            "TableName" => $this->getTableName($object),
            "Item" => $this->toItem($object),
        ];
    }

    /**
     * @inheritDoc
     */
    public function toItem(object $object): array
    {
        $item = [];

        foreach (
            (new DynamoClassReader($object))->getProperties()
            as $property
        ) {
            if (!$property->getReflectionProperty()->isInitialized($object)) {
                continue;
            }

            $dynamoFieldMeta = $property->getDynamoFieldMeta();
            $propertyName = $property->getReflectionProperty()->getName();
            $propertyValue = $property
                ->getReflectionProperty()
                ->getValue($object);

            if ($propertyValue === null) {
                $item[$propertyName] = [DynamoType::NULL->value => true];
                continue;
            }

            $cast = match ($dynamoFieldMeta->getType()) {
                DynamoType::STRING,
                DynamoType::NUMBER
                    => $this->casterRegistry->cast(
                    $propertyValue,
                    $dynamoFieldMeta
                ),
                DynamoType::BOOL => (bool) $propertyValue,
                DynamoType::BINARY, DynamoType::BINARY_SET => $propertyValue,
                DynamoType::NULL => true,
                DynamoType::STRING_SET, DynamoType::NUMBER_SET => array_map(
                    fn(mixed $v) => $this->casterRegistry->cast(
                        $v,
                        new DynamoField(DynamoType::STRING)
                    ),
                    $propertyValue
                ),
                DynamoType::LIST => $this->mapListItems($propertyValue),
                DynamoType::MAP => $this->toItem($propertyValue),
            };
            $item[$propertyName] = [
                $dynamoFieldMeta->getType()->value => $cast,
            ];
        }

        return $item;
    }

    /**
     * Maps array elements to DynamoDB LIST format with automatic type detection
     *
     * @param array $items
     * @return array
     */
    private function mapListItems(array $items): array
    {
        $listItems = [];

        foreach ($items as $item) {
            if ($item === null) {
                $listItems[] = [DynamoType::NULL->value => true];
                continue;
            }

            $detectedType = $this->detectDynamoType($item);
            $dynamoField = new DynamoField($detectedType);

            $castValue = match ($detectedType) {
                DynamoType::STRING,
                DynamoType::NUMBER
                    => $this->casterRegistry->cast($item, $dynamoField),
                DynamoType::BOOL => (bool) $item,
                DynamoType::BINARY, DynamoType::BINARY_SET => $item,
                DynamoType::NULL => true,
                DynamoType::STRING_SET, DynamoType::NUMBER_SET => array_map(
                    fn(mixed $v) => $this->casterRegistry->cast(
                        $v,
                        new DynamoField(DynamoType::STRING)
                    ),
                    $item
                ),
                DynamoType::LIST => $this->mapListItems($item),
                DynamoType::MAP => $this->toItem($item),
            };

            $listItems[] = [$detectedType->value => $castValue];
        }

        return $listItems;
    }

    /**
     * Automatically detects the DynamoDB type for a PHP value
     *
     * @param mixed $value
     * @return DynamoType
     */
    private function detectDynamoType(mixed $value): DynamoType
    {
        if ($value === null) {
            return DynamoType::NULL;
        }

        if (is_bool($value)) {
            return DynamoType::BOOL;
        }

        if (is_string($value)) {
            return DynamoType::STRING;
        }

        if (is_numeric($value)) {
            return DynamoType::NUMBER;
        }

        if (is_array($value)) {
            // Check if it's a set (all elements of same type)
            if ($this->isStringSet($value)) {
                return DynamoType::STRING_SET;
            }

            if ($this->isNumberSet($value)) {
                return DynamoType::NUMBER_SET;
            }

            if ($this->isBinarySet($value)) {
                return DynamoType::BINARY_SET;
            }

            // Default to LIST for mixed arrays
            return DynamoType::LIST;
        }

        if (is_object($value)) {
            // Check if it's a DateTime-like object
            if ($value instanceof \DateTimeInterface) {
                return DynamoType::STRING;
            }

            // Default to MAP for objects
            return DynamoType::MAP;
        }

        // Default fallback to STRING
        return DynamoType::STRING;
    }

    /**
     * Checks if array is a string set (all string values)
     *
     * @param array $array
     * @return bool
     */
    private function isStringSet(array $array): bool
    {
        if (empty($array)) {
            return false;
        }

        foreach ($array as $item) {
            if (!is_string($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if array is a number set (all numeric values)
     *
     * @param array $array
     * @return bool
     */
    private function isNumberSet(array $array): bool
    {
        if (empty($array)) {
            return false;
        }

        foreach ($array as $item) {
            if (!is_numeric($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if array is a binary set (all binary/base64 values)
     *
     * @param array $array
     * @return bool
     */
    private function isBinarySet(array $array): bool
    {
        if (empty($array)) {
            return false;
        }

        foreach ($array as $item) {
            // For now, assume binary data is passed as strings
            // In practice, you might want more sophisticated detection
            if (!is_string($item) || !$this->isBinaryData($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Basic binary data detection (can be enhanced based on requirements)
     *
     * @param string $data
     * @return bool
     */
    private function isBinaryData(string $data): bool
    {
        // Simple heuristic: check if it's valid base64
        return base64_encode(base64_decode($data, true)) === $data;
    }
}
