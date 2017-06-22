<?php

namespace HalTest;

use Hal\InvalidObjectException;
use Hal\Link;
use Hal\LinkGenerator;
use Hal\Metadata;
use Hal\Resource;
use Hal\ResourceGenerator;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Hydrator\ObjectProperty as ObjectPropertyHydrator;

/**
 * @todo Create tests for cases where resources embed other resources.
 */
class ResourceGeneratorTest extends TestCase
{
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
        $this->assertInstanceOf(Resource::class, $resource);

        $self = $resource->getLinksByRel('self');
        $this->assertInternalType('array', $self);
        $this->assertCount(1, $self);
        $self = array_shift($self);
        $this->assertInstanceOf(Link::class, $self);
        $this->assertEquals('/api/example', $self->getHref());

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

        $this->assertInstanceOf(Resource::class, $resource);

        $self = $resource->getLinksByRel('self');
        $this->assertInternalType('array', $self);
        $this->assertCount(1, $self);
        $self = array_shift($self);
        $this->assertInstanceOf(Link::class, $self);
        $this->assertEquals('/api/foo/XXXX-YYYY-ZZZZ', $self->getHref());

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

        $this->assertInstanceOf(Resource::class, $resource);

        $self = $resource->getLinksByRel('self');
        $this->assertInternalType('array', $self);
        $this->assertCount(1, $self);
        $self = array_shift($self);
        $this->assertInstanceOf(Link::class, $self);
        $this->assertEquals('/api/foo-bar/XXXX-YYYY-ZZZZ', $self->getHref());

        $this->assertEquals([
            'id'  => 'XXXX-YYYY-ZZZZ',
            'foo' => 'BAR',
            'bar' => 'BAZ',
        ], $resource->getElements());
    }

    /**
     * @todo Need to determine what a use case looks like, exactly.
     */
    public function testCanGenerateUrlBasedCollectionFromObjectDefinedInMetadataMap()
    {
        $this->markTestIncomplete();
    }

    /**
     * @todo Need to determine what a use case looks like, exactly.
     */
    public function testCanGenerateRouteBasedCollectionFromObjectDefinedInMetadataMap()
    {
        $this->markTestIncomplete();
    }

    public function testGeneratorRaisesExceptionForNonObjectType()
    {
        $this->expectException(InvalidObjectException::class);
        $this->expectExceptionMessage('non-object');
        $this->generator->fromObject('foo', $this->request->reveal());
    }

    public function testGeneratorRaisesExceptionForUnknownObjectType()
    {
        $this->expectException(InvalidObjectException::class);
        $this->expectExceptionMessage('not in metadata map');
        $this->generator->fromObject($this, $this->request->reveal());
    }
}
