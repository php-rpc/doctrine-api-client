<?php

namespace Bankiru\Api\Doctrine\Mapping;

use Bankiru\Api\Doctrine\Exception\MappingException;
use Bankiru\Api\Doctrine\Rpc\Method\MethodProviderInterface;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\ReflectionService;

interface ApiMetadata extends ClassMetadata
{
    /** Identifies a one-to-many association. */
    const ONE_TO_MANY = 4;
    /** Identifies a many-to-many association. */
    const MANY_TO_MANY = 8;
    /** Identifies a one-to-one association. */
    const ONE_TO_ONE = 1;
    /** Combined bitmask for to-many (collection-valued) associations. */
    const TO_MANY = 12;
    /** Identifies a many-to-one association. */
    const MANY_TO_ONE = 2;
    /** Combined bitmask for to-one (single-valued) associations. */
    const TO_ONE = 3;
    /**
     * Specifies that an association is to be fetched when it is first accessed.
     */
    const FETCH_LAZY = 2;
    /**
     * Specifies that an association is to be fetched when the owner of the
     * association is fetched.
     */
    const FETCH_EAGER = 3;
    /**
     * Specifies that an association is to be fetched lazy (on first access) and that
     * commands such as Collection#count, Collection#slice are issued directly against
     * the database if the collection is not yet initialized.
     */
    const FETCH_EXTRA_LAZY = 4;
    /**
     * DEFERRED_IMPLICIT means that changes of entities are calculated at commit-time
     * by doing a property-by-property comparison with the original data. This will
     * be done for all entities that are in MANAGED state at commit-time.
     *
     * This is the default change tracking policy.
     */
    const CHANGETRACKING_DEFERRED_IMPLICIT = 1;
    /**
     * DEFERRED_EXPLICIT means that changes of entities are calculated at commit-time
     * by doing a property-by-property comparison with the original data. This will
     * be done only for entities that were explicitly saved (through persist() or a cascade).
     */
    const CHANGETRACKING_DEFERRED_EXPLICIT = 2;
    /**
     * NOTIFY means that Doctrine relies on the entities sending out notifications
     * when their properties change. Such entity classes must implement
     * the <tt>NotifyPropertyChanged</tt> interface.
     */
    const CHANGETRACKING_NOTIFY = 3;
    /**
     * @return string
     */
    public function getRepositoryClass();

    /**
     * @param ReflectionService $reflService
     */
    public function wakeupReflection(ReflectionService $reflService);

    /**
     * @return MethodProviderInterface
     */
    public function getMethodContainer();

    /**
     * @param ReflectionService $reflService
     */
    public function initializeReflection(ReflectionService $reflService);

    /**
     * @return string
     * @throws MappingException
     */
    public function getApiName();

    /**
     * @return string
     * @throws MappingException
     */
    public function getClientName();

    /**
     * @return \ReflectionProperty[]
     */
    public function getReflectionProperties();

    /**
     * @return \ReflectionProperty
     * @throws MappingException
     */
    public function getReflectionProperty($name);

    /** @return string */
    public function getFieldName($apiFieldName);

    /** @return string */
    public function getApiFieldName($fieldName);

    /** @return object */
    public function newInstance();

    /**
     * Gets the mapping of an association.
     *
     * @param string $fieldName The field name that represents the association in
     *                          the object model.
     *
     * @return array The mapping.
     *
     * @throws MappingException
     */
    public function getAssociationMapping($fieldName);

    /** @return bool */
    public function hasApiField($apiFieldName);

    /**
     * @return array
     * @throws MappingException
     */
    public function getFieldMapping($fieldName);

    /** @return bool */
    public function isIdentifierComposite();

    /** @return string */
    public function getRootEntityName();

    /**
     * @return boolean
     */
    public function containsForeignIdentifier();

    /**
     * Populates the entity identifier of an entity.
     *
     * @param object $entity
     * @param array  $id
     *
     * @return void
     */
    public function assignIdentifier($entity, array $id);

    /**
     * @return array
     */
    public function getSubclasses();

    /** @return array */
    public function getAssociationMappings();

    public function isReadOnly();

    /**
     * Sets the change tracking policy used by this class.
     *
     * @param integer $policy
     *
     * @return void
     */
    public function setChangeTrackingPolicy($policy);

    /**
     * Whether the change tracking policy of this class is "deferred explicit".
     *
     * @return boolean
     */
    public function isChangeTrackingDeferredExplicit();

    /**
     * Whether the change tracking policy of this class is "deferred implicit".
     *
     * @return boolean
     */
    public function isChangeTrackingDeferredImplicit();

    /**
     * Whether the change tracking policy of this class is "notify".
     *
     * @return boolean
     */
    public function isChangeTrackingNotify();
}
