<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Hal\Metadata;

class RouteBasedCollectionMetadataFactory extends AbstractMetadataFactory
{
    public function __invoke(array $metadata) : AbstractMetadata
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

        $paginationParam      = $metadata['pagination_param'] ?? 'page';
        $paginationParamType  = $metadata['pagination_param_type'] ?? RouteBasedCollectionMetadata::TYPE_QUERY;
        $routeParams          = $metadata['route_params'] ?? [];
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
}
