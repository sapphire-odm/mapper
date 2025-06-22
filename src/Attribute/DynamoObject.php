<?php

namespace Sapphire\Mapper\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class DynamoObject
{
    public function __construct(private readonly string $tableName) {}

    public function getTableName(): string
    {
        return $this->tableName;
    }
}
