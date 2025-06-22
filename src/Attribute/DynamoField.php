<?php

namespace Sapphire\Mapper\Attribute;

use Sapphire\Mapper\DynamoType;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class DynamoField
{
    public function __construct(private readonly DynamoType $type) {}

    public function getType(): DynamoType
    {
        return $this->type;
    }
}
