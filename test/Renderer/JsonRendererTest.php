<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Expressive\Hal\Renderer;

use PHPUnit\Framework\TestCase;
use Zend\Expressive\Hal\Renderer\JsonRenderer;

use function json_encode;

class JsonRendererTest extends TestCase
{
    use TestAsset;

    public function testDelegatesToJsonEncode()
    {
        $renderer = new JsonRenderer();
        $resource = $this->createExampleResource();
        $expected = json_encode($resource, JsonRenderer::DEFAULT_JSON_FLAGS);

        $this->assertEquals($expected, $renderer->render($resource));
    }

    public function testRendersUsingJsonFlagsProvidedToConstructor()
    {
        $jsonFlags = 0;
        $renderer  = new JsonRenderer($jsonFlags);
        $resource  = $this->createExampleResource();
        $expected  = json_encode($resource, $jsonFlags);

        $this->assertEquals($expected, $renderer->render($resource));
    }
}
