<?php

declare(strict_types=1);

namespace Goat\Domain\Repository\Definition;

use Goat\Domain\Repository\Key;

/**
 * Primary usage is for service containers.
 */
class ArrayRepositoryDefinition extends AbstractRepositoryDefinition
{
    public function __construct(array $data)
    {
        if (isset($data['entityClassName'])) {
            $this->entityClassName = $data['entityClassName'];
        }
        if (isset($data['databaseColumns'])) {
            foreach ($data['databaseColumns'] as $columnData) {
                $this->databaseColumns[] = new DatabaseColumn(...$columnData);
            }
        }
        if (isset($data['databaseSelectColumns'])) {
            foreach ($data['databaseSelectColumns'] as $columnData) {
                $this->databaseColumns[] = new DatabaseSelectColumn(...$columnData);
            }
        }
        if (isset($data['databaseTable'])) {
            $this->databaseTable = new DatabaseTable(...$data['databaseTable']);
        }
        if (isset($data['databasePrimaryKey'])) {
            $this->databasePrimaryKey = new Key(...$data['databasePrimaryKey']);
        }
    }

    public static function toArray(RepositoryDefinition $definition)
    {
        $ret = [];

        $ret['entityClassName'] = $definition->getEntityClassName();

        foreach ($definition->getDatabaseColumns() as $column) {
            \assert($column instanceof DatabaseColumn);
            $ret['databaseColumns'][] = [$column->getColumnName(), $column->getPropertyName()];
        }

        foreach ($definition->getDatabaseColumns() as $column) {
            \assert($column instanceof DatabaseColumn);
            $ret['databaseSelectColumns'][] = [$column->getColumnName(), $column->getPropertyName()];
        }

        $databaseTable = $definition->getTableName();
        $ret['databaseTable'] = [$databaseTable->getName(), $databaseTable->getAlias(), $databaseTable->getSchema()];

        $primaryKey = $definition->getDatabasePrimaryKey();
        if (!$primaryKey->isEmpty()) {
            $ret['databasePrimaryKey'] = $primaryKey->getColumnNames();
        }

        return $ret;
    }
}
