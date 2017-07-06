<?php

namespace Hal\ResourceGenerator;

use Hal\Link;
use Hal\Metadata;
use Hal\Resource;
use Hal\ResourceGenerator;
use Psr\Http\Message\ServerRequestInterface;
use Traversable;

class RouteBasedCollectionStrategy implements Strategy
{
    use ExtractCollection;

    public function createResource(
        $instance,
        Metadata\AbstractMetadata $metadata,
        ResourceGenerator $resourceGenerator,
        ServerRequestInterface $request
    ) : Resource {
        if (! $metadata instanceof Metadata\RouteBasedCollectionMetadata) {
            throw UnexpectedMetadataTypeException::forMetadata(
                $metadata,
                self::class,
                Metadata\RouteBasedCollectionMetadata
            );
        }

        if (! $instance instanceof Traversable) {
            throw InvalidCollectionException::fromInstance($instance, get_class($this));
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
        $queryStringArgs = $metadata->getQueryStringArguments();

        $paramsWithPage = [$paginationParam => $page];
        $routeParams = $paginationType === Metadata\AbstractCollectionMetadata::TYPE_PLACEHOLDER
            ? $paramsWithPage
            : [];
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
        return $resourceGenerator
            ->getLinkGenerator()
            ->fromRoute('self', $request, $metadata->getRoute());
    }
}
