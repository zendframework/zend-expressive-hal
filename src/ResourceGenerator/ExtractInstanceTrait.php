<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Hal\ResourceGenerator;

use Psr\Http\Message\ServerRequestInterface;
use Zend\Expressive\Hal\Metadata\AbstractCollectionMetadata;
use Zend\Expressive\Hal\Metadata\AbstractMetadata;
use Zend\Expressive\Hal\Metadata\RouteBasedResourceMetadata;
use Zend\Expressive\Hal\ResourceGenerator;
use Zend\Hydrator\ExtractionInterface;

use function get_class;
use function is_object;

trait ExtractInstanceTrait
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
        ServerRequestInterface $request,
        int $depth = 0
    ) : array {
        $hydrators = $resourceGenerator->getHydrators();
        $extractor = $hydrators->get($metadata->getExtractor());
        if (! $extractor instanceof ExtractionInterface) {
            throw Exception\InvalidExtractorException::fromInstance($extractor);
        }

        $array = $extractor->extract($instance);

        if ($metadata instanceof RouteBasedResourceMetadata) {
            $maxDepth = $metadata->getMaxDepth();
            if ($depth > $maxDepth) {
                $resourceIdentifier = $metadata->getResourceIdentifier();
                return [
                    $resourceIdentifier => $array[$resourceIdentifier]
                ];
            }
        }

        // Extract nested resources if present in metadata map
        $metadataMap = $resourceGenerator->getMetadataMap();
        foreach ($array as $key => $value) {
            if (! is_object($value)) {
                continue;
            }

            $childClass = get_class($value);
            if (! $metadataMap->has($childClass)) {
                continue;
            }

            $childData = $resourceGenerator->fromObject($value, $request, $depth + 1);

            // Nested collections need to be merged.
            $childMetadata = $metadataMap->get($childClass);
            if ($childMetadata instanceof AbstractCollectionMetadata) {
                $childData = $childData->getElement($childMetadata->getCollectionRelation());
            }

            $array[$key] = $childData;
        }

        return $array;
    }
}
