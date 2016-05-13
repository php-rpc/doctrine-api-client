<?php
/**
 * Created by PhpStorm.
 * User: batanov.pavel
 * Date: 29.12.2015
 * Time: 15:14
 */

namespace Bankiru\Api\Doctrine;

use Bankiru\Api\Doctrine\Mapping\EntityMetadata;
use Doctrine\Common\Persistence\Mapping\AbstractClassMetadataFactory;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\Common\Persistence\Mapping\ReflectionService;
use ReflectionException;

class EntityMetadataFactory extends AbstractClassMetadataFactory
{
    /** @var  EntityManager */
    private $manager;
    /** @var  MappingDriver */
    private $driver;

    /** @var string[] */
    private $aliases = [];

    public function registerAlias($namespaceAlias, $namespace)
    {
        if (array_key_exists($namespaceAlias, $this->aliases)) {
            throw new \LogicException(sprintf('Alias "%s" is already registered', $namespaceAlias));
        }

        $this->aliases[$namespaceAlias] = $namespace;
    }

    /**
     * @param EntityManager $manager
     */
    public function setEntityManager($manager)
    {
        $this->manager = $manager;
    }


    /**
     * Lazy initialization of this stuff, especially the metadata driver,
     * since these are not needed at all when a metadata cache is active.
     *
     * @return void
     */
    protected function initialize()
    {
        $this->driver      = $this->manager->getConfiguration()->getDriver();
        $this->initialized = true;
    }

    /**
     * Gets the fully qualified class-name from the namespace alias.
     *
     * @param string $namespaceAlias
     * @param string $simpleClassName
     *
     * @return string
     */
    protected function getFqcnFromAlias($namespaceAlias, $simpleClassName)
    {
        //todo: expand records like 'Geo:Region'
    }

    /**
     * Wakes up reflection after ClassMetadata gets unserialized from cache.
     *
     * @param ClassMetadata     $class
     * @param ReflectionService $reflService
     *
     * @return void
     */
    protected function wakeupReflection(ClassMetadata $class, ReflectionService $reflService)
    {
        if (!($class instanceof EntityMetadata)) {
            throw new \LogicException('Metadata is not supported');
        }

        /** @var EntityMetadata $class */
        $class->wakeupReflection($reflService);
    }

    /**
     * Initializes Reflection after ClassMetadata was constructed.
     *
     * @param ClassMetadata     $class
     * @param ReflectionService $reflService
     *
     * @return void
     */
    protected function initializeReflection(ClassMetadata $class, ReflectionService $reflService)
    {
        if (!($class instanceof EntityMetadata)) {
            throw new \LogicException('Metadata is not supported');
        }

        /** @var EntityMetadata $class */
        $class->initializeReflection($reflService);
    }

    /**
     * Checks whether the class metadata is an entity.
     *
     * This method should return false for mapped superclasses or embedded classes.
     *
     * @param ClassMetadata $class
     *
     * @return boolean
     */
    protected function isEntity(ClassMetadata $class)
    {
        return true;
    }

    /**
     * Actually loads the metadata from the underlying metadata.
     *
     * @param EntityMetadata      $class
     * @param EntityMetadata|null $parent
     * @param bool                $rootEntityFound
     * @param array               $nonSuperclassParents    All parent class names
     *                                                     that are not marked as mapped superclasses.
     *
     * @return void
     * @throws MappingException
     */
    protected function doLoadMetadata($class, $parent, $rootEntityFound, array $nonSuperclassParents)
    {
        /* @var $class EntityMetadata */
        /* @var $parent EntityMetadata */
        if ($parent) {
            $this->addInheritedFields($class, $parent);
            $this->addInheritedRelations($class, $parent);
            $class->setIdentifier($parent->identifier);
            $class->clientName     = $parent->clientName;
            $class->searcher       = $parent->searcher;
            $class->methodProvider = $parent->methodProvider;

            if ($parent->isMappedSuperclass) {
                $class->setCustomRepositoryClass($parent->repositoryClass);
            }
        }

        // Invoke driver
        try {
            $this->getDriver()->loadMetadataForClass($class->getName(), $class);
        } catch (ReflectionException $e) {
            throw MappingException::nonExistingClass($class->getName());
        }
    }

    /**
     * Adds inherited fields to the subclass mapping.
     *
     * @param EntityMetadata $subClass
     * @param EntityMetadata $parentClass
     *
     * @return void
     */
    private function addInheritedFields(EntityMetadata $subClass, EntityMetadata $parentClass)
    {
        foreach ($parentClass->fields as $mapping) {
            if (!isset($mapping['inherited']) && !$parentClass->isMappedSuperclass) {
                $mapping['inherited'] = $parentClass->name;
            }
            if (!isset($mapping['declared'])) {
                $mapping['declared'] = $parentClass->name;
            }
            $subClass->addInheritedFieldMapping($mapping);
        }
        foreach ($parentClass->reflFields as $name => $field) {
            $subClass->reflFields[$name] = $field;
        }
    }

    /**
     * Adds inherited association mappings to the subclass mapping.
     *
     * @param EntityMetadata $subClass
     * @param EntityMetadata $parentClass
     *
     * @return void
     *
     * @throws MappingException
     */
    private function addInheritedRelations(EntityMetadata $subClass, EntityMetadata $parentClass)
    {
        //Todo:
//        foreach ($parentClass->associations as $field => $mapping) {
//            if ($parentClass->isMappedSuperclass) {
//                if ($mapping['type'] & ClassMetadata::TO_MANY && !$mapping['isOwningSide']) {
//                    throw MappingException::illegalToManyAssociationOnMappedSuperclass($parentClass->name, $field);
//                }
//                $mapping['sourceEntity'] = $subClass->name;
//            }
//
//            //$subclassMapping = $mapping;
//            if ( ! isset($mapping['inherited']) && ! $parentClass->isMappedSuperclass) {
//                $mapping['inherited'] = $parentClass->name;
//            }
//            if ( ! isset($mapping['declared'])) {
//                $mapping['declared'] = $parentClass->name;
//            }
//            $subClass->addInheritedAssociationMapping($mapping);
//        }
    }

    /**
     * Returns the mapping driver implementation.
     *
     * @return \Doctrine\Common\Persistence\Mapping\Driver\MappingDriver
     */
    protected function getDriver()
    {
        return $this->driver;
    }

    /**
     * Creates a new ClassMetadata instance for the given class name.
     *
     * @param string $className
     *
     * @return ClassMetadata
     */
    protected function newClassMetadataInstance($className)
    {
        return new EntityMetadata($className);
    }
}