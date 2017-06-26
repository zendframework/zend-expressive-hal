<?php

namespace Hal\ResourceGenerator;

use Hal\Link;
use Hal\Metadata;
use Hal\Resource;
use Hal\ResourceGenerator;
use Psr\Http\Message\ServerRequestInterface;

class UrlBasedResourceStrategy implements Strategy
{
    use ExtractInstance;

    public function createResource(
        $instance,
        Metadata\AbstractMetadata $metadata,
        ResourceGenerator $resourceGenerator,
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
            $this->extractInstance($resourceGenerator->getHydrators(), $metadata, $instance),
            [new Link('self', $metadata->getUrl())]
        );
    }
}
