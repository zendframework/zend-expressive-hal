<?php

namespace Hal\Renderer;

use Hal\HalResource;

class JsonRenderer implements Renderer
{
    /** @var int */
    private $jsonFlags;

    public function __construct(int $jsonFlags)
    {
        $this->jsonFlags = $jsonFlags;
    }

    public function render(HalResource $resource) : string
    {
        return json_encode($resource, $this->jsonFlags);
    }
}
