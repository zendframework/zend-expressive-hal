<?php

namespace Hal\ResourceGenerator;

use Hal\Link;
use Hal\LinkGenerator;
use Hal\Metadata;
use Hal\Resource;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

class UrlBasedResourceStrategy implements Strategy
{
    use ExtractInstance;

    public function createResource(
        $instance,
        Metadata\AbstractMetadata $metadata,
        ContainerInterface $hydrators,
        LinkGenerator $linkGenerator,
        ServerRequestInterface $request
    ) : Resource {
        if (! $metadata instanceof Metadata\UrlBasedResourceMetadata) {
            throw UnexpectedMetadataTypeException::forMetadata(
                $metadata,
                self::class,
                Metadata\UrlBasedResourceMetadata
            );
        }

        return new Resource(
            $this->extractInstance($hydrators, $metadata, $instance),
            [new Link('self', $metadata->getUrl())]
        );
    }
}
