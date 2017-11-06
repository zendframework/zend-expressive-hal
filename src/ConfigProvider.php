<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Hal;

class ConfigProvider
{
    public function __invoke() : array
    {
        return [
            'dependencies'        => $this->getDependencies(),
            'zend-expressive-hal' => $this->getHalConfig(),
        ];
    }

    public function getDependencies() : array
    {
        return [
            'aliases' => [
                LinkGenerator\UrlGeneratorInterface::class => LinkGenerator\ExpressiveUrlGenerator::class,
            ],
            'factories' => [
                HalResponseFactory::class                   => HalResponseFactoryFactory::class,
                LinkGenerator::class                        => LinkGeneratorFactory::class,
                LinkGenerator\ExpressiveUrlGenerator::class => LinkGenerator\ExpressiveUrlGeneratorFactory::class,
                Metadata\MetadataMap::class                 => Metadata\MetadataMapFactory::class,
                ResourceGenerator::class                    => ResourceGeneratorFactory::class,
            ],
            'invokables' => [
                // TODO Would you prefer an InvokableFactory instead?
                ResourceGenerator\RouteBasedCollectionStrategy::class => ResourceGenerator\RouteBasedCollectionStrategy::class,
                ResourceGenerator\RouteBasedResourceStrategy::class   => ResourceGenerator\RouteBasedResourceStrategy::class,

                ResourceGenerator\UrlBasedCollectionStrategy::class   => ResourceGenerator\UrlBasedCollectionStrategy::class,
                ResourceGenerator\UrlBasedResourceStrategy::class     => ResourceGenerator\UrlBasedResourceStrategy::class
            ],
        ];
    }

    public function getHalConfig() : array
    {
        return [
            'resource-generator' => [
                'strategies' => [
                    Metadata\RouteBasedCollectionMetadata::class => ResourceGenerator\RouteBasedCollectionStrategy::class,
                    Metadata\RouteBasedResourceMetadata::class   => ResourceGenerator\RouteBasedResourceStrategy::class,

                    Metadata\UrlBasedCollectionMetadata::class   => ResourceGenerator\UrlBasedCollectionStrategy::class,
                    Metadata\UrlBasedResourceMetadata::class     => ResourceGenerator\UrlBasedResourceStrategy::class,
                ],
            ],
        ];
    }
}
