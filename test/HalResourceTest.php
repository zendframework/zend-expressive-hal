<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Expressive\Hal;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Zend\Expressive\Hal\HalResource;
use Zend\Expressive\Hal\Link;

use function array_values;

class HalResourceTest extends TestCase
{
    public function testCanConstructWithData()
    {
        $resource = new HalResource(['foo' => 'bar']);
        $this->assertEquals(['foo' => 'bar'], $resource->getElements());
    }

    public function invalidElementNames()
    {
        return [
            'empty'     => ['', 'cannot be empty'],
            '_links'    => ['_links', 'reserved element $name'],
            '_embedded' => ['_embedded', 'reserved element $name'],
        ];
    }

    /**
     * @dataProvider invalidElementNames
     */
    public function testInvalidDataNamesRaiseExceptionsDuringConstruction(string $name, string $expectedMessage)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);
        $resource = new HalResource([$name => 'bar']);
    }

    public function testCanConstructWithDataContainingEmbeddedResources()
    {
        $embedded = new HalResource(['foo' => 'bar']);
        $resource = new HalResource(['foo' => $embedded]);
        $this->assertEquals(['foo' => $embedded], $resource->getElements());
        $representation = $resource->toArray();
        $this->assertArrayHasKey('_embedded', $representation);
        $this->assertArrayHasKey('foo', $representation['_embedded']);
        $this->assertEquals(['foo' => 'bar'], $representation['_embedded']['foo']);
    }

    public function testCanConstructWithLinks()
    {
        $links = [
            new Link('self', 'https://example.com/'),
            new Link('about', 'https://example.com/about'),
        ];
        $resource = new HalResource([], $links);
        $this->assertSame($links, $resource->getLinks());
    }

    public function testNonLinkItemsRaiseExceptionDuringConstruction()
    {
        $links = [
            new Link('self', 'https://example.com/'),
            'foo',
        ];
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$links');
        $resource = new HalResource([], $links);
    }

    public function testCanConstructWithEmbeddedResources()
    {
        $embedded = new HalResource(['foo' => 'bar']);
        $resource = new HalResource([], [], ['foo' => $embedded]);
        $this->assertEquals(['foo' => $embedded], $resource->getElements());
        $representation = $resource->toArray();
        $this->assertArrayHasKey('_embedded', $representation);
        $this->assertArrayHasKey('foo', $representation['_embedded']);
        $this->assertEquals(['foo' => 'bar'], $representation['_embedded']['foo']);
    }

    public function testNonResourceOrCollectionItemsRaiseExceptionDuringConstruction()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid embedded resource');
        $resource = new HalResource([], [], ['foo' => 'bar']);
    }

    public function testEmptyArrayAsDataWillNotBeEmbeddedDuringConstruction()
    {
        $resource = new HalResource(['bar' => []]);
        $this->assertEquals(['bar' => []], $resource->getElements());
        $representation = $resource->toArray();
        $this->assertArrayNotHasKey('_embeded', $representation);
    }

    /**
     * @dataProvider invalidElementNames
     */
    public function testInvalidResourceNamesRaiseExceptionsDuringConstruction(string $name, string $expectedMessage)
    {
        $embedded = new HalResource(['foo' => 'bar']);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);
        $resource = new HalResource([], [], [$name => $embedded]);
    }

    public function testWithLinkReturnsNewInstanceContainingNewLink()
    {
        $link = new Link('self');
        $resource = new HalResource();
        $new = $resource->withLink($link);
        $this->assertNotSame($resource, $new);
        $this->assertEquals([], $resource->getLinksByRel('self'));
        $this->assertEquals([$link], $new->getLinksByRel('self'));
    }

    public function testWithLinkReturnsSameInstanceIfAlreadyContainsLinkInstance()
    {
        $link = new Link('self');
        $resource = new HalResource([], [$link]);
        $new = $resource->withLink($link);
        $this->assertSame($resource, $new);
    }

    public function testWithoutLinkReturnsNewInstanceRemovingLink()
    {
        $link = new Link('self');
        $resource = new HalResource([], [$link]);
        $new = $resource->withoutLink($link);
        $this->assertNotSame($resource, $new);
        $this->assertEquals([$link], $resource->getLinksByRel('self'));
        $this->assertEquals([], $new->getLinksByRel('self'));
    }

    public function testWithoutLinkReturnsSameInstanceIfLinkIsNotPresent()
    {
        $link = new Link('self');
        $resource = new HalResource();
        $new = $resource->withoutLink($link);
        $this->assertSame($resource, $new);
    }

    public function testGetLinksByRelReturnsAllLinksWithGivenRelationshipAsArray()
    {
        $link1 = new Link('self');
        $link2 = new Link('about');
        $link3 = new Link('self');
        $resource = new HalResource();

        $resource = $resource
            ->withLink($link1)
            ->withLink($link2)
            ->withLink($link3);

        $links = $resource->getLinksByRel('self');
        // array_values needed here, as keys will no longer be sequential
        $this->assertEquals([$link1, $link3], array_values($links));
    }

    /**
     * @dataProvider invalidElementNames
     */
    public function testWithElementRaisesExceptionForInvalidName(string $name, string $expectedMessage)
    {
        $resource = new HalResource();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);
        $resource->withElement($name, 'foo');
    }

    public function testWithElementRaisesExceptionIfNameCollidesWithExistingResource()
    {
        $embedded = new HalResource(['foo' => 'bar']);
        $resource = new HalResource(['foo' => $embedded]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('element matching resource');
        $resource->withElement('foo', 'bar');
    }

    public function testWithElementReturnsNewInstanceWithNewElement()
    {
        $resource = new HalResource();
        $new = $resource->withElement('foo', 'bar');
        $this->assertNotSame($resource, $new);
        $this->assertEquals([], $resource->getElements());
        $this->assertEquals(['foo' => 'bar'], $new->getElements());
    }

    public function testWithElementReturnsNewInstanceOverwritingExistingElementValue()
    {
        $resource = new HalResource(['foo' => 'bar']);
        $new = $resource->withElement('foo', 'baz');
        $this->assertNotSame($resource, $new);
        $this->assertEquals(['foo' => 'bar'], $resource->getElements());
        $this->assertEquals(['foo' => 'baz'], $new->getElements());
    }

    public function testWithElementProxiesToEmbedIfResourceValueProvided()
    {
        $embedded = new HalResource(['foo' => 'bar']);
        $resource = new HalResource();
        $new = $resource->withElement('foo', $embedded);
        $this->assertNotSame($resource, $new);
        $this->assertEquals([], $resource->getElements());
        $this->assertEquals(['foo' => $embedded], $new->getElements());
        $representation = $new->toArray();
        $this->assertArrayHasKey('_embedded', $representation);
        $this->assertArrayHasKey('foo', $representation['_embedded']);
        $this->assertEquals(['foo' => 'bar'], $representation['_embedded']['foo']);
    }

    public function testWithElementProxiesToEmbedIfResourceCollectionValueProvided()
    {
        $resource1 = new HalResource(['foo' => 'bar']);
        $resource2 = new HalResource(['foo' => 'baz']);
        $resource3 = new HalResource(['foo' => 'bat']);
        $collection = [$resource1, $resource2, $resource3];

        $resource = new HalResource();
        $new = $resource->withElement('foo', $collection);
        $this->assertNotSame($resource, $new);
        $this->assertEquals([], $resource->getElements());
        $this->assertEquals(['foo' => $collection], $new->getElements());
    }

    public function testWithElementNotProxiesToEmbededIfEmptyArrayValueProvided()
    {
        $resource = new HalResource(['foo' => 'bar']);
        $new = $resource->withElement('bar', []);

        $representation = $new->toArray();
        $this->assertEquals(['foo' => 'bar', 'bar' => []], $representation);
    }

    /**
     * @dataProvider invalidElementNames
     */
    public function testEmbedRaisesExceptionForInvalidName(string $name, string $expectedMessage)
    {
        $resource = new HalResource();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);
        $resource->embed($name, new HalResource());
    }

    public function testEmbedRaisesExceptionIfNameCollidesWithExistingData()
    {
        $resource = new HalResource(['foo' => 'bar']);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('embed resource matching element');
        $resource->embed('foo', new HalResource());
    }

    public function testEmbedReturnsNewInstanceWithEmbeddedResource()
    {
        $embedded = new HalResource(['foo' => 'bar']);
        $resource = new HalResource();
        $new = $resource->embed('foo', $embedded);
        $this->assertNotSame($resource, $new);
        $this->assertEquals([], $resource->getElements());
        $this->assertEquals(['foo' => $embedded], $new->getElements());
    }

    public function testEmbedReturnsNewInstanceWithEmbeddedCollection()
    {
        $resource1 = new HalResource(['foo' => 'bar']);
        $resource2 = new HalResource(['foo' => 'baz']);
        $resource3 = new HalResource(['foo' => 'bat']);
        $collection = [$resource1, $resource2, $resource3];

        $resource = new HalResource();
        $new = $resource->embed('foo', $collection);
        $this->assertNotSame($resource, $new);
        $this->assertEquals([], $resource->getElements());
        $this->assertEquals(['foo' => $collection], $new->getElements());
    }

    public function testEmbedReturnsNewInstanceAppendingResourceToExistingResource()
    {
        $resource1 = new HalResource(['foo' => 'bar']);
        $resource2 = new HalResource(['foo' => 'baz']);

        $resource = new HalResource(['foo' => $resource1]);
        $new = $resource->embed('foo', $resource2);
        $this->assertNotSame($resource, $new);
        $this->assertEquals(['foo' => $resource1], $resource->getElements());
        $this->assertEquals(['foo' => [$resource1, $resource2]], $new->getElements());
    }

    public function testEmbedReturnsNewInstanceAppendingResourceToExistingCollection()
    {
        $resource1 = new HalResource(['foo' => 'bar']);
        $resource2 = new HalResource(['foo' => 'baz']);
        $resource3 = new HalResource(['foo' => 'bat']);
        $collection = [$resource1, $resource2];

        $resource = new HalResource(['foo' => $collection]);
        $new = $resource->embed('foo', $resource3);
        $this->assertNotSame($resource, $new);
        $this->assertEquals(['foo' => $collection], $resource->getElements());
        $this->assertEquals(['foo' => [$resource1, $resource2, $resource3]], $new->getElements());
    }

    public function testEmbedReturnsNewInstanceAppendingCollectionToExistingCollection()
    {
        $resource1 = new HalResource(['foo' => 'bar']);
        $resource2 = new HalResource(['foo' => 'baz']);
        $resource3 = new HalResource(['foo' => 'bat']);
        $resource4 = new HalResource(['foo' => 'bat']);
        $collection1 = [$resource1, $resource2];
        $collection2 = [$resource3, $resource4];

        $resource = new HalResource(['foo' => $collection1]);
        $new = $resource->embed('foo', $collection2);
        $this->assertNotSame($resource, $new);
        $this->assertEquals(['foo' => $collection1], $resource->getElements());
        $this->assertEquals(['foo' => $collection1 + $collection2], $new->getElements());
    }

    public function testEmbedRaisesExceptionIfNewResourceDoesNotMatchStructureOfExisting()
    {
        $resource1 = new HalResource(['foo' => 'bar']);
        $resource2 = new HalResource(['bar' => 'baz']);

        $resource = new HalResource(['foo' => $resource1]);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('structurally inequivalent');
        $resource->embed('foo', $resource2);
    }

    public function testEmbedRaisesExceptionIfNewResourceDoesNotMatchCollectionResourceStructure()
    {
        $resource1 = new HalResource(['foo' => 'bar']);
        $resource2 = new HalResource(['foo' => 'baz']);
        $resource3 = new HalResource(['bar' => 'bat']);
        $collection = [$resource1, $resource2];

        $resource = new HalResource(['foo' => $collection]);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('structurally inequivalent');
        $resource->embed('foo', $resource3);
    }

    public function testEmbedRaisesExceptionIfResourcesInCollectionAreNotOfSameStructure()
    {
        $resource1 = new HalResource(['foo' => 'bar']);
        $resource2 = new HalResource(['bar' => 'bat']);
        $collection = [$resource1, $resource2];

        $resource = new HalResource();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('structurally inequivalent');
        $resource->embed('foo', $collection);
    }

    public function testWithElementsAddsNewDataToNewResourceInstance()
    {
        $resource = new HalResource();
        $new = $resource->withElements(['foo' => 'bar']);
        $this->assertNotSame($resource, $new);
        $this->assertEquals([], $resource->getElements());
        $this->assertEquals(['foo' => 'bar'], $new->getElements());
    }

    public function testWithElementsAddsNewEmbeddedResourcesToNewResourceInstance()
    {
        $embedded = new HalResource(['foo' => 'bar']);
        $resource = new HalResource();
        $new = $resource->withElements(['foo' => $embedded]);
        $this->assertNotSame($resource, $new);
        $this->assertEquals([], $resource->getElements());
        $this->assertEquals(['foo' => $embedded], $new->getElements());
        $representation = $new->toArray();
        $this->assertArrayHasKey('_embedded', $representation);
        $this->assertArrayHasKey('foo', $representation['_embedded']);
        $this->assertEquals(['foo' => 'bar'], $representation['_embedded']['foo']);
    }

    public function testWithElementsOverwritesExistingDataInNewResourceInstance()
    {
        $resource = new HalResource(['foo' => 'bar']);
        $new = $resource->withElements(['foo' => 'baz']);
        $this->assertNotSame($resource, $new);
        $this->assertEquals(['foo' => 'bar'], $resource->getElements());
        $this->assertEquals(['foo' => 'baz'], $new->getElements());
    }

    public function testWithElementsAppendsEmbeddedResourcesToExistingResourcesInNewResourceInstance()
    {
        $resource1 = new HalResource(['foo' => 'bar']);
        $resource2 = new HalResource(['foo' => 'bar']);
        $resource = new HalResource(['foo' => $resource1]);
        $new = $resource->withElements(['foo' => $resource2]);

        $this->assertNotSame($resource, $new);
        $this->assertEquals(['foo' => $resource1], $resource->getElements());
        $this->assertEquals(['foo' => [$resource1, $resource2]], $new->getElements());
    }

    public function testWithoutElementRemovesDataElementIfItIsPresent()
    {
        $resource = new HalResource(['foo' => 'bar']);
        $new = $resource->withoutElement('foo');
        $this->assertNotSame($resource, $new);
        $this->assertEquals(['foo' => 'bar'], $resource->getElements());
        $this->assertEquals([], $new->getElements());
    }

    public function testWithoutElementDoesNothingIfElementOrResourceNotPresent()
    {
        $resource = new HalResource(['foo' => 'bar']);
        $new = $resource->withoutElement('bar');
        $this->assertSame($resource, $new);
    }

    public function testWithoutElementRemovesEmbeddedResourceIfItIsPresent()
    {
        $embedded = new HalResource();
        $resource = new HalResource(['foo' => $embedded]);
        $new = $resource->withoutElement('foo');
        $this->assertNotSame($resource, $new);
        $this->assertEquals(['foo' => $embedded], $resource->getElements());
        $this->assertEquals([], $new->getElements());
    }

    public function testWithoutElementRemovesEmbeddedCollectionIfPresent()
    {
        $resource1 = new HalResource();
        $resource2 = new HalResource();
        $resource3 = new HalResource();
        $collection = [$resource1, $resource2, $resource3];
        $resource = new HalResource(['foo' => $collection]);
        $new = $resource->withoutElement('foo');
        $this->assertNotSame($resource, $new);
        $this->assertEquals(['foo' => $collection], $resource->getElements());
        $this->assertEquals([], $new->getElements());
    }

    /**
     * @dataProvider invalidElementNames
     */
    public function testWithoutElementRaisesExceptionForInvalidElementName(string $name, string $expectedMessage)
    {
        $resource = new HalResource();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);
        $resource->withoutElement($name);
    }

    public function populatedResources()
    {
        $resource = (new HalResource())
            ->withLink(new Link('self', '/api/foo'))
            ->withLink(new Link('about', '/doc/about'))
            ->withLink(new Link('about', '/doc/resources/foo'))
            ->withElements(['foo' => 'bar', 'id' => 12345678])
            ->embed('bar', new HalResource(['bar' => 'baz'], [new Link('self', '/api/bar')]))
            ->embed('baz', [
                new HalResource(['baz' => 'bat', 'id' => 987654], [new Link('self', '/api/baz/987654')]),
                new HalResource(['baz' => 'bat', 'id' => 987653], [new Link('self', '/api/baz/987653')]),
            ]);
        $expected = [
            'foo' => 'bar',
            'id'  => 12345678,
            '_links' => [
                'self' => [
                    'href' => '/api/foo',
                ],
                'about' => [
                    ['href' => '/doc/about'],
                    ['href' => '/doc/resources/foo'],
                ],
            ],
            '_embedded' => [
                'bar' => [
                    'bar' => 'baz',
                    '_links' => [
                        'self' => ['href' => '/api/bar'],
                    ],
                ],
                'baz' => [
                    [
                        'baz' => 'bat',
                        'id'  => 987654,
                        '_links' => [
                            'self' => ['href' => '/api/baz/987654'],
                        ],
                    ],
                    [
                        'baz' => 'bat',
                        'id'  => 987653,
                        '_links' => [
                            'self' => ['href' => '/api/baz/987653'],
                        ],
                    ],
                ],
            ],
        ];

        yield 'fully-populated' => [$resource, $expected];
    }

    /**
     * @dataProvider populatedResources
     */
    public function testToArrayReturnsHalDataStructure(HalResource $resource, array $expected)
    {
        $this->assertEquals($expected, $resource->toArray());
    }

    /**
     * @dataProvider populatedResources
     */
    public function testJsonSerializeReturnsHalDataStructure(HalResource $resource, array $expected)
    {
        $this->assertEquals($expected, $resource->jsonSerialize());
    }

    public function testAllowsForcingResourceToAggregateAsACollection()
    {
        $resource = (new HalResource())
            ->withLink(new Link('self', '/api/foo'))
            ->embed(
                'bar',
                new HalResource(['bar' => 'baz'], [new Link('self', '/api/bar')]),
                true
            );

        $expected = [
            '_links' => [
                'self' => [
                    'href' => '/api/foo',
                ],
            ],
            '_embedded' => [
                'bar' => [
                    [
                        'bar' => 'baz',
                        '_links' => [
                            'self' => ['href' => '/api/bar'],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $resource->toArray());
    }

    public function testAllowsForcingLinkToAggregateAsACollection()
    {
        $link = new Link('foo', '/api/foo', false, [Link::AS_COLLECTION => true]);
        $resource = new HalResource(['id' => 'foo'], [$link]);

        $expected = [
            '_links' => [
                'foo' => [
                    [
                        'href' => '/api/foo',
                    ],
                ],
            ],
            'id' => 'foo',
        ];

        $this->assertEquals($expected, $resource->toArray());
    }
}
