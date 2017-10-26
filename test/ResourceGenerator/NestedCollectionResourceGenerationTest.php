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
use Zend\Hydrator\ObjectProperty as ObjectPropertyHydrator;
use Zend\Paginator\Adapter\ArrayAdapter;
use Zend\Paginator\Paginator;
use ZendTest\Expressive\Hal\Assertions;
use ZendTest\Expressive\Hal\TestAsset;

class NestedCollectionResourceGenerationTest extends TestCase
{
    use Assertions;

    public function testNestedCollectionIsEmbeddedAsAnArrayNotAHalCollection()
    {
        $collection = $this->createCollection();
        $foo = new TestAsset\FooBar;
        $foo->id = 101010;
        $foo->foo = 'foo';
        $foo->children = $collection;

        $request = $this->prophesize(ServerRequestInterface::class);
        $metadataMap = $this->createMetadataMap();
        $hydrators = $this->createHydrators();
        $linkGenerator = $this->createLinkGenerator($request);

        $generator = new ResourceGenerator(
            $metadataMap->reveal(),
            $hydrators->reveal(),
            $linkGenerator->reveal()
        );

        $resource = $generator->fromObject($foo, $request->reveal());
        $this->assertInstanceOf(HalResource::class, $resource);

        $childCollection = $resource->getElement('children');
        $this->assertInternalType('array', $childCollection);

        foreach ($childCollection as $child) {
            $this->assertInstanceOf(HalResource::class, $child);
            $selfLinks = $child->getLinksByRel('self');
            $this->assertInternalType('array', $selfLinks);
            $this->assertNotEmpty($selfLinks);
            $selfLink = array_shift($selfLinks);
            $this->assertContains('/child/', $selfLink->getHref());
        }
    }

    private function createCollection() : TestAsset\Collection
    {
        $items = [];
        for ($i = 1; $i < 11; $i += 1) {
            $item = new TestAsset\Child;
            $item->id = $i;
            $item->message = 'ack';
            $items[] = $item;
        }
        return new TestAsset\Collection($items);
    }

    private function createMetadataMap()
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

        $collectionMetadata = new RouteBasedCollectionMetadata(
            TestAsset\Collection::class,
            'items',
            'collection'
        );

        $metadataMap->has(TestAsset\Collection::class)->willReturn(true);
        $metadataMap->get(TestAsset\Collection::class)->willReturn($collectionMetadata);

        return $metadataMap;
    }

    private function createHydrators()
    {
        $hydrators = $this->prophesize(ContainerInterface::class);
        $hydrators->get(ObjectPropertyHydrator::class)->willReturn(new ObjectPropertyHydrator());
        return $hydrators;
    }

    public function createLinkGenerator($request)
    {
        $linkGenerator = $this->prophesize(LinkGenerator::class);

        $linkGenerator
            ->fromRoute(
                'self',
                $request->reveal(),
                'foo-bar',
                [ 'id' => 101010 ]
            )
            ->willReturn(new Link('self', '/api/foo-bar/1234'));

        for ($i = 1; $i < 11; $i += 1) {
            $linkGenerator
                ->fromRoute(
                    'self',
                    $request->reveal(),
                    'child',
                    [ 'id' => $i ]
                )
                ->willReturn(new Link('self', '/api/child/' . $i));
        }

        $linkGenerator
            ->fromRoute(
                'self',
                $request->reveal(),
                'collection'
            )
            ->shouldNotBeCalled();

        return $linkGenerator;
    }
}
