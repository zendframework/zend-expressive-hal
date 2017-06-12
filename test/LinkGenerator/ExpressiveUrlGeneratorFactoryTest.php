<?php

namespace HalTest\LinkGenerator;

use Hal\LinkGenerator\ExpressiveUrlGenerator;
use Hal\LinkGenerator\ExpressiveUrlGeneratorFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Zend\Expressive\Helper\ServerUrlHelper;
use Zend\Expressive\Helper\UrlHelper;

class ExpressiveUrlGeneratorFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
    }

    public function testFactoryRaisesExceptionIfUrlHelperIsMissingFromContainer()
    {
        $this->container->has(UrlHelper::class)->willReturn(false);
        $this->container->get(UrlHelper::class)->shouldNotBeCalled();
        $this->container->has(ServerUrlHelper::class)->shouldNotBeCalled();

        $factory = new ExpressiveUrlGeneratorFactory();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(UrlHelper::class);
        $factory($this->container->reveal());
    }

    public function testFactoryCanCreateUrlGeneratorWithOnlyUrlHelperPresentInContainer()
    {
        $urlHelper = $this->prophesize(UrlHelper::class)->reveal();

        $this->container->has(UrlHelper::class)->willReturn(true);
        $this->container->get(UrlHelper::class)->willReturn($urlHelper);
        $this->container->has(ServerUrlHelper::class)->willReturn(false);
        $this->container->get(ServerUrlHelper::class)->shouldNotBeCalled();

        $factory = new ExpressiveUrlGeneratorFactory();
        $generator = $factory($this->container->reveal());

        $this->assertInstanceOf(ExpressiveUrlGenerator::class, $generator);
        $this->assertAttributeSame($urlHelper, 'urlHelper', $generator);
    }

    public function testFactoryCanCreateUrlGeneratorWithBothUrlHelperAndServerUrlHelper()
    {
        $urlHelper = $this->prophesize(UrlHelper::class)->reveal();
        $serverUrlHelper = $this->prophesize(ServerUrlHelper::class)->reveal();

        $this->container->has(UrlHelper::class)->willReturn(true);
        $this->container->get(UrlHelper::class)->willReturn($urlHelper);
        $this->container->has(ServerUrlHelper::class)->willReturn(true);
        $this->container->get(ServerUrlHelper::class)->willReturn($serverUrlHelper);

        $factory = new ExpressiveUrlGeneratorFactory();
        $generator = $factory($this->container->reveal());

        $this->assertInstanceOf(ExpressiveUrlGenerator::class, $generator);
        $this->assertAttributeSame($urlHelper, 'urlHelper', $generator);
        $this->assertAttributeSame($serverUrlHelper, 'serverUrlHelper', $generator);
    }
}
