<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Expressive\Hal;

use Closure;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Zend\Expressive\Hal\HalResponseFactoryFactory;
use Zend\Expressive\Hal\HalResponseFactory;
use Zend\Expressive\Hal\Renderer;

class HalResponseFactoryFactoryTest extends TestCase
{
    public function testReturnsHalResponseFactoryInstance() : void
    {
        $jsonRenderer = $this->prophesize(Renderer\JsonRenderer::class)->reveal();
        $xmlRenderer = $this->prophesize(Renderer\XmlRenderer::class)->reveal();
        $response = $this->prophesize(ResponseInterface::class)->reveal();
        $stream = new class()
        {
            public function __invoke()
            {
            }
        };

        $container = $this->prophesize(ContainerInterface::class);
        $container->has(Renderer\JsonRenderer::class)->willReturn(true);
        $container->get(Renderer\JsonRenderer::class)->willReturn($jsonRenderer);
        $container->has(Renderer\XmlRenderer::class)->willReturn(true);
        $container->get(Renderer\XmlRenderer::class)->willReturn($xmlRenderer);
        $container->has(ResponseInterface::class)->willReturn(true);
        $container->get(ResponseInterface::class)->willReturn($response);
        $container->has(StreamInterface::class)->willReturn(true);
        $container->get(StreamInterface::class)->willReturn($stream);

        $instance = (new HalResponseFactoryFactory())($container->reveal());
        self::assertInstanceOf(HalResponseFactory::class, $instance);
        self::assertAttributeSame($jsonRenderer, 'jsonRenderer', $instance);
        self::assertAttributeSame($xmlRenderer, 'xmlRenderer', $instance);
        self::assertAttributeSame($response, 'responsePrototype', $instance);
        self::assertAttributeSame($stream, 'streamFactory', $instance);
    }


    public function testReturnsHalResponseFactoryInstanceWithoutConfiguredDependencies() : void
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->has(Renderer\JsonRenderer::class)->willReturn(false);
        $container->has(Renderer\XmlRenderer::class)->willReturn(false);
        $container->has(ResponseInterface::class)->willReturn(false);
        $container->has(StreamInterface::class)->willReturn(false);

        $instance = (new HalResponseFactoryFactory())($container->reveal());
        self::assertInstanceOf(HalResponseFactory::class, $instance);
        self::assertAttributeInstanceOf(Renderer\JsonRenderer::class, 'jsonRenderer', $instance);
        self::assertAttributeInstanceOf(Renderer\XmlRenderer::class, 'xmlRenderer', $instance);
        self::assertAttributeInstanceOf(ResponseInterface::class, 'responsePrototype', $instance);
        self::assertAttributeInstanceOf(Closure::class, 'streamFactory', $instance);
    }
}
