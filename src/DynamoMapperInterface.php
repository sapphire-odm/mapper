<?php declare(strict_types=1);

namespace Sapphire\Mapper;

interface DynamoMapperInterface
{
    /**
     * @param object|class-string $object
     *
     * @return string
     */
    public function getTableName(object|string $object): string;

    /**
     * @param object $object Class that has the DynamoClass Attribute #[DynamoClass]
     *
     * @return array
     */
    public function toPutItem(object $object): array;

    /**
     * @param object $object Class that has the DynamoClass Attribute #[DynamoClass]
     *
     * @return array
     */
    public function toItem(object $object): array;
}
