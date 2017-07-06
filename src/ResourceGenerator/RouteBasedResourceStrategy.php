<?php

namespace Hal\ResourceGenerator;

use Hal\HalResource;
use Hal\Link;
use Hal\Metadata;
use Hal\ResourceGenerator;
use Psr\Http\Message\ServerRequestInterface;

class RouteBasedResourceStrategy implements Strategy
{
    use ExtractInstance;

    public function createResource(
        $instance,
        Metadata\AbstractMetadata $metadata,
        ResourceGenerator $resourceGenerator,
        ServerRequestInterface $request
    ) : HalResource {
        if (! $metadata instanceof Metadata\RouteBasedResourceMetadata) {
            throw UnexpectedMetadataTypeException::forMetadata(
                $metadata,
                self::class,
                Metadata\RouteBasedResourceMetadata
            );
        }

        $data = $this->extractInstance(
            $resourceGenerator->getHydrators(),
            $metadata,
            $instance
        );

        $routeParams        = $metadata->getRouteParams();
        $resourceIdentifier = $metadata->getResourceIdentifier();
        $routeIdentifier    = $metadata->getRouteIdentifierPlaceholder();

        if (isset($data[$resourceIdentifier])) {
            $routeParams[$routeIdentifier] = $data[$resourceIdentifier];
        }

        return new HalResource($data, [
            $resourceGenerator->getLinkGenerator()->fromRoute(
                'self',
                $request,
                $metadata->getRoute(),
                $routeParams
            )
        ]);
    }
}
