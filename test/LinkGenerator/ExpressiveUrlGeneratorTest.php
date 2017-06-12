<?php

namespace HalTest\LinkGenerator;

use Hal\LinkGenerator\ExpressiveUrlGenerator;
use Hal\LinkGenerator\UrlGenerator;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Zend\Expressive\Helper\ServerUrlHelper;
use Zend\Expressive\Helper\UrlHelper;

class ExpressiveUrlGeneratorTest extends TestCase
{
    public function testCanGenerateUrlWithOnlyUrlHelper()
    {
        $urlHelper = $this->prophesize(UrlHelper::class);
        $urlHelper->generate('test', ['foo' => 'bar'], ['baz' => 'bat'])->willReturn('/test/bar?baz=bat');

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getUri()->shouldNotBeCalled();

        $generator = new ExpressiveUrlGenerator($urlHelper->reveal());

        $this->assertSame('/test/bar?baz=bat', $generator->generate(
            $request->reveal(),
            'test',
            ['foo' => 'bar'],
            ['baz' => 'bat']
        ));
    }

    public function testCanGenerateFullyQualifiedURIWhenServerUrlHelperIsComposed()
    {
        $uri = $this->prophesize(UriInterface::class);
        $uri->withQuery('')->will([$uri, 'reveal']);
        $uri->withFragment('')->will([$uri, 'reveal'])->shouldBeCalledTimes(1);
        $uri->getPath()->willReturn('/some/path');
        $uri->withPath('/test/bar')->will([$uri, 'reveal']);
        $uri->withQuery('baz=bat')->will([$uri, 'reveal']);

        $uri
            ->withFragment(Argument::that(function ($fragment) {
                return ! empty($fragment);
            }))
            ->shouldNotBeCalled();

        $uri->__toString()->willReturn('https://api.example.com/test/bar?baz=bat');

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getUri()->will([$uri, 'reveal']);

        $urlHelper = $this->prophesize(UrlHelper::class);
        $urlHelper->generate('test', ['foo' => 'bar'], ['baz' => 'bat'])->willReturn('/test/bar?baz=bat');

        $serverUrlHelper = new ServerUrlHelper();

        $generator = new ExpressiveUrlGenerator($urlHelper->reveal(), $serverUrlHelper);

        $this->assertSame('https://api.example.com/test/bar?baz=bat', $generator->generate(
            $request->reveal(),
            'test',
            ['foo' => 'bar'],
            ['baz' => 'bat']
        ));

        // The helper should be cloned on each invocation, ensuring that the URI
        // is not persisted.
        $this->assertAttributeEmpty('uri', $serverUrlHelper);
    }
}
