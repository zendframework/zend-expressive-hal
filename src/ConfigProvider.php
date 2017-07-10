<?php

namespace Hal;

class ConfigProvider
{
    public function __invoke() : array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    public function getDependencies() : array
    {
        return [
            'factories' => [
                LinkGenerator\ExpressiveUrlGenerator::class => LinkGenerator\ExpressiveUrlGeneratorFactory::class,
                MetadataMap\MetadataMap::class => MetadataMap\MetadataMapFactory::class
            ],
        ];
    }
}
