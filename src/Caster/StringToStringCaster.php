<?php

namespace Sapphire\Mapper\Caster;

use Sapphire\Mapper\Attribute\DynamoField;

class StringToStringCaster implements CasterInterface
{
    public function supports(mixed $value, DynamoField $dynamoFieldMeta): bool
    {
        return is_string($value);
    }

    /**
     * @param string $value
     *
     * @return string
     */
    public function cast(mixed $value, DynamoField $dynamoFieldMeta): mixed
    {
        return $value;
    }
}