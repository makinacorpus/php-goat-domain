<?php

declare(strict_types=1);

namespace Goat\Domain\Repository\Hydration;

use Goat\Domain\Repository\DefaultLazyProperty;
use Goat\Domain\Repository\LazyProperty;
use Goat\Domain\Repository\RepositoryInterface;
use Goat\Domain\Repository\Collection\ArrayCollection;
use Goat\Domain\Repository\Collection\Collection;
use Goat\Domain\Repository\Repository\AbstractRepository;
use Goat\Runner\Hydrator\HydratorRegistry;

/**
 * Hydrator service for this whole API.
 */
class RepositoryHydrator
{
    private HydratorRegistry $hydratorRegistry;

    public function __construct(HydratorRegistry $hydratorRegistry)
    {
        $this->hydratorRegistry = $hydratorRegistry;
    }

    /**
     * Create raw values hydrator.
     */
    public function createHydrator(RepositoryInterface $repository, /* callable */ $normalizer = null): callable
    {
        $definition = $repository->getRepositoryDefinition();
        $hydrator = $this->hydratorRegistry->getHydrator($definition->getEntityClassName());

        // Validation first.
        if (!$definition->hasDatabasePrimaryKey()) {
            throw new \InvalidArgumentException("Default hydration process requires that the repository defines a primary key.");
        }
        $primaryKey = $definition->getDatabasePrimaryKey();

        // Backward compatility layer in the next line.
        if ($normalizer) {
            $normalizer = $this->userDefinedNormalizerNormalize($normalizer);
        }

        // Build lazy property hydrators. Both createLazyCollection() and
        // createLazyProperty() methods will normalizer the user given callbacks
        // to a callback using the ResultRow class as argument, for easier key
        // extraction from values.
        $lazyProperties = [];

        // Ugly hack until we do best.
        if ($repository instanceof AbstractRepository) {
            $collectionMapping = (\Closure::bind(
                fn () => $this->defineLazyCollectionMapping(),
                $repository,
                AbstractRepository::class
            ))();

            if ($collectionMapping) {
                foreach ($collectionMapping as $propertyName => $initializer) {
                    $lazyProperties[$propertyName] = $this->createLazyCollection($repository, $initializer);
                }
            }

            $propertyMapping = (\Closure::bind(
                fn () => $this->defineLazyPropertyMapping(),
                $repository,
                AbstractRepository::class
            ))();

            if ($propertyMapping) {
                foreach ($propertyMapping as $propertyName => $initializer) {
                    $lazyProperties[$propertyName] = $this->createLazyProperty($repository, $initializer);
                }
            }
        }

        if ($lazyProperties) {
            if ($normalizer) {
                return function (array $values) use ($lazyProperties, $primaryKey, $hydrator, $normalizer) {
                    $values = new ResultRow($primaryKey, $values);
                    $normalizer($values);
                    foreach ($lazyProperties as $propertyName => $callback) {
                        $values->set($propertyName, $callback($values));
                    }
                    return $hydrator($values->toArray());
                };
            }

            return function (array $values) use ($lazyProperties, $primaryKey, $hydrator, $normalizer) {
                $values = new ResultRow($primaryKey, $values);
                foreach ($lazyProperties as $propertyName => $callback) {
                    $values->set($propertyName, $callback($values));
                }
                return $hydrator($values->toArray());
            };
        }

        if ($normalizer) {
            return static function (array $values) use ($normalizer, $hydrator, $primaryKey) {
                $values = new ResultRow($primaryKey, $values);
                $normalizer($values);
                return $hydrator($values->toArray());
            };
        }

        return $hydrator;
    }

    /**
     * Create a lazy collection wrapper for method.
     */
    private function createLazyCollection(RepositoryInterface $repository, $callback): callable
    {
        if (\is_callable($callback)) {
            $callback = \Closure::fromCallable($callback);
        } else if (\method_exists($repository, $callback)) {
            $callback = \Closure::fromCallable([$repository, $callback]);
        } else {
            throw new \InvalidArgumentException("Lazy collection initializer must be a callable or a repository method name.");
        }

        $callbackReturnsIterable = false;

        // Check for callback return type.
        // @todo Later, we should not check for this and just return
        //    the normalized initializer.
        $refFunc = new \ReflectionFunction($callback);
        if ($refFunc->hasReturnType()) {
            $refType = $refFunc->getReturnType();
            if ($refType instanceof \ReflectionNamedType) {
                $refTypeName = $refType->getName();

                try {
                    $refClass = new \ReflectionClass($refType->getName());
                    if ($refClass->name === Collection::class ||
                        $refClass->implementsInterface(Collection::class) ||
                        $refClass->name === \Traversable ||
                        $refClass->implementsInterface(\Traversable::class)
                    ) {
                        $callbackReturnsIterable = true;
                    }
                } catch (\ReflectionException $e) {
                    if ('array' === $refTypeName || 'iterable' === $refTypeName) {
                        $callbackReturnsIterable = true;
                    }
                }
            }
        }

        $callback = $this->userDefinedLazyHydratorNormalize($callback);

        if ($callbackReturnsIterable) {
            return $callback;
        }

        return fn (ResultRow $row) => new ArrayCollection(fn () => $callback($row));
    }

    /**
     * Create a lazy property wrapper for method.
     */
    private function createLazyProperty(RepositoryInterface $repository, $callback): callable
    {
        if (\is_callable($callback)) {
            $callback = \Closure::fromCallable($callback);
        } else if (\method_exists($repository, $callback)) {
            $callback = \Closure::fromCallable([$repository, $callback]);
        } else {
            throw new \InvalidArgumentException("Lazy collection initializer must be a callable or a repository method name.");
        }

        $callbackReturnsLazy = false;
        $refFunc = new \ReflectionFunction($callback);
        if ($refFunc->hasReturnType() && ($refType = $refFunc->getReturnType()) && $refType instanceof \ReflectionNamedType) {
            try {
                $refClass = new \ReflectionClass($refType->getName());
                if ($refClass->name === LazyProperty::class || $refClass->implementsInterface(LazyProperty::class)) {
                    $callbackReturnsLazy = true;
                }
            } catch (\ReflectionException $e) {
                // Can not determine return type.
            }
        }

        if ($callbackReturnsLazy) {
            return $this->userDefinedLazyHydratorNormalize($callback);
        }

        $callback = $this->userDefinedLazyHydratorNormalize($callback);

        // @todo Later here, lazy property may be a ghost object instead.
        return fn (ResultRow $row) => new DefaultLazyProperty(fn () => $callback($row));
    }

    /**
     * From a callback that is supposed to use a ResultRow instance as only
     * parameter, wrap it if necessary to allow usage of a bare array instead
     * for backward compatibility.
     */
    private function userDefinedNormalizerNormalize(callable $callback): callable
    {
        if (!$callback instanceof \Closure) {
            $callback = \Closure::fromCallable($callback);
        }

        $refFunc = new \ReflectionFunction($callback);

        $normParamCount = 0;
        foreach ($refFunc->getParameters() as $parameter) {
            \assert($parameter instanceof \ReflectionParameter);

            if ($normParamCount) {
                throw new \InvalidArgumentException("Normalizer callback can have only one parameter.");
            }
            $normParamCount++;

            // Check parameter type.
            if (!$parameter->hasType() || !($refType = $parameter->getType()) instanceof \ReflectionNamedType || ResultRow::class !== $refType->getName()) {
                @\trigger_error(\sprintf("Using result row as array is deprecated, please type and use your normalizer parameter with the %s interface.", __CLASS__), E_USER_DEPRECATED);

                return fn (ResultRow $row) => $row->apply($callback);
            }
        }

        if (!$normParamCount) {
            throw new \InvalidArgumentException("Normalizer callback from repository has no parameters.");
        }

        return $callback;
    }

    /**
     * Attempt to normalize user given lazy property hydrator.
     *
     * Legacy code can return many things.
     *
     * First, if the user returns a LazyProperty object, we will wrap the user
     * callback to get the primary key value as first parameter, which is the
     * legacy behaviour, then emit a deprecation notice.
     *
     * Then, we check if the callback expect a ResultRow parameter, case in
     * which we do not modify it and return it as-is. In the opposite case,
     * we will consider the callback expects the primary key value as well,
     * and wrap the callback to use it.
     */
    private function userDefinedLazyHydratorNormalize(callable $callback): callable
    {
        if (!$callback instanceof \Closure) {
            $callback = \Closure::fromCallable($callback);
        }

        $refFunc = new \ReflectionFunction($callback);
        $refParams = $refFunc->getParameters();
        $refParamsCount = \count($refParams);
        $refTypeName = null;

        if (0 === $refParamsCount) {
            return $callback;
        }
        if (1 !== $refParamsCount) {
            throw new \InvalidArgumentException("User-defined lazy hydrators callback can have only one parameter.");
        }

        $parameter = $refParams[0];
        \assert($parameter instanceof \ReflectionParameter);

        if ($parameter->hasType()) {
            $refType = $parameter->getType();
            if (!$refType instanceof \ReflectionNamedType) {
                throw new \InvalidArgumentException("User-defined lazy hydrators callback first parameter must be a single named type.");
            }
            $refTypeName = $refType->getName();
        }

        if (ResultRow::class === $refTypeName) {
            // Yes, user is up-to-date, return its method directly.
            return $callback;
        }

        @\trigger_error(\sprintf("User-defined lazy hydrators callback should use a %s instance as first parameter.", ResultRow::class), E_USER_DEPRECATED);

        if ('array' === $refTypeName) {
            // User defined hydrator expects an array, consider it
            // wants the primary key value.
            return static fn (ResultRow $row) => $callback($row->extractPrimaryKey()->all());
        }
        if (null !== $refTypeName) {
            // We cannot pass an array here, take the first value instead.
            // We are not sure the first primary key value has the actual
            // expected type, but at least we won't give an array for it.
            return static fn (ResultRow $row) => $callback($row->extractPrimaryKey()->first());
        }

        // Extract primary key value in a backward-compatible way.
        // This means that if the primary key value has only value
        // we extract it and give it as-is.
        return static function (ResultRow $row) use ($callback) {
            $primaryKeyValue = $row->extractPrimaryKey();
            if (1 === \count($primaryKeyValue)) {
                return $callback($primaryKeyValue->first());
            }
            return $callback($primaryKeyValue->all());
        };
    }
    
}
