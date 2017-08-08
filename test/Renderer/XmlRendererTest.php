<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Expressive\Hal\Renderer;

use PHPUnit\Framework\TestCase;
use Zend\Expressive\Hal\Renderer\XmlRenderer;

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
