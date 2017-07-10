<?php

namespace Hal;

use Psr\Container\ContainerInterface;
use Zend\Hydrator\HydratorPluginManager;

class ResourceGeneratorFactory
{
    public function __invoke(ContainerInterface $container) : ResourceGenerator
    {
        return new ResourceGenerator(
            $container->get(Metadata\MetadataMap::class),
            $container->get(HydratorPluginManager::class),
            $container->get(LinkGenerator::class)
        );
    }
}
