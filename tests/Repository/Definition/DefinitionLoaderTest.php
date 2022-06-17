<?php

declare(strict_types=1);

namespace Goat\Domain\Tests\Repository\Definition;

use Goat\Domain\Repository\Definition\DatabaseColumn;
use Goat\Domain\Repository\Definition\DatabaseSelectColumn;
use Goat\Domain\Repository\Definition\DefinitionLoader;
use Goat\Domain\Repository\Definition\RepositoryDefinition;
use Goat\Domain\Tests\Repository\Definition\Annotations\AnnotationsCompleteRepository;
use Goat\Domain\Tests\Repository\Definition\Annotations\AnnotationsMissingDatabaseTableRepository;
use Goat\Domain\Tests\Repository\Definition\Annotations\AnnotationsMissingEntityClassNameEntity;
use Goat\Domain\Tests\Repository\Definition\Attributes\AttributesCompleteRepository;
use Goat\Domain\Tests\Repository\Definition\Attributes\AttributesMissingDatabaseTableRepository;
use Goat\Domain\Tests\Repository\Definition\Attributes\AttributesMissingEntityClassNameEntity;
use PHPUnit\Framework\TestCase;

class DefinitionLoaderTest extends TestCase
{
    /**
     * @requires PHP >= 8.0
     */
    public function testWorkingAttributesDefinition(): void
    {
        $definitionLoader = new DefinitionLoader();

        $definition = $definitionLoader->loadDefinition(AttributesCompleteRepository::class);
        self::assertSame('aa1a39e182e89844af1da9848b8e5e859163e45b', $this->computeDefinitionHash($definition));
    }

    public function testWorkingAnnotationsDefinition(): void
    {
        $definitionLoader = new DefinitionLoader();

        $definition = $definitionLoader->loadDefinition(AnnotationsCompleteRepository::class);
        self::assertSame('aa1a39e182e89844af1da9848b8e5e859163e45b', $this->computeDefinitionHash($definition));
    }

    /**
     * @requires PHP >= 8.0
     */
    public function testMissingEntityClassNameAttributesDefinition(): void
    {
        $definitionLoader = new DefinitionLoader();

        $definition = $definitionLoader->loadDefinition(AttributesMissingEntityClassNameEntity::class);
        self::expectExceptionMessageMatches('/Entity class name is not set/');
        $this->computeDefinitionHash($definition);
    }

    public function testMissingEntityClassNameAnnotationsDefinition(): void
    {
        $definitionLoader = new DefinitionLoader();

        $definition = $definitionLoader->loadDefinition(AnnotationsMissingEntityClassNameEntity::class);
        self::expectExceptionMessageMatches('/Entity class name is not set/');
        $this->computeDefinitionHash($definition);
    }

    /**
     * @requires PHP >= 8.0
     */
    public function testMissingDatabaseTableAttributesDefinition(): void
    {
        $definitionLoader = new DefinitionLoader();

        $definition = $definitionLoader->loadDefinition(AttributesMissingDatabaseTableRepository::class);
        self::expectExceptionMessageMatches('/Database table is not set/');
        $this->computeDefinitionHash($definition);
    }

    public function testMissingDatabaseTableAnnotationsDefinition(): void
    {
        $definitionLoader = new DefinitionLoader();

        $definition = $definitionLoader->loadDefinition(AnnotationsMissingDatabaseTableRepository::class);
        self::expectExceptionMessageMatches('/Database table is not set/');
        $this->computeDefinitionHash($definition);
    }

    /**
     * Arbitrary predictible definition output string.
     */
    private function computeDefinitionString(RepositoryDefinition $definition): string
    {
        $ret = "ec:" . $definition->getEntityClassName() . ";\n";
        $ret .= "dt:" . $definition->getTableName()->getSchema() . "." . $definition->getTableName()->getName() . "->" . $definition->getTableName()->getAlias() . ";\n";
        $ret .= "dp:(" . \implode(',', $definition->getDatabasePrimaryKey()->getColumnNames()) . ");\n";
        $ret .= "dc:([" . \implode(
            "],[",
            \array_map(
                fn (DatabaseColumn $column) => $column->getColumnName() . '->' . $column->getPropertyName(),
                $definition->getDatabaseColumns()
            )
        ) . "]);\n";
        $ret .= "ds:([" . \implode(
            "],[",
            \array_map(
                fn (DatabaseSelectColumn $column) => $column->getColumnName() . '->' . $column->getPropertyName(),
                $definition->getDatabaseSelectColumns()
            )
        ) . "]);\n";

        return $ret;
    }

    /**
     * Arbitrary predictible definition output string hash.
     */
    private function computeDefinitionHash(RepositoryDefinition $definition): string
    {
        return \sha1($this->computeDefinitionString($definition));
    }
}
