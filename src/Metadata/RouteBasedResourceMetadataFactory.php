<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Hal\Metadata;

class RouteBasedResourceMetadataFactory extends AbstractMetadataFactory
{
    public function __invoke(array $metadata) : AbstractMetadata
    {
        $requiredKeys = [
            'resource_class',
            'route',
            'extractor'
        ];
        if ($requiredKeys !== array_intersect($requiredKeys, array_keys($metadata))) {
            throw Exception\InvalidConfigException::dueToMissingMetadata(
                RouteBasedResourceMetadata::class,
                $requiredKeys
            );
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
}
