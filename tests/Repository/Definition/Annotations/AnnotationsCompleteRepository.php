<?php

declare(strict_types=1);

namespace Goat\Domain\Tests\Repository\Definition\Annotations;

/**
 * @\Goat\Domain\Repository\Definition\EntityClassName(name=\Goat\Domain\Tests\Repository\Definition\MissingEntityClassNameEntity::class)
 * @\Goat\Domain\Repository\Definition\DatabaseTable(table="some_schema.some_table")
 * @\Goat\Domain\Repository\Definition\DatabasePrimaryKey(columns="id")
 * @\Goat\Domain\Repository\Definition\DatabaseColumn(column="id")
 * @\Goat\Domain\Repository\Definition\DatabaseColumn(column="some_text", property="someText")
 * @\Goat\Domain\Repository\Definition\DatabaseSelectColumn(column="other_table.bar")
 * @\Goat\Domain\Repository\Definition\DatabaseSelectColumn(column="other_table.some_column", property="someColumn")
 */
class AnnotationsCompleteRepository
{
}
