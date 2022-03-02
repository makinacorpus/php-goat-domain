<?php

declare(strict_types=1);

namespace Goat\Domain\Repository;

/**
 * @codeCoverageIgnore
 */
final class LazyCollectionHydrator
{
    private $nested;
    private $primaryKey;
    private $properties;

    /**
     * Default constructor
     */
    public function __construct(callable $nested, array $properties, array $primaryKey)
    {
        if (!$primaryKey) {
            throw new \InvalidArgumentException("We cannot call lazy collection hydrators without a primary key");
        }

        $this->nested = $nested;
        $this->primaryKey = $primaryKey;
        $this->properties = $properties;
    }

    /**
     * Can't reduce primary key, missing column
     */
    private function missingPrimaryKey(string $key): void
    {
        throw new \InvalidArgumentException(\sprintf("Can't reduce primary key from values, column value '%s' is missing", $key));
    }

    /**
     * Reduce primary key from values
     */
    private function reducePrimaryKey(array $values)
    {
        $primaryKey = [];

        foreach ($this->primaryKey as $key) {
            $primaryKey[$key] = $values[$key] ?? $this->missingPrimaryKey($key);
        }

        // As per defineLazyCollectionMapping() definition, if primary key as a
        // single column, reduce the primary key to the value instead of giving
        // an array to the lazy initializer callable.
        return 1 === \count($primaryKey) ? reset($primaryKey) : $primaryKey;
    }

    /**
     * Should the callable be wrapped using a lazy collection.
     *
     * If the method return type is itself a lazy collection implementation,
     * we don't need to wrap a lazy collection into a lazy collection. Note
     * that this can only work if the return type is defined.
     */
    private function callableNeedsWrapper(callable $callable): bool
    {
        if ($callable instanceof \Closure) {
            $ref = new \ReflectionFunction($callable);
            $returnType = null;
            if ($refType= $ref->getReturnType()) {
                $returnType = $refType->getName();
            }

            if ($returnType === LazyProperty::class || $returnType === LazyCollection::class) {
                return false;
            }
            if ($returnType) {
                if (\class_exists($returnType) || \interface_exists($returnType)) {
                    $ref = new \ReflectionClass($returnType);

                    return !$ref->implementsInterface(LazyProperty::class);
                }
            }
        }

        return true;
    }

    /**
     * Incoporate lazy collections into result
     */
    private function expand(array $values): array
    {
        $primaryKey = $this->reducePrimaryKey($values);

        foreach ($this->properties as $key => $callback) {
            // Do not overwrite fetched results.
            // @todo should we raise an error here?
            if (!\array_key_exists($key, $values)) {

                // In theory, this should be checked prior to getting in here.
                // @todo this should be done prior to iteration.
                if ($callback instanceof LazyProperty) {
                    $value = $callback;
                } else if ($this->callableNeedsWrapper($callback)) {
                    if (!\is_callable($callback)) {
                        throw new \InvalidArgumentException(\sprintf(
                            "Lazy collection initializer for property '%s' must be a callable, '%s' given",
                            $key, \gettype($callback)
                        ));
                    }
                    $value = new DefaultLazyCollection(static function () use ($callback, $primaryKey): iterable {
                        return \call_user_func($callback, $primaryKey);
                    });
                } else {
                    $value = \call_user_func($callback, $primaryKey);
                }
                $values[$key] = $value;
            }
        }

        return $values;
    }

    /**
     * Create and hydrate object.
     */
    public function __invoke(array $values)
    {
        return \call_user_func($this->nested, $this->expand($values));
    }
}
