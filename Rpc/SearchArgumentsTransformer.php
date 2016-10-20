<?php

namespace Bankiru\Api\Doctrine\Rpc;

use Bankiru\Api\Doctrine\ApiEntityManager;
use Bankiru\Api\Doctrine\Mapping\ApiMetadata;
use Bankiru\Api\Doctrine\Mapping\EntityMetadata;
use Bankiru\Api\Doctrine\Proxy\ApiCollection;
use Doctrine\Common\Collections\Collection;

/** @internal */
final class SearchArgumentsTransformer
{
    /** @var  ApiMetadata */
    private $metadata;
    /** @var  ApiEntityManager */
    private $manager;

    /**
     * SearchArgumentsTransformer constructor.
     *
     * @param ApiMetadata      $metadata
     * @param ApiEntityManager $manager
     */
    public function __construct(ApiMetadata $metadata, ApiEntityManager $manager)
    {
        $this->metadata = $metadata;
        $this->manager  = $manager;
    }

    /**
     * Converts doctrine entity criteria to API-ready criteria (converts types and field names)
     *
     * @param array $criteria
     *
     * @return array API-ready criteria
     */
    public function transformCriteria(array $criteria)
    {
        $apiCriteria = [];
        foreach ($criteria as $field => $values) {
            if ($this->metadata->hasAssociation($field)) {
                $mapping = $this->metadata->getAssociationMapping($field);
                /** @var EntityMetadata $target */
                $target = $this->manager->getClassMetadata($mapping['target']);

                $converter = function ($value) use ($target) {
                    if (!is_object($value)) {
                        return $value;
                    }

                    $ids = $target->getIdentifierValues($value);
                    if ($target->isIdentifierComposite) {
                        return $ids;
                    }

                    return array_shift($ids);
                };

                if ($values instanceof Collection) {
                    if ($values instanceof ApiCollection && !$values->isInitialized()) {
                        continue;
                    }
                    $values = $values->toArray();
                }
                
                if (is_array($values)) {
                    $values = array_map($converter, $values);
                } else {
                    $values = $converter($values);
                }
            } else {
                $caster = function ($value) use ($field) {
                    $type = $this->manager
                        ->getConfiguration()
                        ->getTypeRegistry()->get($this->metadata->getTypeOfField($field));

                    return $type->toApiValue($value);
                };

                if (is_array($values)) {
                    $values = array_map($caster, $values);
                } else {
                    $values = $caster($values);
                }
            }

            $apiCriteria[$this->metadata->getApiFieldName($field)] = $values;
        }

        return $apiCriteria;
    }

    /**
     * Converts doctrine entity order to API-ready order (converts field names)
     *
     * @param array $orderBy
     *
     * @return array API-ready order
     */
    public function transformOrder(array $orderBy = null)
    {
        $apiOrder = [];
        foreach ((array)$orderBy as $field => $direction) {
            $apiOrder[$this->metadata->getApiFieldName($field)] = $direction;
        }

        return $apiOrder;
    }
}
