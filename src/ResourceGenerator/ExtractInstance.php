<?php

namespace Hal\ResourceGenerator;

use Hal\Metadata\AbstractMetadata;
use Psr\Container\ContainerInterface;
use Zend\Hydrator\ExtractionInterface;

trait ExtractInstance
{
    /**
     * @param object $instance
     * @throws \Psr\Container\ContainerExceptionInterface if the extractor
     *     service cannot be retrieved.
     */
    private function extractInstance(
        ContainerInterface $hydrators,
        AbstractMetadata $metadata,
        $instance
    ) : array {
        $extractor = $hydrators->get($metadata->getExtractor());
        if (! $extractor instanceof ExtractionInterface) {
            throw InvalidExtractorException::fromInstance($extractor);
        }
        return $extractor->extract($instance);
    }
}
