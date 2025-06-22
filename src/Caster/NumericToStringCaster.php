<?php

namespace Sapphire\Mapper\Caster;

use Sapphire\Mapper\Attribute\DynamoField;

class NumericToStringCaster implements CasterInterface
{
    public function supports(mixed $value, DynamoField $dynamoFieldMeta): bool
    {
        return is_numeric($value);
    }

    /**
     * @param numeric $value
     *
     * @return string
     */
    public function cast(mixed $value, DynamoField $dynamoFieldMeta): mixed
    {
        return (string) $value;
    }
}