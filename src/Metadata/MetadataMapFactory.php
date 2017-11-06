<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Hal\Metadata;

use Psr\Container\ContainerInterface;

/**
 * Create a MetadataMap based on configuration.
 *
 * Utilizes the "config" service, and pulls the subkey `Hal\Metadata\MetadataMap`.
 * Each entry is expected to be an associative array, with the following
 * structure:
 *
 * <code>
 * [
 *     '__class__' => 'Fully qualified class name of an AbstractMetadata type',
 *     // additional key/value pairs as required by the metadata type.
 * ]
 * </code>
 *
 * The additional pairs are as follows:
 *
 * - For UrlBasedResourceMetadata:
 *   - resource_class: the resource class the metadata describes.
 *   - url: the URL to use when generating a self-relational link for the
 *     resource.
 *   - extractor: the extractor/hydrator service to use to extract resource
 *     data.
 * - For UrlBasedCollectionMetadata:
 *   - collection_class: the collection class the metadata describes.
 *   - collection_relation: the embedded relation for the collection in the
 *     generated resource.
 *   - url: the URL to use when generating a self-relational link for the
 *     collection resource.
 *   - pagination_param: the name of the parameter indicating what page of data
 *     is present. Defaults to "page".
 *   - pagination_param_type: whether the pagination parameter is a query string
 *     or path placeholder; use either AbstractCollectionMetadata::TYPE_QUERY
 *     ("query") or AbstractCollectionMetadata::TYPE_PLACEHOLDER ("placeholder");
 *     default is "query".
 * - For RouteBasedResourceMetadata:
 *   - resource_class: the resource class the metadata describes.
 *   - route: the route to use when generating a self relational link for the
 *     resource.
 *   - extractor: the extractor/hydrator service to use to extract resource
 *     data.
 *   - resource_identifier: what property in the resource represents its
 *     identifier; defaults to "id".
 *   - route_identifier_placeholder: what placeholder in the route string
 *     represents the resource identifier; defaults to "id".
 *   - route_params: an array of additional routing parameters to use when
 *     generating the self relational link for the resource.
 * - For RouteBasedCollectionMetadata:
 *   - collection_class: the collection class the metadata describes.
 *   - collection_relation: the embedded relation for the collection in the
 *     generated resource.
 *   - route: the route to use when generating a self relational link for the
 *     collection resource.
 *   - pagination_param: the name of the parameter indicating what page of data
 *     is present. Defaults to "page".
 *   - pagination_param_type: whether the pagination parameter is a query string
 *     or path placeholder; use either AbstractCollectionMetadata::TYPE_QUERY
 *     ("query") or AbstractCollectionMetadata::TYPE_PLACEHOLDER ("placeholder");
 *     default is "query".
 *   - route_params: an array of additional routing parameters to use when
 *     generating the self relational link for the collection resource. Defaults
 *     to an empty array.
 *   - query_string_arguments: an array of query string parameters to include
 *     when generating the self relational link for the collection resource.
 *     Defaults to an empty array.
 *
 * If you have created custom metadata types, you can have to register a factory
 * each in your configuration to support them. Add an entry to the config array:
 * $config['zend-expressive-hal']['metadata-factories'][MyMetadata::class] = MyMetadataFactory::class.
 *
 * Your factory should extends the `AbstractMetadataFactory`.
 */
class MetadataMapFactory
{
    public function __invoke(ContainerInterface $container) : MetadataMap
    {
        $config            = $container->has('config') ? $container->get('config') : [];
        $metadataMapConfig = $config[MetadataMap::class] ?? [];

        if (! is_array($metadataMapConfig)) {
            throw Exception\InvalidConfigException::dueToNonArray($metadataMapConfig);
        }

        $metadataFactories = $config['zend-expressive-hal']['metadata-factories'] ?? [];

        return $this->populateMetadataMapFromConfig(
            new MetadataMap(),
            $metadataMapConfig,
            $metadataFactories
        );
    }

    private function populateMetadataMapFromConfig(
        MetadataMap $metadataMap,
        array $metadataMapConfig,
        array $metadataFactories
    ) : MetadataMap {
        foreach ($metadataMapConfig as $metadata) {
            if (! is_array($metadata)) {
                throw Exception\InvalidConfigException::dueToNonArrayMetadata($metadata);
            }

            $this->injectMetadata($metadataMap, $metadata, $metadataFactories);
        }

        return $metadataMap;
    }

    /**
     * @throws Exception\InvalidConfigException if the metadata is missing a
     *     "__class__" entry.
     * @throws Exception\InvalidConfigException if the "__class__" entry is not
     *     a class.
     * @throws Exception\InvalidConfigException if the "__class__" entry is not
     *     an AbstractMetadata class.
     * @throws Exception\InvalidConfigException if no matching `create*()`
     *     method is found for the "__class__" entry.
     */
    private function injectMetadata(MetadataMap $metadataMap, array $metadata, array $metadataFactories)
    {
        if (! isset($metadata['__class__'])) {
            throw Exception\InvalidConfigException::dueToMissingMetadataClass();
        }

        if (! class_exists($metadata['__class__'])) {
            throw Exception\InvalidConfigException::dueToInvalidMetadataClass($metadata['__class__']);
        }

        $metadataClass = $metadata['__class__'];
        if (! in_array(AbstractMetadata::class, class_parents($metadataClass), true)) {
            throw Exception\InvalidConfigException::dueToNonMetadataClass($metadataClass);
        }

        if (! isset($metadataFactories[$metadataClass])) {
            throw Exception\InvalidConfigException::dueToUnrecognizedMetadataClass($metadataClass);
        }

        $factoryClass = $metadataFactories[$metadataClass];
        if (! in_array(AbstractMetadataFactory::class, class_parents($factoryClass), true)) {
            throw Exception\InvalidConfigException::dueToNonMetadataFactoryClass($factoryClass);
        }

        $factory = new $metadataFactories[$metadataClass];
        $metadataMap->add($factory($metadata));
    }
}
