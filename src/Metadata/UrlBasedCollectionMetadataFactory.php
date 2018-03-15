<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Hal\Metadata;

use function array_intersect;
use function array_keys;

class UrlBasedCollectionMetadataFactory implements MetadataFactoryInterface
{
    /**
     * Creates a UrlBasedCollectionMetadata based on the MetadataMap configuration.
     *
     * @param string $requestedName The requested name of the metadata type.
     * @param array $metadata The metadata should have the following structure:
     *     <code>
     *     [
     *          // Fully qualified class name of the AbstractMetadata type.
     *          '__class__' => UrlBasedCollectionMetadata::class,
     *
     *          // Fully qualified class name of the collection class.
     *          'collection_class' => MyCollection::class,
     *
     *          // The embedded relation for the collection in the generated
     *          // resource.
     *          'collection_relation' => 'items',
     *
     *          // The URL to use when generating a self-relational link for
     *          // the collection resource.
     *          'url' => 'https://example.org/my-collection',
     *
     *          // Optional params
     *
     *          // The name of the parameter indicating what page of data is
     *          // present. Defaults to "page".
     *          'pagination_param' => 'page',
     *
     *          // Whether the pagination parameter is a query string or path
     *          // placeholder use either AbstractCollectionMetadata::TYPE_QUERY
     *          // (the default) or AbstractCollectionMetadata::TYPE_PLACEHOLDER.
     *          'pagination_param_type' => AbstractCollectionMetadata::TYPE_QUERY,
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
            'url',
        ];

        if ($requiredKeys !== array_intersect($requiredKeys, array_keys($metadata))) {
            throw Exception\InvalidConfigException::dueToMissingMetadata(
                UrlBasedCollectionMetadata::class,
                $requiredKeys
            );
        }

        return new $requestedName(
            $metadata['collection_class'],
            $metadata['collection_relation'],
            $metadata['url'],
            $metadata['pagination_param'] ?? 'page',
            $metadata['pagination_param_type'] ?? UrlBasedCollectionMetadata::TYPE_QUERY
        );
    }
}
