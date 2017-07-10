<?php

namespace Hal\ResourceGenerator;

use Hal\Metadata\AbstractMetadata;
use Hal\ResourceGenerator;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Hydrator\ExtractionInterface;

trait ExtractInstance
{
    /**
     * @param object $instance
     * @throws \Psr\Container\ContainerExceptionInterface if the extractor
     *     service cannot be retrieved.
     */
    private function extractInstance(
        $instance,
        AbstractMetadata $metadata,
        ResourceGenerator $resourceGenerator,
        ServerRequestInterface $request
    ) : array {
        $hydrators = $resourceGenerator->getHydrators();
        $extractor = $hydrators->get($metadata->getExtractor());
        if (! $extractor instanceof ExtractionInterface) {
            throw Exception\InvalidExtractorException::fromInstance($extractor);
        }

        $array = $extractor->extract($instance);

        // Extract nested resources if present in metadata map
        $metadataMap = $resourceGenerator->getMetadataMap();
        foreach ($array as $key => $value) {
            if (! is_object($value)) {
                continue;
            }

            if (! $metadataMap->has(get_class($value))) {
                continue;
            }

            $array[$key] = $resourceGenerator->fromObject($value, $request);
        }

        return $array;
    }
}
