<?php

namespace Hal\ResourceGenerator;

use Hal\Link;
use Hal\LinkGenerator;
use Hal\Metadata;
use Hal\Resource;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

class RouteBasedResourceStrategy implements Strategy
{
    use ExtractInstance;

    public function createResource(
        $instance,
        Metadata\AbstractMetadata $metadata,
        ContainerInterface $hydrators,
        LinkGenerator $linkGenerator,
        ServerRequestInterface $request
    ) : Resource {
        if (! $metadata instanceof Metadata\RouteBasedResourceMetadata) {
            throw UnexpectedMetadataTypeException::forMetadata(
                $metadata,
                self::class,
                Metadata\RouteBasedResourceMetadata
            );
        }

        $data               = $this->extractInstance($hydrators, $metadata, $instance);
        $routeParams        = $metadata->getRouteParams();
        $resourceIdentifier = $metadata->getResourceIdentifier();
        $routeIdentifier    = $metadata->getRouteIdentifierPlaceholder();

        if (isset($data[$resourceIdentifier])) {
            $routeParams[$routeIdentifier] = $data[$resourceIdentifier];
        }

        return new Resource($data, [
            $linkGenerator->fromRoute(
                'self',
                $request,
                $metadata->getRoute(),
                $routeParams
            )
        ]);
    }
}
