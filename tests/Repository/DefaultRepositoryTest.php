<?php

declare(strict_types=1);

namespace Goat\Domain\Tests\Repository;

use Goat\Domain\Repository\DefaultRepository;
use Goat\Domain\Repository\GoatRepositoryInterface;
use Goat\Domain\Repository\WritableDefaultRepository;
use Goat\Domain\Repository\WritableRepositoryInterface;
use Goat\Runner\Runner;
use Goat\Query\ExpressionRelation;

class DefaultRepositoryTest extends AbstractRepositoryTest
{
    /**
     * {@inheritdoc}
     */
    protected function supportsJoin(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function createRepository(Runner $driver, string $class, array $primaryKey): GoatRepositoryInterface
    {
        return new class ($driver, $primaryKey, $class) extends DefaultRepository
        {
            private $testProvidedClass;

            public function __construct(Runner $runner, array $primaryKey, $class)
            {
                parent::__construct($runner, $primaryKey, ExpressionRelation::create('some_entity', 't'));

                $this->testProvidedClass = $class;
            }

            /**
             * Define class that will be hydrated by this repository, if none given raw
             * database result will be returned by load/find methods.
             */
            protected function defineClass(): ?string
            {
                return $this->testProvidedClass;
            }

            /**
             * Define columns you wish to map onto the relation in select, update
             * and insert queries.
             *
             * Keys will always be ignored, don't set keys here.
             *
             * Override this method in your implementation to benefit from this. If not
             * overriden, per default select queries will select "RELATION_ALIAS.*".
             *
             * If defineSelectColumns() is defined, this method will NOT be used for
             * select queries.
             *
             * @return string[]
             */
            protected function defineColumns(): array
            {
                return ['id', 'id_user', 'status', 'foo', 'bar', 'baz'];
            }
        };
    }

    /**
     * {@inheritdoc}
     */
    protected function createWritableRepository(Runner $driver, string $class, array $primaryKey): WritableRepositoryInterface
    {
        $this->markTestSkipped();

        return new class ($driver, $primaryKey, $class) extends WritableDefaultRepository
        {
            private $testProvidedClass;

            public function __construct(Runner $runner, array $primaryKey, $class)
            {
                parent::__construct($runner, $primaryKey, ExpressionRelation::create('some_entity', 't'));

                $this->testProvidedClass = $class;
            }

            /**
             * Define class that will be hydrated by this repository, if none given raw
             * database result will be returned by load/find methods.
             */
            protected function defineClass(): ?string
            {
                return $this->testProvidedClass;
            }

            /**
             * Define columns you wish to map onto the relation in select, update
             * and insert queries.
             *
             * Keys will always be ignored, don't set keys here.
             *
             * Override this method in your implementation to benefit from this. If not
             * overriden, per default select queries will select "RELATION_ALIAS.*".
             *
             * If defineSelectColumns() is defined, this method will NOT be used for
             * select queries.
             *
             * @return string[]
             */
            protected function defineColumns(): array
            {
                return ['id', 'id_user', 'status', 'foo', 'bar', 'baz'];
            }
        };
    }
}
