<?php

namespace Hal\LinkGenerator;

use Psr\Container\ContainerInterface;
use RuntimeException;
use Zend\Expressive\Helper\ServerUrlHelper;
use Zend\Expressive\Helper\UrlHelper;

class ExpressiveUrlGeneratorFactory
{
    public function __invoke(ContainerInterface $container) : ExpressiveUrlGenerator
    {
        if (! $container->has(UrlHelper::class)) {
            throw new RuntimeException(sprintf(
                '%s requires a %s in order to generate a %s instance; none found',
                __CLASS__,
                UrlHelper::class,
                ExpressiveUrlGenerator::class
            ));
        }

        return new ExpressiveUrlGenerator(
            $container->get(UrlHelper::class),
            $container->has(ServerUrlHelper::class) ? $container->get(ServerUrlHelper::class) : null
        );
    }
}
