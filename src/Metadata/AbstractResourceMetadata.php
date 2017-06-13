<?php

namespace Hal\Metadata;

abstract class AbstractResourceMetadata extends AbstractMetadata
{
    /**
     * Service name of an ExtractionInterface implementation to use when
     * extracting a resource of this type.
     * @var string
     */
    protected $extractor;

    public function getExtractor() : string
    {
        return $this->extractor;
    }
}
