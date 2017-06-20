<?php

namespace HalTest;

use Hal\HalResponseFactory;
use Hal\Link;
use Hal\Resource;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use ReflectionProperty;

class HalResponseFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->request  = $this->prophesize(ServerRequestInterface::class);
        $this->factory  = new HalResponseFactory();
    }

    public function createExampleResource()
    {
        $resource = new Resource([
            'id'      => 'XXXX-YYYY-ZZZZ-ABAB',
            'example' => true,
            'foo'     => 'bar',
        ]);
        $resource = $resource->withLink(new Link('self', '/example/XXXX-YYYY-ZZZZ-ABAB'));
        $resource = $resource->withLink(new Link('shift', '/example/XXXX-YYYY-ZZZZ-ABAB/shift'));

        $bar = new Resource([
            'id'   => 'BABA-ZZZZ-YYYY-XXXX',
            'bar'  => true,
            'some' => 'data',
        ]);
        $bar = $bar->withLink(new Link('self', '/bar/BABA-ZZZZ-YYYY-XXXX'));
        $bar = $bar->withLink(new Link('doc', '/doc/bar'));

        $baz = [];
        for ($i = 0; $i < 3; $i += 1) {
            $temp = new Resource([
                'id' => 'XXXX-' . $i,
                'baz' => true,
            ]);
            $temp = $temp->withLink('self', '/baz/XXXX-' . $i);
            $temp = $temp->withLink('doc', '/doc/baz');
            $baz[] = $temp;
        }

        $resource = $resource->embed('bar', $bar);
        $resource = $resource->embed('baz', $baz);

        return $resource;
    }

    public function createExampleJsonPayload(HalResponseFactory $factory = null)
    {
        $factory = $factory ?: $this->factory;
        $r = new ReflectionProperty($factory, 'jsonFlags');
        $r->setAccessible(true);
        $flags = $r->getValue($factory);

        $resource = $this->createExampleResource();
        return json_encode($resource, $flags);
    }

    public function createExampleXmlPayload()
    {
        // Closing tag causes syntax highlighting to fail everwhere
        $xml = '<?xml version="1.0" encoding="UTF-8"?' . ">\n";
        $xml .= <<< 'EOX'
<resource rel="self" href="/example/XXXX-YYYY-ZZZZ-ABAB">
    <link rel="shift" href="/example/XXXX-YYYY-ZZZZ-ABAB/shift"/>
    <id>XXXX-YYYY-ZZZZ-ABAB</id>
    <example>true</example>
    <foo>bar</foo>
    <resource rel="bar" href="/bar/BABA-ZZZZ-YYYY-XXXX">
        <link rel="doc" href="/doc/bar"/>
        <id>BABA-ZZZZ-YYYY-XXXX</id>
        <bar>true</bar>
        <some>data</some>
    </resource>
    <resource rel="baz" href="/bar/XXXX-0">
        <link rel="doc" href="/doc/baz"/>
        <id>XXXX-0</id>
        <baz>true</baz>
    </resource>
    <resource rel="baz" href="/bar/XXXX-1">
        <link rel="doc" href="/doc/baz"/>
        <id>XXXX-1</id>
        <baz>true</baz>
    </resource>
    <resource rel="baz" href="/bar/XXXX-2">
        <link rel="doc" href="/doc/baz"/>
        <id>XXXX-2</id>
        <baz>true</baz>
    </resource>
</resource>
EOX;
        return $xml;
    }

    public function testReturnsJsonResponseIfNoAcceptHeaderPresent()
    {
        $this->request->getHeaderLine('Accept')->willReturn('');
        $response = $this->factory->createResponse(
            $request,
            $this->createExampleResource()
        );
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertContains('application/hal+json', $response->getHeaderLine('Content-Type'));
        $json = (string) $response->getBody();
        $this->assertEquals($this->createExampleJsonPayload(), $json);
    }

    public function jsonAcceptHeaders()
    {
        return [
            'application/json'             => ['application/json'],
            'application/hal+json'         => ['application/hal+json'],
            'application/vnd.example+json' => ['application/vnd.example+json'],
        ];
    }

    /**
     * @dataProvider jsonAcceptHeaders
     */
    public function testReturnsJsonResponseIfAcceptHeaderMatchesJson(string $header)
    {
        $this->markTestIncomplete();
    }

    public function xmlAcceptHeaders()
    {
        return [
            'application/xml'             => ['application/xml'],
            'application/xhtml+xml'       => ['application/xhtml+xml'],
            'application/hal+xml'         => ['application/hal+xml'],
            'application/vnd.example+xml' => ['application/vnd.example+xml'],
        ];
    }

    /**
     * @dataProvider xmlAcceptHeaders
     */
    public function testReturnsXmlResponseIfAcceptHeaderMatchesXml(string $header)
    {
        $this->markTestIncomplete();
    }

    public function customMediaTypes()
    {
        // @codingStandardsIgnoreStart
        return [
            'json' => ['application/json', 'application/vnd.example', 'createExampleJsonPayload', 'application/vnd.example+json'],
            'xml'  => ['application/xml',  'application/vnd.example', 'createExampleXmlPayload',  'application/vnd.example+xml'],
        ];
        // @codingStandardsIgnoreEnd
    }

    /**
     * @dataProvider customMediaTypes
     */
    public function testUsesProvidedMediaTypeInReturnedResponseWithMatchedFormatAppended(
        string $header,
        string $mediaType,
        string $responseBodyCallback,
        string $expectedMediaType
    ) {
        $this->markTestIncomplete();
    }

    public function testAllowsProvidingFlagsForJsonSerializationToConstructor()
    {
        $this->markTestIncomplete();
    }

    public function testAllowsProvidingResponsePrototypeToConstructor()
    {
        $this->markTestIncomplete();
    }

    public function testAllowsProvidingStreamFactoryToConstructor()
    {
        $this->markTestIncomplete();
    }
}
