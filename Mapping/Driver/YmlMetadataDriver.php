<?php

namespace Bankiru\Api\Doctrine\Mapping\Driver;

use Bankiru\Api\Doctrine\Exception\MappingException;
use Bankiru\Api\Doctrine\Mapping\EntityMetadata;
use Bankiru\Api\Doctrine\Rpc\DoctrineApi;
use Bankiru\Api\Doctrine\Rpc\Method\EntityMethodProvider;
use Bankiru\Api\Doctrine\Rpc\Method\MethodProvider;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\Driver\FileDriver;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class YmlMetadataDriver extends FileDriver
{
    /**
     * Loads the metadata for the specified class into the provided container.
     *
     * @param string                       $className
     * @param EntityMetadata|ClassMetadata $metadata
     *
     * @return void
     * @throws MappingException
     */
    public function loadMetadataForClass($className, ClassMetadata $metadata)
    {
        $element = $this->getElement($className);

        switch ($element['type']) {
            case 'entity':
                if (array_key_exists('repositoryClass', $element)) {
                    $metadata->setCustomRepositoryClass($element['repositoryClass']);
                }
                break;
            case 'mappedSuperclass':
                $metadata->isMappedSuperclass = true;
                $metadata->setCustomRepositoryClass(
                    array_key_exists('repositoryClass', $element) ? $element['repositoryClass'] : null
                );
                break;
        }

        // Configure API
        if (array_key_exists('api', $element)) {
            if (array_key_exists('name', $element['api'])) {
                $metadata->apiName = $element['api']['name'];
            }
        }

        // Configure Client
        if (array_key_exists('client', $element)) {
            if (array_key_exists('name', $element['client'])) {
                $metadata->clientName = $element['client']['name'];
            }

            $methodProvider = null;
            if (array_key_exists('methods', $element['client'])) {
                $methodProvider = new MethodProvider($element['client']['methods']);
            }
            if (array_key_exists('entityPath', $element['client'])) {
                $pathSeparator  =
                    array_key_exists('entityPathSeparator', $element['client']) ?
                        $element['client']['entityPathSeparator'] :
                        null;
                $methodProvider =
                    new EntityMethodProvider($element['client']['entityPath'], $pathSeparator, $methodProvider);
            }

            if (null === $methodProvider) {
                throw MappingException::noMethods();
            }

            $metadata->methodProvider = $methodProvider;
        }

        // Configure fields
        if (array_key_exists('fields', $element)) {
            foreach ($element['fields'] as $field => $mapping) {
                $mapping = $this->fieldToArray($field, $mapping);
                $metadata->mapField($mapping);
            }
        }

        // Configure identifiers
        $associationIds = [];
        if (array_key_exists('id', $element)) {
            // Evaluate identifier settings
            foreach ($element['id'] as $name => $idElement) {
                if (isset($idElement['associationKey']) && (bool)$idElement['associationKey'] === true) {
                    $associationIds[$name] = true;
                    continue;
                }

                $mapping = $this->fieldToArray($name, $idElement);

                $mapping['id'] = true;
                $metadata->mapField($mapping);
            }
        }

        foreach (['oneToOne', 'manyToOne', 'oneToMany'] as $type) {
            if (array_key_exists($type, $element)) {
                $associations = $element[$type];
                foreach ($associations as $name => $association) {
                    $this->mapAssociation($metadata, $type, $name, $association, $associationIds);
                }
            }
        }
    }

    /**
     * @param EntityMetadata $metadata
     * @param string         $type
     * @param string         $name
     * @param array          $association
     * @param int[]          $associationIds
     */
    protected function mapAssociation(EntityMetadata $metadata, $type, $name, $association, $associationIds)
    {
        $mapping           = $this->fieldToArray($name, $association);
        $mapping['target'] = $association['target'];

        switch ($type) {
            case 'oneToOne':
                $mapping['type'] = EntityMetadata::ONE_TO_ONE;
                if (isset($associationIds[$mapping['field']])) {
                    $mapping['id'] = true;
                }
                if (array_key_exists('mappedBy', $association)) {
                    $mapping['mappedBy'] = $association['mappedBy'];
                }
                if (array_key_exists('inversedBy', $association)) {
                    $mapping['inversedBy'] = $association['inversedBy'];
                }
                break;
            case 'manyToOne':
                $mapping['type'] = EntityMetadata::MANY_TO_ONE;
                if (array_key_exists('inversedBy', $association)) {
                    $mapping['inversedBy'] = $association['inversedBy'];
                }
                break;
            case 'oneToMany':
                $mapping['type'] = EntityMetadata::ONE_TO_MANY;
                if (array_key_exists('mappedBy', $association)) {
                    $mapping['mappedBy'] = $association['mappedBy'];
                }
                if (array_key_exists('orderBy', $association)) {
                    $mapping['orderBy'] = $association['orderBy'];
                }
                if (array_key_exists('indexBy', $association)) {
                    $mapping['indexBy'] = $association['indexBy'];
                }
                break;
        }

        $metadata->mapAssociation($mapping);
    }

    /**
     * Loads a mapping file with the given name and returns a map
     * from class/entity names to their corresponding file driver elements.
     *
     * @param string $file The mapping file to load.
     *
     * @return array
     * @throws ParseException
     */
    protected function loadMappingFile($file)
    {
        return Yaml::parse(file_get_contents($file));
    }

    private function fieldToArray($field, $source)
    {
        $mapping = [
            'field'    => $field,
            'type'     => 'string',
            'nullable' => true,
        ];

        if (array_key_exists('type', $source)) {
            $mapping['type'] = $source['type'];
        }

        if (array_key_exists('nullable', $source)) {
            $mapping['nullable'] = $source['nullable'];
        }

        if (array_key_exists('api_field', $source)) {
            $mapping['api_field'] = $source['api_field'];
        }

        return $mapping;
    }
}
