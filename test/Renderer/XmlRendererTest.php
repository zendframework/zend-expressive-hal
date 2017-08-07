<?php

namespace HalTest\Renderer;

use Hal\Renderer\XmlRenderer;
use PHPUnit\Framework\TestCase;

class XmlRendererTest extends TestCase
{
    use TestAsset;

    public function createExampleXmlPayload()
    {
        // Closing tag causes syntax highlighting to fail everwhere
        $xml = '<?xml version="1.0" encoding="UTF-8"?' . ">\n";
        $xml .= <<< 'EOX'
<resource rel="self" href="/example/XXXX-YYYY-ZZZZ-ABAB">
  <link rel="shift" href="/example/XXXX-YYYY-ZZZZ-ABAB/shift"/>
  <resource rel="bar" href="/bar/BABA-ZZZZ-YYYY-XXXX">
    <link rel="doc" href="/doc/bar"/>
    <id>BABA-ZZZZ-YYYY-XXXX</id>
    <bar>true</bar>
    <some>data</some>
  </resource>
  <resource rel="baz" href="/baz/XXXX-0">
    <link rel="doc" href="/doc/baz"/>
    <id>XXXX-0</id>
    <baz>true</baz>
  </resource>
  <resource rel="baz" href="/baz/XXXX-1">
    <link rel="doc" href="/doc/baz"/>
    <id>XXXX-1</id>
    <baz>true</baz>
  </resource>
  <resource rel="baz" href="/baz/XXXX-2">
    <link rel="doc" href="/doc/baz"/>
    <id>XXXX-2</id>
    <baz>true</baz>
  </resource>
  <id>XXXX-YYYY-ZZZZ-ABAB</id>
  <example>true</example>
  <foo>bar</foo>
  <list>1</list>
  <list>2</list>
  <list>3</list>
</resource>
EOX;
        return $xml;
    }

    public function testRendersExpectedXmlPayload()
    {
        $resource = $this->createExampleResource();
        $expected = $this->createExampleXmlPayload();
        $renderer = new XmlRenderer();

        $this->assertSame($expected, $renderer->render($resource));
    }
}
