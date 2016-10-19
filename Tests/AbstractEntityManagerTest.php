<?php

namespace Bankiru\Api\Doctrine\Tests;

use Bankiru\Api\Doctrine\ApiEntityManager;
use Bankiru\Api\Doctrine\ClientRegistry;
use Bankiru\Api\Doctrine\ClientRegistryInterface;
use Bankiru\Api\Doctrine\Configuration;
use Bankiru\Api\Doctrine\ConstructorFactoryResolver;
use Bankiru\Api\Doctrine\EntityManager;
use Bankiru\Api\Doctrine\EntityMetadataFactory;
use Bankiru\Api\Doctrine\Mapping\Driver\YmlMetadataDriver;
use Bankiru\Api\Doctrine\Test\TestClient;
use Bankiru\Api\Doctrine\Type\BaseTypeRegistry;
use Bankiru\Api\Doctrine\Type\TypeRegistry;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\Common\Persistence\Mapping\Driver\SymfonyFileLocator;
use GuzzleHttp\Handler\MockHandler;
use ScayTrase\Api\IdGenerator\IdGeneratorInterface;

abstract class AbstractEntityManagerTest extends \PHPUnit_Framework_TestCase
{
    const DEFAULT_CLIENT = 'test-client';
    /** @var  ClientRegistryInterface */
    private $registry;
    /** @var  ApiEntityManager */
    private $manager;
    /** @var  MockHandler[] */
    private $mocks = [];

    /**
     * @return mixed
     */
    public function getRegistry()
    {
        return $this->registry;
    }

    /**
     * @return ApiEntityManager
     */
    protected function getManager()
    {
        return $this->manager;
    }

    protected function setUp()
    {
        $this->createEntityManager($this->getClientNames());
        parent::setUp();
    }

    protected function createEntityManager($clients = [self::DEFAULT_CLIENT])
    {
        /** @var IdGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject $idGenerator */
        $idGenerator = $this->getMock(IdGeneratorInterface::class);
        $idGenerator->method('getRequestIdentifier')->willReturn('test');

        $this->registry = new ClientRegistry();
        foreach ($clients as $name) {
            $this->registry->add($name, new TestClient($this->getResponseMock($name), $idGenerator));
        }

        $configuration = $this->createConfiguration();

        $this->manager = new EntityManager($configuration);
    }

    /**
     * @param string $name
     *
     * @return MockHandler
     */
    protected function getResponseMock($name = self::DEFAULT_CLIENT)
    {
        if (!array_key_exists($name, $this->mocks)) {
            $this->mocks[$name] = new MockHandler();
        }

        return $this->mocks[$name];
    }

    protected function getClientNames()
    {
        return [self::DEFAULT_CLIENT];
    }

    protected function tearDown()
    {
        foreach ($this->mocks as $mock) {
            self::assertCount(0, $mock);
        }

        $this->manager = null;
        $this->mocks   = [];
        parent::tearDown(); // TODO: Change the autogenerated stub
    }

    /**
     * @return Configuration
     */
    protected function createConfiguration()
    {
        $configuration = new Configuration();
        $configuration->setMetadataFactory(new EntityMetadataFactory());
        $configuration->setRegistry($this->registry);
        $configuration->setTypeRegistry(new BaseTypeRegistry(new TypeRegistry()));
        $configuration->setResolver(new ConstructorFactoryResolver());
        $configuration->setProxyDir(CACHE_DIR . '/doctrine/proxy/');
        $configuration->setProxyNamespace('Bankiru\Api\Doctrine\Test\Proxy');
        $driver = new MappingDriverChain();
        $driver->addDriver(
            new YmlMetadataDriver(
                new SymfonyFileLocator(
                    [
                        __DIR__ . '/../Test/Resources/config/api/' => 'Bankiru\Api\Doctrine\Test\Entity',
                    ],
                    '.api.yml',
                    DIRECTORY_SEPARATOR
                )
            ),
            'Bankiru\Api\Doctrine\Test\Entity'
        );
        $configuration->setDriver($driver);

        return $configuration;
    }
}