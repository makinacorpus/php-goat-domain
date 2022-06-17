<?php

declare(strict_types=1);

namespace Goat\Domain\Repository\Repository;

use Goat\Domain\Repository\RepositoryInterface;
use Goat\Domain\Repository\Definition\DefinitionLoader;
use Goat\Domain\Repository\Definition\RepositoryDefinition;
use Goat\Domain\Repository\Definition\RepositoryDefinitionBuilder;
use Goat\Domain\Repository\Hydration\RepositoryHydratorAware;
use Goat\Domain\Repository\Hydration\RepositoryHydratorAwareTrait;
use Goat\Domain\Repository\Registry\RepositoryRegistryAware;
use Goat\Domain\Repository\Registry\RepositoryRegistryAwareTrait;
use Goat\Query\ExpressionRelation;

/**
 * Repository base class that brings most of the non-database related
 * boilerplate that you would have to write otherwise.
 */
abstract class AbstractRepository implements RepositoryInterface, RepositoryHydratorAware, RepositoryRegistryAware
{
    use RepositoryHydratorAwareTrait, RepositoryRegistryAwareTrait;

    private ?RepositoryDefinition $repositoryDefinition = null;

    /**
     * Get this repository definition.
     */
    public function getRepositoryDefinition(): RepositoryDefinition
    {
        return $this->repositoryDefinition ?? ($this->repositoryDefinition = $this->buildDefinition());
    }

    /**
     * Inject preloaded definition for production runtime.
     */
    public function setRepositoryDefinition(RepositoryDefinition $repositoryDefinition): void
    {
        if ($this->repositoryDefinition) {
            throw new \LogicException("Repository definition was already set before initialization.");
        }
        $this->repositoryDefinition = $repositoryDefinition;
    }

    /**
     * {@inheritdoc}
     *
     * @deprecated
     *   Please use getRepositoryDefinition() instead.
     */
    public final function getClassName(): string
    {
        // @\trigger_error(\sprintf("You should use %s::getRepositoryDefinition() instead.", static::class), E_USER_DEPRECATED);

        return $this->getRepositoryDefinition()->getEntityClassName();
    }

    /**
     * Get table for queries.
     */
    public function getTable(): ExpressionRelation
    {
        $table = $this->getRepositoryDefinition()->getTableName();

        return ExpressionRelation::create($table->getName(), $table->getAlias(), $table->getSchema());
    }

    /**
     * Define repository using a custom builder.
     */
    protected function define(RepositoryDefinitionBuilder $builder): void
    {
    }

    /**
     * Map references collections on the hydrated objects.
     *
     * This allows reference collections lazy-loading.
     *
     * @return string[]|callable[]
     *   Values are either string values, which must be existring public
     *   method names on this repository instance, or callable instances.
     *   Callables should take a single ResultRow argument, which is a
     *   container of being hydrated values.
     *   Keys are property names on which the callback result will be
     *   set.
     */
    protected function defineLazyCollectionMapping(): array
    {
        return [];
    }

    /**
     * Allow the repository to map method results on hydrated object properties.
     *
     * This is the very same that defineLazyCollectionMapping() except it will
     * return LazyProperty instances instead.
     *
     * Read defineLazyCollectionMapping() for documentation.
     *
     * Only difference is that the domain object that will inherit from this
     * property will need to call LazyProperty::unwrap() programmatically to
     * fetch the result.
     *
     * @return string|]|callable[]
     */
    protected function defineLazyPropertyMapping(): array
    {
        return [];
    }

    /**
     * Runtime build definition when no definition was injected.
     */
    protected function buildDefinition(): RepositoryDefinition
    {
        $builder = new RepositoryDefinitionBuilder();
        $this->define($builder);

        if (!$builder->isEmpty()) {
            return $builder->build();
        }

        // Otherwise, this should not happen on a production environment but
        // we are going to spawn a definition loader and do everything that
        // otherwise a cache warmup pass would do.
        return (new DefinitionLoader())->loadDefinition(static::class);
    }

    /**
     * Get raw SQL values normalizer.
     *
     * This gives you the chance to manually normalise or expand database
     * values from the resulting row prior to hydration process.
     *
     * @return callable
     *   Callback will receive only one argument, which is supposed to be
     *   typed using the ResultRow class.
     *   If the callback does not type the argument or uses the array type
     *   then a backward compatibility layer will provide an array instead,
     *   but this is unsupported and will be removed later.
     */
    public function getHydratorNormalizer(): callable
    {
        return static fn ($values) => $values;
    }

    /**
     * Create raw values hydrator.
     */
    public function getHydrator(): callable
    {
        return $this
            ->getRepositoryHydrator()
            ->createHydrator(
                $this->getRepositoryDefinition(),
                $this->getHydratorNormalizer()
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function createInstance(array $values)
    {
        // @todo Should this use lazy properties or not?
        return ($this->getHydrator())($values);
    }

    /**
     * @param string $targetEntity
     *   Target repository name.
     * @param string|string[]|Key $sourceKey
     *   Source table key.
     *
    protected function referenceAnyToOne(string $targetEntity, $sourceKey): callable
    {
        if (!$sourceKey instanceof Key) {
            $sourceKey = new Key($sourceKey);
        }

        $targetRepository = $this->getRepositoryRegistry()->getRepository($targetEntity);

        return fn (ResultRow $row) => $targetRepository->findFirst($criteria);
    }
     */
}
