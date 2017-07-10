<?php

namespace Hal\Metadata;

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
 * If you have created custom metadata types, you can extend this class to
 * support them. Create `create<type>(array $metadata)` methods for each
 * type you wish to support, where `<type>` is your custom class name, minus
 * the namespace.
 */
class MetadataMapFactory
{
    public function __invoke(ContainerInterface $container) : MetadataMap
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $config = $config[MetadataMap::class] ?? [];

        if (! is_array($config)) {
            throw InvalidConfigException::dueToNonArray($config);
        }

        $metadataMap = $this->populateMetadataMapFromConfig(new MetadataMap(), $config);
        return $metadataMap;
    }

    /**
     * @throws InvalidConfigException if any of the keys "collection_class",
     *     "collection_relation", or "route" are missing.
     */
    protected function createRouteBasedCollectionMetadata(array $metadata) : RouteBasedCollectionMetadata
    {
        $requiredKeys = [
            'collection_class',
            'collection_relation',
            'route',
        ];
        if ($requiredKeys !== array_intersect($requiredKeys, array_keys($metadata))) {
            throw InvalidConfigException::dueToMissingMetadata(RouteBasedCollectionMetadata::class, $requiredKeys);
        }

        $paginationParam = $metadata['pagination_param'] ?? 'page';
        $paginationParamType = $metadata['pagination_param_type'] ?? RouteBasedCollectionMetadata::TYPE_QUERY;
        $routeParams = $metadata['route_params'] ?? [];
        $queryStringArguments = $metadata['query_string_arguments'] ?? [];

        return new RouteBasedCollectionMetadata(
            $metadata['collection_class'],
            $metadata['collection_relation'],
            $metadata['route'],
            $paginationParam,
            $paginationParamType,
            $routeParams,
            $queryStringArguments
        );
    }

    /**
     * @throws InvalidConfigException if any of the keys "resource_class",
     *     "route", or "extractor" are missing.
     */
    protected function createRouteBasedResourceMetadata(array $metadata) : RouteBasedResourceMetadata
    {
        $requiredKeys = [
            'resource_class',
            'route',
            'extractor'
        ];
        if ($requiredKeys !== array_intersect($requiredKeys, array_keys($metadata))) {
            throw InvalidConfigException::dueToMissingMetadata(RouteBasedResourceMetadata::class, $requiredKeys);
        }

        $resourceIdentifier = $metadata['resource_identifier'] ?? 'id';
        $routeIdentifierPlaceholder = $metadata['route_identifier_placeholder'] ?? 'id';
        $routeParams = $metadata['route_params'] ?? [];

        return new RouteBasedResourceMetadata(
            $metadata['resource_class'],
            $metadata['route'],
            $metadata['extractor'],
            $resourceIdentifier,
            $routeIdentifierPlaceholder,
            $routeParams
        );
    }

    /**
     * @throws InvalidConfigException if any of the keys "collection_class",
     *     "collection_relation", or "url" are missing.
     */
    protected function createUrlBasedCollectionMetadata(array $metadata) : UrlBasedCollectionMetadata
    {
        $requiredKeys = [
            'collection_class',
            'collection_relation',
            'url',
        ];
        if ($requiredKeys !== array_intersect($requiredKeys, array_keys($metadata))) {
            throw InvalidConfigException::dueToMissingMetadata(UrlBasedCollectionMetadata::class, $requiredKeys);
        }

        $paginationParam = $metadata['pagination_param'] ?? 'page';
        $paginationParamType = $metadata['pagination_param_type'] ?? UrlBasedCollectionMetadata::TYPE_QUERY;

        return new UrlBasedCollectionMetadata(
            $metadata['collection_class'],
            $metadata['collection_relation'],
            $metadata['url'],
            $paginationParam,
            $paginationParamType
        );
    }

    /**
     * @throws InvalidConfigException if any of the keys "resource_class",
     *     "url", or "extractor" are missing.
     */
    protected function createUrlBasedResourceMetadata(array $metadata) : UrlBasedResourceMetadata
    {
        $requiredKeys = ['resource_class', 'url', 'extractor'];
        if ($requiredKeys !== array_intersect($requiredKeys, array_keys($metadata))) {
            throw InvalidConfigException::dueToMissingMetadata(UrlBasedResourceMetadata::class, $requiredKeys);
        }

        return new UrlBasedResourceMetadata(
            $metadata['resource_class'],
            $metadata['url'],
            $metadata['extractor']
        );
    }

    private function populateMetadataMapFromConfig(MetadataMap $metadataMap, array $config) : MetadataMap
    {
        foreach ($config as $metadata) {
            if (! is_array($metadata)) {
                throw InvalidConfigException::dueToNonArrayMetadata($metadata);
            }

            $this->injectMetadata($metadataMap, $metadata);
        }

        return $metadataMap;
    }

    /**
     * @throws InvalidConfigException if the metadata is missing a "__class__" entry.
     * @throws InvalidConfigException if the "__class__" entry is not a class.
     * @throws InvalidConfigException if the "__class__" entry is not an AbstractMetadata class.
     * @throws InvalidConfigException if no matching `create*()` method is found for the "__class__" entry.
     */
    private function injectMetadata(MetadataMap $metadataMap, array $metadata)
    {
        if (! isset($metadata['__class__'])) {
            throw InvalidConfigException::dueToMissingMetadataClass();
        }

        if (! class_exists($metadata['__class__'])) {
            throw InvalidConfigException::dueToInvalidMetadataClass($metadata['__class__']);
        }

        if (! in_array(AbstractMetadata::class, class_parents($metadata['__class__']), true)) {
            throw InvalidConfigException::dueToNonMetadataClass($metadata['__class__']);
        }

        $normalizedClass = $this->stripNamespaceFromClass($metadata['__class__']);
        $method          = sprintf('create%s', $normalizedClass);

        if (! method_exists($this, $method)) {
            throw InvalidConfigException::dueToUnrecognizedMetadataClass($metadata['__class__'], $normalizedClass);
        }

        $metadataMap->add($this->$method($metadata));
    }

    private function stripNamespaceFromClass(string $class) : string
    {
        $segments = explode('\\', $class);
        return array_pop($segments);
    }
}
