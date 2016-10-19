<?php

namespace Bankiru\Api\Doctrine;

use Bankiru\Api\Doctrine\Mapping\ApiMetadata;
use Bankiru\Api\Doctrine\Mapping\EntityMetadata;
use Bankiru\Api\Doctrine\Proxy\ProxyFactory;
use Bankiru\Api\Doctrine\Utility\IdentifierFixer;
use Doctrine\Common\Persistence\ObjectRepository;

class EntityManager implements ApiEntityManager
{
    /** @var EntityMetadataFactory */
    private $metadataFactory;
    /** @var  Configuration */
    private $configuration;
    /** @var ObjectRepository[] */
    private $repositories = [];
    /** @var UnitOfWork */
    private $unitOfWork;
    /** @var ProxyFactory */
    private $proxyFactory;

    /**
     * EntityManager constructor.
     *
     * @param Configuration $configuration
     */
    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;

        $this->metadataFactory = $configuration->getMetadataFactory();
        $this->metadataFactory->setEntityManager($this);

        $this->unitOfWork   = new UnitOfWork($this);
        $this->proxyFactory = new ProxyFactory($this);
    }

    /** {@inheritdoc} */
    public function getConfiguration()
    {
        return $this->configuration;
    }


    /** {@inheritdoc} */
    public function find($className, $id)
    {
        $metadata = $this->getClassMetadata($className);
        $id = IdentifierFixer::fixScalarId($id, $metadata);

        /** @var EntityMetadata $metadata */
        if (false !== ($entity = $this->getUnitOfWork()->tryGetById($id, $metadata->rootEntityName))) {
            return $entity instanceof $metadata->name ? $entity : null;
        }

        return $this->getUnitOfWork()->getEntityPersister($className)->loadById($id);
    }

    /**
     * {@inheritdoc}
     * @return ApiMetadata
     */
    public function getClassMetadata($className)
    {
        return $this->getMetadataFactory()->getMetadataFor($className);
    }

    /** {@inheritdoc} */
    public function getMetadataFactory()
    {
        return $this->metadataFactory;
    }

    /** {@inheritdoc} */
    public function getUnitOfWork()
    {
        return $this->unitOfWork;
    }

    /** {@inheritdoc} */
    public function persist($object)
    {
        throw new \BadMethodCallException('Persisting object is not supported');
    }

    /** {@inheritdoc} */
    public function remove($object)
    {
        //Todo: support object deletion via API (@scaytrase)
        throw new \BadMethodCallException('Removing object is not supported');
    }

    /** {@inheritdoc} */
    public function merge($object)
    {
        throw new \BadMethodCallException('Merge is not supported');
    }

    /** {@inheritdoc} */
    public function clear($objectName = null)
    {
        throw new \BadMethodCallException('Clearing EM is not supported');
    }

    /** {@inheritdoc} */
    public function detach($object)
    {
        throw new \BadMethodCallException('Detach object is not supported');
    }

    /** {@inheritdoc} */
    public function refresh($object)
    {
        $this->getRepository(get_class($object))->find($object->getId());
    }

    /** {@inheritdoc} */
    public function getRepository($className)
    {
        if (!array_key_exists($className, $this->repositories)) {
            /** @var ApiMetadata $metadata */
            $metadata        = $this->getClassMetadata($className);
            $repositoryClass = $metadata->getRepositoryClass();
            /** @noinspection PhpInternalEntityUsedInspection */
            $this->repositories[$className] = new $repositoryClass($this, $className);
        }

        return $this->repositories[$className];
    }

    /** {@inheritdoc} */
    public function flush()
    {
        throw new \BadMethodCallException('Flush is not supported');
    }

    /** {@inheritdoc} */
    public function initializeObject($obj)
    {
        // Todo: generate proxy class here (@scaytrase)
    }

    /** {@inheritdoc} */
    public function contains($object)
    {
        return false;
    }

    /**
     * Gets a reference to the entity identified by the given type and identifier
     * without actually loading it, if the entity is not yet loaded.
     *
     * @param string $entityName The name of the entity type.
     * @param mixed  $id         The entity identifier.
     *
     * @return object The entity reference.
     */
    public function getReference($entityName, $id)
    {
        /** @var EntityMetadata $metadata */
        $metadata = $this->getClassMetadata($entityName);
        $id = IdentifierFixer::fixScalarId($id, $metadata);

        if (false !== ($entity = $this->getUnitOfWork()->tryGetById($id, $metadata->rootEntityName))) {
            return $entity instanceof $metadata->name ? $entity : null;
        }

        $proxy = $this->getProxyFactory()->getProxy($entityName, $id);
        $this->getUnitOfWork()->registerManaged($proxy, $id, null);

        return $proxy;
    }

    /** {@inheritdoc} */
    public function getProxyFactory()
    {
        return $this->proxyFactory;
    }
}