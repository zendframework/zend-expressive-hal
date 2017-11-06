<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Hal;

use Psr\Container\ContainerInterface;
use Zend\Hydrator\HydratorPluginManager;

class ResourceGeneratorFactory
{
    public function __invoke(ContainerInterface $container) : ResourceGenerator
    {
        $generator = new ResourceGenerator(
            $container->get(Metadata\MetadataMap::class),
            $container->get(HydratorPluginManager::class),
            $container->get(LinkGenerator::class)
        );

        $config = $container->get('config');
        if (!empty($config['zend-expressive-hal']['resource-generator']['strategies'])) {
            foreach ($config['zend-expressive-hal']['resource-generator']['strategies'] as $metadataType => $strategy) {
                $generator->addStrategy(
                    $metadataType,
                    $container->get($strategy)
                );
            }
        }

        return $generator;
    }
}
