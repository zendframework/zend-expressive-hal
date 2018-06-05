<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Expressive\Hal\LinkGenerator;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Zend\Expressive\Hal\LinkGenerator\ExpressiveUrlGenerator;
use Zend\Expressive\Hal\LinkGenerator\ExpressiveUrlGeneratorFactory;
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

    public function testFactoryCanAcceptUrlHelperServiceNameToConstructor()
    {
        $urlHelper = $this->prophesize(UrlHelper::class)->reveal();

        $this->container->has(CustomUrlHelper::class)->willReturn(true);
        $this->container->get(CustomUrlHelper::class)->willReturn($urlHelper);
        $this->container->has(ServerUrlHelper::class)->willReturn(false);

        $factory = new ExpressiveUrlGeneratorFactory(CustomUrlHelper::class);
        $generator = $factory($this->container->reveal());

        $this->assertInstanceOf(ExpressiveUrlGenerator::class, $generator);
        $this->assertAttributeSame($urlHelper, 'urlHelper', $generator);
        $this->assertAttributeEmpty('serverUrlHelper', $generator);
    }

    public function testFactoryIsSerializable()
    {
        $factory = ExpressiveUrlGeneratorFactory::__set_state([
            'urlHelperServiceName' => CustomUrlHelper::class,
        ]);

        $this->assertInstanceOf(ExpressiveUrlGeneratorFactory::class, $factory);
        $this->assertAttributeSame(CustomUrlHelper::class, 'urlHelperServiceName', $factory);
    }
}
