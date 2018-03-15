<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Hal\Metadata;

use function array_intersect;
use function array_keys;

class RouteBasedCollectionMetadataFactory implements MetadataFactoryInterface
{
    /**
     * Creates a RouteBasedCollectionMetadata based on the MetadataMap configuration.
     *
     * @param string $requestedName The requested name of the metadata type.
     * @param array $metadata The metadata should have the following structure:
     *     <code>
     *     [
     *          // Fully qualified class name of the AbstractMetadata type.
     *          '__class__' => RouteBasedCollectionMetadata::class,
     *
     *          // Fully qualified class name of the collection class.
     *          'collection_class' => MyCollection::class,
     *
     *          // The embedded relation for the collection in the generated resource.
     *          'collection_relation' => 'items',
     *
     *          // The route to use when generating a self relational link for the
     *          // collection resource.
     *          'route' => 'items.list',
     *
     *          // Optional params
     *
     *          // The name of the parameter indicating what page of data is present.
     *          // Defaults to "page".
     *          'pagination_param' => 'page',
     *
     *          // Whether the pagination parameter is a query string or path placeholder.
     *          // Use either AbstractCollectionMetadata::TYPE_QUERY (the default)
     *          // or AbstractCollectionMetadata::TYPE_PLACEHOLDER.
     *          'pagination_param_type' => AbstractCollectionMetadata::TYPE_QUERY,
     *
     *          // An array of additional routing parameters to use when generating
     *          // the self relational link for the collection resource.
     *          // Defaults to an empty array.
     *          'route_params' => [],
     *
     *          // An array of query string parameters to include when generating the
     *          // self relational link for the collection resource.
     *          // Defaults to an empty array.
     *          'query_string_arguments' => [],
     *     ]
     *     </code>
     * @return AbstractMetadata
     * @throws Exception\InvalidConfigException
     */
    public function createMetadata(string $requestedName, array $metadata) : AbstractMetadata
    {
        $requiredKeys = [
            'collection_class',
            'collection_relation',
            'route',
        ];

        if ($requiredKeys !== array_intersect($requiredKeys, array_keys($metadata))) {
            throw Exception\InvalidConfigException::dueToMissingMetadata(
                RouteBasedCollectionMetadata::class,
                $requiredKeys
            );
        }

        return new $requestedName(
            $metadata['collection_class'],
            $metadata['collection_relation'],
            $metadata['route'],
            $metadata['pagination_param'] ?? 'page',
            $metadata['pagination_param_type'] ?? RouteBasedCollectionMetadata::TYPE_QUERY,
            $metadata['route_params'] ?? [],
            $metadata['query_string_arguments'] ?? []
        );
    }
}
