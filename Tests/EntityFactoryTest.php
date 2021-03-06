<?php

namespace Bankiru\Api\Doctrine\Tests;

use Bankiru\Api\Doctrine\Test\Entity\CompositeKeyEntity;
use Bankiru\Api\Doctrine\Test\Entity\Sub\SubEntity;
use Bankiru\Api\Doctrine\Test\Entity\TestEntity;
use ScayTrase\Api\Rpc\RpcRequestInterface;

final class EntityFactoryTest extends AbstractEntityManagerTest
{
    public function testEntityLoading()
    {
        $repository = $this->getManager()->getRepository(TestEntity::class);

        $this->getClient()->push(
            $this->getResponseMock(
                true,
                (object)[
                    'id'      => '1',
                    'payload' => 'test-payload',
                ]
            ),
            function(RpcRequestInterface $request) {
                self::assertEquals('test-entity/find', $request->getMethod());
                self::assertEquals(['id' => 1], $request->getParameters());

                return true;
            }
        );

        /** @var TestEntity $entity */
        $entity = $repository->find(1);

        self::assertInstanceOf(TestEntity::class, $entity);
        self::assertEquals(1, $entity->getId());
        self::assertInternalType('int', $entity->getId());
        self::assertEquals('test-payload', $entity->getPayload());
    }

    public function testInheritanceLoading()
    {
        $repository = $this->getManager()->getRepository(SubEntity::class);
        $this->getClient()->push(
            $this->getResponseMock(
                true,
                (object)[
                    'id'          => 2,
                    'payload'     => 'test-payload',
                    'sub-payload' => 'sub-payload',
                ]
            ),
            function(RpcRequestInterface $request) {
                self::assertEquals('test-entity/find', $request->getMethod());
                self::assertEquals(['id' => 2], $request->getParameters());

                return true;
            }
        );

        /** @var SubEntity $entity */
        $entity = $repository->find(2);

        self::assertInstanceOf(SubEntity::class, $entity);
        self::assertEquals(2, $entity->getId());
        self::assertEquals('test-payload', $entity->getPayload());
        self::assertEquals('sub-payload', $entity->getSubPayload());
    }

    public function testCompositeKeyLoading()
    {
        $repository = $this->getManager()->getRepository(CompositeKeyEntity::class);
        $this->getClient()->push(
            $this->getResponseMock(
                true,
                (object)[
                    'first_key'  => 2,
                    'second_key' => 'test',
                    'payload'    => 'test-payload',
                ]
            ),
            function(RpcRequestInterface $request) {
                self::assertEquals('composite-key-entity/find', $request->getMethod());
                self::assertEquals(['first_key' => 2, 'second_key' => 'test'], $request->getParameters());

                return true;
            }
        );

        /** @var CompositeKeyEntity $entity */
        $entity = $repository->find(['firstKey' => '2', 'secondKey' => 'test']);

        self::assertInstanceOf(CompositeKeyEntity::class, $entity);
        self::assertEquals(2, $entity->getFirstKey());
        self::assertEquals('test', $entity->getSecondKey());
        self::assertEquals('test-payload', $entity->getPayload());
    }

    protected function getClientNames()
    {
        return array_merge(parent::getClientNames(), ['test-reference-client']);
    }
}
