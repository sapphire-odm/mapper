<?php

namespace Sapphire\Mapper\Exception;

class MissingAttributeException extends \RuntimeException
{
    /**
     * @param class-string $attribute
     */
    public function __construct(string $attribute)
    {
        parent::__construct(sprintf('Missing attribute "%s"', $attribute));
    }
}