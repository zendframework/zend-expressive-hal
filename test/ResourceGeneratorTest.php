<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Expressive\Hal;

use ArrayIterator;
use Generator;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Expressive\Hal\Exception\InvalidObjectException;
use Zend\Expressive\Hal\HalResource;
use Zend\Expressive\Hal\Link;
use Zend\Expressive\Hal\LinkGenerator;
use Zend\Expressive\Hal\Metadata;
use Zend\Expressive\Hal\ResourceGenerator;
use Zend\Expressive\Hal\ResourceGenerator\Exception\OutOfBoundsException;
use Zend\Hydrator\ObjectProperty as ObjectPropertyHydrator;
use Zend\Paginator\Adapter\ArrayAdapter;
use Zend\Paginator\Paginator;

/**
 * @todo Create tests for cases where resources embed other resources.
 */
class ResourceGeneratorTest extends TestCase
{
    use Assertions;

    public function setUp()
    {
        $this->request = $this->prophesize(ServerRequestInterface::class);
        $this->hydrators = $this->prophesize(ContainerInterface::class);
        $this->linkGenerator = $this->prophesize(LinkGenerator::class);
        $this->metadataMap = $this->prophesize(Metadata\MetadataMap::class);
        $this->generator = new ResourceGenerator(
            $this->metadataMap->reveal(),
            $this->hydrators->reveal(),
            $this->linkGenerator->reveal()
        );
    }

    public function testCanGenerateResourceWithSelfLinkFromArrayData()
    {
        $data = [
            'foo' => 'bar',
            'bar' => 'baz',
        ];
        $this->linkGenerator->fromRoute()->shouldNotBeCalled();
        $this->metadataMap->has()->shouldNotBeCalled();

        $resource = $this->generator->fromArray($data, '/api/example');
        $this->assertInstanceOf(HalResource::class, $resource);

        $self = $this->getLinkByRel('self', $resource);
        $this->assertLink('self', '/api/example', $self);

        $this->assertEquals($data, $resource->getElements());
    }

    public function testCanGenerateUrlBasedResourceFromObjectDefinedInMetadataMap()
    {
        $instance      = new TestAsset\FooBar;
        $instance->id  = 'XXXX-YYYY-ZZZZ';
        $instance->foo = 'BAR';
        $instance->bar = 'BAZ';

        $metadata = new Metadata\UrlBasedResourceMetadata(
            TestAsset\FooBar::class,
            '/api/foo/XXXX-YYYY-ZZZZ',
            ObjectPropertyHydrator::class
        );

        $this->metadataMap->has(TestAsset\FooBar::class)->willReturn(true);
        $this->metadataMap->get(TestAsset\FooBar::class)->willReturn($metadata);

        $this->hydrators->get(ObjectPropertyHydrator::class)->willReturn(new ObjectPropertyHydrator());
        $this->linkGenerator->fromRoute()->shouldNotBeCalled();

        $resource = $this->generator->fromObject($instance, $this->request->reveal());

        $this->assertInstanceOf(HalResource::class, $resource);

        $self = $this->getLinkByRel('self', $resource);
        $this->assertLink('self', '/api/foo/XXXX-YYYY-ZZZZ', $self);

        $this->assertEquals([
            'id'  => 'XXXX-YYYY-ZZZZ',
            'foo' => 'BAR',
            'bar' => 'BAZ',
        ], $resource->getElements());
    }

    public function testCanGenerateRouteBasedResourceFromObjectDefinedInMetadataMap()
    {
        $instance      = new TestAsset\FooBar;
        $instance->id  = 'XXXX-YYYY-ZZZZ';
        $instance->foo = 'BAR';
        $instance->bar = 'BAZ';

        $metadata = new Metadata\RouteBasedResourceMetadata(
            TestAsset\FooBar::class,
            'foo-bar',
            ObjectPropertyHydrator::class,
            'id',
            'foo_bar_id',
            ['test' => 'param']
        );

        $this->metadataMap->has(TestAsset\FooBar::class)->willReturn(true);
        $this->metadataMap->get(TestAsset\FooBar::class)->willReturn($metadata);

        $this->hydrators->get(ObjectPropertyHydrator::class)->willReturn(new ObjectPropertyHydrator());
        $this->linkGenerator
            ->fromRoute(
                'self',
                $this->request->reveal(),
                'foo-bar',
                [
                    'foo_bar_id' => 'XXXX-YYYY-ZZZZ',
                    'test' => 'param',
                ]
            )
            ->willReturn(new Link('self', '/api/foo-bar/XXXX-YYYY-ZZZZ'));

        $resource = $this->generator->fromObject($instance, $this->request->reveal());

        $this->assertInstanceOf(HalResource::class, $resource);

        $self = $this->getLinkByRel('self', $resource);
        $this->assertLink('self', '/api/foo-bar/XXXX-YYYY-ZZZZ', $self);

        $this->assertEquals([
            'id'  => 'XXXX-YYYY-ZZZZ',
            'foo' => 'BAR',
            'bar' => 'BAZ',
        ], $resource->getElements());
    }

    public function testCanGenerateUrlBasedCollectionFromObjectDefinedInMetadataMap()
    {
        $first      = new TestAsset\FooBar;
        $first->id  = 'XXXX-YYYY-ZZZZ';
        $first->foo = 'BAR';
        $first->bar = 'BAZ';

        $second = clone $first;
        $second->id = 'XXXX-YYYY-ZZZA';
        $third = clone $first;
        $third->id = 'XXXX-YYYY-ZZZB';

        $resourceMetadata = new Metadata\UrlBasedResourceMetadata(
            TestAsset\FooBar::class,
            '/api/foo/XXXX-YYYY-ZZZZ',
            ObjectPropertyHydrator::class
        );

        $this->metadataMap->has(TestAsset\FooBar::class)->willReturn(true);
        $this->metadataMap->get(TestAsset\FooBar::class)->willReturn($resourceMetadata);

        $collectionMetadata = new Metadata\UrlBasedCollectionMetadata(
            ArrayIterator::class,
            'foo-bar',
            '/api/foo'
        );

        $this->metadataMap->has(ArrayIterator::class)->willReturn(true);
        $this->metadataMap->get(ArrayIterator::class)->willReturn($collectionMetadata);

        $collection = new ArrayIterator([$first, $second, $third]);

        $this->hydrators->get(ObjectPropertyHydrator::class)->willReturn(new ObjectPropertyHydrator());
        $this->linkGenerator->fromRoute()->shouldNotBeCalled();

        $resource = $this->generator->fromObject($collection, $this->request->reveal());

        $this->assertInstanceOf(HalResource::class, $resource);

        $self = $this->getLinkByRel('self', $resource);
        $this->assertLink('self', '/api/foo', $self);

        $this->assertEquals(3, $resource->getElement('_total_items'));

        $embedded = $resource->getElement('foo-bar');
        $this->assertInternalType('array', $embedded);
        $this->assertCount(3, $embedded);

        $ids = [];
        foreach ($embedded as $instance) {
            $this->assertInstanceOf(HalResource::class, $instance);
            $ids[] = $instance->getElement('id');

            $self = $this->getLinkByRel('self', $instance);
            $this->assertLink('self', '/api/foo/XXXX-YYYY-ZZZZ', $self);
        }

        $this->assertEquals([
            'XXXX-YYYY-ZZZZ',
            'XXXX-YYYY-ZZZA',
            'XXXX-YYYY-ZZZB',
        ], $ids);
    }

    public function testCanGenerateRouteBasedCollectionFromObjectDefinedInMetadataMap()
    {
        $instance      = new TestAsset\FooBar;
        $instance->foo = 'BAR';
        $instance->bar = 'BAZ';

        $resourceMetadata = new Metadata\RouteBasedResourceMetadata(
            TestAsset\FooBar::class,
            'foo-bar',
            ObjectPropertyHydrator::class,
            'id',
            'foo_bar_id',
            ['test' => 'param']
        );

        $this->metadataMap->has(TestAsset\FooBar::class)->willReturn(true);
        $this->metadataMap->get(TestAsset\FooBar::class)->willReturn($resourceMetadata);

        $instances = [];
        for ($i = 1; $i < 15; $i += 1) {
            $next = clone $instance;
            $next->id = $i;
            $instances[] = $next;

            $this->linkGenerator
                ->fromRoute(
                    'self',
                    $this->request->reveal(),
                    'foo-bar',
                    [
                        'foo_bar_id' => $i,
                        'test' => 'param',
                    ]
                )
                ->willReturn(new Link('self', '/api/foo-bar/' . $i));
        }

        $collectionMetadata = new Metadata\RouteBasedCollectionMetadata(
            Paginator::class,
            'foo-bar',
            'foo-bar'
        );

        $this->metadataMap->has(Paginator::class)->willReturn(true);
        $this->metadataMap->get(Paginator::class)->willReturn($collectionMetadata);

        $this->linkGenerator
            ->fromRoute(
                'self',
                $this->request->reveal(),
                'foo-bar',
                [],
                ['page' => 3]
            )
            ->willReturn(new Link('self', '/api/foo-bar?page=3'));
        $this->linkGenerator
            ->fromRoute(
                'first',
                $this->request->reveal(),
                'foo-bar',
                [],
                ['page' => 1]
            )
            ->willReturn(new Link('first', '/api/foo-bar?page=1'));
        $this->linkGenerator
            ->fromRoute(
                'prev',
                $this->request->reveal(),
                'foo-bar',
                [],
                ['page' => 2]
            )
            ->willReturn(new Link('prev', '/api/foo-bar?page=2'));
        $this->linkGenerator
            ->fromRoute(
                'next',
                $this->request->reveal(),
                'foo-bar',
                [],
                ['page' => 4]
            )
            ->willReturn(new Link('next', '/api/foo-bar?page=4'));
        $this->linkGenerator
            ->fromRoute(
                'last',
                $this->request->reveal(),
                'foo-bar',
                [],
                ['page' => 5]
            )
            ->willReturn(new Link('last', '/api/foo-bar?page=5'));

        $this->hydrators->get(ObjectPropertyHydrator::class)->willReturn(new ObjectPropertyHydrator());

        $this->request->getQueryParams()->willReturn(['page' => 3]);

        $collection = new Paginator(new ArrayAdapter($instances));
        $collection->setItemCountPerPage(3);

        $resource = $this->generator->fromObject($collection, $this->request->reveal());

        $this->assertInstanceOf(HalResource::class, $resource);

        $self = $this->getLinkByRel('self', $resource);
        $this->assertLink('self', '/api/foo-bar?page=3', $self);
        $first = $this->getLinkByRel('first', $resource);
        $this->assertLink('first', '/api/foo-bar?page=1', $first);
        $prev = $this->getLinkByRel('prev', $resource);
        $this->assertLink('prev', '/api/foo-bar?page=2', $prev);
        $next = $this->getLinkByRel('next', $resource);
        $this->assertLink('next', '/api/foo-bar?page=4', $next);
        $last = $this->getLinkByRel('last', $resource);
        $this->assertLink('last', '/api/foo-bar?page=5', $last);

        $this->assertEquals(14, $resource->getElement('_total_items'));
        $this->assertEquals(3, $resource->getElement('_page'));
        $this->assertEquals(5, $resource->getElement('_page_count'));

        $id = 7;
        foreach ($resource->getElement('foo-bar') as $item) {
            $self = $this->getLinkByRel('self', $item);
            $this->assertLink('self', '/api/foo-bar/' . $id, $self);

            $this->assertEquals($id, $item->getElement('id'));
            $id += 1;
        }
    }

    public function testGeneratedRouteBasedCollectionCastsPaginationMetadataToIntegers()
    {
        $instance      = new TestAsset\FooBar;
        $instance->foo = 'BAR';
        $instance->bar = 'BAZ';

        $resourceMetadata = new Metadata\RouteBasedResourceMetadata(
            TestAsset\FooBar::class,
            'foo-bar',
            ObjectPropertyHydrator::class,
            'id',
            'foo_bar_id',
            ['test' => 'param']
        );

        $this->metadataMap->has(TestAsset\FooBar::class)->willReturn(true);
        $this->metadataMap->get(TestAsset\FooBar::class)->willReturn($resourceMetadata);

        $instances = [];
        for ($i = 1; $i <= 5; $i += 1) {
            $next = clone $instance;
            $next->id = $i;
            $instances[] = $next;

            $this->linkGenerator
                ->fromRoute(
                    'self',
                    $this->request->reveal(),
                    'foo-bar',
                    [
                        'foo_bar_id' => $i,
                        'test' => 'param',
                    ]
                )
                ->willReturn(new Link('self', '/api/foo-bar/' . $i));
        }

        $collectionMetadata = new Metadata\RouteBasedCollectionMetadata(
            Paginator::class,
            'foo-bar',
            'foo-bar'
        );

        $this->metadataMap->has(Paginator::class)->willReturn(true);
        $this->metadataMap->get(Paginator::class)->willReturn($collectionMetadata);

        $this->linkGenerator
            ->fromRoute(
                'self',
                $this->request->reveal(),
                'foo-bar',
                [],
                ['page' => 3]
            )
            ->willReturn(new Link('self', '/api/foo-bar?page=3'));
        $this->linkGenerator
            ->fromRoute(
                'first',
                $this->request->reveal(),
                'foo-bar',
                [],
                ['page' => 1]
            )
            ->willReturn(new Link('first', '/api/foo-bar?page=1'));
        $this->linkGenerator
            ->fromRoute(
                'prev',
                $this->request->reveal(),
                'foo-bar',
                [],
                ['page' => 2]
            )
            ->willReturn(new Link('prev', '/api/foo-bar?page=2'));
        $this->linkGenerator
            ->fromRoute(
                'next',
                $this->request->reveal(),
                'foo-bar',
                [],
                ['page' => 4]
            )
            ->willReturn(new Link('next', '/api/foo-bar?page=4'));
        $this->linkGenerator
            ->fromRoute(
                'last',
                $this->request->reveal(),
                'foo-bar',
                [],
                ['page' => 5]
            )
            ->willReturn(new Link('last', '/api/foo-bar?page=5'));

        $this->hydrators->get(ObjectPropertyHydrator::class)->willReturn(new ObjectPropertyHydrator());

        $this->request->getQueryParams()->willReturn(['page' => '3']);

        $collection = new Paginator(new ArrayAdapter($instances));
        $collection->setItemCountPerPage(1);

        $resource = $this->generator->fromObject($collection, $this->request->reveal());

        $this->assertSame(5, $resource->getElement('_total_items'));
        $this->assertSame(3, $resource->getElement('_page'));
        $this->assertSame(5, $resource->getElement('_page_count'));
    }

    public function testGeneratorDoesNotAcceptPageQueryOutOfBounds()
    {
        $instance      = new TestAsset\FooBar;
        $instance->foo = 'BAR';
        $instance->bar = 'BAZ';

        $resourceMetadata = new Metadata\RouteBasedResourceMetadata(
            TestAsset\FooBar::class,
            'foo-bar',
            ObjectPropertyHydrator::class,
            'id',
            'foo_bar_id',
            ['test' => 'param']
        );

        $this->metadataMap->has(TestAsset\FooBar::class)->willReturn(true);
        $this->metadataMap->get(TestAsset\FooBar::class)->willReturn($resourceMetadata);

        $instances = [];
        for ($i = 1; $i < 15; $i += 1) {
            $next = clone $instance;
            $next->id = $i;
            $instances[] = $next;

            $this->linkGenerator
                ->fromRoute(
                    'self',
                    $this->request->reveal(),
                    'foo-bar',
                    [
                        'foo_bar_id' => $i,
                        'test' => 'param',
                    ]
                )
                ->willReturn(new Link('self', '/api/foo-bar/' . $i));
        }

        $collectionMetadata = new Metadata\RouteBasedCollectionMetadata(
            Paginator::class,
            'foo-bar',
            'foo-bar'
        );

        $this->metadataMap->has(Paginator::class)->willReturn(true);
        $this->metadataMap->get(Paginator::class)->willReturn($collectionMetadata);

        $this->request->getQueryParams()->willReturn(['page' => 10]);

        $collection = new Paginator(new ArrayAdapter($instances));
        $collection->setItemCountPerPage(3);

        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage('Page 10 is out of bounds. Collection has 5 pages.');
        $this->generator->fromObject($collection, $this->request->reveal());
    }

    public function testGeneratorDoesNotAcceptNegativePageQuery()
    {
        $instance      = new TestAsset\FooBar;
        $instance->foo = 'BAR';
        $instance->bar = 'BAZ';

        $resourceMetadata = new Metadata\RouteBasedResourceMetadata(
            TestAsset\FooBar::class,
            'foo-bar',
            ObjectPropertyHydrator::class,
            'id',
            'foo_bar_id',
            ['test' => 'param']
        );

        $this->metadataMap->has(TestAsset\FooBar::class)->willReturn(true);
        $this->metadataMap->get(TestAsset\FooBar::class)->willReturn($resourceMetadata);

        $instances = [];
        for ($i = 1; $i < 2; $i += 1) {
            $next = clone $instance;
            $next->id = $i;
            $instances[] = $next;

            $this->linkGenerator
                ->fromRoute(
                    'self',
                    $this->request->reveal(),
                    'foo-bar',
                    [
                        'foo_bar_id' => $i,
                        'test' => 'param',
                    ]
                )
                ->willReturn(new Link('self', '/api/foo-bar/' . $i));
        }

        $collectionMetadata = new Metadata\RouteBasedCollectionMetadata(
            Paginator::class,
            'foo-bar',
            'foo-bar'
        );

        $this->metadataMap->has(Paginator::class)->willReturn(true);
        $this->metadataMap->get(Paginator::class)->willReturn($collectionMetadata);

        $this->request->getQueryParams()->willReturn(['page' => -10]);

        $collection = new Paginator(new ArrayAdapter($instances));
        $collection->setItemCountPerPage(3);

        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage('Page -10 is out of bounds. Collection has 1 page.');
        $this->generator->fromObject($collection, $this->request->reveal());
    }

    public function testGeneratorAcceptsOnePageWhenCollectionHasNoEmbedded()
    {
        $instance      = new TestAsset\FooBar;
        $instance->foo = 'BAR';
        $instance->bar = 'BAZ';

        $resourceMetadata = new Metadata\RouteBasedResourceMetadata(
            TestAsset\FooBar::class,
            'foo-bar',
            ObjectPropertyHydrator::class,
            'id',
            'foo_bar_id',
            ['test' => 'param']
        );

        $this->metadataMap->has(TestAsset\FooBar::class)->willReturn(true);
        $this->metadataMap->get(TestAsset\FooBar::class)->willReturn($resourceMetadata);

        $this->linkGenerator
            ->fromRoute(
                'self',
                $this->request->reveal(),
                'foo-bar',
                [],
                ['page' => 1]
            )
            ->willReturn(new Link('self', '/api/foo-bar?page=3'));

        $instances = [];

        $collectionMetadata = new Metadata\RouteBasedCollectionMetadata(
            Paginator::class,
            'foo-bar',
            'foo-bar'
        );

        $this->metadataMap->has(Paginator::class)->willReturn(true);
        $this->metadataMap->get(Paginator::class)->willReturn($collectionMetadata);

        $collection = new Paginator(new ArrayAdapter($instances));
        $collection->setItemCountPerPage(3);

        $resource = $this->generator->fromObject($collection, $this->request->reveal());

        $this->assertEquals(0, $resource->getElement('_total_items'));
        $this->assertEquals(1, $resource->getElement('_page'));
        $this->assertEquals(0, $resource->getElement('_page_count'));
    }

    public function testGeneratorRaisesExceptionForNonObjectType()
    {
        $this->expectException(InvalidObjectException::class);
        $this->expectExceptionMessage('non-object');
        $this->generator->fromObject('foo', $this->request->reveal());
    }

    public function testGeneratorRaisesExceptionForUnknownObjectType()
    {
        $this->metadataMap->has(__CLASS__)->willReturn(false);
        $this->expectException(InvalidObjectException::class);
        $this->expectExceptionMessage('not in metadata map');
        $this->generator->fromObject($this, $this->request->reveal());
    }

    public function strategyCollection() : Generator
    {
        yield 'route-based-collection' => [
            new ResourceGenerator\RouteBasedCollectionStrategy(),
            Metadata\RouteBasedCollectionMetadata::class,
        ];

        yield 'url-based-collection' => [
            new ResourceGenerator\UrlBasedCollectionStrategy(),
            Metadata\UrlBasedCollectionMetadata::class,
        ];
    }

    public function strategyResource() : Generator
    {
        yield 'route-based-resource' => [
            new ResourceGenerator\RouteBasedResourceStrategy(),
        ];

        yield 'url-based-resource' => [
            new ResourceGenerator\UrlBasedResourceStrategy(),
        ];
    }

    /**
     * @dataProvider strategyCollection
     * @dataProvider strategyResource
     */
    public function testUnexpectedMetadataForStrategy(ResourceGenerator\StrategyInterface $strategy)
    {
        $this->generator->addStrategy(
            TestAsset\TestMetadata::class,
            $strategy
        );

        $collectionMetadata = new TestAsset\TestMetadata();

        $this->metadataMap->has(TestAsset\FooBar::class)->willReturn(true);
        $this->metadataMap->get(TestAsset\FooBar::class)->willReturn($collectionMetadata);

        $instance = new TestAsset\FooBar();

        $this->expectException(ResourceGenerator\Exception\UnexpectedMetadataTypeException::class);
        $this->expectExceptionMessage('Unexpected metadata of type');
        $this->generator->fromObject($instance, $this->request->reveal());
    }

    /**
     * @dataProvider strategyCollection
     */
    public function testNotTraversableInstanceForCollectionStrategy(
        ResourceGenerator\StrategyInterface $strategy,
        string $metadata
    ) {
        $collectionMetadata = new $metadata(
            TestAsset\FooBar::class,
            'foo-bar',
            '/api/foo'
        );

        $this->metadataMap->has(TestAsset\FooBar::class)->willReturn(true);
        $this->metadataMap->get(TestAsset\FooBar::class)->willReturn($collectionMetadata);

        $instance = new TestAsset\FooBar();

        $this->expectException(ResourceGenerator\Exception\InvalidCollectionException::class);
        $this->expectExceptionMessage('not a Traversable');
        $this->generator->fromObject($instance, $this->request->reveal());
    }
}
