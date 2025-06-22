<?php

namespace Sapphire\Mapper\Caster;

use Sapphire\Mapper\Attribute\DynamoField;

interface CasterInterface
{
    public function supports(mixed $value, DynamoField $dynamoFieldMeta): bool;
    public function cast(mixed $value, DynamoField $dynamoFieldMeta): mixed;
}