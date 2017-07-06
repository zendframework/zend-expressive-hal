<?php

namespace HalTest\ResourceGenerator;

use Hal\HalResource;
use Hal\Link;
use Hal\LinkGenerator;
use Hal\Metadata\MetadataMap;
use Hal\Metadata\RouteBasedCollectionMetadata;
use Hal\Metadata\RouteBasedResourceMetadata;
use Hal\ResourceGenerator;
use HalTest\Assertions;
use HalTest\TestAsset;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Hydrator\ObjectProperty as ObjectPropertyHydrator;
use Zend\Paginator\Adapter\ArrayAdapter;
use Zend\Paginator\Paginator;

class ResourceWithNestedInstancesTest extends TestCase
{
    use Assertions;

    public function testNestedObjectInMetadataMapIsEmbeddedAsResource()
    {
        $child = new TestAsset\Child;
        $child->id = 9876;
        $child->message = 'ack';

        $parent = new TestAsset\FooBar;
        $parent->id = 1234;
        $parent->foo = 'FOO';
        $parent->bar = $child;

        $request = $this->prophesize(ServerRequestInterface::class);

        $metadataMap = $this->createMetadataMap();
        $hydrators = $this->createHydrators();
        $linkGenerator = $this->createLinkGenerator($request);

        $generator = new ResourceGenerator(
            $metadataMap->reveal(),
            $hydrators->reveal(),
            $linkGenerator->reveal()
        );

        $resource = $generator->fromObject($parent, $request->reveal());
        $this->assertInstanceOf(HalResource::class, $resource);

        $childResource = $resource->getElement('bar');
        $this->assertInstanceOf(HalResource::class, $childResource);
        $this->assertEquals($child->id, $childResource->getElement('id'));
        $this->assertEquals($child->message, $childResource->getElement('message'));
    }

    public function createMetadataMap()
    {
        $metadataMap = $this->prophesize(MetadataMap::class);

        $fooBarMetadata = new RouteBasedResourceMetadata(
            TestAsset\FooBar::class,
            'foo-bar',
            ObjectPropertyHydrator::class
        );

        $metadataMap->has(TestAsset\FooBar::class)->willReturn(true);
        $metadataMap->get(TestAsset\FooBar::class)->willReturn($fooBarMetadata);

        $childMetadata = new RouteBasedResourceMetadata(
            TestAsset\Child::class,
            'child',
            ObjectPropertyHydrator::class
        );

        $metadataMap->has(TestAsset\Child::class)->willReturn(true);
        $metadataMap->get(TestAsset\Child::class)->willReturn($childMetadata);

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

        $linkGenerator
            ->fromRoute(
                'self',
                $request->reveal(),
                'child',
                [ 'id' => 9876 ]
            )
            ->willReturn(new Link('self', '/api/child/9876'));

        return $linkGenerator;
    }

    public function createHydrators()
    {
        $hydrators = $this->prophesize(ContainerInterface::class);
        $hydrators->get(ObjectPropertyHydrator::class)->willReturn(new ObjectPropertyHydrator());
        return $hydrators;
    }
}
