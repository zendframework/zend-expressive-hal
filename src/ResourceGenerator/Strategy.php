<?php

namespace Hal\ResourceGenerator;

use Hal\LinkGenerator;
use Hal\Metadata;
use Hal\Resource;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

interface Strategy
{
    /**
     * @param object $instance Instance from which to create Resource.
     * @throws UnexpectedMetadataTypeException for metadata types the strategy
     *     cannot handle.
     */
    public function createResource(
        $instance,
        Metadata\AbstractMetadata $metadata,
        ContainerInterface $hydrators,
        LinkGenerator $linkGenerator,
        ServerRequestInterface $request
    ) : Resource;
}
