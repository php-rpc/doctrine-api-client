<?php

namespace Bankiru\Api\Doctrine\Tests;

use Bankiru\Api\Doctrine\Test\Entity\IndirectIdEntity;
use GuzzleHttp\Psr7\Response;

class IndirectFieldsTestAbstract extends AbstractEntityManagerTest
{
    public function testIndirectId()
    {
        $repository = $this->getManager()->getRepository(IndirectIdEntity::class);
        $this->getResponseMock()->append(
            new Response(
                200,
                [],
                json_encode(
                    [
                        'jsonrpc' => '2.0',
                        'id'      => 'test',
                        'result'  => [
                            'some-long-api-field-name' => 241,
                            'payload'                  => 'test',
                        ],
                    ]
                )
            )
        );

        /** @var IndirectIdEntity $entity */
        $entity = $repository->find(241);

        self::assertInstanceOf(IndirectIdEntity::class, $entity);
        self::assertEquals(241, $entity->getId());
        self::assertEquals('test', $entity->getPayload());
    }
}