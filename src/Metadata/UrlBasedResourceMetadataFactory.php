<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Hal\Metadata;

class UrlBasedResourceMetadataFactory implements MetadataFactoryInterface
{
    /**
     * Creates a UrlBasedResourceMetadata based on the MetadataMap configuration.
     *
     * @param array $metadata The metadata should have the following structure:
     * <code>
     * [
     *      // Fully qualified class name of the AbstractMetadata type.
     *      '__class__'                    => RouteBasedResourceMetadata::class,
     *
     *      // Fully qualified class name of the resource class.
     *      'resource_class'               => MyResource::class,
     *
     *      // The URL to use when generating a self-relational link for the resource.
     *      'url'                          => 'https://example.org/my-resource',
     *
     *      // The extractor/hydrator service to use to extract resource data.
     *      'extractor'                    => 'MyExtractor',
     * ]
     * </code>
     *
     * @return AbstractMetadata
     * @throws Exception\InvalidConfigException
     */
    public function createMetadata(array $metadata) : AbstractMetadata
    {
        $requiredKeys = [
            'resource_class',
            'url',
            'extractor',
        ];

        if ($requiredKeys !== array_intersect($requiredKeys, array_keys($metadata))) {
            throw Exception\InvalidConfigException::dueToMissingMetadata(
                UrlBasedResourceMetadata::class,
                $requiredKeys
            );
        }

        return new UrlBasedResourceMetadata(
            $metadata['resource_class'],
            $metadata['url'],
            $metadata['extractor']
        );
    }
}
