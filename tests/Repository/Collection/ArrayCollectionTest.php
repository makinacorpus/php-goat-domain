<?php

declare(strict_types=1);

namespace Goat\Domain\Tests\Repository\Collection;

use Goat\Domain\Repository\Collection\ArrayCollection;
use PHPUnit\Framework\TestCase;

final class ArrayCollectionTest extends TestCase
{
    public function testInitializeWithArray(): void
    {
        $coll = new ArrayCollection([1, 2, 3]);
        self::assertSame([1, 2, 3], \array_values(\iterator_to_array($coll)));
        self::assertSame(3, $coll->count());
    }

    public function testInitializeWithGenerator(): void
    {
        $coll = new ArrayCollection((fn () => yield from [1, 2, 3])());
        self::assertSame([1, 2, 3], \array_values(\iterator_to_array($coll)));
        self::assertSame(3, $coll->count());
    }

    public function testInitializeWithNull(): void
    {
        $coll = new ArrayCollection(null);
        self::assertSame([], \array_values(\iterator_to_array($coll)));
        self::assertSame(0, $coll->count());
    }

    public function testInitializeWithCallbackArray(): void
    {
        $coll = new ArrayCollection(fn () => [1, 2, 3]);
        self::assertSame([1, 2, 3], \array_values(\iterator_to_array($coll)));
        self::assertSame(3, $coll->count());
    }

    public function testInitializeWithCallbackGenerator(): void
    {
        $coll = new ArrayCollection(fn () => yield from [1, 2, 3]);
        self::assertSame([1, 2, 3], \array_values(\iterator_to_array($coll)));
        self::assertSame(3, $coll->count());
    }

    public function testInitializeWithCallbackNull(): void
    {
        $coll = new ArrayCollection(fn () => null);
        self::assertSame([], \array_values(\iterator_to_array($coll)));
        self::assertSame(0, $coll->count());
    }

    public function testAll(): void
    {
        $coll = new ArrayCollection([1, 2, 3]);
        self::assertTrue($coll->all(fn ($value) => 0 < $value));
        self::assertFalse($coll->all(fn ($value) => 2 < $value));

        $coll = new ArrayCollection([]);
        self::assertFalse($coll->all(fn () => true));
    }

    public function testAny(): void
    {
        $coll = new ArrayCollection([1, 2, 3]);
        self::assertTrue($coll->any(fn ($value) => 0 < $value));
        self::assertTrue($coll->any(fn ($value) => 2 < $value));
        self::assertFalse($coll->any(fn ($value) => 17 < $value));

        $coll = new ArrayCollection([]);
        self::assertFalse($coll->all(fn () => true));
    }

    public function testContainsKeyWithInt(): void
    {
        $sampleDate = new \DateTimeImmutable();
        $coll = new ArrayCollection([1, "foo", $sampleDate]);

        self::assertFalse($coll->containsKey(-1));
        self::assertTrue($coll->containsKey(0));
        self::assertTrue($coll->containsKey(1));
        self::assertTrue($coll->containsKey(2));
        self::assertFalse($coll->containsKey(3));
    }

    public function testContainsCallback(): void
    {
        $sampleDate = new \DateTimeImmutable();
        $coll = new ArrayCollection([1, "foo", $sampleDate]);

        self::assertTrue($coll->contains(fn ($value) => 1 === $value));
        self::assertFalse($coll->contains(fn ($value) => 2 === $value));
        self::assertTrue($coll->contains(fn ($value) => $value instanceof \DateTimeInterface));
    }

    public function testContainsValue(): void
    {
        $sampleDate = new \DateTimeImmutable();
        $coll = new ArrayCollection([1, "foo", $sampleDate]);

        self::assertFalse($coll->contains('bla'));
        self::assertTrue($coll->contains($sampleDate));
    }

    public function testGetWithNegativeRaiseError(): void
    {
        $sampleDate = new \DateTimeImmutable();
        $coll = new ArrayCollection([1, "foo", $sampleDate]);

        self::expectException(\InvalidArgumentException::class);
        $coll->get(-1);
    }

    public function testGetWithPositiveRaiseError(): void
    {
        $sampleDate = new \DateTimeImmutable();
        $coll = new ArrayCollection([1, "foo", $sampleDate]);

        self::expectException(\InvalidArgumentException::class);
        $coll->get(3);
    }

    public function testGetWithInt(): void
    {
        $sampleDate = new \DateTimeImmutable();
        $coll = new ArrayCollection([1, "foo", $sampleDate]);

        self::assertSame(1, $coll->get(0));
        self::assertSame("foo", $coll->get(1));
        self::assertSame($sampleDate, $coll->get(2));
    }

    public function testGetCallback(): void
    {
        $sampleDate = new \DateTimeImmutable();
        $coll = new ArrayCollection([1, "foo", $sampleDate]);

        self::assertSame(1, $coll->get(fn ($value) => 1 === $value));
        self::assertSame($sampleDate, $coll->get(fn ($value) => $value instanceof \DateTimeInterface));
    }

    public function testGetCallbackRaiseErrorIfNotFound(): void
    {
        $sampleDate = new \DateTimeImmutable();
        $coll = new ArrayCollection([1, "foo", $sampleDate]);

        self::expectException(\InvalidArgumentException::class);
        $coll->get(fn () => false);
    }

    public function testFirstWithNull(): void
    {
        $sampleDate = new \DateTimeImmutable();
        $coll = new ArrayCollection([1, "foo", "bar", $sampleDate]);

        self::assertSame(1, $coll->first());
    }

    public function testFirstWithNullWhenEmpty(): void
    {
        $coll = new ArrayCollection([]);

        self::assertNull($coll->first());
    }

    public function testFirstWithCallback(): void
    {
        $sampleDate = new \DateTimeImmutable();
        $coll = new ArrayCollection([1, "foo", "bar", $sampleDate]);

        self::assertSame("foo", $coll->first(fn ($value) => \is_string($value)));
    }

    public function testFirstWithCallbackNoMatch(): void
    {
        $sampleDate = new \DateTimeImmutable();
        $coll = new ArrayCollection([1, "foo", "bar", $sampleDate]);

        self::assertNull($coll->first(fn () => false));
    }

    public function testLastWithNull(): void
    {
        $sampleDate = new \DateTimeImmutable();
        $coll = new ArrayCollection([1, "foo", "bar", $sampleDate]);

        self::assertSame($sampleDate, $coll->last());
    }

    public function testLastWithNullWhenEmpty(): void
    {
        $coll = new ArrayCollection([]);

        self::assertNull($coll->last());
    }

    public function testLastWithCallback(): void
    {
        $sampleDate = new \DateTimeImmutable();
        $coll = new ArrayCollection([1, "foo", "bar", $sampleDate]);

        self::assertSame("bar", $coll->last(fn ($value) => \is_string($value)));
    }

    public function testLastWithCallbackNoMatch(): void
    {
        $sampleDate = new \DateTimeImmutable();
        $coll = new ArrayCollection([1, "foo", "bar", $sampleDate]);

        self::assertNull($coll->first(fn () => false));
    }

    public function testSetWithNegativeOutOfBounds(): void
    {
        self::markTestSkipped();

        $sampleDate = new \DateTimeImmutable();
        $coll = new ArrayCollection([1, "foo", $sampleDate]);

        self::assertFalse($coll->isModified());

        $coll->set(-1, "bar");
        self::assertSame(["bar", 1, "foo", $sampleDate], \array_values(\iterator_to_array($coll)));
        self::assertSame(4, $coll->count());

        self::assertTrue($coll->isModified());
    }

    public function testSetWithPositiveOutOfBounds(): void
    {
        self::markTestSkipped();

        $sampleDate = new \DateTimeImmutable();
        $coll = new ArrayCollection([1, "foo", $sampleDate]);

        self::assertFalse($coll->isModified());

        $coll->set(17, "bar");
        self::assertSame([1, "foo", $sampleDate, "bar"], \array_values(\iterator_to_array($coll)));
        self::assertSame(5, $coll->count());

        self::assertTrue($coll->isModified());
    }

    public function testSet(): void
    {
        self::markTestSkipped();

        $sampleDate = new \DateTimeImmutable();
        $coll = new ArrayCollection([1, "foo", $sampleDate]);

        $coll->set(2, "bar", "baz");
        self::assertSame([1, "foo", "bar", "baz", $sampleDate], \array_values(\iterator_to_array($coll)));
        self::assertSame(5, $coll->count());

        self::assertTrue($coll->isModified());
    }

    public function testRemoveAtWithNegativeOutOfBounds(): void
    {
        self::markTestSkipped();

        $sampleDate = new \DateTimeImmutable();
        $coll = new ArrayCollection([1, "foo", "bar", $sampleDate]);

        self::expectNotToPerformAssertions();
        self::assertNull($coll->removeAt(-1));
    }

    public function testRemoveAtWithPositiveOutOfBounds(): void
    {
        self::markTestSkipped();

        $sampleDate = new \DateTimeImmutable();
        $coll = new ArrayCollection([1, "foo", "bar", $sampleDate]);

        self::expectNotToPerformAssertions();
        self::assertNull($coll->removeAt(4));
    }

    public function testRemoveWithInt(): void
    {
        self::markTestSkipped();

        $sampleDate = new \DateTimeImmutable();
        $coll = new ArrayCollection([1, "foo", "bar", $sampleDate]);

        self::assertFalse($coll->isModified());

        self::assertSame(1, $coll->remove(2));
        self::assertSame([1, "foo", $sampleDate], \array_values(\iterator_to_array($coll)));
        self::assertSame(3, $coll->count());

        self::assertSame(1, $coll->remove(2));
        self::assertSame([1, "foo"], \array_values(\iterator_to_array($coll)));
        self::assertSame(2, $coll->count());

        self::assertTrue($coll->isModified());
    }

    public function testRemoveWithCallback(): void
    {
        $sampleDate = new \DateTimeImmutable();
        $coll = new ArrayCollection([1, "foo", "bar", $sampleDate]);

        self::assertFalse($coll->isModified());

        self::assertSame(2, $coll->remove(fn ($value) => \is_string($value)));
        self::assertSame([1, $sampleDate], \array_values(\iterator_to_array($coll)));
        self::assertSame(2, $coll->count());

        self::assertTrue($coll->isModified());
    }

    public function testRemoveWithCallbackNoMatch(): void
    {
        $sampleDate = new \DateTimeImmutable();
        $coll = new ArrayCollection([1, "foo", "bar", $sampleDate]);

        self::assertFalse($coll->isModified());

        self::assertSame(0, $coll->remove(fn () => false));
        self::assertSame([1, "foo", "bar", $sampleDate], \array_values(\iterator_to_array($coll)));
        self::assertSame(4, $coll->count());

        self::assertFalse($coll->isModified());
    }

    public function testRemoveWithValue(): void
    {
        $sampleDate = new \DateTimeImmutable();
        $coll = new ArrayCollection([1, "foo", "bar", $sampleDate]);

        self::assertFalse($coll->isModified());

        self::assertSame(1, $coll->remove("foo"));
        self::assertSame([1, "bar", $sampleDate], \array_values(\iterator_to_array($coll)));
        self::assertSame(3, $coll->count());

        self::assertSame(1, $coll->remove($sampleDate));
        self::assertSame([1, "bar"], \array_values(\iterator_to_array($coll)));
        self::assertSame(2, $coll->count());

        self::assertTrue($coll->isModified());
    }

    public function testRemoveWithValueNoMatch(): void
    {
        $sampleDate = new \DateTimeImmutable();
        $coll = new ArrayCollection([1, "foo", "bar", $sampleDate]);

        self::assertFalse($coll->isModified());

        self::assertSame(0, $coll->remove(new \DateTimeImmutable()));
        self::assertSame([1, "foo", "bar", $sampleDate], \array_values(\iterator_to_array($coll)));
        self::assertSame(4, $coll->count());

        self::assertFalse($coll->isModified());
    }
}
