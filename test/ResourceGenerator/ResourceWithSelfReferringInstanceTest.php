<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Expressive\Hal\ResourceGenerator;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Expressive\Hal\HalResource;
use Zend\Expressive\Hal\Link;
use Zend\Expressive\Hal\LinkGenerator;
use Zend\Expressive\Hal\Metadata\MetadataMap;
use Zend\Expressive\Hal\Metadata\RouteBasedCollectionMetadata;
use Zend\Expressive\Hal\Metadata\RouteBasedResourceMetadata;
use Zend\Expressive\Hal\ResourceGenerator;
use ZendTest\Expressive\Hal\Assertions;
use ZendTest\Expressive\Hal\TestAsset;

class ResourceWithSelfReferringInstanceTest extends TestCase
{
    use Assertions;

    public function testSelfReferringIsEmbeddedAsResource()
    {
        $parent = new TestAsset\FooBar;
        $parent->id = 1234;
        $parent->foo = 'FOO';
        $parent->bar = $parent;

        $request = $this->prophesize(ServerRequestInterface::class);

        $metadataMap = $this->createMetadataMap();
        $hydrators = $this->createHydrators();
        $linkGenerator = $this->createLinkGenerator($request);

        $generator = new ResourceGenerator(
            $metadataMap->reveal(),
            $hydrators->reveal(),
            $linkGenerator->reveal()
        );

        $generator->addStrategy(
            RouteBasedResourceMetadata::class,
            ResourceGenerator\RouteBasedResourceStrategy::class
        );

        $generator->addStrategy(
            RouteBasedCollectionMetadata::class,
            ResourceGenerator\RouteBasedCollectionStrategy::class
        );

        $resource = $generator->fromObject($parent, $request->reveal());
        $this->assertInstanceOf(HalResource::class, $resource);

        $childResource = $resource->getElement('bar');
        $this->assertInstanceOf(HalResource::class, $childResource);
        $this->assertCount(0, $childResource->getElements());
    }

    public function createMetadataMap()
    {
        $metadataMap = $this->prophesize(MetadataMap::class);

        $fooBarMetadata = new RouteBasedResourceMetadata(
            TestAsset\FooBar::class,
            'foo-bar',
            self::getObjectPropertyHydratorClass(),
            'id',
            'id',
            [],
            0
        );

        $metadataMap->has(TestAsset\FooBar::class)->willReturn(true);
        $metadataMap->get(TestAsset\FooBar::class)->willReturn($fooBarMetadata);

        return $metadataMap;
    }

    public function createLinkGenerator($request)
    {
        $linkGenerator = $this->prophesize(LinkGenerator::class);

        $linkGenerator
            ->fromRoute(
                'self',
                $request->reveal(),
                'foo-bar',
                [ 'id' => 1234 ]
            )
            ->willReturn(new Link('self', '/api/foo-bar/1234'));

        return $linkGenerator;
    }

    public function createHydrators()
    {
        $hydratorClass = self::getObjectPropertyHydratorClass();

        $hydrators = $this->prophesize(ContainerInterface::class);
        $hydrators->get($hydratorClass)->willReturn(new $hydratorClass());
        return $hydrators;
    }
}
