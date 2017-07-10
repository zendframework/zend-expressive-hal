<?php

namespace Hal\ResourceGenerator;

use Hal\HalResource;
use Hal\Link;
use Hal\Metadata;
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
    ) : HalResource {
        if (! $metadata instanceof Metadata\UrlBasedResourceMetadata) {
            throw Exception\UnexpectedMetadataTypeException::forMetadata(
                $metadata,
                self::class,
                Metadata\UrlBasedResourceMetadata
            );
        }

        return new HalResource(
            $this->extractInstance($instance, $metadata, $resourceGenerator, $request),
            [new Link('self', $metadata->getUrl())]
        );
    }
}
