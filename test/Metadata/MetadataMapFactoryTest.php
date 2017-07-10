<?php

namespace HalTest\Metadata;

use Hal\Metadata;
use Hal\Metadata\Exception\InvalidConfigException;
use Hal\Metadata\MetadataMap;
use Hal\Metadata\MetadataMapFactory;
use HalTest\TestAsset;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use stdClass;

class MetadataMapFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->factory = new MetadataMapFactory();
    }

    public function testFactoryReturnsEmptyMetadataMapWhenNoConfigServicePresent()
    {
        $this->container->has('config')->willReturn(false);
        $metadataMap = ($this->factory)($this->container->reveal());
        $this->assertInstanceOf(MetadataMap::class, $metadataMap);
        $this->assertAttributeSame([], 'map', $metadataMap);
    }

    public function testFactoryReturnsEmptyMetadataMapWhenConfigServiceHasNoMetadataMapEntries()
    {
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn([]);
        $metadataMap = ($this->factory)($this->container->reveal());
        $this->assertInstanceOf(MetadataMap::class, $metadataMap);
        $this->assertAttributeSame([], 'map', $metadataMap);
    }

    public function testFactoryRaisesExceptionIfMetadataMapConfigIsNotAnArray()
    {
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn([MetadataMap::class => 'nope']);
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('expected an array');
        ($this->factory)($this->container->reveal());
    }

    public function testFactoryRaisesExceptionIfMetadataMapItemIsNotAnArray()
    {
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn([MetadataMap::class => ['nope']]);
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('metadata item configuration');
        ($this->factory)($this->container->reveal());
    }

    public function testFactoryRaisesExceptionIfAnyMetadataIsMissingAClassEntry()
    {
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn([MetadataMap::class => [['nope']]]);
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('missing "__class__"');
        ($this->factory)($this->container->reveal());
    }

    public function testFactoryRaisesExceptionIfTheMetadataClassDoesNotExist()
    {
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn([MetadataMap::class => [[
            '__class__' => 'not-a-class',
        ]]]);
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Invalid metadata class provided');
        ($this->factory)($this->container->reveal());
    }

    public function testFactoryRaisesExceptionIfTheMetadataClassIsNotAnAbstractMetadataType()
    {
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn([MetadataMap::class => [[
            '__class__' => __CLASS__,
        ]]]);
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('does not extend ' . Metadata\AbstractMetadata::class);
        ($this->factory)($this->container->reveal());
    }

    public function testFactoryRaisesExceptionIfMetadataClassDoesNotHaveACreationMethodInTheFactory()
    {
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn([MetadataMap::class => [[
            '__class__' => TestAsset\TestMetadata::class,
        ]]]);
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('"createTestMetadata"');
        ($this->factory)($this->container->reveal());
    }

    public function invalidMetadata()
    {
        $types = [
            Metadata\UrlBasedResourceMetadata::class,
            Metadata\UrlBasedCollectionMetadata::class,
            Metadata\RouteBasedResourceMetadata::class,
            Metadata\RouteBasedCollectionMetadata::class,
        ];

        foreach ($types as $type) {
            yield $type => [['__class__' => $type], $type];
        }
    }

    /**
     * @dataProvider invalidMetadata
     */
    public function testFactoryRaisesExceptionIfMetadataIsMissingRequiredElements(array $metadata, $expectExceptionString)
    {
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn([MetadataMap::class => [$metadata]]);
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage($expectExceptionString);
        ($this->factory)($this->container->reveal());
    }

    public function testFactoryCanMapUrlBasedResourceMetadata()
    {
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn([MetadataMap::class => [[
            '__class__'      => Metadata\UrlBasedResourceMetadata::class,
            'resource_class' => stdClass::class,
            'url'            => '/test/foo',
            'extractor'      => 'ObjectProperty',
        ]]]);

        $metadataMap = ($this->factory)($this->container->reveal());
        $this->assertInstanceOf(MetadataMap::class, $metadataMap);
        $this->assertTrue($metadataMap->has(stdClass::class));
        $metadata = $metadataMap->get(stdClass::class);

        $this->assertInstanceOf(Metadata\UrlBasedResourceMetadata::class, $metadata);
        $this->assertSame(stdClass::class, $metadata->getClass());
        $this->assertSame('ObjectProperty', $metadata->getExtractor());
        $this->assertSame('/test/foo', $metadata->getUrl());
    }

    public function testFactoryCanMapUrlBasedCollectionMetadata()
    {
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn([MetadataMap::class => [[
            '__class__'             => Metadata\UrlBasedCollectionMetadata::class,
            'collection_class'      => stdClass::class,
            'collection_relation'   => 'foo',
            'url'                   => '/test/foo',
            'pagination_param'      => 'p',
            'pagination_param_type' => Metadata\AbstractCollectionMetadata::TYPE_PLACEHOLDER,
        ]]]);

        $metadataMap = ($this->factory)($this->container->reveal());
        $this->assertInstanceOf(MetadataMap::class, $metadataMap);
        $this->assertTrue($metadataMap->has(stdClass::class));
        $metadata = $metadataMap->get(stdClass::class);

        $this->assertInstanceOf(Metadata\UrlBasedCollectionMetadata::class, $metadata);
        $this->assertSame(stdClass::class, $metadata->getClass());
        $this->assertSame('foo', $metadata->getCollectionRelation());
        $this->assertSame('/test/foo', $metadata->getUrl());
        $this->assertSame('p', $metadata->getPaginationParam());
        $this->assertSame(Metadata\AbstractCollectionMetadata::TYPE_PLACEHOLDER, $metadata->getPaginationParamType());
    }

    public function testFactoryCanMapRouteBasedResourceMetadata()
    {
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn([MetadataMap::class => [[
            '__class__'                    => Metadata\RouteBasedResourceMetadata::class,
            'resource_class'               => stdClass::class,
            'route'                        => 'foo',
            'extractor'                    => 'ObjectProperty',
            'resource_identifier'          => 'foo_id',
            'route_identifier_placeholder' => 'foo_id',
            'route_params'                 => ['foo' => 'bar'],
        ]]]);

        $metadataMap = ($this->factory)($this->container->reveal());
        $this->assertInstanceOf(MetadataMap::class, $metadataMap);
        $this->assertTrue($metadataMap->has(stdClass::class));
        $metadata = $metadataMap->get(stdClass::class);

        $this->assertInstanceOf(Metadata\RouteBasedResourceMetadata::class, $metadata);
        $this->assertSame(stdClass::class, $metadata->getClass());
        $this->assertSame('ObjectProperty', $metadata->getExtractor());
        $this->assertSame('foo', $metadata->getRoute());
        $this->assertSame('foo_id', $metadata->getResourceIdentifier());
        $this->assertSame('foo_id', $metadata->getRouteIdentifierPlaceholder());
        $this->assertSame(['foo' => 'bar'], $metadata->getRouteParams());
    }

    public function testFactoryCanMapRouteBasedCollectionMetadata()
    {
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn([MetadataMap::class => [[
            '__class__'              => Metadata\RouteBasedCollectionMetadata::class,
            'collection_class'       => stdClass::class,
            'collection_relation'    => 'foo',
            'route'                  => 'foo',
            'pagination_param'       => 'p',
            'pagination_param_type'  => Metadata\AbstractCollectionMetadata::TYPE_PLACEHOLDER,
            'route_params'           => ['foo' => 'bar'],
            'query_string_arguments' => ['baz' => 'bat'],
        ]]]);

        $metadataMap = ($this->factory)($this->container->reveal());
        $this->assertInstanceOf(MetadataMap::class, $metadataMap);
        $this->assertTrue($metadataMap->has(stdClass::class));
        $metadata = $metadataMap->get(stdClass::class);

        $this->assertInstanceOf(Metadata\RouteBasedCollectionMetadata::class, $metadata);
        $this->assertSame(stdClass::class, $metadata->getClass());
        $this->assertSame('foo', $metadata->getCollectionRelation());
        $this->assertSame('foo', $metadata->getRoute());
        $this->assertSame('p', $metadata->getPaginationParam());
        $this->assertSame(Metadata\AbstractCollectionMetadata::TYPE_PLACEHOLDER, $metadata->getPaginationParamType());
        $this->assertSame(['foo' => 'bar'], $metadata->getRouteParams());
        $this->assertSame(['baz' => 'bat'], $metadata->getQueryStringArguments());
    }
}
