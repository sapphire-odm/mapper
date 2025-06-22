<?php

namespace Sapphire\Mapper\Caster;

use Sapphire\Mapper\Attribute\DynamoField;
use Sapphire\Mapper\DynamoClassProperty;
use Sapphire\Mapper\Exception\NoCasterFoundException;

/**
 * @internal
 */
class CasterRegistry
{
    /**
     * @var CasterInterface[]
     */
    private array $caster;

    public function __construct(array $caster = []) {
        $this->caster = $caster;
    }

    public function addCaster(CasterInterface $caster): void
    {
        $this->caster = [$caster, ...$this->caster];
    }

    public function cast(mixed $value, DynamoField $dynamoFieldMeta): mixed
    {
        foreach ($this->caster as $caster) {
            if ($caster->supports($value, $dynamoFieldMeta)) {
                return $caster->cast($value, $dynamoFieldMeta);
            }
        }

        throw new NoCasterFoundException($value, $dynamoFieldMeta);
    }
}