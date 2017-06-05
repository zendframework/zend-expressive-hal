<?php

namespace HalTest\TestAsset;

class Uri
{
    private $uri;

    public function __construct(string $uri)
    {
        $this->uri = $uri;
    }

    public function __toString() : string
    {
        return $this->uri;
    }
}
