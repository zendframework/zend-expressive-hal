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

use function sprintf;

class RouteBasedCollectionWithRouteParamsTest extends TestCase
{
    use Assertions;

    public function testUsesRouteParamsSpecifiedInMetadataWhenGeneratingLinkHref()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getAttribute('p', 1)->willReturn(3);
        $request->getQueryParams()->shouldNotBeCalled();

        $linkGenerator = $this->prophesize(LinkGenerator::class);
        $this->createLinkGeneratorProphecy($linkGenerator, $request, 'self', 3);
        $this->createLinkGeneratorProphecy($linkGenerator, $request, 'first', 1);
        $this->createLinkGeneratorProphecy($linkGenerator, $request, 'prev', 2);
        $this->createLinkGeneratorProphecy($linkGenerator, $request, 'next', 4);
        $this->createLinkGeneratorProphecy($linkGenerator, $request, 'last', 5);

        $metadataMap = $this->prophesize(MetadataMap::class);

        $resourceMetadata = new RouteBasedResourceMetadata(
            TestAsset\FooBar::class,
            'foo-bar',
            ObjectPropertyHydrator::class,
            'id',
            'bar_id',
            ['foo_id' => 1234]
        );

        $metadataMap->has(TestAsset\FooBar::class)->willReturn(true);
        $metadataMap->get(TestAsset\FooBar::class)->willReturn($resourceMetadata);

        $collectionMetadata = new RouteBasedCollectionMetadata(
            Paginator::class,
            'foo-bar',
            'foo-bar',
            'p',
            RouteBasedCollectionMetadata::TYPE_PLACEHOLDER,
            ['foo_id' => 1234],
            ['sort' => 'ASC']
        );

        $metadataMap->has(Paginator::class)->willReturn(true);
        $metadataMap->get(Paginator::class)->willReturn($collectionMetadata);

        $hydrators = $this->prophesize(ContainerInterface::class);
        $hydrators->get(ObjectPropertyHydrator::class)->willReturn(new ObjectPropertyHydrator());

        $collection = new Paginator(new ArrayAdapter($this->createCollectionItems(
            $linkGenerator,
            $request
        )));
        $collection->setItemCountPerPage(3);

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

        $resource = $generator->fromObject($collection, $request->reveal());

        $this->assertInstanceOf(HalResource::class, $resource);
        $self = $this->getLinkByRel('self', $resource);
        $this->assertLink('self', '/api/foo/1234/p/3?sort=ASC', $self);
        $first = $this->getLinkByRel('first', $resource);
        $this->assertLink('first', '/api/foo/1234/p/1?sort=ASC', $first);
        $prev = $this->getLinkByRel('prev', $resource);
        $this->assertLink('prev', '/api/foo/1234/p/2?sort=ASC', $prev);
        $next = $this->getLinkByRel('next', $resource);
        $this->assertLink('next', '/api/foo/1234/p/4?sort=ASC', $next);
        $last = $this->getLinkByRel('last', $resource);
        $this->assertLink('last', '/api/foo/1234/p/5?sort=ASC', $last);
    }

    private function createLinkGeneratorProphecy($linkGenerator, $request, string $rel, int $page)
    {
        $linkGenerator->fromRoute(
            $rel,
            $request->reveal(),
            'foo-bar',
            ['foo_id' => 1234, 'p' => $page],
            ['sort' => 'ASC']
        )->willReturn(new Link($rel, sprintf('/api/foo/1234/p/%d?sort=ASC', $page)));
    }

    private function createCollectionItems($linkGenerator, $request) : array
    {
        $instance      = new TestAsset\FooBar;
        $instance->foo = 'BAR';
        $instance->bar = 'BAZ';

        $items = [];
        for ($i = 1; $i < 15; $i += 1) {
            $next = clone $instance;
            $next->id = $i;
            $items[] = $next;

            $linkGenerator
                ->fromRoute(
                    'self',
                    $request->reveal(),
                    'foo-bar',
                    [
                        'foo_id' => 1234,
                        'bar_id' => $i,
                    ]
                )
                ->willReturn(new Link('self', '/api/foo/1234/bar/' . $i));
        }
        return $items;
    }
}
