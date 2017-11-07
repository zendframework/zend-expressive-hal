<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Expressive\Hal;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Zend\Expressive\Hal\LinkGenerator;
use Zend\Expressive\Hal\Metadata;
use Zend\Expressive\Hal\Metadata\RouteBasedCollectionMetadata;
use Zend\Expressive\Hal\ResourceGenerator;
use Zend\Expressive\Hal\ResourceGenerator\RouteBasedCollectionStrategy;
use Zend\Expressive\Hal\ResourceGeneratorFactory;
use Zend\Hydrator\HydratorPluginManager;

class ResourceGeneratorFactoryTest extends TestCase
{
    /**
     * @var ObjectProphecy|ContainerInterface
     */
    private $container;

    public function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);

        $this->container->get(Metadata\MetadataMap::class)
            ->willReturn($this->prophesize(Metadata\MetadataMap::class));

        $this->container->get(HydratorPluginManager::class)
            ->willReturn($this->prophesize(ContainerInterface::class));

        $this->container->get(LinkGenerator::class)
            ->willReturn($this->prophesize(LinkGenerator::class));
    }

    public function testFactoryRaisesExceptionIfMetadataMapConfigIsNotAnArray()
    {
        $this->container->get('config')->willReturn(new \stdClass());

        $object = new ResourceGeneratorFactory();

        $this->expectException(ResourceGenerator\Exception\InvalidConfigException::class);
        $this->expectExceptionMessage('expected an array');
        $object($this->container->reveal());
    }

    public function testFactoryWithoutAnyStrategies()
    {
        $this->container->get('config')->willReturn(
            [
                'zend-expressive-hal' => [
                    'resource-generator' => [
                        'strategies' => [],
                    ],
                ],
            ]
        );

        $object = new ResourceGeneratorFactory();

        $resourceGenerator = $object($this->container->reveal());
        self::assertInstanceOf(ResourceGenerator::class, $resourceGenerator);
        self::assertEmpty($resourceGenerator->getStrategies());
    }

    public function testFactoryWithRouteBasedCollectionStrategy()
    {
        $this->container->get('config')->willReturn(
            [
                'zend-expressive-hal' => [
                    'resource-generator' => [
                        'strategies' => [
                            RouteBasedCollectionMetadata::class => RouteBasedCollectionStrategy::class,
                        ],
                    ],
                ],
            ]
        );

        $this->container->get(RouteBasedCollectionStrategy::class)->willReturn(
            $this->prophesize(RouteBasedCollectionStrategy::class)
        );

        $object = new ResourceGeneratorFactory();

        $resourceGenerator = $object($this->container->reveal());
        self::assertInstanceOf(ResourceGenerator::class, $resourceGenerator);

        $registeredStrategies = $resourceGenerator->getStrategies();
        self::assertCount(1, $registeredStrategies);
        self::assertArrayHasKey(RouteBasedCollectionMetadata::class, $registeredStrategies);
        self::assertInstanceOf(
            RouteBasedCollectionStrategy::class,
            $registeredStrategies[RouteBasedCollectionMetadata::class]
        );
    }
}
