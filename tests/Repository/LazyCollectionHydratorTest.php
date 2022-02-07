<?php

declare(strict_types=1);

namespace Goat\Domain\Tests\Repository;

use Goat\Domain\Repository\DefaultLazyCollection;
use Goat\Domain\Repository\LazyCollectionHydrator;
use PHPUnit\Framework\TestCase;

final class LazyCollectionHydratorTest extends TestCase
{
    private function createMockNestedHydrator(): callable
    {
        return static function ($object) {
            return (array)$object;
        };
    }

    public function testThatLazyCollectionsAreNotWrapped()
    {
        $callable = new MockLazyCollection([7, 11, 17]);

        $hydrator = new LazyCollectionHydrator(
            $this->createMockNestedHydrator(),
            [
                'foo' => $callable,
            ],
            ['id']
        );

        $values = $hydrator(['id' => 12]);

        $this->assertSame($callable, $values['foo']);
        $this->assertSame([7, 11, 17], \iterator_to_array($values['foo']));
    }

    public function testThatFunctionsReturningLazyCollectionsAreNotWrapped()
    {
        $callable = function (): MockLazyCollection {
            return new MockLazyCollection([7, 11, 17]);
        };

        $hydrator = new LazyCollectionHydrator(
            $this->createMockNestedHydrator(),
            [
                'foo' => $callable,
            ],
            ['id']
        );

        $values = $hydrator(['id' => 12]);

        $this->assertInstanceOf(MockLazyCollection::class, $values['foo']);
        $this->assertSame([7, 11, 17], \iterator_to_array($values['foo']));
    }

    public function testThatNonLazyCollectionsAreWrapped()
    {
        $callable = function () {
            return [7, 11, 17];
        };

        $hydrator = new LazyCollectionHydrator(
            $this->createMockNestedHydrator(),
            [
                'foo' => $callable,
            ],
            ['id']
        );

        $values = $hydrator(['id' => 12]);

        $this->assertInstanceOf(DefaultLazyCollection::class, $values['foo']);
        $this->assertSame([7, 11, 17], \iterator_to_array($values['foo']));
    }

    public function testGlobalBehaviour()
    {
        $hydrator = new LazyCollectionHydrator(
            $this->createMockNestedHydrator(),
            [
                'one' => function (array $primaryKey) {
                    for ($i = 10; $i < 20; ++$i) {
                        yield $i;
                    }
                },
                'two' => function (array $primaryKey) {
                    foreach ($primaryKey as $columnName) {
                        yield $columnName;
                    }
                },
                'three' => function (array $primaryKey) {
                    foreach ($primaryKey as $columnName) {
                        yield $columnName;
                    }
                },
            ],
            ['id', 'type']
        );

        $values = $hydrator(['baz' => 'test', 'three' => 'oups', 'id' => 12, 'type' => 'cake']);

        $this->assertSame('test', $values['baz'], "Hydrator passes values");
        $this->assertSame('oups', $values['three'], "Hydrator does not overwrite existing properties");
        $this->assertCount(10, $values['one']);
        $this->assertCount(2, $values['two']);
        $this->assertSame([12, 'cake'], \iterator_to_array($values['two']));
    }
}
