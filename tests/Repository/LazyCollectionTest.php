<?php

declare(strict_types=1);

namespace Goat\Domain\Tests\Repository;

use Goat\Domain\Repository\DefaultLazyCollection;
use PHPUnit\Framework\TestCase;

final class LazyCollectionTest extends TestCase
{
    public function testArrayAccessInterface()
    {
        $collection = new DefaultLazyCollection(static function () {
            foreach (['foo' => 1, 'bar' => 2, 'baz' => 12] as $key => $value) {
                yield $key => $value;
            }
        });

        $this->assertSame(1, $collection['foo']);
        $this->assertSame(2, $collection['bar']);
        $this->assertSame(12, $collection['baz']);
        $this->assertFalse(isset($collection['pouet']));
        $this->assertFalse(isset($collection[3]));
    }

    public function testArrayAccessInterfaceWithNumericEntries()
    {
        $collection = new DefaultLazyCollection(static function () {
            foreach ([1 => 'foo', 2 => 'bar', 7 => 'baz'] as $key => $value) {
                yield $key => $value;
            }
        });

        $this->assertSame('foo', $collection[1]);
        $this->assertSame('bar', $collection[2]);
        $this->assertSame('baz', $collection[7]);
        $this->assertFalse(isset($collection[0]));
        $this->assertFalse(isset($collection[3]));
    }

    public function testArrayAccessOffsetSetRaiseError()
    {
        $collection = new DefaultLazyCollection(static function () {
            foreach (['foo' => 1, 'bar' => 2, 'baz' => 12] as $key => $value) {
                yield $key => $value;
            }
        });

        $this->expectException(\BadMethodCallException::class);
        $collection['baz'] = 'test';
    }

    public function testArrayAccessOffsetUnsetRaiseError()
    {
        $collection = new DefaultLazyCollection(static function () {
            foreach (['foo' => 1, 'bar' => 2, 'baz' => 12] as $key => $value) {
                yield $key => $value;
            }
        });

        $this->expectException(\BadMethodCallException::class);
        unset($collection['bar']);
    }

    public function testCount()
    {
        $collection = new DefaultLazyCollection(static function () {
            foreach (['foo' => 1, 'bar' => 2, 'baz' => 12] as $key => $value) {
                yield $key => $value;
            }
        });

        $this->assertSame(3, \count($collection));
    }

    public function testInitializeIsCalledOnlyOnce()
    {
        $count = 0;

        $collection = new DefaultLazyCollection(static function () use (&$count) {
            $count++;
            foreach (['foo' => 1, 'bar' => 2, 'baz' => 12] as $key => $value) {
                yield $key => $value;
            }
        });

        $this->assertSame(3, \count($collection));

        foreach ($collection as $key => $item) {
            // Do nothing.
        }

        $this->assertSame(3, \count($collection));
        $this->assertSame(1, $count);
    }
}
