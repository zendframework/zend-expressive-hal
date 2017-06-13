<?php

namespace Hal\Metadata;

use Hal\LinkCollection;

abstract class AbstractMetadata
{
    use LinkCollection;

    /**
     * @var string
     */
    protected $class;

    public function getClass() : string
    {
        return $this->class;
    }
}
