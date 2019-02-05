<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Expressive\Hal\ResourceGenerator;

use ArrayIterator;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Expressive\Hal\HalResource;
use Zend\Expressive\Hal\Link;
use Zend\Expressive\Hal\LinkGenerator;
use Zend\Expressive\Hal\Metadata\RouteBasedCollectionMetadata;
use Zend\Expressive\Hal\ResourceGenerator;
use Zend\Expressive\Hal\ResourceGenerator\Exception\OutOfBoundsException;
use Zend\Expressive\Hal\ResourceGenerator\RouteBasedCollectionStrategy;

class DoctrinePaginatorTest extends TestCase
{
    public function setUp()
    {
        $this->metadata      = $this->prophesize(RouteBasedCollectionMetadata::class);
        $this->linkGenerator = $this->prophesize(LinkGenerator::class);
        $this->generator     = $this->prophesize(ResourceGenerator::class);
        $this->request       = $this->prophesize(ServerRequestInterface::class);
        $this->paginator     = $this->prophesize(Paginator::class);

        $this->strategy      = new RouteBasedCollectionStrategy();
    }

    public function mockQuery()
    {
        return $this->getMockBuilder(AbstractQuery::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMaxResults', 'setFirstResult'])
            ->getMockForAbstractClass();
    }

    public function mockLinkGeneration(string $relation, string $route, array $routeParams, array $queryStringArgs)
    {
        $link = $this->prophesize(Link::class)->reveal();
        $this->linkGenerator
            ->fromRoute(
                $relation,
                $this->request->reveal(),
                $route,
                $routeParams,
                $queryStringArgs
            )
            ->willReturn($link);
    }

    public function invalidPageCombinations() : iterable
    {
        yield 'negative'   => [-1, 100];
        yield 'zero'       => [0, 100];
        yield 'too-high'   => [2, 1];
    }

    /**
     * @dataProvider invalidPageCombinations
     */
    public function testThrowsOutOfBoundsExceptionForInvalidPage(int $page, int $numPages)
    {
        $query = $this->mockQuery();
        $query->expects($this->once())
            ->method('getMaxResults')
            ->with()
            ->willReturn(15);
        $this->paginator->getQuery()->willReturn($query);
        $this->paginator->count()->willReturn($numPages);

        $this->metadata->getPaginationParamType()->willReturn(RouteBasedCollectionMetadata::TYPE_QUERY);
        $this->metadata->getPaginationParam()->willReturn('page_num');
        $this->request->getQueryParams()->willReturn(['page_num' => $page]);

        $this->expectException(OutOfBoundsException::class);
        $this->strategy->createResource(
            $this->paginator->reveal(),
            $this->metadata->reveal(),
            $this->generator->reveal(),
            $this->request->reveal()
        );
    }

    public function testDoesNotCreateLinksForUnknownPaginationParamType()
    {
        $query = $this->mockQuery();
        $query->expects($this->once())
            ->method('getMaxResults')
            ->with()
            ->willReturn(15);
        $this->paginator->getQuery()->willReturn($query);
        $this->paginator->count()->willReturn(100);

        $this->metadata->getPaginationParamType()->willReturn('unknown');
        $this->metadata->getPaginationParam()->shouldNotBeCalled();
        $this->metadata->getRouteParams()->willReturn([]);
        $this->metadata->getQueryStringArguments()->willReturn([]);
        $this->metadata->getRoute()->willReturn('test');
        $this->metadata->getCollectionRelation()->willReturn('test');

        $this->request
            ->getQueryParams()
            ->willReturn(['page' => 3])
            ->shouldBeCalledTimes(1);
        $this->request->getAttribute(Argument::any(), Argument::any())->shouldNotBeCalled();

        $values = array_map(function ($value) {
            return (object) ['value' => $value];
        }, range(46, 60));
        $this->paginator->getIterator()->willReturn(new ArrayIterator($values));

        $testCase = $this;
        foreach (range(46, 60) as $value) {
            $this->generator
                ->fromObject(
                    (object) ['value' => $value],
                    Argument::that([$this->request, 'reveal'])
                )
                ->will(function () use ($testCase) {
                    $resource = $testCase->prophesize(HalResource::class);
                    $resource->getElements()->willReturn(['test' => true]);
                    return $resource->reveal();
                })
                ->shouldBeCalledTimes(1);
        }
        $this->generator->getLinkGenerator()->will([$this->linkGenerator, 'reveal']);

        $this->mockLinkGeneration('self', 'test', [], ['page' => 3]);

        $resource = $this->strategy->createResource(
            $this->paginator->reveal(),
            $this->metadata->reveal(),
            $this->generator->reveal(),
            $this->request->reveal()
        );

        $this->assertInstanceOf(HalResource::class, $resource);
    }

    public function testCreatesLinksForQueryBasedPagination()
    {
        $query = $this->mockQuery();
        $query->expects($this->once())
            ->method('getMaxResults')
            ->with()
            ->willReturn(15);
        $this->paginator->getQuery()->willReturn($query);
        $this->paginator->count()->willReturn(100);

        $this->metadata->getPaginationParamType()->willReturn(RouteBasedCollectionMetadata::TYPE_QUERY);
        $this->metadata->getPaginationParam()->willReturn('page_num');
        $this->metadata->getRouteParams()->willReturn([]);
        $this->metadata->getQueryStringArguments()->willReturn([]);
        $this->metadata->getRoute()->willReturn('test');
        $this->metadata->getCollectionRelation()->willReturn('test');

        $this->request
            ->getQueryParams()
            ->willReturn(['page_num' => 3])
            ->shouldBeCalledTimes(1);
        $this->request->getAttribute(Argument::any(), Argument::any())->shouldNotBeCalled();

        $values = array_map(function ($value) {
            return (object) ['value' => $value];
        }, range(46, 60));
        $this->paginator->getIterator()->willReturn(new ArrayIterator($values));

        $testCase = $this;
        foreach (range(46, 60) as $value) {
            $this->generator
                ->fromObject(
                    (object) ['value' => $value],
                    Argument::that([$this->request, 'reveal'])
                )
                ->will(function () use ($testCase) {
                    $resource = $testCase->prophesize(HalResource::class);
                    $resource->getElements()->willReturn(['test' => true]);
                    return $resource->reveal();
                })
                ->shouldBeCalledTimes(1);
        }
        $this->generator->getLinkGenerator()->will([$this->linkGenerator, 'reveal']);

        $paginationLinks = [
            'self'  => ['page_num' => 3],
            'first' => ['page_num' => 1],
            'prev'  => ['page_num' => 2],
            'next'  => ['page_num' => 4],
            'last'  => ['page_num' => 7],
        ];
        foreach ($paginationLinks as $relation => $queryStringArgs) {
            $this->mockLinkGeneration($relation, 'test', [], $queryStringArgs);
        }

        $resource = $this->strategy->createResource(
            $this->paginator->reveal(),
            $this->metadata->reveal(),
            $this->generator->reveal(),
            $this->request->reveal()
        );

        $this->assertInstanceOf(HalResource::class, $resource);
    }

    public function testCreatesLinksForRouteBasedPagination()
    {
        $query = $this->mockQuery();
        $query->expects($this->once())
            ->method('getMaxResults')
            ->with()
            ->willReturn(15);
        $this->paginator->getQuery()->willReturn($query);
        $this->paginator->count()->willReturn(100);

        $this->metadata->getPaginationParamType()->willReturn(RouteBasedCollectionMetadata::TYPE_PLACEHOLDER);
        $this->metadata->getPaginationParam()->willReturn('page_num');
        $this->metadata->getRouteParams()->willReturn([]);
        $this->metadata->getQueryStringArguments()->willReturn([]);
        $this->metadata->getRoute()->willReturn('test');
        $this->metadata->getCollectionRelation()->willReturn('test');

        $this->request->getQueryParams()->shouldNotBeCalled();
        $this->request
            ->getAttribute('page_num', 1)
            ->willReturn(3)
            ->shouldBeCalledTimes(1);

        $values = array_map(function ($value) {
            return (object) ['value' => $value];
        }, range(46, 60));
        $this->paginator->getIterator()->willReturn(new ArrayIterator($values));

        $testCase = $this;
        foreach (range(46, 60) as $value) {
            $this->generator
                ->fromObject(
                    (object) ['value' => $value],
                    Argument::that([$this->request, 'reveal'])
                )
                ->will(function () use ($testCase) {
                    $resource = $testCase->prophesize(HalResource::class);
                    $resource->getElements()->willReturn(['test' => true]);
                    return $resource->reveal();
                })
                ->shouldBeCalledTimes(1);
        }
        $this->generator->getLinkGenerator()->will([$this->linkGenerator, 'reveal']);

        $paginationLinks = [
            'self'  => ['page_num' => 3],
            'first' => ['page_num' => 1],
            'prev'  => ['page_num' => 2],
            'next'  => ['page_num' => 4],
            'last'  => ['page_num' => 7],
        ];
        foreach ($paginationLinks as $relation => $routeParams) {
            $this->mockLinkGeneration($relation, 'test', $routeParams, []);
        }

        $resource = $this->strategy->createResource(
            $this->paginator->reveal(),
            $this->metadata->reveal(),
            $this->generator->reveal(),
            $this->request->reveal()
        );

        $this->assertInstanceOf(HalResource::class, $resource);
    }
}
