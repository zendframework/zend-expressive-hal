<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Expressive\Hal;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Expressive\Hal\Link;
use Zend\Expressive\Hal\LinkGenerator;

class LinkGeneratorTest extends TestCase
{
    public function testUsesComposedUrlGeneratorToGenerateHrefForLink()
    {
        $request = $this->prophesize(ServerRequestInterface::class)->reveal();

        $urlGenerator = $this->prophesize(LinkGenerator\UrlGenerator::class);
        $urlGenerator->generate(
            $request,
            'test',
            ['library' => 'zf'],
            ['sort' => 'asc']
        )->willReturn('/library/test?sort=asc');

        $linkGenerator = new LinkGenerator($urlGenerator->reveal());

        $link = $linkGenerator->fromRoute(
            'library',
            $request,
            'test',
            ['library' => 'zf'],
            ['sort' => 'asc'],
            ['type' => 'https://example.com/doc/library']
        );

        $this->assertInstanceOf(Link::class, $link);
        $this->assertSame('/library/test?sort=asc', $link->getHref());
        $this->assertSame(['library'], $link->getRels());
        $this->assertSame(['type' => 'https://example.com/doc/library'], $link->getAttributes());
        $this->assertFalse($link->isTemplated());
    }

    public function testUsesComposedUrlGeneratorToGenerateHrefForTemplatedLink()
    {
        $request = $this->prophesize(ServerRequestInterface::class)->reveal();

        $urlGenerator = $this->prophesize(LinkGenerator\UrlGenerator::class);
        $urlGenerator->generate(
            $request,
            'test',
            ['library' => 'zf'],
            ['sort' => 'asc']
        )->willReturn('/library/test?sort=asc');

        $linkGenerator = new LinkGenerator($urlGenerator->reveal());

        $link = $linkGenerator->templatedFromRoute(
            'library',
            $request,
            'test',
            ['library' => 'zf'],
            ['sort' => 'asc'],
            ['type' => 'https://example.com/doc/library']
        );

        $this->assertInstanceOf(Link::class, $link);
        $this->assertSame('/library/test?sort=asc', $link->getHref());
        $this->assertSame(['library'], $link->getRels());
        $this->assertSame(['type' => 'https://example.com/doc/library'], $link->getAttributes());
        $this->assertTrue($link->isTemplated());
    }
}
