<?php

namespace Sapphire\Mapper\Caster;

use Sapphire\Mapper\Attribute\DynamoField;

class DateToStringCaster implements CasterInterface
{
    public function supports(mixed $value, DynamoField $dynamoFieldMeta): bool
    {
        return $value instanceof \DateTimeInterface;
    }

    /**
     * @param \DateTimeInterface $value
     * @param DynamoField $dynamoFieldMeta
     *
     * @return string
     */
    public function cast(mixed $value, DynamoField $dynamoFieldMeta): mixed
    {
        return $value->format('c');
    }
}