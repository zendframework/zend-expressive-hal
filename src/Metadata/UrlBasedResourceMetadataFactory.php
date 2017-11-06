<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Hal\Metadata;

class UrlBasedResourceMetadataFactory extends AbstractMetadataFactory
{
    public function __invoke(array $metadata) : AbstractMetadata
    {
        $requiredKeys = ['resource_class', 'url', 'extractor'];
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
