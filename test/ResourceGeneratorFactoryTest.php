<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Expressive\Hal;

use ArrayObject;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use stdClass;
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
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn(new stdClass());

        $object = new ResourceGeneratorFactory();

        $this->expectException(ResourceGenerator\Exception\InvalidConfigException::class);
        $this->expectExceptionMessage('expected an array');
        $object($this->container->reveal());
    }

    public function missingOrEmptyStrategiesConfiguration()
    {
        yield 'missing-top-level' => [[]];
        yield 'missing-second-level' => [[
            'zend-expressive-hal' => [],
        ]];
        yield 'missing-third-level' => [[
            'zend-expressive-hal' => [
                'resource-generator' => [],
            ],
        ]];
        yield 'empty-array' => [[
            'zend-expressive-hal' => [
                'resource-generator' => [
                    'strategies' => [],
                ],
            ],
        ]];
        yield 'empty-array-object' => [[
            'zend-expressive-hal' => [
                'resource-generator' => [
                    'strategies' => new ArrayObject([]),
                ],
            ],
        ]];
    }

    /**
     * @depends missingOrEmptyStrategiesConfiguration
     */
    public function testFactoryWithoutAnyStrategies(array $config)
    {
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn($config);

        $object = new ResourceGeneratorFactory();

        $resourceGenerator = $object($this->container->reveal());
        self::assertInstanceOf(ResourceGenerator::class, $resourceGenerator);
        self::assertEmpty($resourceGenerator->getStrategies());
    }

    public function invalidStrategiesConfig()
    {
        yield 'null'       => [null];
        yield 'false'      => [false];
        yield 'true'       => [true];
        yield 'zero'       => [0];
        yield 'int'        => [1];
        yield 'zero-float' => [0.0];
        yield 'float'      => [1.1];
        yield 'string'     => ['invalid'];
        yield 'object'     => [(object) ['item' => 'invalid']];
    }

    /**
     * @depends invalidStrategiesConfig
     * @param mixed $strategies
     */
    public function testFactoryRaisesExceptionIfStrategiesConfigIsNonTraversable($strategies)
    {
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn([
            'zend-expressive-hal' => [
                'resource-generator' => [
                    'strategies' => $strategies,
                ],
            ],
        ]);

        $factory = new ResourceGeneratorFactory();

        $this->expectException(ResourceGenerator\Exception\InvalidConfigException::class);
        $this->expectExceptionMessage('strategies configuration');
        $factory($this->container->reveal());
    }

    public function testFactoryWithRouteBasedCollectionStrategy()
    {
        $this->container->has('config')->willReturn(true);
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
            $this->prophesize(RouteBasedCollectionStrategy::class)->reveal()
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

    public function testConstructorAllowsSpecifyingLinkGeneratorServiceName()
    {
        $container = $this->prophesize(ContainerInterface::class);

        $container
            ->get(Metadata\MetadataMap::class)
            ->willReturn($this->prophesize(Metadata\MetadataMap::class)->reveal());

        $container
            ->get(HydratorPluginManager::class)
            ->willReturn($this->prophesize(ContainerInterface::class)->reveal());

        $linkGenerator = $this->prophesize(LinkGenerator::class)->reveal();
        $container
            ->get(CustomLinkGenerator::class)
            ->willReturn($linkGenerator);

        $container->has('config')->willReturn(false);

        $factory = new ResourceGeneratorFactory(CustomLinkGenerator::class);

        $generator = $factory($container->reveal());

        $this->assertInstanceOf(ResourceGenerator::class, $generator);
        $this->assertAttributeSame($linkGenerator, 'linkGenerator', $generator);
    }

    public function testFactoryIsSerializable()
    {
        $factory = ResourceGeneratorFactory::__set_state([
            'linkGeneratorServiceName' => CustomLinkGenerator::class,
        ]);

        $this->assertInstanceOf(ResourceGeneratorFactory::class, $factory);
        $this->assertAttributeSame(CustomLinkGenerator::class, 'linkGeneratorServiceName', $factory);
    }
}
