<?php

namespace HalTest;

use Hal\Link;
use Hal\LinkGenerator;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

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
