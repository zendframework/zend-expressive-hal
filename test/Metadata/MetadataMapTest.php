<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Expressive\Hal\Metadata;

use PHPUnit\Framework\TestCase;
use Zend\Expressive\Hal\Metadata;

class MetadataMapTest extends TestCase
{
    private $metadataClasses = [
        Metadata\AbstractMetadata::class,
        Metadata\AbstractCollectionMetadata::class,
        Metadata\AbstractResourceMetadata::class,
        Metadata\RouteBasedCollectionMetadata::class,
        Metadata\RouteBasedResourceMetadata::class,
        Metadata\UrlBasedCollectionMetadata::class,
        Metadata\UrlBasedResourceMetadata::class,
    ];

    public function setUp()
    {
        $this->map = new Metadata\MetadataMap();
    }

    public function validMetadataTypes()
    {
        foreach ($this->metadataClasses as $class) {
            $metadata = $this->prophesize($class);
            $metadata->getClass()->willReturn($class);
            yield $class => [$class, $metadata->reveal()];
        }
    }

    /**
     * @dataProvider validMetadataTypes
     */
    public function testCanAggregateAnyMetadataType(string $class, Metadata\AbstractMetadata $metadata)
    {
        $this->assertFalse($this->map->has($class));
        $this->map->add($metadata);
        $this->assertTrue($this->map->has($class));
        $this->assertSame($metadata, $this->map->get($class));
    }

    public function testAddWillRaiseUndefinedClassExceptionIfClassDoesNotExist()
    {
        $metadata = $this->prophesize(Metadata\AbstractMetadata::class);
        $metadata->getClass()->willReturn('undefined-class');

        $this->expectException(Metadata\Exception\UndefinedClassException::class);
        $this->expectExceptionMessage('undefined-class');
        $this->map->add($metadata->reveal());
    }

    public function testAddWillRaiseDuplicateMetadataExceptionWhenDuplicateMetadataEncountered()
    {
        $first = $this->prophesize(Metadata\AbstractMetadata::class);
        $first->getClass()->willReturn(__CLASS__);

        $this->map->add($first->reveal());
        $this->assertSame($first->reveal(), $this->map->get(__CLASS__));

        $second = $this->prophesize(Metadata\AbstractMetadata::class);
        $second->getClass()->willReturn(__CLASS__);

        $this->expectException(Metadata\Exception\DuplicateMetadataException::class);
        $this->expectExceptionMessage(__CLASS__);
        $this->map->add($second->reveal());
    }

    public function testGetWilRaiseUndefinedMetadataExceptionIfClassNotPresentInMap()
    {
        $this->expectException(Metadata\Exception\UndefinedMetadataException::class);
        $this->expectExceptionMessage(__CLASS__);
        $this->map->get(__CLASS__);
    }
}
