<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Hal;

use Psr\Container\ContainerInterface;

class LinkGeneratorFactory
{
    public function __invoke(ContainerInterface $container) : LinkGenerator
    {
        return new LinkGenerator(
            $container->get(LinkGenerator\UrlGeneratorInterface::class)
        );
    }
}
