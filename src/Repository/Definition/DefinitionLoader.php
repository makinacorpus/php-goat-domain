<?php

declare(strict_types=1);

namespace Goat\Domain\Repository\Definition;

use Doctrine\Common\Annotations\AnnotationReader;
use Goat\Domain\Repository\Error\RepositoryDefinitionNotFoundError;

/**
 * Definition loader is not a runtime object, it is meant to work during static
 * compilation or configuration phase. This means that when working with it,
 * repository instance and database access are not available.
 */
class DefinitionLoader
{
    private ?AnnotationReader $annotationReader = null;

    public function __construct(?AnnotationReader $annotationReader = null)
    {
        if ($annotationReader) {
            $this->setAnnotationReader($annotationReader);
        } else if (\class_exists(AnnotationReader::class)) {
            // @todo Doctrine documents that this is required, but looking at
            //   the code, it seems it can really work without.
            // AnnotationRegistry::registerLoader('class_exists');
            $this->annotationReader = new AnnotationReader();
        }
    }

    /**
     * Set annotation reader for PHP < 8.0 compat.
     */
    public function setAnnotationReader(AnnotationReader $annotationReader): void
    {
        $this->annotationReader = $annotationReader;
    }

    /**
     * Load repository definition.
     *
     * @param string $repositoryClassName
     *   Repository class name.
     */
    public function loadDefinition(string $repositoryClassName): RepositoryDefinition
    {
        $refClass = new \ReflectionClass($repositoryClassName);
        $builder = new RepositoryDefinitionBuilder();
        $found = false;

        $targetList = [
            DatabaseColumn::class,
            DatabasePrimaryKey::class,
            DatabaseSelectColumn::class,
            DatabaseTable::class,
            EntityClassName::class,
        ];

        if (PHP_VERSION_ID >= 80000) {
            // Attributes are available, let's work with it.
            foreach ($targetList as $target) {
                foreach ($refClass->getAttributes($target) as $attribute) {
                    $found = true;
                    $this->append($builder, $attribute->newInstance());
                }
            }
        }

        if ($this->annotationReader) {
            foreach ($this->annotationReader->getClassAnnotations($refClass) as $object) {
                if (\in_array(\get_class($object), $targetList)) {
                    $found = true;
                    $this->append($builder, $object);
                }
            }
        } else if (PHP_VERSION_ID < 80000) {
            throw new \LogicException("You cannot use the DefinitionLoader without the doctrine/annotations AnnotationReader when using PHP < 8.0");
        }

        if (!$found) {
            throw new RepositoryDefinitionNotFoundError(\sprintf("Class %s does not carry any repository definition annotations or attributes.", $repositoryClassName));
        }

        return $builder->build();
    }

    /**
     * Append attribute data to builder.
     */
    private function append(RepositoryDefinitionBuilder $builder, object $object): void
    {
        if ($object instanceof DatabaseColumn) {
            $builder->addDatabaseColumns($object);
        } else if ($object instanceof DatabasePrimaryKey) {
            $builder->setDatabasePrimaryKey($object->getPrimaryKey());
        } else if ($object instanceof DatabaseSelectColumn) {
            $builder->addDatabaseSelectColumns($object);
        } else if ($object instanceof DatabaseTable) {
            $builder->setTableName($object);
        } else if ($object instanceof EntityClassName) {
            $builder->setEntityClassName($object->getClassName());
        } else {
            // Silently do nothing. Simplifies things loadDefinition().
        }
    }
}
