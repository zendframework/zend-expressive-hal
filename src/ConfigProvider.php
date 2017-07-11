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
                HalResponseFactory::class => HalResponseFactoryFactory::class,
                LinkGenerator::class => LinkGeneratorFactory::class,
                LinkGenerator\ExpressiveUrlGenerator::class => LinkGenerator\ExpressiveUrlGeneratorFactory::class,
                Metadata\MetadataMap::class => Metadata\MetadataMapFactory::class,
                ResourceGenerator::class => ResourceGeneratorFactory::class,
            ],
        ];
    }
}
