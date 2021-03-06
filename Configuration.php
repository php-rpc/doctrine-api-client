<?php

namespace Bankiru\Api\Doctrine;

use Bankiru\Api\Doctrine\Cache\CacheConfiguration;
use Bankiru\Api\Doctrine\Cache\CacheConfigurationInterface;
use Bankiru\Api\Doctrine\Type\TypeRegistryInterface;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Configuration
{
    /** @var  EntityMetadataFactory */
    private $metadataFactory;
    /** @var  MappingDriver */
    private $driver;
    /** @var  ClientRegistryInterface */
    private $clientRegistry;
    /** @var  ApiFactoryRegistryInterface */
    private $factoryRegistry;
    /** @var  string */
    private $proxyDir;
    /** @var  string */
    private $proxyNamespace;
    /** @var bool */
    private $autogenerateProxies = true;
    /** @var  TypeRegistryInterface */
    private $typeRegistry;
    /** @var  array */
    private $cacheConfiguration = [];
    /** @var  CacheItemPoolInterface */
    private $apiCache;
    /** @var  LoggerInterface */
    private $apiCacheLogger;
    /** @var  CacheConfigurationInterface[] */
    private $cacheConfigurationCache = [];

    /**
     * Configuration constructor.
     */
    public function __construct()
    {
        $this->apiCacheLogger = new NullLogger();
    }

    /**
     * @return ClientRegistryInterface
     */
    public function getClientRegistry()
    {
        return $this->clientRegistry;
    }

    /**
     * @param ClientRegistryInterface $clientRegistry
     */
    public function setClientRegistry($clientRegistry)
    {
        $this->clientRegistry = $clientRegistry;
    }

    /**
     * @return ApiFactoryRegistryInterface
     */
    public function getFactoryRegistry()
    {
        return $this->factoryRegistry;
    }

    /**
     * @param ApiFactoryRegistryInterface $factoryRegistry
     */
    public function setFactoryRegistry(ApiFactoryRegistryInterface $factoryRegistry)
    {
        $this->factoryRegistry = $factoryRegistry;
    }

    /**
     * @return LoggerInterface
     */
    public function getApiCacheLogger()
    {
        return $this->apiCacheLogger;
    }

    /**
     * @param LoggerInterface $apiCacheLogger
     */
    public function setApiCacheLogger(LoggerInterface $apiCacheLogger = null)
    {
        $this->apiCacheLogger = $apiCacheLogger ?: new NullLogger();
    }

    /**
     * @return CacheItemPoolInterface|null
     */
    public function getApiCache()
    {
        return $this->apiCache;
    }

    /**
     * @param CacheItemPoolInterface|null $apiCache
     */
    public function setApiCache(CacheItemPoolInterface $apiCache = null)
    {
        $this->apiCache = $apiCache;
    }

    /**
     * Returns class cache configuration.
     *
     * Checks for parent classes recursively
     *
     * @param $class
     *
     * @return CacheConfigurationInterface
     */
    public function getCacheConfiguration($class)
    {
        if ($this->getMetadataFactory()->isTransient($class)) {
            return CacheConfiguration::disabled();
        }

        if (array_key_exists($class, $this->cacheConfigurationCache)) {
            return $this->cacheConfigurationCache[$class];
        }

        if ($this->hasCacheConfigurationFor($class)) {
            return $this->cacheConfigurationCache[$class] =
                CacheConfiguration::create($this->cacheConfiguration[$class]);
        }

        $metadata = $this->getMetadataFactory()->getMetadataFor($class);
        $parent   = $metadata->getReflectionClass()->getParentClass();
        while ($parent) {
            if ($this->hasCacheConfigurationFor($parent->getName())) {
                return $this->cacheConfigurationCache[$class] = $this->getCacheConfiguration($parent->getName());
            }

            $parent = $parent->getParentClass();
        }

        return $this->cacheConfigurationCache[$class] = CacheConfiguration::disabled();
    }

    /**
     * @param string $class
     * @param array  $options
     */
    public function setCacheConfiguration($class, array $options = null)
    {
        $this->cacheConfiguration[$class] = $options;

        if (null === $this->cacheConfiguration[$class]) {
            unset($this->cacheConfiguration[$class]);
        }
    }

    public function hasCacheConfigurationFor($class)
    {
        return array_key_exists($class, $this->cacheConfiguration);
    }

    /**
     *
     *
     * @param string                      $class
     * @param CacheConfigurationInterface $configuration
     */
    public function setCacheConfigurationInstance($class, CacheConfigurationInterface $configuration)
    {
        $this->cacheConfigurationCache[$class] = $configuration;
    }

    /**
     * @return TypeRegistryInterface
     */
    public function getTypeRegistry()
    {
        return $this->typeRegistry;
    }

    /**
     * @param TypeRegistryInterface $typeRegistry
     */
    public function setTypeRegistry(TypeRegistryInterface $typeRegistry)
    {
        $this->typeRegistry = $typeRegistry;
    }

    /**
     * @return string
     */
    public function getProxyDir()
    {
        return $this->proxyDir;
    }

    /**
     * @param string $proxyDir
     */
    public function setProxyDir($proxyDir)
    {
        $this->proxyDir = $proxyDir;
    }

    /**
     * @return string
     */
    public function getProxyNamespace()
    {
        return $this->proxyNamespace;
    }

    /**
     * @param string $proxyNamespace
     */
    public function setProxyNamespace($proxyNamespace)
    {
        $this->proxyNamespace = $proxyNamespace;
    }

    /**
     * @return boolean
     */
    public function isAutogenerateProxies()
    {
        return $this->autogenerateProxies;
    }

    /**
     * @param boolean $autogenerateProxies
     */
    public function setAutogenerateProxies($autogenerateProxies)
    {
        $this->autogenerateProxies = $autogenerateProxies;
    }

    /**
     * @return MappingDriver
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * @param MappingDriver $driver
     */
    public function setDriver($driver)
    {
        $this->driver = $driver;
    }

    /**
     * @return EntityMetadataFactory
     */
    public function getMetadataFactory()
    {
        return $this->metadataFactory;
    }

    /**
     * @param string $metadataFactory
     */
    public function setMetadataFactory($metadataFactory)
    {
        $this->metadataFactory = $metadataFactory;
    }
}
