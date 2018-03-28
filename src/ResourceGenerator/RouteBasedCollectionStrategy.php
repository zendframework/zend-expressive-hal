<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Hal\ResourceGenerator;

use Psr\Http\Message\ServerRequestInterface;
use Traversable;
use Zend\Expressive\Hal\HalResource;
use Zend\Expressive\Hal\Link;
use Zend\Expressive\Hal\Metadata;
use Zend\Expressive\Hal\ResourceGenerator;

use function array_merge;
use function get_class;

class RouteBasedCollectionStrategy implements StrategyInterface
{
    use ExtractCollectionTrait;

    public function createResource(
        $instance,
        Metadata\AbstractMetadata $metadata,
        ResourceGenerator $resourceGenerator,
        ServerRequestInterface $request
    ) : HalResource {
        if (! $metadata instanceof Metadata\RouteBasedCollectionMetadata) {
            throw Exception\UnexpectedMetadataTypeException::forMetadata(
                $metadata,
                self::class,
                Metadata\RouteBasedCollectionMetadata::class
            );
        }

        if (! $instance instanceof Traversable) {
            throw Exception\InvalidCollectionException::fromInstance($instance, get_class($this));
        }

        return $this->extractCollection($instance, $metadata, $resourceGenerator, $request);
    }

    /**
     * @param string $rel Relation to use when creating Link
     * @param int $page Page number for generated link
     * @param Metadata\AbstractCollectionMetadata $metadata Used to provide the
     *     base URL, pagination parameter, and type of pagination used (query
     *     string, path parameter)
     * @param ResourceGenerator $resourceGenerator Used to retrieve link
     *     generator in order to generate link based on routing information.
     * @param ServerRequestInterface $request Passed to link generator when
     *     generating link based on routing information.
     * @return Link
     */
    protected function generateLinkForPage(
        string $rel,
        int $page,
        Metadata\AbstractCollectionMetadata $metadata,
        ResourceGenerator $resourceGenerator,
        ServerRequestInterface $request
    ) : Link {
        $route = $metadata->getRoute();
        $paginationType = $metadata->getPaginationParamType();
        $paginationParam = $metadata->getPaginationParam();
        $routeParams = $metadata->getRouteParams();
        $queryStringArgs = $metadata->getQueryStringArguments();

        $paramsWithPage = [$paginationParam => $page];
        $routeParams = $paginationType === Metadata\AbstractCollectionMetadata::TYPE_PLACEHOLDER
            ? array_merge($routeParams, $paramsWithPage)
            : $routeParams;
        $queryParams = $paginationType === Metadata\AbstractCollectionMetadata::TYPE_QUERY
            ? array_merge($queryStringArgs, $paramsWithPage)
            : $queryStringArgs;

        return $resourceGenerator
            ->getLinkGenerator()
            ->fromRoute(
                $rel,
                $request,
                $route,
                $routeParams,
                $queryParams
            );
    }

    /**
     * @param Metadata\AbstractCollectionMetadata $metadata Provides base URL
     *     for self link.
     * @param ResourceGenerator $resourceGenerator Used to retrieve link
     *     generator in order to generate link based on routing information.
     * @param ServerRequestInterface $request Passed to link generator when
     *     generating link based on routing information.
     * @return Link
     */
    protected function generateSelfLink(
        Metadata\AbstractCollectionMetadata $metadata,
        ResourceGenerator $resourceGenerator,
        ServerRequestInterface $request
    ) {

        $routeParams = $metadata->getRouteParams() ?? [];
        $queryStringArgs = array_merge($request->getQueryParams() ?? [], $metadata->getQueryStringArguments() ?? []);

        return $resourceGenerator
            ->getLinkGenerator()
            ->fromRoute(
                'self',
                $request,
                $metadata->getRoute(),
                $routeParams,
                $queryStringArgs
            );
    }
}
