<?php

declare(strict_types=1);

namespace Goat\Domain\Tests\Repository;

use Goat\Domain\Repository\RepositoryInterface;
use Goat\Domain\Repository\WritableRepositoryInterface;
use Goat\Domain\Repository\Error\RepositoryEntityNotFoundError;
use Goat\Query\ExpressionRaw;
use Goat\Query\QueryError;
use Goat\Query\Where;
use Goat\Runner\DatabaseError;
use Goat\Runner\Runner;
use Goat\Runner\Testing\DatabaseAwareQueryTest;
use Goat\Runner\Testing\TestDriverFactory;

abstract class AbstractRepositoryTest extends DatabaseAwareQueryTest
{
    const ID_ADMIN = 1;
    const ID_JEAN = 2;

    /**
     * {@inheritdoc}
     */
    protected function createTestData(Runner $runner, ?string $schema): void
    {
        $runner->execute(
            <<<SQL
            DROP TABLE IF EXISTS some_entity
            SQL
        );

        $runner->execute(
            <<<SQL
            CREATE TABLE some_entity (
                id serial PRIMARY KEY,
                id_user integer DEFAULT NULL,
                status integer DEFAULT 1,
                foo integer NOT NULL,
                bar varchar(255),
                baz timestamp NOT NULL
            )
            SQL
        );

        $runner->execute(
            <<<SQL
            DROP TABLE IF EXISTS users
            SQL
        );

        $runner->execute(
            <<<SQL
            CREATE TABLE users (
                id integer PRIMARY KEY,
                name varchar(255)
            )
            SQL
        );

        $runner
            ->getQueryBuilder()
            ->insert('users')
            ->columns(['id', 'name'])
            ->values([self::ID_ADMIN, "admin"])
            ->values([self::ID_JEAN, "jean"])
            ->execute()
        ;

        $runner
            ->getQueryBuilder()
            ->insert('some_entity')
            ->columns(['foo', 'status', 'bar', 'id_user', 'baz'])
            ->values([2,  1, 'foo', self::ID_ADMIN, new \DateTime()])
            ->values([3,  1, 'bar', self::ID_JEAN, new \DateTime('now +1 day')])
            ->values([5,  1, 'baz', self::ID_ADMIN, new \DateTime('now -2 days')])
            ->values([7,  1, 'foo', self::ID_ADMIN, new \DateTime('now -6 hours')])
            ->values([11, 1, 'foo', self::ID_JEAN, new \DateTime()])
            ->values([13, 0, 'bar', self::ID_JEAN, new \DateTime('now -3 months')])
            ->values([17, 0, 'bar', self::ID_ADMIN, new \DateTime('now -3 years')])
            ->values([19, 0, 'baz', self::ID_ADMIN, new \DateTime()])
            ->values([23, 0, 'baz', self::ID_JEAN, new \DateTime('now +7 years')])
            ->values([29, 1, 'foo', self::ID_JEAN, new \DateTime('now +2 months')])
            ->values([31, 0, 'foo', self::ID_JEAN, new \DateTime('now +17 hours')])
            ->values([37, 2, 'foo', self::ID_ADMIN, new \DateTime('now -128 hours')])
            ->values([41, 2, 'bar', self::ID_JEAN, new \DateTime('now -8 days')])
            ->values([43, 2, 'bar', self::ID_ADMIN, new \DateTime('now -6 minutes')])
            ->execute()
        ;
    }

    /**
     * Does this repository supports join
     */
    abstract protected function supportsJoin(): bool;

    /**
     * Create the repository to test
     *
     * @param Runner $runner
     *   Current connection to test with
     * @param string $class
     *   Object class to use for hydrators
     * @param string[] $primaryKey
     *   Entity primary key definition
     */
    abstract protected function createRepository(Runner $runner, string $class, array $primaryKey): RepositoryInterface;

    /**
     * Create writable repository to test
     *
     * @param Runner $runner
     *   Current connection to test with
     * @param string $class
     *   Object class to use for hydrators
     * @param string[] $primaryKey
     *   Entity primary key definition
     *
     * @return WritableRepositoryInterface
     */
    abstract protected function createWritableRepository(Runner $runner, string $class, array $primaryKey): WritableRepositoryInterface;

    /**
     * Tests various utility methods
     *
     * @dataProvider runnerDataProvider
     */
    public function testUtilityMethods(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        $repository = $this->createRepository($runner, DomainModelObject::class, ['t.id']);
        $table = $repository->getTable();
        $this->assertSame('some_entity', $table->getName());
        $this->assertSame('t', $table->getAlias());
        $this->assertSame(DomainModelObject::class, $repository->getClassName());
        $this->assertSame($runner, $repository->getRunner());

        $repository = $this->createWritableRepository($runner, DomainModelObject::class, ['t.id']);
        $table = $repository->getTable();
        $this->assertSame('some_entity', $table->getName());
        $this->assertSame('t', $table->getAlias());
        $this->assertSame(DomainModelObject::class, $repository->getClassName());
        $this->assertSame($runner, $repository->getRunner());
    }

    /**
     * Tests find by primary key(s) feature
     *
     * @dataProvider runnerDataProvider
     */
    public function testFind(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        $repository = $this->createRepository($runner, DomainModelObject::class, ['t.id']);

        foreach ([1, [1]] as $id) {
            $item1 = $repository->findOne($id);
            $this->assertTrue($item1 instanceof DomainModelObject);
            // This also tests there is no conflict between table columns
            $this->assertSame(1, $item1->id);
        }

        foreach ([8, [8]] as $id) {
            $item8 = $repository->findOne($id);
            $this->assertTrue($item8 instanceof DomainModelObject);
            // This also tests there is no conflict between table columns
            $this->assertSame(8, $item8->id);
        }

        $this->assertNotSame($item1, $item8);

        // Also ensure that the user can legally be stupid
        try {
            $repository->findOne([1, 12]);
            $this->fail();
        } catch (DatabaseError $e) {
        }

        foreach ([[2, 3], [[2], [3]]] as $idList) {
            $result = $repository->findAll($idList);
            $this->assertCount(2, $result);
            $item2or3 = $result->fetch();
            $this->assertTrue($item2or3 instanceof DomainModelObject);
            // This also tests there is no conflict between table columns
            $this->assertContains($item2or3->id, [2, 3]);
        }
    }

    /**
     * Tests find by primary key(s) feature
     *
     * @dataProvider runnerDataProvider
     */
    public function testFindFirst(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        $repository = $this->createRepository($runner, DomainModelObject::class, ['t.id']);

        $item1 = $repository->findFirst(['id_user' => self::ID_ADMIN]);
        $this->assertInstanceOf(DomainModelObject::class, $item1);
        $this->assertSame($item1->id_user, self::ID_ADMIN);

        $item2 = $repository->findFirst(['id_user' => -1], false);
        $this->assertNull($item2);

        $item3 = $repository->findFirst(['id_user' => -1]);
        $this->assertNull($item3);

        // Also ensure that the user can legally be stupid
        try {
            $repository->findFirst(['id_user' => -1], true);
            $this->fail();
        } catch (RepositoryEntityNotFoundError $e) {
        }
    }

    /**
     * Tests find by primary key(s) feature when primary key has more than one column
     *
     * @dataProvider runnerDataProvider
     */
    public function testFindMultiple(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        $repository = $this->createRepository($runner, DomainModelObject::class, ['foo', 'status']);

        $item1 = $repository->findOne([2, 1]);
        $this->assertTrue($item1 instanceof DomainModelObject);
        // This also tests there is no conflict between table columns
        $this->assertSame(2, $item1->foo);
        $this->assertSame(1, $item1->status);

        $result = $repository->findAll([[2, 1], [23, 0], [999, 1000]]);
        $this->assertCount(2, $result);
        foreach ($result as $item) {
            $this->assertTrue($item instanceof DomainModelObject);
            $this->assertContains($item->foo, [2, 23]);
            $this->assertContains($item->status, [1, 0]);
        }
    }

    /**
     * Tests find by criteria
     *
     * @dataProvider runnerDataProvider
     */
    public function testFindByCriteria(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        $supportsJoin = $this->supportsJoin();
        $repository = $this->createRepository($runner, DomainModelObject::class, ['id']);

        // Most simple condition ever
        $result = $repository->query(['id_user' => self::ID_ADMIN])->execute();
        $this->assertCount(7, $result);
        foreach ($result as $item) {
            $this->assertTrue($item instanceof DomainModelObject);
            $this->assertSame(self::ID_ADMIN, $item->id_user);
            if ($supportsJoin) {
                $this->assertSame("admin", $item->name);
            }
        }

        // Using a single expression
        $result = $repository->query(new ExpressionRaw('id_user = ?', [self::ID_ADMIN]))->execute();
        $this->assertCount(7, $result);
        foreach ($result as $item) {
            $this->assertTrue($item instanceof DomainModelObject);
            $this->assertSame(self::ID_ADMIN, $item->id_user);
            if ($supportsJoin) {
                $this->assertSame("admin", $item->name);
            }
        }

        // Using a Where instance
        $result = $repository->query((new Where())->condition('id_user', self::ID_ADMIN))->execute();
        $this->assertCount(7, $result);
        foreach ($result as $item) {
            $this->assertTrue($item instanceof DomainModelObject);
            $this->assertSame(self::ID_ADMIN, $item->id_user);
            if ($supportsJoin) {
                $this->assertSame("admin", $item->name);
            }
        }

        // More than one condition
        $result = $repository
            ->query([
                'id_user' => self::ID_JEAN,
                new ExpressionRaw('baz < ?', [new \DateTime("now -1 second")])
            ])
            ->execute()
        ;
        $this->assertCount(2, $result);
        foreach ($result as $item) {
            $this->assertTrue($item instanceof DomainModelObject);
            $this->assertSame(self::ID_JEAN, $item->id_user);
            if ($supportsJoin) {
                $this->assertSame("jean", $item->name);
            }
            $this->assertLessThan(new \DateTime("now -1 second"), $item->baz);
        }

        // Assert that user can be stupid sometime
        try {
            $repository->query('oh you you you');
            $this->fail();
        } catch (QueryError $e) {
        }
    }

    /**
     * Tests pagination
     *
     * @dataProvider runnerDataProvider
     */
    public function testPaginate(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        $supportsJoin = $this->supportsJoin();
        $repository = $this->createRepository($runner, DomainModelObject::class, ['id']);

        // Most simple condition ever
        $result = $repository
            ->query(['id_user' => self::ID_ADMIN])
            ->paginate()
            ->setLimit(3)
            ->setPage(2)
        ;
        $this->assertSame(2, $result->getCurrentPage());
        $this->assertSame(3, $result->getLastPage());
        $this->assertCount(3, $result);
        foreach ($result as $item) {
            $this->assertTrue($item instanceof DomainModelObject);
            $this->assertSame(self::ID_ADMIN, $item->id_user);
            if ($supportsJoin) {
                $this->assertSame("admin", $item->name);
            }
        }

        // Using a single expression
        $result = $repository
            ->query(new ExpressionRaw('id_user = ?', [self::ID_ADMIN]))
            ->paginate()
        ;
        $this->assertCount(7, $result);
        foreach ($result as $item) {
            $this->assertTrue($item instanceof DomainModelObject);
            $this->assertSame(self::ID_ADMIN, $item->id_user);
            if ($supportsJoin) {
                $this->assertSame("admin", $item->name);
            }
        }

        // Using a Where instance
        $result = $repository
            ->query((new Where())->condition('id_user', self::ID_ADMIN))
            ->paginate()
            ->setLimit(6)
            ->setPage(1)
        ;
        $this->assertSame(1, $result->getCurrentPage());
        $this->assertSame(2, $result->getLastPage());
        $this->assertTrue($result->hasNextPage());
        $this->assertFalse($result->hasPreviousPage());
        $this->assertCount(6, $result);
        foreach ($result as $item) {
            $this->assertTrue($item instanceof DomainModelObject);
            $this->assertSame(self::ID_ADMIN, $item->id_user);
            if ($supportsJoin) {
                $this->assertSame("admin", $item->name);
            }
        }

        // More than one condition
        $result = $repository
            ->query([
                'id_user' => self::ID_JEAN,
                new ExpressionRaw('baz < ?', [new \DateTime("now -1 second")])
            ])
            ->paginate()
            ->setLimit(10)
            ->setPage(1)
        ;
        $this->assertSame(1, $result->getCurrentPage());
        $this->assertSame(1, $result->getLastPage());
        $this->assertFalse($result->hasNextPage());
        $this->assertFalse($result->hasPreviousPage());
        $this->assertCount(2, $result);
        foreach ($result as $item) {
            $this->assertTrue($item instanceof DomainModelObject);
            $this->assertSame(self::ID_JEAN, $item->id_user);
            if ($supportsJoin) {
                $this->assertSame("jean", $item->name);
            }
            $this->assertLessThan(new \DateTime("now -1 second"), $item->baz);
        }
    }

    /**
     * @dataProvider runnerDataProvider
     */
    public function testCreate(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        $repository = $this->createWritableRepository($runner, DomainModelObject::class, ['id']);

        if (!$runner->supportsReturning()) {
            $this->markTestIncomplete("driver does not support RETURNING");
        }

        $entity1 = $repository->create([
            'foo' => 113,
            'bar' => 'Created entity 1',
            'baz' => new \DateTime(),
        ]);
        $this->assertTrue($entity1 instanceof DomainModelObject);
        $this->assertSame(113, $entity1->foo);
        $this->assertSame("Created entity 1", $entity1->bar);

        $entity2 = $repository->create([
            'foo' => 1096,
            'bar' => 'Created entity 2',
            'baz' => new \DateTime(),
        ]);
        $this->assertTrue($entity2 instanceof DomainModelObject);
        $this->assertSame(1096, $entity2->foo);
        $this->assertSame("Created entity 2", $entity2->bar);

        $result = $repository->findAll([$entity1->id, $entity2->id]);
        $this->assertSame(2, $result->countRows());
    }

    /**
     * @dataProvider runnerDataProvider
     */
    public function testCreateFrom(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        $this->markTestIncomplete("Cannot use hydrator directly.");

        $repository = $this->createWritableRepository($runner, DomainModelObject::class, ['id']);

        if (!$runner->supportsReturning()) {
            $this->markTestIncomplete("driver does not support RETURNING");
        }

        $entity = $runner->getHydratorMap()->get(DomainModelObject::class)->createAndHydrateInstance([
            'foo' => 113,
            'bar' => 'Created entity 1',
            'baz' => new \DateTime(),
        ]);
        $this->assertTrue($entity instanceof DomainModelObject);
        $this->assertSame(113, $entity->foo);
        $this->assertSame("Created entity 1", $entity->bar);
        $this->assertEmpty($entity->id);

        $created = $repository->createFrom($entity);
        $this->assertNotSame($entity, $created);
        $this->assertTrue($created instanceof DomainModelObject);
        $this->assertSame(113, $created->foo);
        $this->assertSame("Created entity 1", $created->bar);
        $this->assertNotEmpty($created->id);
    }

    /**
     * @dataProvider runnerDataProvider
     */
    public function testUpdate(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        $repository = $this->createWritableRepository($runner, DomainModelObject::class, ['id']);

        if (!$runner->supportsReturning()) {
            $this->markTestIncomplete("driver does not support RETURNING");
        }

        $updated = $repository->update(9, [
            'bar' => 'The new bar value',
            'status' => 112,
        ]);
        $this->assertTrue($updated instanceof DomainModelObject);
        $this->assertSame(9, $updated->id);
        $this->assertSame('The new bar value', $updated->bar);
        $this->assertSame(112, $updated->status);

        $reloaded = $repository->findOne(9);
        $this->assertTrue($reloaded instanceof DomainModelObject);
        $this->assertSame('The new bar value', $reloaded->bar);
        $this->assertSame(112, $reloaded->status);
    }

    /**
     * @dataProvider runnerDataProvider
     */
    public function testUpdateFrom(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        $repository = $this->createWritableRepository($runner, DomainModelObject::class, ['id']);

        if (!$runner->supportsReturning()) {
            $this->markTestIncomplete("driver does not support RETURNING");
        }

        $reference = $repository->findOne(5);
        $this->assertTrue($reference instanceof DomainModelObject);
        $this->assertSame(5, $reference->id);
        $this->assertSame('foo', $reference->bar);

        $toBeUpdated = $repository->findOne(9);
        $this->assertSame(9, $toBeUpdated->id);
        $this->assertSame('baz', $toBeUpdated->bar);

        $updated = $repository->updateFrom(9, $reference);
        $this->assertInstanceOf(DomainModelObject::class, $updated);
        $this->assertSame(9, $updated->id);
        $this->assertSame('foo', $updated->bar);

        $updatedReloaded = $repository->findOne(9);
        $this->assertInstanceOf(DomainModelObject::class, $updatedReloaded);
        $this->assertSame(9, $updatedReloaded->id);
        $this->assertSame('foo', $updatedReloaded->bar);

        // Assert that original has not changed (no side effect)
        $this->assertInstanceOf(DomainModelObject::class, $reference);
        $this->assertSame(5, $reference->id);
        $this->assertSame('foo', $reference->bar);

        // and the same by reloading it
        $reloaded = $repository->findOne(5);
        $this->assertInstanceOf(DomainModelObject::class, $reloaded);
        $this->assertSame(5, $reloaded->id);
        $this->assertSame('foo', $reloaded->bar);

        try {
            $repository->delete(666, true);
            $this->fail("updating from to a non existing row should have raised an exception");
        } catch (\Exception $e) {
            $this->assertTrue(true, "updating from to a non existing row raised an exception");
        }
    }

    /**
     * @dataProvider runnerDataProvider
     */
    public function testDelete(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        $repository = $this->createWritableRepository($runner, DomainModelObject::class, ['id']);

        if (!$runner->supportsReturning()) {
            $this->markTestIncomplete("driver does not support RETURNING");
        }

        $deleted = $repository->delete(11, false);
        $this->assertInstanceOf(DomainModelObject::class, $deleted);
        $this->assertSame(11, $deleted->id);

        $deleted = $repository->delete(11, false);
        $this->assertEmpty($deleted);

        try {
            $repository->delete(666, true);
            $this->fail("deleting a non existing row should have raised an exception");
        } catch (\Exception $e) {
            $this->assertTrue(true, "deleting a non existing row raised an exception");
        }
    }
}
