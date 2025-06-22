<?php

namespace Sapphire\Mapper\Exception;

use Sapphire\Mapper\Attribute\DynamoField;

class NoCasterFoundException extends \RuntimeException
{
    public function __construct(mixed $value, DynamoField $dynamoFieldMeta)
    {
        parent::__construct(sprintf('No caster found for value of type "%s" on property for dynamo type "%s"', gettype($value), $dynamoFieldMeta->getType()->name));
    }
}