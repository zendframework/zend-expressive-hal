<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Hal;

use Zend\Expressive\Hal\LinkGenerator\ExpressiveUrlGenerator;
use Zend\Expressive\Hal\LinkGenerator\UrlGeneratorInterface;
use Zend\Expressive\Hal\Metadata\MetadataMap;
use Zend\Expressive\Hal\Metadata\RouteBasedCollectionMetadata;
use Zend\Expressive\Hal\Metadata\RouteBasedCollectionMetadataFactory;
use Zend\Expressive\Hal\Metadata\RouteBasedResourceMetadata;
use Zend\Expressive\Hal\Metadata\RouteBasedResourceMetadataFactory;
use Zend\Expressive\Hal\Metadata\UrlBasedCollectionMetadata;
use Zend\Expressive\Hal\Metadata\UrlBasedCollectionMetadataFactory;
use Zend\Expressive\Hal\Metadata\UrlBasedResourceMetadata;
use Zend\Expressive\Hal\Metadata\UrlBasedResourceMetadataFactory;
use Zend\Expressive\Hal\ResourceGenerator\RouteBasedCollectionStrategy;
use Zend\Expressive\Hal\ResourceGenerator\RouteBasedResourceStrategy;
use Zend\Expressive\Hal\ResourceGenerator\UrlBasedCollectionStrategy;
use Zend\Expressive\Hal\ResourceGenerator\UrlBasedResourceStrategy;

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
                UrlGeneratorInterface::class => LinkGenerator\ExpressiveUrlGenerator::class,
            ],
            'factories' => [
                HalResponseFactory::class     => HalResponseFactoryFactory::class,
                LinkGenerator::class          => LinkGeneratorFactory::class,
                ExpressiveUrlGenerator::class => LinkGenerator\ExpressiveUrlGeneratorFactory::class,
                MetadataMap::class            => Metadata\MetadataMapFactory::class,
                ResourceGenerator::class      => ResourceGeneratorFactory::class,
            ],
            'invokables' => [
                RouteBasedCollectionStrategy::class => RouteBasedCollectionStrategy::class,
                RouteBasedResourceStrategy::class   => RouteBasedResourceStrategy::class,

                UrlBasedCollectionStrategy::class   => UrlBasedCollectionStrategy::class,
                UrlBasedResourceStrategy::class     => UrlBasedResourceStrategy::class
            ],
        ];
    }

    public function getHalConfig() : array
    {
        return [
            'resource-generator' => [
                'strategies' => [ // The registered strategies and their metadata types
                    RouteBasedCollectionMetadata::class => RouteBasedCollectionStrategy::class,
                    RouteBasedResourceMetadata::class   => RouteBasedResourceStrategy::class,

                    UrlBasedCollectionMetadata::class   => UrlBasedCollectionStrategy::class,
                    UrlBasedResourceMetadata::class     => UrlBasedResourceStrategy::class,
                ],
            ],
            'metadata-factories' => [ // The factories for the metadata types
                RouteBasedCollectionMetadata::class => RouteBasedCollectionMetadataFactory::class,
                RouteBasedResourceMetadata::class   => RouteBasedResourceMetadataFactory::class,

                UrlBasedCollectionMetadata::class   => UrlBasedCollectionMetadataFactory::class,
                UrlBasedResourceMetadata::class     => UrlBasedResourceMetadataFactory::class,
            ],
        ];
    }
}
