<?php

namespace Hal\ResourceGenerator;

use Hal\Link;
use Hal\LinkGenerator;
use Hal\Metadata;
use Hal\Resource;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

class UrlBasedCollectionStrategy implements Strategy
{
    public function createResource(
        $instance,
        Metadata\AbstractMetadata $metadata,
        ContainerInterface $hydrators,
        LinkGenerator $linkGenerator,
        ServerRequestInterface $request
    ) : Resource {
        if (! $metadata instanceof Metadata\UrlBasedCollectionMetadata) {
            throw UnexpectedMetadataTypeException::forMetadata(
                $metadata,
                self::class,
                Metadata\UrlBasedCollectionMetadata
            );
        }

        return new Resource($resourceData, [
            new Link('self', $metadata->getUrl())
        ]);
    }
}
