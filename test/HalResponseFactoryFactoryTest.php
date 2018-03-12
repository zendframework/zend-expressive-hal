<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Expressive\Hal;

use Closure;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use ReflectionProperty;
use Zend\Expressive\Hal\HalResponseFactoryFactory;
use Zend\Expressive\Hal\HalResponseFactory;
use Zend\Expressive\Hal\Renderer;

class HalResponseFactoryFactoryTest extends TestCase
{
    public static function assertResponseFactoryReturns(ResponseInterface $expected, HalResponseFactory $factory) : void
    {
        $r = new ReflectionProperty($factory, 'responseFactory');
        $r->setAccessible(true);
        $responseFactory = $r->getValue($factory);
        Assert::assertSame($expected, $responseFactory());
    }

    public function testReturnsHalResponseFactoryInstance() : void
    {
        $jsonRenderer = $this->prophesize(Renderer\JsonRenderer::class)->reveal();
        $xmlRenderer = $this->prophesize(Renderer\XmlRenderer::class)->reveal();
        $response = $this->prophesize(ResponseInterface::class)->reveal();
        $responseFactory = function () use ($response) {
            return $response;
        };

        $container = $this->prophesize(ContainerInterface::class);
        $container->get(ResponseInterface::class)->willReturn($responseFactory);
        $container->has(Renderer\JsonRenderer::class)->willReturn(true);
        $container->get(Renderer\JsonRenderer::class)->willReturn($jsonRenderer);
        $container->has(Renderer\XmlRenderer::class)->willReturn(true);
        $container->get(Renderer\XmlRenderer::class)->willReturn($xmlRenderer);

        $instance = (new HalResponseFactoryFactory())($container->reveal());
        self::assertInstanceOf(HalResponseFactory::class, $instance);
        self::assertAttributeSame($jsonRenderer, 'jsonRenderer', $instance);
        self::assertAttributeSame($xmlRenderer, 'xmlRenderer', $instance);
        self::assertResponseFactoryReturns($response, $instance);
    }


    public function testReturnsHalResponseFactoryInstanceWithoutConfiguredDependencies() : void
    {
        $response = $this->prophesize(ResponseInterface::class)->reveal();
        $responseFactory = function () use ($response) {
            return $response;
        };
        $container = $this->prophesize(ContainerInterface::class);
        $container->get(ResponseInterface::class)->willReturn($responseFactory);
        $container->has(Renderer\JsonRenderer::class)->willReturn(false);
        $container->has(Renderer\XmlRenderer::class)->willReturn(false);

        $instance = (new HalResponseFactoryFactory())($container->reveal());
        self::assertInstanceOf(HalResponseFactory::class, $instance);
        self::assertAttributeInstanceOf(Renderer\JsonRenderer::class, 'jsonRenderer', $instance);
        self::assertAttributeInstanceOf(Renderer\XmlRenderer::class, 'xmlRenderer', $instance);
        self::assertResponseFactoryReturns($response, $instance);
    }

    public function testReturnsHalResponseFactoryInstanceWhenResponseInterfaceReturnsFactory()
    {
        $jsonRenderer = $this->prophesize(Renderer\JsonRenderer::class)->reveal();
        $xmlRenderer = $this->prophesize(Renderer\XmlRenderer::class)->reveal();
        $response = $this->prophesize(ResponseInterface::class)->reveal();
        $responseFactory = function () use ($response) {
            return $response;
        };
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
        $container->get(ResponseInterface::class)->willReturn($responseFactory);
        $container->has(StreamInterface::class)->willReturn(true);
        $container->get(StreamInterface::class)->willReturn($stream);

        $instance = (new HalResponseFactoryFactory())($container->reveal());
        self::assertInstanceOf(HalResponseFactory::class, $instance);
        self::assertAttributeSame($jsonRenderer, 'jsonRenderer', $instance);
        self::assertAttributeSame($xmlRenderer, 'xmlRenderer', $instance);
        self::assertAttributeSame($stream, 'streamFactory', $instance);
    }
}
