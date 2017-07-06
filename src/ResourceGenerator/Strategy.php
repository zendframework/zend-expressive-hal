<?php

namespace Hal\ResourceGenerator;

use Hal\HalResource;
use Hal\Metadata;
use Hal\ResourceGenerator;
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
        ResourceGenerator $resourceGenerator,
        ServerRequestInterface $request
    ) : HalResource;
}
