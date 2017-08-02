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
