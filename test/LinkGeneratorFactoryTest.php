<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Expressive\Hal;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Zend\Expressive\Hal\LinkGenerator;
use Zend\Expressive\Hal\LinkGeneratorFactory;

class LinkGeneratorFactoryTest extends TestCase
{
    public function testReturnsLinkGeneratorInstance() : void
    {
        $urlGenerator = $this->prophesize(LinkGenerator\UrlGeneratorInterface::class)->reveal();

        $container = $this->prophesize(ContainerInterface::class);
        $container->get(LinkGenerator\UrlGeneratorInterface::class)->willReturn($urlGenerator);

        $instance = (new LinkGeneratorFactory())($container->reveal());
        self::assertInstanceOf(LinkGenerator::class, $instance);
        self::assertAttributeSame($urlGenerator, 'urlGenerator', $instance);
    }
}
