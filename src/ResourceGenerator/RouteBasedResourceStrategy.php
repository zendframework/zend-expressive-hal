<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Hal\ResourceGenerator;

use Psr\Http\Message\ServerRequestInterface;
use Zend\Expressive\Hal\HalResource;
use Zend\Expressive\Hal\Metadata;
use Zend\Expressive\Hal\ResourceGenerator;

class RouteBasedResourceStrategy implements StrategyInterface
{
    use ExtractInstanceTrait;

    public function createResource(
        $instance,
        Metadata\AbstractMetadata $metadata,
        ResourceGenerator $resourceGenerator,
        ServerRequestInterface $request
    ) : HalResource {
        if (! $metadata instanceof Metadata\RouteBasedResourceMetadata) {
            throw Exception\UnexpectedMetadataTypeException::forMetadata(
                $metadata,
                self::class,
                Metadata\RouteBasedResourceMetadata::class
            );
        }

        $data = $this->extractInstance(
            $instance,
            $metadata,
            $resourceGenerator,
            $request
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
