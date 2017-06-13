<?php

namespace Hal\Metadata;

class UrlBasedResourceMetadata extends AbstractResourceMetadata
{
    /**
     * @var string
     */
    private $url;

    public function __construct(string $class, string $url, string $extractor)
    {
        $this->class = $class;
        $this->url = $url;
        $this->extractor = $extractor;
    }

    public function getUrl() : string
    {
        return $this->url;
    }
}
