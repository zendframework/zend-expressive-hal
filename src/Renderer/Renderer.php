<?php

namespace Hal\Renderer;

use Hal\HalResource;

interface Renderer
{
    public function render(HalResource $resource) : string;
}
