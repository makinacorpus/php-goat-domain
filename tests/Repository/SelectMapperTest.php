<?php

declare(strict_types=1);

namespace Goat\Domain\Tests\Repository;

use Goat\Domain\Repository\GoatRepositoryInterface;
use Goat\Domain\Repository\SelectRepository;
use Goat\Domain\Repository\WritableRepositoryInterface;
use Goat\Domain\Repository\WritableSelectRepository;
use Goat\Runner\Runner;

class SelectRepositoryTest extends AbstractRepositoryTest
{
    /**
     * {@inheritdoc}
     */
    protected function supportsJoin() : bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function createRepository(Runner $runner, string $class, array $primaryKey): GoatRepositoryInterface
    {
        $this->markTestSkipped();

        return new SelectRepository(
            $runner,
            $class,
            $primaryKey,
            $runner
                ->getQueryBuilder()
                ->select('some_entity', 't')
                ->column('t.*')
                ->column('u.name')
                ->leftJoin('users', 'u.id = t.id_user', 'u'),
            ['id', 'id_user', 'status', 'foo', 'bar', 'baz']
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function createWritableRepository(Runner $runner, string $class, array $primaryKey): WritableRepositoryInterface
    {
        $this->markTestSkipped();

        return new WritableSelectRepository(
            $runner,
            $class,
            $primaryKey,
            $runner
                ->getQueryBuilder()
                ->select('some_entity', 't')
                ->column('t.*')
                ->column('u.name')
                ->leftJoin('users', 'u.id = t.id_user', 'u'),
            ['id', 'id_user', 'status', 'foo', 'bar', 'baz']
        );
    }
}
