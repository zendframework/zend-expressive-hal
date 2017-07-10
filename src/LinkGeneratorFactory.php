<?php

namespace Hal;

use Psr\Container\ContainerInterface;

class LinkGeneratorFactory
{
    public function __invoke(ContainerInterface $container) : LinkGenerator
    {
        return new LinkGenerator(
            $container->get(LinkGenerator\UrlGenerator::class)
        );
    }
}
