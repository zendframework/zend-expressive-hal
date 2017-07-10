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
            'aliases' => [
                LinkGenerator\UrlGenerator::class => LinkGenerator\ExpressiveUrlGenerator::class,
            ],
            'factories' => [
                LinkGenerator::class => LinkGeneratorFactory::class,
                LinkGenerator\ExpressiveUrlGenerator::class => LinkGenerator\ExpressiveUrlGeneratorFactory::class,
                MetadataMap\MetadataMap::class => MetadataMap\MetadataMapFactory::class,
                ResourceGenerator::class => ResourceGeneratorFactory::class,
            ],
        ];
    }
}
